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
        Schema::table('pool_schema.access_badges', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_id')->nullable()->after('member_id');
            $table->foreign('staff_id')->references('staff_id')->on('pool_schema.staff')->onDelete('cascade');
        });

        Schema::table('pool_schema.access_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_id')->nullable()->after('member_id');
            $table->foreign('staff_id')->references('staff_id')->on('pool_schema.staff')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_schema.access_logs', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropColumn('staff_id');
        });

        Schema::table('pool_schema.access_badges', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropColumn('staff_id');
        });
    }
};
