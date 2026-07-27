<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSession;
use Database\Seeders\CourtSeeder;

uses()->beforeEach(fn () => $this->seed(CourtSeeder::class));

function bookingPayload(array $overrides = []): array
{
    return array_merge([
        'court_id' => 1,
        'customer_name' => 'Juan Dela Cruz',
        'customer_phone' => '09171234567',
        'booking_date' => '2026-08-01',
        'start_time' => '14:00',
        'end_time' => '15:00',
        'source' => 'online',
    ], $overrides);
}

test('online booking is created pending', function () {
    $this->postJson('/api/bookings', bookingPayload())
        ->assertCreated()
        ->assertJsonFragment(['status' => 'pending', 'source' => 'online']);
});

test('walk-in booking is auto-confirmed', function () {
    $this->postJson('/api/bookings', bookingPayload(['source' => 'walk_in']))
        ->assertCreated()
        ->assertJsonFragment(['status' => 'confirmed']);
});

test('overlapping booking is queued', function () {
    $this->postJson('/api/bookings', bookingPayload())->assertCreated();

    // 14:30-15:30 overlaps the existing 14:00-15:00 slot.
    $this->postJson('/api/bookings', bookingPayload([
        'start_time' => '14:30',
        'end_time' => '15:30',
    ]))
        ->assertStatus(202)
        ->assertJsonFragment([
            'queued' => true,
        ]);

    $queued = Booking::latest('id')->first();

    expect($queued->queued_at)->not->toBeNull()
        ->and($queued->status)->toBe('pending');
});

test('end time must be after start time', function () {
    $this->postJson('/api/bookings', bookingPayload([
        'start_time' => '15:00',
        'end_time' => '14:00',
    ]))->assertStatus(422);
});

test('missing required fields are rejected', function () {
    $this->postJson('/api/bookings', [])->assertStatus(422);
});

test('invalid source enum is rejected', function () {
    $this->postJson('/api/bookings', bookingPayload(['source' => 'phone']))
        ->assertStatus(422);
});

test('index filters by court and excludes cancelled', function () {
    $this->postJson('/api/bookings', bookingPayload())->assertCreated();
    $this->postJson('/api/bookings', bookingPayload([
        'court_id' => 2,
        'start_time' => '16:00',
        'end_time' => '17:00',
    ]))->assertCreated();

    $cancelled = Booking::create(bookingPayload([
        'start_time' => '18:00',
        'end_time' => '19:00',
    ]));
    $cancelled->update(['status' => 'cancelled']);

    $this->getJson('/api/bookings?court_id=1')->assertOk()->assertJsonCount(1);
    $this->getJson('/api/bookings')->assertOk()->assertJsonCount(2); // cancelled excluded
});

test('reschedule into a taken slot queues the booking', function () {
    $id = $this->postJson('/api/bookings', bookingPayload())->assertCreated()->json('id');

    // Another booking already occupies an adjacent slot (15:00-16:00),
    // which the rescheduled 14:30-15:30 would overlap.
    $this->postJson('/api/bookings', bookingPayload([
        'start_time' => '15:00',
        'end_time' => '16:00',
    ]))->assertCreated();

    $this->patchJson("/api/bookings/{$id}", [
        'start_time' => '14:30',
        'end_time' => '15:30',
    ])
        ->assertOk()
        ->assertJsonFragment(['queued' => true]);

    expect(Booking::find($id)?->queued_at)->not->toBeNull();
});

test('reschedule into a free slot succeeds', function () {
    $id = $this->postJson('/api/bookings', bookingPayload())->assertCreated()->json('id');

    $this->patchJson("/api/bookings/{$id}", [
        'start_time' => '15:00',
        'end_time' => '16:00',
    ])->assertOk();
});

test('patch can cancel a booking (soft)', function () {
    $id = $this->postJson('/api/bookings', bookingPayload())->assertCreated()->json('id');

    $this->patchJson("/api/bookings/{$id}", ['status' => 'cancelled'])
        ->assertOk()
        ->assertJsonFragment(['status' => 'cancelled']);

    expect(Booking::find($id))->not->toBeNull();
});

test('delete soft-cancels the booking', function () {
    $id = $this->postJson('/api/bookings', bookingPayload())->assertCreated()->json('id');

    $this->deleteJson("/api/bookings/{$id}")->assertOk();

    expect(Booking::find($id)->status)->toBe('cancelled');
});

test('availability endpoint marks courts with reasons and priority', function () {
    Court::find(2)?->update(['is_active' => false]);

    $this->postJson('/api/bookings', bookingPayload())->assertCreated();

    CourtSession::create([
        'court_id' => 1,
        'planned_minutes' => 30,
        'started_at' => now()->setDate(2026, 8, 1)->setTime(14, 15),
        'status' => 'active',
    ]);

    $response = $this->getJson('/api/courts/availability?date=2026-08-01&start_time=14:30&end_time=15:00')
        ->assertOk();

    $courts = collect($response->json());
    $court1 = $courts->firstWhere('id', 1);
    $court2 = $courts->firstWhere('id', 2);

    expect($court1['available'])->toBeFalse()
        ->and($court1['reason'])->toBe('occupied')
        ->and($court2['available'])->toBeFalse()
        ->and($court2['reason'])->toBe('inactive');
});
