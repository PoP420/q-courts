<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSession;
use Carbon\Carbon;

class BookingConflictChecker
{
    public function conflicts(array $data, ?int $ignoreId = null): bool
    {
        return $this->conflictsWithBookings($data, $ignoreId)
            || $this->conflictsWithSessions($data);
    }

    public function conflictsWithBookings(array $data, ?int $ignoreId = null): bool
    {
        $query = Booking::where('court_id', $data['court_id'])
            ->whereDate('booking_date', $data['booking_date'])
            ->whereNotIn('status', ['cancelled'])
            ->whereNull('queued_at')
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function conflictsWithSessions(array $data): bool
    {
        $date = Carbon::parse($data['booking_date'])->toDateString();
        $requestedStart = Carbon::parse($date.' '.$data['start_time']);
        $requestedEnd = Carbon::parse($date.' '.$data['end_time']);

        $activeSessions = CourtSession::where('court_id', $data['court_id'])
            ->where('status', 'active')
            ->get(['started_at', 'planned_minutes']);

        foreach ($activeSessions as $session) {
            $sessionStart = Carbon::parse($session->started_at);
            $sessionEnd = (clone $sessionStart)->addMinutes((int) $session->planned_minutes);

            if ($sessionStart->lt($requestedEnd) && $sessionEnd->gt($requestedStart)) {
                return true;
            }
        }

        return false;
    }

    public function availabilityReason(Court $court, array $data, ?int $ignoreBookingId = null): ?string
    {
        if (! $court->is_active) {
            return 'inactive';
        }

        if ($this->conflictsWithSessions($data)) {
            return 'occupied';
        }

        if ($this->conflictsWithBookings($data, $ignoreBookingId)) {
            return 'booked';
        }

        return null;
    }
}