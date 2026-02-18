<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tableName = $argv[1] ?? null;

if (!$tableName) {
    die("Usage: php check_table.php <table_name>\n");
}

$columns = Schema::getColumnListing($tableName);
print_r($columns);
