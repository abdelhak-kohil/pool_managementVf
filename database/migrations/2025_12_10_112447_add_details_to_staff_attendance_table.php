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
        Schema::table('pool_schema.staff_attendance', function (Blueprint $table) {
            $table->decimal('overtime_hours', 5, 2)->default(0)->after('working_hours');
            $table->decimal('night_hours', 5, 2)->default(0)->after('overtime_hours');
            $table->integer('break_minutes')->default(0)->after('night_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_schema.staff_attendance', function (Blueprint $table) {
            //
        });
    }
};
