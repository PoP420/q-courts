<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CourtSession;
use App\Support\BookingConflictChecker;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CourtSessionController extends Controller
{
    public function __construct(private readonly BookingConflictChecker $conflicts)
    {
    }

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
            'planned_minutes' => ['nullable', 'integer', 'min:5', 'max:240', 'required_without:booking_id'],
        ]);

        $booking = null;

        if (! is_null($data['booking_id'])) {
            $booking = Booking::find($data['booking_id']);

            if ($booking && $booking->court_id !== $data['court_id']) {
                return response()->json([
                    'message' => 'The booking does not belong to this court.',
                ], 422);
            }

            $bookingStart = Carbon::createFromFormat('H:i', $booking->start_time);
            $bookingEnd = Carbon::createFromFormat('H:i', $booking->end_time);
            $data['planned_minutes'] = $bookingStart->diffInMinutes($bookingEnd);
        }

        $plannedMinutes = (int) $data['planned_minutes'];

        $proposedStart = now();
        $proposedEnd = (clone $proposedStart)->addMinutes($plannedMinutes);
        $proposedBookingDate = $booking?->booking_date?->toDateString() ?? $proposedStart->toDateString();

        $conflictData = [
            'court_id' => $data['court_id'],
            'booking_date' => $proposedBookingDate,
            'start_time' => $proposedStart->format('H:i'),
            'end_time' => $proposedEnd->format('H:i'),
        ];

        if ($this->conflicts->conflicts($conflictData, ignoreId: $booking?->id)) {
            return response()->json([
                'message' => 'This court has a booking/session conflict for the selected time window.',
            ], 409);
        }

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
        if ($session->status === 'completed') {
            return response()->json(['message' => 'Session already ended.'], 422);
        }

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
        if ($session->status !== 'active') {
            return response()->json([
                'message' => 'Can only update the score of an active session.',
            ], 422);
        }

        $data = $request->validate([
            'score' => ['required', 'array'],
        ]);

        $session->update(['score' => $data['score']]);

        return $session;
    }
}
