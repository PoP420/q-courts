<?php

use App\Models\Court;
use Database\Seeders\CourtSeeder;

uses()->beforeEach(fn () => $this->seed(CourtSeeder::class));

test('lists active courts', function () {
    $this->getJson('/api/courts')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['name' => 'Court 1'])
        ->assertJsonFragment(['name' => 'Court 2']);
});

test('excludes inactive courts', function () {
    Court::firstWhere('name', 'Court 2')->update(['is_active' => false]);

    $this->getJson('/api/courts')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonMissing(['name' => 'Court 2']);
});

test('includes the active session for an occupied court', function () {
    $court = Court::firstWhere('name', 'Court 1');

    \App\Models\CourtSession::create([
        'court_id' => $court->id,
        'game_type' => 'Doubles',
        'planned_minutes' => 30,
        'started_at' => now(),
        'status' => 'active',
    ]);

    $this->getJson('/api/courts')
        ->assertOk()
        ->assertJsonFragment(['game_type' => 'Doubles', 'status' => 'active']);
});
