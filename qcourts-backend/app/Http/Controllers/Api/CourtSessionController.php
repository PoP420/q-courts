<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourtSession;
use Illuminate\Http\Request;

class CourtSessionController extends Controller
{
    /** GET /api/sessions/active — powers the live "which courts are occupied" board. */
    public function active()
    {
        return CourtSession::with(['court', 'booking'])
            ->where('status', 'active')
            ->get()
            ->map(fn ($session) => [
                ...$session->toArray(),
                'minutes_remaining' => $session->minutes_remaining,
            ]);
    }

    /** POST /api/sessions/start — staff taps "Start" on a court in the app. */
    public function start(Request $request)
    {
        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'game_type' => ['nullable', 'string', 'max:100'],
            'planned_minutes' => ['required', 'integer', 'min:5', 'max:240'],
        ]);

        $alreadyActive = CourtSession::where('court_id', $data['court_id'])
            ->where('status', 'active')
            ->exists();

        if ($alreadyActive) {
            return response()->json(['message' => 'This court already has an active session.'], 409);
        }

        $session = CourtSession::create([
            ...$data,
            'started_at' => now(),
            'status' => 'active',
        ]);

        return response()->json($session->load('court'), 201);
    }

    /** PATCH /api/sessions/{session}/end — staff taps "End" or the timer runs out. */
    public function end(Request $request, CourtSession $session)
    {
        $data = $request->validate([
            'score' => ['nullable', 'array'],
        ]);

        $session->update([
            'ended_at' => now(),
            'status' => 'completed',
            'score' => $data['score'] ?? $session->score,
        ]);

        return $session;
    }

    /** PATCH /api/sessions/{session}/score — live score updates during play. */
    public function updateScore(Request $request, CourtSession $session)
    {
        $data = $request->validate([
            'score' => ['required', 'array'],
        ]);

        $session->update(['score' => $data['score']]);

        return $session;
    }
}
