<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pool_schema.partner_group_attendance', function (Blueprint $table) {
            $table->id('attendance_id');
            $table->integer('partner_group_id');
            $table->integer('badge_id')->nullable(); // Can be null if manual override without badge
            $table->integer('slot_id')->nullable(); // Can be null if override outside slots
            $table->integer('staff_id'); // Who processed it
            $table->integer('attendee_count');
            $table->timestamp('access_time');
            $table->string('access_decision'); // granted, denied
            $table->string('denial_reason')->nullable();

            $table->foreign('partner_group_id')->references('group_id')->on('pool_schema.partner_groups');
            $table->foreign('slot_id')->references('slot_id')->on('pool_schema.time_slots');
            $table->foreign('staff_id')->references('staff_id')->on('pool_schema.staff');
            // Assuming AccessBadge PK is badge_id
             $table->foreign('badge_id')->references('badge_id')->on('pool_schema.access_badges');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pool_schema.partner_group_attendance');
    }
};
