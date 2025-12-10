<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1. Create Task Templates Table
        Schema::create('pool_schema.task_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['daily', 'weekly', 'monthly']);
            $table->json('items'); // Stores the form definition
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Add custom_data and template_id to Daily Tasks
        Schema::table('pool_schema.pool_daily_tasks', function (Blueprint $table) {
            $table->json('custom_data')->nullable()->after('anomalies_comment');
            $table->foreignId('template_id')->nullable()->constrained('pool_schema.task_templates')->nullOnDelete();
        });

        // 3. Add custom_data and template_id to Weekly Tasks
        Schema::table('pool_schema.pool_weekly_tasks', function (Blueprint $table) {
            $table->json('custom_data')->nullable()->after('general_inspection_comment');
            $table->foreignId('template_id')->nullable()->constrained('pool_schema.task_templates')->nullOnDelete();
        });

        // 4. Add custom_data and template_id to Monthly Tasks
        Schema::table('pool_schema.pool_monthly_tasks', function (Blueprint $table) {
            $table->json('custom_data')->nullable()->after('notes');
            $table->foreignId('template_id')->nullable()->constrained('pool_schema.task_templates')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('pool_schema.pool_monthly_tasks', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['custom_data', 'template_id']);
        });

        Schema::table('pool_schema.pool_weekly_tasks', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['custom_data', 'template_id']);
        });

        Schema::table('pool_schema.pool_daily_tasks', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['custom_data', 'template_id']);
        });

        Schema::dropIfExists('pool_schema.task_templates');
    }
};
