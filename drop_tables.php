<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Disable foreign key checks
DB::statement('SET session_replication_role = replica;');

$tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'pool_schema'");

foreach ($tables as $table) {
    DB::statement("DROP TABLE IF EXISTS pool_schema.{$table->tablename} CASCADE");
    echo "Dropped pool_schema.{$table->tablename}\n";
}

// Re-enable foreign key checks
DB::statement('SET session_replication_role = origin;');
