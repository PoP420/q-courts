<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('staff login page is accessible', function () {
    $this->get('/staff/login')->assertOk();
});

test('guest is redirected away from the staff dashboard', function () {
    $this->get('/staff')->assertRedirect('/staff/login');
});

test('staff user can access the dashboard', function () {
    $user = User::factory()->create([
        'role' => 'staff',
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user)->get('/staff')->assertOk();
});

test('non-staff user is forbidden from the dashboard', function () {
    $user = User::factory()->create([
        'role' => 'customer',
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user)->get('/staff')->assertForbidden();
});