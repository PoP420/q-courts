<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\CourtSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LiveBoardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Staff/LiveBoard', [
            'auth' => [
                'user' => $request->user(),
            ],
            'courts' => Court::with('activeSession.booking')
                ->orderBy('id')
                ->get()
                ->map(fn (Court $court): array => [
                    'id' => $court->id,
                    'name' => $court->name,
                    'is_active' => $court->is_active,
                    'active_session' => $court->activeSession ? [
                        'id' => $court->activeSession->id,
                        'booking' => $court->activeSession->booking?->customer_name,
                        'game_type' => $court->activeSession->game_type,
                        'planned_minutes' => $court->activeSession->planned_minutes,
                        'minutes_remaining' => $court->activeSession->minutes_remaining,
                        'score' => $court->activeSession->score,
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
                    'score' => $session->score,
                    'started_at' => $session->started_at?->toDateTimeString(),
                ]),
        ]);
    }
}