<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'System Admin',
            'email' => 'admin@qcourts.com',
            'email_verified_at' => now(),
            'password' => '$2y$12$awBPdTZVNINeXN2zHPzyl.m1A5T6sxRzs/UxtVZjw13rQ6Vqren/K',
            'remember_token' => Str::random(10),
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin@qcourts.com')->delete();
    }
};
