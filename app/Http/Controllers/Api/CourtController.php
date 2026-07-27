<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Support\BookingConflictChecker;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    public function __construct(private readonly BookingConflictChecker $conflicts)
    {
    }

    /** GET /api/courts */
    public function index()
    {
        return Court::with('activeSession')->where('is_active', true)->get();
    }

    /** GET /api/courts/availability?date=YYYY-MM-DD&start_time=HH:MM&end_time=HH:MM */
    public function availability(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        return Court::orderBy('id')->get()->map(function (Court $court) use ($data): array {
            $payload = [
                'court_id' => $court->id,
                'booking_date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ];

            $reason = $this->conflicts->availabilityReason($court, $payload);

            return [
                'id' => $court->id,
                'name' => $court->name,
                'is_active' => $court->is_active,
                'available' => is_null($reason),
                'reason' => $reason,
            ];
        });
    }
}
