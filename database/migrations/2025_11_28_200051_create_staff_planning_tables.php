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
        Schema::create('pool_schema.staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->integer('staff_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type')->default('work'); // work, meeting, training
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('staff_id')->on('pool_schema.staff')->onDelete('cascade');
        });

        Schema::create('pool_schema.staff_leaves', function (Blueprint $table) {
            $table->id();
            $table->integer('staff_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('type'); // vacation, sick, absence, other
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('staff_id')->on('pool_schema.staff')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_schema.staff_leaves');
        Schema::dropIfExists('pool_schema.staff_schedules');
    }
};
