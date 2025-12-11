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
        if (!Schema::hasTable('pool_schema.rfid_devices')) {
            Schema::create('pool_schema.rfid_devices', function (Blueprint $table) {
                $table->id();
                $table->string('device_id')->unique();
                $table->string('name')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('version')->nullable();
                $table->string('status')->default('offline'); // online, offline
                $table->timestamp('last_heartbeat')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_schema.rfid_devices');
    }
};
