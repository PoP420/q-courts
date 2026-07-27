<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('queued_at')->nullable()->after('notes');
            $table->text('queue_notes')->nullable()->after('queued_at');
            $table->index('queued_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['queued_at']);
            $table->dropColumn(['queued_at', 'queue_notes']);
        });
    }
};