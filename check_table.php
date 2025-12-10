<?php
use Illuminate\Support\Facades\DB;

$exists = DB::select("SELECT * FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'staff'");
dump($exists);
