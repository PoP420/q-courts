<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSession;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $recentBookings = Booking::with('court')
            ->latest('booking_date')
            ->latest('start_time')
            ->limit(8)
            ->get()
            ->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'court' => $booking->court?->name,
                'customer_name' => $booking->customer_name,
                'booking_date' => $booking->booking_date?->toDateString(),
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'status' => $booking->status,
                'source' => $booking->source,
            ]);

        $courts = Court::with('activeSession.booking')
            ->orderBy('id')
            ->get()
            ->map(fn (Court $court): array => [
                'id' => $court->id,
                'name' => $court->name,
                'is_active' => $court->is_active,
                'active_session' => $court->activeSession ? [
                    'id' => $court->activeSession->id,
                    'game_type' => $court->activeSession->game_type,
                    'planned_minutes' => $court->activeSession->planned_minutes,
                    'minutes_remaining' => $court->activeSession->minutes_remaining,
                    'status' => $court->activeSession->status,
                    'booking' => $court->activeSession->booking?->customer_name,
                ] : null,
            ]);

        $activeSessions = CourtSession::with(['court', 'booking'])
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
            ]);

        return Inertia::render('Staff/Dashboard', [
            'auth' => [
                'user' => request()->user(),
            ],
            'stats' => [
                'courts' => Court::count(),
                'active_sessions' => $activeSessions->count(),
                'pending_bookings' => Booking::where('status', 'pending')->count(),
                'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            ],
            'recentBookings' => $recentBookings,
            'courts' => $courts,
            'activeSessions' => $activeSessions,
        ]);
    }
}