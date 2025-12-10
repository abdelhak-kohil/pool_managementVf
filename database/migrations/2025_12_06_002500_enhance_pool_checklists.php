<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Update Daily Tasks
        Schema::table('pool_schema.pool_daily_tasks', function (Blueprint $table) {
            $table->boolean('debris_removed')->default(false);
            $table->boolean('drain_covers_inspected')->default(false);
            $table->boolean('clarity_test_passed')->default(false);
        });

        // Update Weekly Tasks
        Schema::table('pool_schema.pool_weekly_tasks', function (Blueprint $table) {
            $table->boolean('fittings_retightened')->default(false);
            $table->boolean('heater_tested')->default(false);
            // lighting_checked is already in daily? If moving, we'd drop from daily and add here.
            // For now, assuming we just add new ones.
        });

        // Create Monthly Tasks
        Schema::create('pool_schema.pool_monthly_tasks', function (Blueprint $table) {
            $table->id('monthly_task_id');
            $table->foreignId('facility_id')->constrained('pool_schema.facilities', 'facility_id')->onDelete('cascade');
            $table->unsignedBigInteger('technician_id');
            $table->foreign('technician_id')->references('staff_id')->on('pool_schema.staff');
            
            $table->boolean('water_replacement_partial')->default(false);
            $table->boolean('full_system_inspection')->default(false);
            $table->boolean('chemical_dosing_calibration')->default(false);
            
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pool_schema.pool_monthly_tasks');

        Schema::table('pool_schema.pool_weekly_tasks', function (Blueprint $table) {
            $table->dropColumn(['fittings_retightened', 'heater_tested']);
        });

        Schema::table('pool_schema.pool_daily_tasks', function (Blueprint $table) {
            $table->dropColumn(['debris_removed', 'drain_covers_inspected', 'clarity_test_passed']);
        });
    }
};
