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
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pool_schema.pool_water_tests ALTER COLUMN chlorine_free TYPE NUMERIC(6,2)');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pool_schema.pool_water_tests ALTER COLUMN chlorine_total TYPE NUMERIC(6,2)');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pool_schema.pool_water_tests ALTER COLUMN ph TYPE NUMERIC(6,2)');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pool_schema.pool_water_tests ALTER COLUMN bromine TYPE NUMERIC(6,2)');
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pool_schema.pool_water_tests ALTER COLUMN chlorine_free TYPE NUMERIC(4,2)');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pool_schema.pool_water_tests ALTER COLUMN chlorine_total TYPE NUMERIC(4,2)');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pool_schema.pool_water_tests ALTER COLUMN ph TYPE NUMERIC(4,2)');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pool_schema.pool_water_tests ALTER COLUMN bromine TYPE NUMERIC(4,2)');
    }
};
