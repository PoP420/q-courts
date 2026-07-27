<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Court;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourtController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Staff/Courts', [
            'auth' => [
                'user' => $request->user(),
            ],
            'courts' => Court::withCount(['bookings', 'sessions'])
                ->with('activeSession.booking')
                ->orderBy('id')
                ->get()
                ->map(fn (Court $court): array => [
                    'id' => $court->id,
                    'name' => $court->name,
                    'is_active' => $court->is_active,
                    'bookings_count' => $court->bookings_count,
                    'sessions_count' => $court->sessions_count,
                    'active_session' => $court->activeSession ? [
                        'booking' => $court->activeSession->booking?->customer_name,
                        'minutes_remaining' => $court->activeSession->minutes_remaining,
                    ] : null,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:courts,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Court::create([
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Court created.');
    }

    public function update(Request $request, Court $court): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:courts,name,' . $court->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $court->update([
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Court updated.');
    }
}