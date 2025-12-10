<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Backup Settings Table
        Schema::create('pool_schema.backup_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('automatic_enabled')->default(false);
            $table->time('scheduled_time')->default('00:00');
            $table->string('frequency')->default('daily'); // daily, weekly, monthly
            $table->integer('retention_days')->default(30);
            $table->string('storage_preference')->default('local'); // local, network, cloud
            $table->string('network_path')->nullable();
            $table->string('cloud_bucket')->nullable();
            $table->timestamps();
        });

        // Initialize default settings
        DB::table('pool_schema.backup_settings')->insert([
            'automatic_enabled' => false,
            'scheduled_time' => '02:00',
            'frequency' => 'daily',
            'retention_days' => 7,
            'storage_preference' => 'local',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Backup Jobs Table
        Schema::create('pool_schema.backup_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('backup_type'); // full, schema, data
            $table->string('file_name');
            $table->string('file_size')->nullable();
            $table->string('storage_location'); // local, network, cloud
            $table->string('status')->default('pending'); // pending, running, success, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('triggered_by')->nullable(); // Staff ID
            $table->timestamps();

            $table->foreign('triggered_by')->references('staff_id')->on('pool_schema.staff')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pool_schema.backup_jobs');
        Schema::dropIfExists('pool_schema.backup_settings');
    }
};
