<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Using raw SQL for precise control as requested, wrapped in Schema builder/Blueprint for consistency if preferred,
        // but since user provided SQL, I will map it to Blueprint to stay Laravel-idiomatic or use DB::statement.
        // Mapping to Blueprint is better for maintainability.

        Schema::create('pool_schema.partner_group_slots', function (Blueprint $table) {
            $table->id(); // BigIncrements 'id'
            $table->integer('partner_group_id');
            $table->integer('slot_id');
            $table->integer('max_capacity');

            $table->foreign('partner_group_id')
                  ->references('group_id')
                  ->on('pool_schema.partner_groups')
                  ->onDelete('cascade');
            
            $table->foreign('slot_id')
                  ->references('slot_id')
                  ->on('pool_schema.time_slots')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pool_schema.partner_group_slots');
    }
};
