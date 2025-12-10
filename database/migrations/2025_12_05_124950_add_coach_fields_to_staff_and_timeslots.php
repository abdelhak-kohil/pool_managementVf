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
        Schema::table('pool_schema.staff', function (Blueprint $table) {
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('specialty')->nullable();
            $table->date('hiring_date')->nullable();
            $table->string('salary_type')->default('per_hour'); // fixed, per_hour, per_session
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->text('notes')->nullable();
        });

        Schema::table('pool_schema.time_slots', function (Blueprint $table) {
            $table->unsignedBigInteger('assistant_coach_id')->nullable();
            
            $table->foreign('assistant_coach_id')
                  ->references('staff_id')
                  ->on('pool_schema.staff')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_schema.time_slots', function (Blueprint $table) {
            $table->dropForeign(['assistant_coach_id']);
            $table->dropColumn('assistant_coach_id');
        });

        Schema::table('pool_schema.staff', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number', 
                'email', 
                'specialty', 
                'hiring_date', 
                'salary_type', 
                'hourly_rate', 
                'notes'
            ]);
        });
    }
};
