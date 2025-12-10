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
        Schema::create('coach_time_slot', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_schema.time_slots', function (Blueprint $table) {
            $table->foreignId('coach_id')->nullable()->constrained('pool_schema.staff', 'staff_id')->onDelete('set null');
        });

        // Restore data (pick first coach if multiple)
        // Check if table exists first to avoid errors if it was already dropped manually
        if (Schema::hasTable('pool_schema.coach_time_slot')) {
            $assignments = DB::table('pool_schema.coach_time_slot')->get();
            foreach ($assignments as $assignment) {
                DB::table('pool_schema.time_slots')
                    ->where('slot_id', $assignment->slot_id)
                    ->whereNull('coach_id') // Only set if not already set
                    ->update(['coach_id' => $assignment->coach_id]);
            }
        }

        Schema::dropIfExists('pool_schema.coach_time_slot');
    }
};
