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
        // 1. Update Subscriptions Table (Add allowed_days)
        if (Schema::hasTable('pool_schema.subscriptions') && !Schema::hasColumn('pool_schema.subscriptions', 'allowed_days')) {
            Schema::table('pool_schema.subscriptions', function (Blueprint $table) {
                $table->jsonb('allowed_days')->nullable()->after('status'); // e.g., ["Monday", "Wednesday"]
            });
        }

        // 2. Create Subscription Slots Table
        if (!Schema::hasTable('pool_schema.subscription_slots')) {
            Schema::create('pool_schema.subscription_slots', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('subscription_id');
                $table->unsignedInteger('activity_id')->nullable();
                $table->string('day_of_week', 15); // e.g. 'Monday'
                $table->time('start_time');
                $table->time('end_time');
                $table->timestamps();

                $table->foreign('subscription_id')
                      ->references('id')
                      ->on('pool_schema.subscriptions')
                      ->onDelete('cascade');
            });
        }

        // 3. Create/Update Attendance Logs (Members)
        if (!Schema::hasTable('pool_schema.access_logs')) {
             Schema::create('pool_schema.access_logs', function (Blueprint $table) {
                $table->id();
                $table->string('badge_uid', 50)->index();
                $table->unsignedInteger('member_id')->nullable();
                $table->timestamp('access_time')->useCurrent();
                $table->string('action_type', 20)->default('check_in'); // check_in, denied
                $table->string('location', 50)->nullable();
                $table->string('status', 20)->default('granted'); // granted, denied
                $table->string('denial_reason')->nullable();
                $table->timestamps();

                $table->foreign('member_id')
                      ->references('member_id')
                      ->on('pool_schema.members')
                      ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
