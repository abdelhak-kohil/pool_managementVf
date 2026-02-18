<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pool_schema.partner_contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('partner_group_slot_id')->nullable();

            $table->foreign('subscription_id')->references('subscription_id')->on('pool_schema.subscriptions')->onDelete('cascade');
            // Assuming partner_group_slots exists and has 'id' as PK based on PartnerGroupController code
             $table->foreign('partner_group_slot_id')->references('id')->on('pool_schema.partner_group_slots')->onDelete('set null');
        });

        Schema::table('pool_schema.partner_group_attendance', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->foreign('contract_id')->references('contract_id')->on('pool_schema.partner_contracts')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('pool_schema.partner_group_attendance', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropColumn('contract_id');
        });

        Schema::table('pool_schema.partner_contracts', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['partner_group_slot_id']);
            $table->dropColumn(['subscription_id', 'partner_group_slot_id']);
        });
    }
};
