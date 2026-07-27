<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\BookingConflictChecker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(private readonly BookingConflictChecker $conflicts)
    {
    }

    /** GET /api/bookings?date=YYYY-MM-DD&court_id=1 */
    public function index(Request $request)
    {
        $query = Booking::with('court')
            ->whereNotIn('status', ['cancelled']);

        if ($request->filled('date')) {
            $query->whereDate('booking_date', $request->date('date'));
        }

        if ($request->filled('court_id')) {
            $query->where('court_id', $request->integer('court_id'));
        }

        return $query->orderBy('booking_date')->orderBy('start_time')->get();
    }

    /** POST /api/bookings */
    public function store(Request $request)
    {
        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'booking_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'source' => ['required', Rule::in(['online', 'walk_in'])],
            'notes' => ['nullable', 'string'],
            'queue_notes' => ['nullable', 'string'],
        ]);

        $requestedSlot = $data;

        if ($this->conflicts->conflicts($data)) {
            $booking = Booking::create([
                ...$data,
                'status' => 'pending',
                'queued_at' => now(),
            ]);

            return response()->json([
                'message' => 'No court is currently available for that slot. The booking has been added to the waiting list.',
                'queued' => true,
                'requested_slot' => [
                    'court_id' => $requestedSlot['court_id'],
                    'booking_date' => $requestedSlot['booking_date'],
                    'start_time' => $requestedSlot['start_time'],
                    'end_time' => $requestedSlot['end_time'],
                ],
                'booking' => $booking->load('court'),
            ], 202);
        }

        $data['status'] = $data['source'] === 'walk_in' ? 'confirmed' : 'pending';
        $data['queued_at'] = null;

        $booking = Booking::create($data);

        return response()->json($booking->load('court'), 201);
    }

    /** PATCH /api/bookings/{booking} */
    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'court_id' => ['sometimes', 'exists:courts,id'],
            'booking_date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'queue_notes' => ['nullable', 'string'],
        ]);

        if (isset($data['start_time']) || isset($data['end_time']) || isset($data['court_id']) || isset($data['booking_date'])) {
            $check = array_merge($booking->only(['court_id', 'booking_date', 'start_time', 'end_time']), $data);

            if ($this->conflicts->conflicts($check, ignoreId: $booking->id)) {
                $booking->update([
                    'court_id' => $check['court_id'],
                    'booking_date' => $check['booking_date'],
                    'start_time' => $check['start_time'],
                    'end_time' => $check['end_time'],
                    'status' => 'pending',
                    'queued_at' => now(),
                    'queue_notes' => $data['queue_notes'] ?? $booking->queue_notes,
                ]);

                return response()->json([
                    'message' => 'Requested slot is unavailable. This booking has been placed in the waiting list.',
                    'queued' => true,
                    'booking' => $booking->fresh('court'),
                ], 200);
            }

            $data['queued_at'] = null;
        }

        $booking->update($data);

        return $booking->fresh('court');
    }

    /** DELETE /api/bookings/{booking} */
    public function destroy(Booking $booking)
    {
        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Booking cancelled.']);
    }
}
