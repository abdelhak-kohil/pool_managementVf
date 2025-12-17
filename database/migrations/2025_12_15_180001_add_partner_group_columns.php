<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pool_schema.access_badges', function (Blueprint $table) {
            $table->integer('partner_group_id')->nullable()->index();
            $table->foreign('partner_group_id')->references('group_id')->on('pool_schema.partner_groups')->onDelete('cascade');
        });

        Schema::table('pool_schema.subscriptions', function (Blueprint $table) {
            $table->integer('partner_group_id')->nullable()->index();
            $table->foreign('partner_group_id')->references('group_id')->on('pool_schema.partner_groups')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('pool_schema.access_badges', function (Blueprint $table) {
            $table->dropForeign(['partner_group_id']);
            $table->dropColumn('partner_group_id');
        });

        Schema::table('pool_schema.subscriptions', function (Blueprint $table) {
            $table->dropForeign(['partner_group_id']);
            $table->dropColumn('partner_group_id');
        });
    }
};
