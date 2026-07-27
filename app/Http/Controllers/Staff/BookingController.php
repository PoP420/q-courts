<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Support\BookingConflictChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(private readonly BookingConflictChecker $conflicts)
    {
    }

    public function index(Request $request): Response
    {
        $bookings = Booking::with('court')
            ->orderByRaw('queued_at is null')
            ->orderBy('queued_at')
            ->orderByDesc('booking_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'court_id' => $booking->court_id,
                'court_name' => $booking->court?->name,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'booking_date' => $booking->booking_date?->toDateString(),
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'status' => $booking->status,
                'source' => $booking->source,
                'notes' => $booking->notes,
                'queued_at' => $booking->queued_at?->toDateTimeString(),
                'queue_notes' => $booking->queue_notes,
            ]);

        return Inertia::render('Staff/Bookings', [
            'auth' => [
                'user' => $request->user(),
            ],
            'bookings' => $bookings,
            'courts' => Court::orderBy('id')->get()->map(fn (Court $court): array => [
                'id' => $court->id,
                'name' => $court->name,
                'is_active' => $court->is_active,
            ]),
        ]);
    }

    public function confirm(Booking $booking): RedirectResponse
    {
        $booking->update(['status' => 'confirmed']);

        return back()->with('success', 'Booking confirmed.');
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'Booking cancelled.');
    }

    public function reschedule(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'booking_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'queue_notes' => ['nullable', 'string'],
        ]);

        if ($this->conflicts->conflicts($data, ignoreId: $booking->id)) {
            $booking->update([
                ...$data,
                'status' => 'pending',
                'queued_at' => now(),
                'queue_notes' => $data['queue_notes'] ?? $booking->queue_notes,
            ]);

            return back()->with('success', 'Requested slot is unavailable. Booking moved to waiting list.');
        }

        $booking->update([
            ...$data,
            'queued_at' => null,
        ]);

        return back()->with('success', 'Booking rescheduled.');
    }

    public function assign(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'booking_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        if ($this->conflicts->conflicts($data, ignoreId: $booking->id)) {
            return back()->withErrors([
                'slot' => 'That court is still unavailable for this time slot.',
            ]);
        }

        $booking->update([
            ...$data,
            'status' => 'pending',
            'queued_at' => null,
            'queue_notes' => null,
        ]);

        return back()->with('success', 'Queued booking assigned to court.');
    }
}