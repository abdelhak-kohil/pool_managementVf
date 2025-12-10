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
        // 1. Update Facilities (instead of creating Pools)
        if (Schema::hasTable('pool_schema.facilities')) {
            Schema::table('pool_schema.facilities', function (Blueprint $table) {
                // Add columns if they don't exist
                if (!Schema::hasColumn('pool_schema.facilities', 'type')) {
                    $table->string('type')->nullable(); // main_pool, kids_pool, jacuzzi, etc.
                }
                if (!Schema::hasColumn('pool_schema.facilities', 'volume_liters')) {
                    $table->integer('volume_liters')->nullable();
                }
                if (!Schema::hasColumn('pool_schema.facilities', 'min_temperature')) {
                    $table->decimal('min_temperature', 4, 1)->nullable();
                }
                if (!Schema::hasColumn('pool_schema.facilities', 'max_temperature')) {
                    $table->decimal('max_temperature', 4, 1)->nullable();
                }
                if (!Schema::hasColumn('pool_schema.facilities', 'active')) {
                    $table->boolean('active')->default(true);
                }
            });
        } else {
            // Create facilities table if it doesn't exist (fallback)
            Schema::create('pool_schema.facilities', function (Blueprint $table) {
                $table->id('facility_id');
                $table->string('name');
                $table->integer('capacity')->nullable();
                $table->string('status')->default('active');
                $table->string('type')->nullable();
                $table->integer('volume_liters')->nullable();
                $table->decimal('min_temperature', 4, 1)->nullable();
                $table->decimal('max_temperature', 4, 1)->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Pool Equipment
        Schema::create('pool_schema.pool_equipment', function (Blueprint $table) {
            $table->id('equipment_id');
            $table->string('name');
            $table->string('type'); // pump, filter, heater, etc.
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();
            $table->date('install_date')->nullable();
            $table->string('status')->default('operational'); // operational, warning, failure
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Pool Water Tests
        Schema::create('pool_schema.pool_water_tests', function (Blueprint $table) {
            $table->id();
            $table->dateTime('test_date');
            $table->unsignedBigInteger('technician_id');
            $table->unsignedBigInteger('pool_id'); // Maps to facility_id
            
            // Measurements
            $table->decimal('ph', 4, 2)->nullable();
            $table->decimal('chlorine_free', 4, 2)->nullable();
            $table->decimal('chlorine_total', 4, 2)->nullable();
            $table->decimal('bromine', 4, 2)->nullable();
            $table->integer('alkalinity')->nullable();
            $table->integer('hardness')->nullable();
            $table->integer('salinity')->nullable(); // ppm
            $table->decimal('turbidity', 5, 2)->nullable(); // NTU
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('orp')->nullable(); // mV
            
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->foreign('technician_id')->references('staff_id')->on(DB::raw('"pool_schema"."staff"'));
            $table->foreign('pool_id')->references('facility_id')->on('pool_schema.facilities');
        });

        // 4. Chemical Stock
        Schema::create('pool_schema.pool_chemical_stock', function (Blueprint $table) {
            $table->id('chemical_id');
            $table->string('name');
            $table->string('type'); // chlorine, pH+, etc.
            $table->decimal('quantity_available', 8, 2)->default(0);
            $table->string('unit'); // kg, L
            $table->decimal('minimum_threshold', 8, 2)->default(10);
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
        });

        // 5. Chemical Usage
        Schema::create('pool_schema.pool_chemical_usage', function (Blueprint $table) {
            $table->id('usage_id');
            $table->unsignedBigInteger('chemical_id');
            $table->unsignedBigInteger('technician_id');
            $table->decimal('quantity_used', 8, 2);
            $table->dateTime('usage_date');
            $table->string('purpose')->nullable();
            $table->unsignedBigInteger('related_test_id')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->foreign('chemical_id')->references('chemical_id')->on('pool_schema.pool_chemical_stock');
            $table->foreign('technician_id')->references('staff_id')->on(DB::raw('"pool_schema"."staff"'));
            $table->foreign('related_test_id')->references('id')->on('pool_schema.pool_water_tests')->nullOnDelete();
        });

        // 6. Equipment Maintenance Logs
        Schema::create('pool_schema.pool_equipment_maintenance', function (Blueprint $table) {
            $table->id('maintenance_id');
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('technician_id');
            $table->string('task_type'); // cleaning, replacement, etc.
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->text('description')->nullable();
            $table->text('used_parts')->nullable();
            $table->decimal('working_hours_spent', 4, 1)->nullable();
            $table->timestamps();

            $table->foreign('equipment_id')->references('equipment_id')->on('pool_schema.pool_equipment');
            $table->foreign('technician_id')->references('staff_id')->on(DB::raw('"pool_schema"."staff"'));
        });

        // 7. Incidents
        Schema::create('pool_schema.pool_incidents', function (Blueprint $table) {
            $table->id('incident_id');
            $table->string('title');
            $table->text('description');
            $table->string('severity'); // low, medium, high, critical
            $table->unsignedBigInteger('equipment_id')->nullable();
            $table->unsignedBigInteger('pool_id')->nullable(); // Maps to facility_id
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('status')->default('open'); // open, assigned, in_progress, resolved, closed
            $table->timestamps();

            $table->foreign('equipment_id')->references('equipment_id')->on('pool_schema.pool_equipment');
            $table->foreign('pool_id')->references('facility_id')->on('pool_schema.facilities');
            $table->foreign('created_by')->references('staff_id')->on(DB::raw('"pool_schema"."staff"'));
            $table->foreign('assigned_to')->references('staff_id')->on(DB::raw('"pool_schema"."staff"'));
        });

        // 8. Daily Tasks
        Schema::create('pool_schema.pool_daily_tasks', function (Blueprint $table) {
            $table->id('task_id');
            $table->unsignedBigInteger('technician_id');
            $table->unsignedBigInteger('pool_id'); // Maps to facility_id
            $table->date('task_date');
            
            // Checklist items
            $table->string('pump_status')->nullable();
            $table->decimal('pressure_reading', 5, 2)->nullable(); // bar/psi
            $table->boolean('skimmer_cleaned')->default(false);
            $table->boolean('vacuum_done')->default(false);
            $table->boolean('drains_checked')->default(false);
            $table->boolean('lighting_checked')->default(false);
            
            $table->text('anomalies_comment')->nullable();
            $table->timestamps();

            $table->foreign('technician_id')->references('staff_id')->on(DB::raw('"pool_schema"."staff"'));
            $table->foreign('pool_id')->references('facility_id')->on('pool_schema.facilities');
        });
        
        // 9. Weekly Tasks
        Schema::create('pool_schema.pool_weekly_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('technician_id');
            $table->unsignedBigInteger('pool_id'); // Maps to facility_id
            $table->integer('week_number');
            $table->integer('year');
            
            $table->boolean('backwash_done')->default(false);
            $table->boolean('filter_cleaned')->default(false);
            $table->boolean('brushing_done')->default(false);
            $table->boolean('heater_checked')->default(false);
            $table->boolean('chemical_doser_checked')->default(false);
            
            $table->text('general_inspection_comment')->nullable();
            $table->timestamps();

            $table->foreign('technician_id')->references('staff_id')->on(DB::raw('"pool_schema"."staff"'));
            $table->foreign('pool_id')->references('facility_id')->on('pool_schema.facilities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_schema.pool_weekly_tasks');
        Schema::dropIfExists('pool_schema.pool_daily_tasks');
        Schema::dropIfExists('pool_schema.pool_incidents');
        Schema::dropIfExists('pool_schema.pool_equipment_maintenance');
        Schema::dropIfExists('pool_schema.pool_chemical_usage');
        Schema::dropIfExists('pool_schema.pool_chemical_stock');
        Schema::dropIfExists('pool_schema.pool_water_tests');
        Schema::dropIfExists('pool_schema.pool_equipment');
        Schema::dropIfExists('pool_schema.pools');
    }
};
