<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Schema
        DB::statement('CREATE SCHEMA IF NOT EXISTS pool_schema');

        // 2. Create Permissions Table
        Schema::create('pool_schema.permissions', function (Blueprint $table) {
            $table->id('permission_id');
            $table->string('permission_name')->unique();
        });

        // 3. Create Roles Table
        Schema::create('pool_schema.roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role_name')->unique();
        });

        // 4. Create Role Permissions Pivot Table
        Schema::create('pool_schema.role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            
            $table->foreign('role_id')->references('role_id')->on('pool_schema.roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('permission_id')->on('pool_schema.permissions')->onDelete('cascade');
        });

        // 5. Create Staff Table
        Schema::create('pool_schema.staff', function (Blueprint $table) {
            $table->id('staff_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('username')->unique();
            $table->string('password_hash');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('role_id')->references('role_id')->on('pool_schema.roles')->nullOnDelete();
        });

        // 6. Create Weekdays Table
        Schema::create('pool_schema.weekdays', function (Blueprint $table) {
            $table->id('weekday_id');
            $table->string('day_name')->unique();
        });

        // 7. Create Activities Table
        Schema::create('pool_schema.activities', function (Blueprint $table) {
            $table->id('activity_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('access_type')->nullable();
            $table->string('color_code')->nullable();
            $table->boolean('is_active')->default(true);
        });

        // 8. Create Time Slots Table
        Schema::create('pool_schema.time_slots', function (Blueprint $table) {
            $table->id('slot_id');
            $table->unsignedBigInteger('weekday_id');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('activity_id')->nullable();
            $table->string('assigned_group')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->foreign('weekday_id')->references('weekday_id')->on('pool_schema.weekdays')->onDelete('cascade');
            $table->foreign('activity_id')->references('activity_id')->on('pool_schema.activities')->onDelete('set null');
            $table->foreign('created_by')->references('staff_id')->on('pool_schema.staff')->onDelete('set null');
        });

        // 9. Create Plans Table
        Schema::create('pool_schema.plans', function (Blueprint $table) {
            $table->id('plan_id');
            $table->string('plan_name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->string('plan_type')->nullable();
            $table->integer('visits_per_week')->nullable();
            $table->integer('duration_months')->nullable();
            $table->boolean('is_active')->default(true);
        });

        // 10. Create Members Table
        Schema::create('pool_schema.members', function (Blueprint $table) {
            $table->id('member_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('staff_id')->on('pool_schema.staff')->onDelete('set null');
            $table->foreign('updated_by')->references('staff_id')->on('pool_schema.staff')->onDelete('set null');
        });

        // 11. Create Subscriptions Table
        Schema::create('pool_schema.subscriptions', function (Blueprint $table) {
            $table->id('subscription_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('plan_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active');
            $table->dateTime('paused_at')->nullable();
            $table->date('resumes_at')->nullable();
            $table->integer('visits_per_week')->nullable();
            $table->unsignedBigInteger('deactivated_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('activity_id')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('member_id')->on('pool_schema.members')->onDelete('cascade');
            $table->foreign('plan_id')->references('plan_id')->on('pool_schema.plans')->onDelete('cascade');
            $table->foreign('deactivated_by')->references('staff_id')->on('pool_schema.staff')->onDelete('set null');
            $table->foreign('created_by')->references('staff_id')->on('pool_schema.staff')->onDelete('set null');
            $table->foreign('updated_by')->references('staff_id')->on('pool_schema.staff')->onDelete('set null');
            $table->foreign('activity_id')->references('activity_id')->on('pool_schema.activities')->onDelete('set null');
        });

        // 11.5 Create Payments Table
        Schema::create('pool_schema.payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('subscription_id');
            $table->decimal('amount', 8, 2);
            $table->dateTime('payment_date');
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('received_by_staff_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('subscription_id')->on('pool_schema.subscriptions')->onDelete('cascade');
            $table->foreign('received_by_staff_id')->references('staff_id')->on('pool_schema.staff')->onDelete('set null');
        });

        // 12. Create Access Badges Table
        Schema::create('pool_schema.access_badges', function (Blueprint $table) {
            $table->id('badge_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('badge_uid')->unique();
            $table->string('status')->default('active');
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            // staff_id added by later migration
            
            $table->foreign('member_id')->references('member_id')->on('pool_schema.members')->onDelete('cascade');
        });

        // 13. Create Access Logs Table
        Schema::create('pool_schema.access_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->string('badge_uid')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->dateTime('access_time');
            $table->string('access_decision'); // granted, denied
            $table->string('denial_reason')->nullable();
            // staff_id added by later migration
            // activity_id, subscription_id, slot_id added by later migration
            
            $table->foreign('member_id')->references('member_id')->on('pool_schema.members')->onDelete('cascade');
        });

        // 13. Create Pivot Tables
        Schema::create('pool_schema.subscription_allowed_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('weekday_id');
            
            $table->foreign('subscription_id')->references('subscription_id')->on('pool_schema.subscriptions')->onDelete('cascade');
            $table->foreign('weekday_id')->references('weekday_id')->on('pool_schema.weekdays')->onDelete('cascade');
        });

        Schema::create('pool_schema.activity_plan_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('plan_id');
            $table->decimal('price', 8, 2);
            
            $table->foreign('activity_id')->references('activity_id')->on('pool_schema.activities')->onDelete('cascade');
            $table->foreign('plan_id')->references('plan_id')->on('pool_schema.plans')->onDelete('cascade');
        });

        // 14. Create Shop Tables
        Schema::create('pool_schema.categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('pool_schema.products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->decimal('purchase_price', 8, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('alert_threshold')->default(0);
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('pool_schema.categories')->onDelete('cascade');
        });

        Schema::create('pool_schema.sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->decimal('total_amount', 8, 2);
            $table->string('payment_method');
            $table->timestamps();

            $table->foreign('staff_id')->references('staff_id')->on('pool_schema.staff')->onDelete('cascade');
            $table->foreign('member_id')->references('member_id')->on('pool_schema.members')->onDelete('set null');
        });

        Schema::create('pool_schema.sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->decimal('subtotal', 8, 2);
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('pool_schema.sales')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('pool_schema.products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_schema.sale_items');
        Schema::dropIfExists('pool_schema.sales');
        Schema::dropIfExists('pool_schema.products');
        Schema::dropIfExists('pool_schema.categories');
        Schema::dropIfExists('pool_schema.activity_plan_prices');
        Schema::dropIfExists('pool_schema.subscription_allowed_days');
        Schema::dropIfExists('pool_schema.access_logs');
        Schema::dropIfExists('pool_schema.access_badges');
        Schema::dropIfExists('pool_schema.access_badges');
        Schema::dropIfExists('pool_schema.payments');
        Schema::dropIfExists('pool_schema.subscriptions');
        Schema::dropIfExists('pool_schema.members');
        Schema::dropIfExists('pool_schema.plans');
        Schema::dropIfExists('pool_schema.time_slots');
        Schema::dropIfExists('pool_schema.activities');
        Schema::dropIfExists('pool_schema.weekdays');
        Schema::dropIfExists('pool_schema.staff');
        Schema::dropIfExists('pool_schema.role_permissions');
        Schema::dropIfExists('pool_schema.roles');
        Schema::dropIfExists('pool_schema.permissions');
        DB::statement('DROP SCHEMA IF EXISTS pool_schema CASCADE');
    }
};
