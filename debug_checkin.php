<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Member\AccessBadge;
use App\Models\Member\Member;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$start = microtime(true);

$badgeUid = 'BDG000002';
$readerId = 'DEBUG_SCRIPT';

echo "Starting CheckIn Simulation for $badgeUid...\n";

try {
    // 1. Find Badge
    $badge = AccessBadge::where('badge_uid', $badgeUid)->first();
    if (!$badge) {
        die("Badge not found. Time: " . (microtime(true) - $start) . "s\n");
    }

    echo "Badge found. Staff: " . ($badge->staff_id ? 'Yes' : 'No') . ", Member: " . ($badge->member_id ? 'Yes' : 'No') . "\n";

    if ($badge->member_id) {
        $member = Member::with(['subscriptions.plan', 'subscriptions.weekdays'])->find($badge->member_id);
        if (!$member) die("Member not found.\n");
        
        echo "Member: {$member->first_name} {$member->last_name}\n";

        // Logic from Controller processMemberCheckIn
        $activeSub = DB::table('pool_schema.subscriptions as s')
            ->join('pool_schema.plans as p', 'p.plan_id', '=', 's.plan_id')
            ->join('pool_schema.activities as a', 'a.activity_id', '=', 's.activity_id')
            ->where('s.member_id', $member->member_id)
            ->where('s.status', 'active')
            ->whereDate('s.start_date', '<=', now())
            ->whereDate('s.end_date', '>=', now())
            ->select('s.*', 'p.plan_type', 'p.plan_name', 'a.activity_id')
            ->first();

        if (!$activeSub) echo "No Active Subscription found.\n";
        else echo "Active Subscription: {$activeSub->plan_name}\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$end = microtime(true);
echo "Total Execution Time: " . ($end - $start) . " seconds.\n";
