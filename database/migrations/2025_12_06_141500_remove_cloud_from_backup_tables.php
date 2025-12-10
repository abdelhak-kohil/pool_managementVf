<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pool_schema.backup_settings', function (Blueprint $table) {
            $table->dropColumn('cloud_bucket');
        });
    }

    public function down()
    {
        Schema::table('pool_schema.backup_settings', function (Blueprint $table) {
            $table->string('cloud_bucket')->nullable();
        });
    }
};
