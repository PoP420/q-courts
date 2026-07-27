<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete(); // null = walk-in
            $table->string('game_type')->nullable();     // e.g. "Singles", "Doubles"
            $table->unsignedInteger('planned_minutes')->default(30);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->json('score')->nullable();           // e.g. {"team_a": 11, "team_b": 7}
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->timestamps();

            $table->index(['court_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_sessions');
    }
};
