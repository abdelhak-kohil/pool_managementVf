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
        Schema::table('pool_schema.members', function (Blueprint $table) {
            $table->string('photo_path', 255)->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->text('health_conditions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_schema.members', function (Blueprint $table) {
            $table->dropColumn([
                'photo_path',
                'emergency_contact_name',
                'emergency_contact_phone',
                'notes',
                'health_conditions'
            ]);
        });
    }
};
