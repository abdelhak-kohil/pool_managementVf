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
        Schema::create('pool_schema.subscription_slots', function (Blueprint $table) {
            $table->id('subscription_slot_id');
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('slot_id');
            $table->timestamps();

            $table->foreign('subscription_id')->references('subscription_id')->on('pool_schema.subscriptions')->onDelete('cascade');
            $table->foreign('slot_id')->references('slot_id')->on('pool_schema.time_slots')->onDelete('cascade');
            
            // Optional: Ensure a subscription can't have duplicate slots
            $table->unique(['subscription_id', 'slot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_schema.subscription_slots');
    }
};
