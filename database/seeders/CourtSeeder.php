<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        Court::firstOrCreate(['name' => 'Court 1']);
        Court::firstOrCreate(['name' => 'Court 2']);
    }
}
