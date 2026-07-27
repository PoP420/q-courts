<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
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
        ]);

        if ($this->hasConflict($data)) {
            return response()->json([
                'message' => 'That court is already booked for part or all of this time slot.',
            ], 409);
        }

        $data['status'] = $data['source'] === 'walk_in' ? 'confirmed' : 'pending';

        $booking = Booking::create($data);

        return response()->json($booking->load('court'), 201);
    }

    /** PATCH /api/bookings/{booking} */
    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
        ]);

        if (isset($data['start_time']) || isset($data['end_time'])) {
            $check = array_merge($booking->only(['court_id', 'booking_date', 'start_time', 'end_time']), $data);
            if ($this->hasConflict($check, ignoreId: $booking->id)) {
                return response()->json([
                    'message' => 'That court is already booked for part or all of this time slot.',
                ], 409);
            }
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

    /**
     * Overlap check: two slots on the same court/date conflict if one starts before
     * the other ends, in both directions.
     */
    private function hasConflict(array $data, ?int $ignoreId = null): bool
    {
        $query = Booking::where('court_id', $data['court_id'])
            ->whereDate('booking_date', $data['booking_date'])
            ->whereNotIn('status', ['cancelled'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
