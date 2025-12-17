<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select("
    SELECT column_name, is_nullable, column_default 
    FROM information_schema.columns 
    WHERE table_schema = 'pool_schema' 
    AND table_name = 'staff'
");

foreach ($columns as $col) {
    echo "{$col->column_name} | Nullable: {$col->is_nullable} | Default: {$col->column_default}\n";
}
