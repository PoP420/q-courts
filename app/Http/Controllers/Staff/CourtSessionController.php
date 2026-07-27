<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\CourtSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourtSessionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Staff/Sessions', [
            'auth' => [
                'user' => $request->user(),
            ],
            'courts' => Court::with('activeSession.booking')
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->map(fn (Court $court): array => [
                    'id' => $court->id,
                    'name' => $court->name,
                    'is_active' => $court->is_active,
                    'active_session' => $court->activeSession ? [
                        'booking' => $court->activeSession->booking?->customer_name,
                        'game_type' => $court->activeSession->game_type,
                        'minutes_remaining' => $court->activeSession->minutes_remaining,
                    ] : null,
                ]),
            'activeSessions' => CourtSession::with(['court', 'booking'])
                ->where('status', 'active')
                ->orderBy('started_at')
                ->get()
                ->map(fn (CourtSession $session): array => [
                    'id' => $session->id,
                    'court' => $session->court?->name,
                    'booking' => $session->booking?->customer_name,
                    'game_type' => $session->game_type,
                    'planned_minutes' => $session->planned_minutes,
                    'minutes_remaining' => $session->minutes_remaining,
                    'started_at' => $session->started_at?->toDateTimeString(),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'game_type' => ['nullable', 'string', 'max:100'],
            'planned_minutes' => ['required', 'integer', 'min:5', 'max:240'],
        ]);

        $court = Court::findOrFail($data['court_id']);

        if (! $court->is_active) {
            return back()->withErrors([
                'court_id' => 'That court is inactive.',
            ]);
        }

        $alreadyActive = CourtSession::where('court_id', $data['court_id'])
            ->where('status', 'active')
            ->exists();

        if ($alreadyActive) {
            return back()->withErrors([
                'court_id' => 'This court already has an active session.',
            ]);
        }

        CourtSession::create([
            'court_id' => $data['court_id'],
            'booking_id' => null,
            'game_type' => $data['game_type'] ?: 'Walk-in',
            'planned_minutes' => $data['planned_minutes'],
            'started_at' => now(),
            'status' => 'active',
        ]);

        return back()->with('success', 'Walk-in session started.');
    }
}