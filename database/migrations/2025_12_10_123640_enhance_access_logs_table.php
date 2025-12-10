<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pool_schema.access_logs', function (Blueprint $table) {
            $table->string('action_type')->default('entry'); // entry, exit, door_open, maintenance_start
            $table->string('location')->nullable(); // Main Entrance, Pump Room, etc.
            $table->jsonb('metadata')->nullable(); // Extra info
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_schema.access_logs', function (Blueprint $table) {
            $table->dropColumn(['action_type', 'location', 'metadata']);
        });
    }
};
