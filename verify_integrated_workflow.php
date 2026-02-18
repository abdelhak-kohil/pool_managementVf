<?php

use App\Models\Member\PartnerGroup;
use App\Models\Finance\Plan;
use App\Models\Activity\Activity;
use App\Models\Staff\Staff;
use Illuminate\Support\Facades\DB;
use App\Models\Finance\PricingContract;
use App\Models\Finance\Payment;
use App\Models\Member\PartnerGroupSlot;
use App\Models\Activity\TimeSlot;
use App\Models\Activity\Weekday;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Starting Integrated Workflow Verification ---\n";

// 1. Setup Data
DB::transaction(function () {
    // Reset sequences to avoid unique constraints
    if (DB::connection()->getDriverName() === 'pgsql') {
         DB::statement("SELECT setval(pg_get_serial_sequence('pool_schema.partner_groups', 'group_id'), coalesce(max(group_id), 0) + 1, false) FROM pool_schema.partner_groups;");
         // Corrected table name from contracts to partner_contracts
         DB::statement("SELECT setval(pg_get_serial_sequence('pool_schema.partner_contracts', 'contract_id'), coalesce(max(contract_id), 0) + 1, false) FROM pool_schema.partner_contracts;"); 
    }

    $staff = Staff::first();
    $activity = Activity::firstOrCreate(['name' => 'Aquagym Group Test']);

    // Create a "Pack" Plan
    $plan = Plan::create([
        'plan_name' => 'Group Pack 10',
        'description' => '10 Sessions for Groups',
        'plan_type' => 'pack', // Assuming we distinguish by type or name
        'visits_per_week' => 0, // Unlimited freq
        'duration_months' => 3,
        'is_active' => true
    ]);
    
    // Create Group
    $groupName = "Integrated Test Group " . uniqid();
    $group = PartnerGroup::create([
        'name' => $groupName,
        'contact_name' => 'John Doe',
        'contact_phone' => '0555000000',
        'email' => 'group_' . bin2hex(random_bytes(4)) . '@test.com',
        'address' => '123 Test St'
    ]);
    
    echo "1. Created Group: {$group->name} (ID: {$group->group_id})\n";

    // 2. Simulate Controller Logic (Store Contract)
    // We manually invoke the logic we put in the Controller
    
    $input = [
        'plan_id' => $plan->plan_id,
        'value' => 25000, // Total Price
        'start_date' => now()->toDateString(),
        'contract_type' => 'fixed_package',
        'total_sessions' => 10,
        'attendees_per_session' => 5,
        'activity_id' => $activity->activity_id
    ];
    
    echo "2. Simulating Contract Creation (Fixed Package, 10 Sessions, 25000 DA)...\n";

    // ... Copy of Controller Logic ...
    $startDate = \Carbon\Carbon::parse($input['start_date']);
    $endDate = $startDate->copy()->addMonths($plan->duration_months);

    // Create Subscription
    $subId = DB::table('pool_schema.subscriptions')->insertGetId([
        'partner_group_id' => $group->group_id,
        'plan_id' => $plan->plan_id,
        'activity_id' => $input['activity_id'],
        'start_date' => $startDate,
        'end_date' => $endDate,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ], 'subscription_id');
    
    echo "   -> Created Subscription ID: {$subId}\n";

    // Create Contract
    $contract = PricingContract::create([
        'partner_group_id' => $group->group_id,
        'contract_type' => $input['contract_type'],
        'value' => 0,
        'price' => $input['value'],
        'subscription_id' => $subId,
        'billing_frequency' => 'per_session',
        'total_sessions' => $input['total_sessions'],
        'remaining_sessions' => $input['total_sessions'],
        'attendees_per_session' => $input['attendees_per_session'],
        // 'package_price' => $input['value'], <-- REMOVED
        'activity_id' => $input['activity_id'],
        'plan_id' => $plan->plan_id,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'is_active' => true,
    ]);
    
    echo "   -> Created Contract ID: {$contract->contract_id}\n";
    
    // Create Payment
    if ($input['value'] > 0) {
        $paymentId = DB::table('pool_schema.payments')->insertGetId([
            'subscription_id' => $subId,
            'amount' => $input['value'],
            'payment_method' => 'cash',
            'payment_date' => now(),
            'notes' => "Auto Payment",
        ], 'payment_id');
        echo "   -> Created Payment ID: {$paymentId} for amount {$input['value']}\n";
    }

    // 3. Verify Links
    $fetchedContract = PricingContract::find($contract->contract_id);
    if ($fetchedContract->subscription_id !== $subId) {
        throw new Exception("Contract NOT linked to Subscription!");
    }
    
    // 4. Test Usage (Check-in)
    // Assign Slot
    $today = now()->dayOfWeekIso;
    $slot = TimeSlot::create([
        'activity_id' => $activity->activity_id,
        'day_of_week' => Weekday::find($today)->name ?? 'Monday',
        'weekday_id' => $today,
        'start_time' => '00:00:00',
        'end_time' => '23:59:59',
        'capacity' => 20,
        'gender' => 'mixed',
        'is_active' => true
    ]);
    
    $groupSlot = PartnerGroupSlot::create([
        'partner_group_id' => $group->group_id,
        'slot_id' => $slot->slot_id,
        'max_capacity' => 20
    ]);
    
    // Create Access Badge
    $badge = \App\Models\Member\AccessBadge::create([
        'badge_uid' => 'TEST_INT_' . uniqid(),
        'status' => 'active',
        'partner_group_id' => $group->group_id
    ]);

    echo "4. Simulating Check-in...\n";
    
    $action = app(\App\Modules\Access\Actions\CheckInGroupAction::class);
    $result = $action->execute($badge, $staff, 3); // 3 attendees
    
    if (!$result->allowed) {
        throw new Exception("Check-in Failed: " . $result->message);
    }
    
    echo "   -> Check-in Allowed: {$result->message}\n";
    
    // Verify Contract Decrement
    $fetchedContract->refresh();
    if ($fetchedContract->remaining_sessions !== 9) {
        throw new Exception("Contract sessions NOT decremented! Remaining: " . $fetchedContract->remaining_sessions);
    }
    echo "   -> Contract sessions decremented correctly (10 -> 9).\n";
    
    // Verify Attendance Log has Contract ID
    $log = \App\Models\Member\PartnerGroupAttendance::latest('attendance_id')->first();
    if ($log->contract_id !== $contract->contract_id) {
         throw new Exception("Attendance Log missing Contract ID! Log Contract: " . $log->contract_id);
    }
    echo "   -> Attendance Log linked to Contract ID {$log->contract_id}.\n";
    
    echo "--- Verification SUCCESS ---\n";
});
