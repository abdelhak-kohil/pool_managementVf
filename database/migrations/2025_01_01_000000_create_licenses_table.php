<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure schemas exist (if not already handled by other migrations)
        DB::statement('CREATE SCHEMA IF NOT EXISTS pool_schema');

        Schema::create('pool_schema.licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('license_key'); // The full signed payload
            $table->string('client_name')->nullable(); // Cached
            $table->string('email')->nullable(); // Cached
            $table->enum('status', ['active', 'suspended', 'expired'])->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->string('server_hash')->nullable(); // Fingerprint binding
            $table->json('metadata')->nullable(); // Cached modules/limits
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_schema.licenses');
    }
};
