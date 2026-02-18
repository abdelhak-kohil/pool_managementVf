<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Add contract_id to partner_group_slots (Contract -> hasMany Slots)
        Schema::table('pool_schema.partner_group_slots', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable()->after('partner_group_id');
            $table->foreign('contract_id')->references('contract_id')->on('pool_schema.partner_contracts')->onDelete('cascade');
        });

        // 2. Remove partner_group_slot_id from partner_contracts (Fixing wrong direction)
        Schema::table('pool_schema.partner_contracts', function (Blueprint $table) {
            $table->dropForeign(['partner_group_slot_id']);
            $table->dropColumn('partner_group_slot_id');
        });
    }

    public function down()
    {
        Schema::table('pool_schema.partner_contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('partner_group_slot_id')->nullable();
            $table->foreign('partner_group_slot_id')->references('id')->on('pool_schema.partner_group_slots')->onDelete('set null');
        });

        Schema::table('pool_schema.partner_group_slots', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropColumn('contract_id');
        });
    }
};
