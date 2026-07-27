<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSession;
use App\Models\User;
use Database\Seeders\CourtSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses()->beforeEach(fn () => $this->seed(CourtSeeder::class));

function staffUser(): User
{
    return User::factory()->create([
        'role' => 'staff',
        'password' => Hash::make('password'),
    ]);
}

function bookingData(array $overrides = []): array
{
    return array_merge([
        'court_id' => 1,
        'customer_name' => 'Mia Santos',
        'customer_phone' => '09170000000',
        'booking_date' => '2026-08-01',
        'start_time' => '14:00',
        'end_time' => '15:00',
        'status' => 'pending',
        'source' => 'online',
        'notes' => null,
    ], $overrides);
}

test('staff can view the bookings page', function () {
    Booking::create(bookingData());

    $this->actingAs(staffUser())
        ->get('/staff/bookings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Staff/Bookings')
            ->has('bookings', 1)
            ->where('bookings.0.customer_name', 'Mia Santos'));
});

test('staff can confirm and cancel bookings', function () {
    $booking = Booking::create(bookingData());

    $this->actingAs(staffUser())
        ->patch("/staff/bookings/{$booking->id}/confirm")
        ->assertRedirect();

    expect($booking->refresh()->status)->toBe('confirmed');

    $this->actingAs(staffUser())
        ->patch("/staff/bookings/{$booking->id}/cancel")
        ->assertRedirect();

    expect($booking->refresh()->status)->toBe('cancelled');
});

test('staff can reschedule a booking into a free slot', function () {
    $booking = Booking::create(bookingData());

    $this->actingAs(staffUser())
        ->patch("/staff/bookings/{$booking->id}/reschedule", [
            'court_id' => 1,
            'booking_date' => '2026-08-01',
            'start_time' => '16:00',
            'end_time' => '17:00',
        ])
        ->assertRedirect();

    expect($booking->refresh()->start_time)->toBe('16:00');
});

test('staff reschedule queues an overlapping slot', function () {
    $booking = Booking::create(bookingData());
    Booking::create(bookingData([
        'start_time' => '15:00',
        'end_time' => '16:00',
    ]));

    $this->actingAs(staffUser())
        ->patch("/staff/bookings/{$booking->id}/reschedule", [
            'court_id' => 1,
            'booking_date' => '2026-08-01',
            'start_time' => '15:30',
            'end_time' => '16:30',
        ])
        ->assertRedirect();

    expect($booking->refresh()->queued_at)->not->toBeNull();
});

test('staff can assign a queued booking to an available slot', function () {
    $booking = Booking::create(bookingData([
        'queued_at' => now(),
        'queue_notes' => 'Please prioritize this customer',
    ]));

    $this->actingAs(staffUser())
        ->patch("/staff/bookings/{$booking->id}/assign", [
            'court_id' => 2,
            'booking_date' => '2026-08-01',
            'start_time' => '16:00',
            'end_time' => '17:00',
        ])
        ->assertRedirect();

    $booking->refresh();

    expect($booking->court_id)->toBe(2)
        ->and($booking->queued_at)->toBeNull()
        ->and($booking->queue_notes)->toBeNull();
});

test('staff can manage courts', function () {
    $this->actingAs(staffUser())
        ->post('/staff/courts', [
            'name' => 'Court 3',
            'is_active' => true,
        ])
        ->assertRedirect();

    expect(Court::where('name', 'Court 3')->exists())->toBeTrue();

    $this->actingAs(staffUser())
        ->patch('/staff/courts/1', [
            'name' => 'Court 1 Updated',
            'is_active' => false,
        ])
        ->assertRedirect();

    expect(Court::find(1)?->name)->toBe('Court 1 Updated')
        ->and(Court::find(1)?->is_active)->toBeFalse();
});

test('staff can create a walk-in session', function () {
    $this->actingAs(staffUser())
        ->get('/staff/sessions')
        ->assertOk();

    $this->actingAs(staffUser())
        ->post('/staff/sessions', [
            'court_id' => 1,
            'game_type' => 'Walk-in',
            'planned_minutes' => 30,
        ])
        ->assertRedirect();

    expect(CourtSession::where('court_id', 1)->where('status', 'active')->exists())->toBeTrue();
});

test('staff can view the live court board', function () {
    CourtSession::create([
        'court_id' => 1,
        'booking_id' => null,
        'game_type' => 'Singles',
        'planned_minutes' => 30,
        'started_at' => now(),
        'score' => ['team_a' => 11, 'team_b' => 7],
        'status' => 'active',
    ]);

    $this->actingAs(staffUser())
        ->get('/staff/board')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Staff/LiveBoard')
            ->has('activeSessions', 1)
            ->where('activeSessions.0.court', 'Court 1'));
});