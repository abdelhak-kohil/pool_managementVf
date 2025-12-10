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
            $table->enum('validation_status', ['pending', 'validated', 'rejected', 'corrected'])->default('pending');
            $table->timestamp('validation_date')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->text('justification')->nullable(); // For absence/lateness
            $table->text('correction_reason')->nullable(); // For manual edits
            $table->text('admin_comments')->nullable();
            
            // Foreign key for validator (assuming it's a staff member or user, let's link to staff for now, or users if distinct)
            // Let's assume Validated By refers to a User ID (Admin)
             $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_schema.staff_attendance', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn([
                'validation_status', 
                'validation_date', 
                'validated_by', 
                'justification', 
                'correction_reason', 
                'admin_comments'
            ]);
        });
    }
};
