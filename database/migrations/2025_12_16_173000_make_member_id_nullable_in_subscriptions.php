<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE pool_schema.subscriptions ALTER COLUMN member_id DROP NOT NULL');
    }

    public function down()
    {
        // We cannot easily revert this without checking for nulls, so we generally leave it nullable or risk failure.
        // DB::statement('ALTER TABLE pool_schema.subscriptions ALTER COLUMN member_id SET NOT NULL');
    }
};
