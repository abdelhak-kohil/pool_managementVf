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
        Schema::create('pool_schema.reservations', function (Blueprint $table) {
            $table->id('reservation_id');
            $table->unsignedBigInteger('slot_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('partner_group_id')->nullable();
            $table->string('reservation_type')->default('individual'); // individual, group
            $table->dateTime('reserved_at');
            $table->string('status')->default('confirmed'); // confirmed, cancelled, attended
            $table->text('notes')->nullable();
            // Timestamps not enabled in model, but useful to have in DB
            $table->timestamps();

            $table->foreign('slot_id')->references('slot_id')->on('pool_schema.time_slots')->onDelete('cascade');
            $table->foreign('member_id')->references('member_id')->on('pool_schema.members')->onDelete('cascade');
            $table->foreign('partner_group_id')->references('group_id')->on('pool_schema.partner_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_schema.reservations');
    }
};
