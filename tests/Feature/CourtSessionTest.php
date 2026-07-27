<?php

use App\Models\Booking;
use App\Models\CourtSession;
use Database\Seeders\CourtSeeder;

uses()->beforeEach(fn () => $this->seed(CourtSeeder::class));

function startSession(array $overrides = []): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/sessions/start', array_merge([
        'court_id' => 1,
        'booking_id' => null,
        'game_type' => 'Doubles',
        'planned_minutes' => 30,
    ], $overrides));
}

test('active sessions list is empty initially', function () {
    $this->getJson('/api/sessions/active')->assertOk()->assertJsonCount(0);
});

test('active endpoint returns minutes_remaining', function () {
    $startedAt = now()->subMinutes(10);

    CourtSession::create([
        'court_id' => 1,
        'planned_minutes' => 30,
        'started_at' => $startedAt,
        'status' => 'active',
    ]);

    $expected = (int) max(0, 30 - $startedAt->diffInMinutes(now(), true));

    $this->getJson('/api/sessions/active')
        ->assertOk()
        ->assertJsonFragment(['minutes_remaining' => $expected]);
});

test('start creates an active session', function () {
    startSession()
        ->assertCreated()
        ->assertJsonFragment(['status' => 'active', 'court_id' => 1]);
});

test('cannot start a second session on an occupied court', function () {
    startSession()->assertCreated();

    startSession()->assertStatus(409);
});

test('starting a session from booking auto-sets planned minutes', function () {
    $booking = Booking::create([
        'court_id' => 1,
        'customer_name' => 'Auto Minutes',
        'customer_phone' => '09170000000',
        'booking_date' => now()->toDateString(),
        'start_time' => '14:00',
        'end_time' => '15:30',
        'source' => 'online',
        'status' => 'confirmed',
    ]);

    $this->postJson('/api/sessions/start', [
        'court_id' => 1,
        'booking_id' => $booking->id,
        'game_type' => 'Doubles',
    ])
        ->assertCreated()
        ->assertJsonFragment(['planned_minutes' => 90]);
});

test('cannot start session that overlaps another booking window', function () {
    Booking::create([
        'court_id' => 1,
        'customer_name' => 'Booked Slot',
        'customer_phone' => '09170000000',
        'booking_date' => now()->toDateString(),
        'start_time' => now()->subMinutes(5)->format('H:i'),
        'end_time' => now()->addMinutes(25)->format('H:i'),
        'source' => 'online',
        'status' => 'confirmed',
    ]);

    startSession(['planned_minutes' => 20])->assertStatus(409);
});

test('start with a booking on another court is rejected', function () {
    $otherBooking = Booking::create([
        'court_id' => 2,
        'customer_name' => 'X',
        'customer_phone' => '09170000000',
        'booking_date' => '2026-08-01',
        'start_time' => '14:00',
        'end_time' => '15:00',
        'source' => 'walk_in',
        'status' => 'confirmed',
    ]);

    startSession(['booking_id' => $otherBooking->id])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'The booking does not belong to this court.']);
});

test('score update on an active session succeeds', function () {
    $id = startSession()->assertCreated()->json('id');

    $this->patchJson("/api/sessions/{$id}/score", [
        'score' => ['team_a' => 11, 'team_b' => 7],
    ])
        ->assertOk()
        ->assertJsonFragment(['team_a' => 11, 'team_b' => 7]);
});

test('score update on a completed session is rejected', function () {
    $id = startSession()->assertCreated()->json('id');
    $this->patchJson("/api/sessions/{$id}/end")->assertOk();

    $this->patchJson("/api/sessions/{$id}/score", [
        'score' => ['team_a' => 1, 'team_b' => 0],
    ])->assertStatus(422);
});

test('end completes the session', function () {
    $id = startSession()->assertCreated()->json('id');

    $this->patchJson("/api/sessions/{$id}/end", [
        'score' => ['team_a' => 11, 'team_b' => 7],
    ])
        ->assertOk()
        ->assertJsonFragment(['status' => 'completed']);

    expect(CourtSession::find($id)->minutes_remaining)->toBe(0);
});

test('ending an already-ended session is rejected', function () {
    $id = startSession()->assertCreated()->json('id');
    $this->patchJson("/api/sessions/{$id}/end")->assertOk();

    $this->patchJson("/api/sessions/{$id}/end")
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Session already ended.']);
});
