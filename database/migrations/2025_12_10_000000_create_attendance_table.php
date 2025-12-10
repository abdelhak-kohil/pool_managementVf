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
        Schema::create('pool_schema.staff_attendance', function (Blueprint $table) {
            $table->id('attendance_id');
            $table->unsignedBigInteger('staff_id');
            $table->dateTime('check_in');
            $table->dateTime('check_out')->nullable();
            $table->date('date');
            $table->string('status')->default('present'); // present, late, overtime, anomaly
            $table->integer('delay_minutes')->default(0);
            $table->decimal('working_hours', 5, 2)->default(0);
            $table->string('check_in_method')->default('manual'); // rfid, pin, manual
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('staff_id')->on('pool_schema.staff')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_schema.staff_attendance');
    }
};
