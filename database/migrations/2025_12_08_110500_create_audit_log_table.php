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
        // Ensure the schema exists (it should, but good practice)
        // Schema::createSchema('pool_schema'); 

        if (!Schema::hasTable('pool_schema.audit_log')) {
            Schema::create('pool_schema.audit_log', function (Blueprint $table) {
                $table->id('log_id');
                $table->string('table_name');
                $table->string('record_id')->nullable(); // Changed to string to match trigger input
                $table->string('action'); // e.g. INSERT, UPDATE, DELETE
                $table->unsignedBigInteger('changed_by_staff_id')->nullable();
                $table->timestamp('change_timestamp')->useCurrent();
                $table->jsonb('old_data_jsonb')->nullable();
                $table->jsonb('new_data_jsonb')->nullable();

                $table->foreign('changed_by_staff_id')
                      ->references('staff_id')
                      ->on('pool_schema.staff')
                      ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_schema.audit_log');
    }
};
