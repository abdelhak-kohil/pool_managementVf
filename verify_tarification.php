<?php

use Illuminate\Support\Facades\DB;
use App\Models\Member\PartnerGroup;
use App\Models\Finance\PricingContract;
use App\Models\Activity\Activity;
use App\Models\Finance\Plan;
use App\Models\Finance\Subscription;
use App\Services\Pricing\PricingCalculator;
use App\Modules\Finance\Actions\GenerateGroupInvoiceAction;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Starting Tarification Verification ---\n";

DB::beginTransaction();

try {
    // 1. Setup Data
    echo "[1] Creating Test Data...\n";
    $random = \Illuminate\Support\Str::random(10);
    echo "    [1.1] Fetching or Creating Activity...\n";
    $activity = Activity::first();
    if (!$activity) {
        $activity = Activity::create(['name' => 'Test Activity ' . $random, 'type' => 'pool']);
    } else {
        echo "    Using existing activity: {$activity->name} (#{$activity->activity_id})\n";
    }
    echo "    [1.2] Creating Plan (Test Plan {$random})...\n";
    $planId = random_int(100000, 999999);
    $plan = Plan::forceCreate(['plan_id' => $planId, 'plan_name' => 'Test Plan ' . $random, 'plan_type' => 'prepaid']);
    // Attach activity to plan with base price 1000
    // Pivot attach is fine
    $plan->activities()->attach($activity->activity_id, ['price' => 1000.00]);

    echo "    [1.3] Creating Group (Test Group {$random})...\n";
    $groupId = random_int(100000, 999999);
    $group = PartnerGroup::forceCreate([
        'group_id' => $groupId,
        'name' => 'Test Group ' . $random,
        'contact_name' => 'Test Contact',
        'email' => 'test_' . $random . '@example.com',
        'contact_phone' => '1234567890',
        // 'address' => 'Test Address' // Removed as column doesn't exist
    ]);
    echo "    [1.4] Creating Subscription...\n";

    $subId = random_int(100000, 999999);
    $subscription = Subscription::forceCreate([
        'subscription_id' => $subId,
        'member_id' => null, // Set to null explicitly
        'partner_group_id' => $group->group_id,
        'plan_id' => $plan->plan_id,
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'status' => 'active'
    ]);

    echo "    Group: {$group->name}\n";
    echo "    Plan Base Price: 1000.00\n";

    // 2. Test Pricing Calculator (No Contract)
    echo "\n[2] Testing Base Price (No Contract)...\n";
    $calculator = new PricingCalculator();
    $result = $calculator->calculate($group, $activity, $plan, 5);
    
    // 5 * 1000 = 5000? No, base price is unit price. Result 'original_price' is unit.
    // calculate() returns unit prices usually? Let's check logic.
    // logic: $basePrice = pivot->price.
    // result = $basePrice.
    // Wait, calculate() signature: calculate(..., int $attendeeCount = 1)
    // inside: $result = $contract->calculatePrice($basePrice, $attendeeCount);
    // If no contract: finalPrice = basePrice.
    // So usually calculate() returns UNIT price? 
    // PricingCalculator::calculate returns ['final_price' => ..., 'attendee_count' => ...].
    // If per_head contract, calculatePrice returns total?
    // Let's check PricingContract::calculatePrice logic.
    // case 'per_head': $finalPrice = $rate * $billableCount;
    // So it returns TOTAL price for the group.
    
    // If NO contract:
    // implementation: $finalPrice = $basePrice.
    // This is WRONG if basePrice is PER PERSON.
    // Current PricingCalculator implementation: 
    // "No Contract" -> $finalPrice = $basePrice.
    // Usually Subscription plans are Fixed Price access or Per Visit?
    // If it's a "Standard Group Plan", usually there IS a contract.
    // If no contract, default behavior is... ambiguous.
    // But let's verify what happens.
    
    if ($result['final_price'] == 1000.00) {
        echo "    SUCCESS: Base price correct (1000.00)\n";
    } else {
        echo "    FAILURE: Base price mismatch. Got {$result['final_price']}\n";
    }

    // 3. Test Per-Head Contract
    echo "\n[3] Testing Per-Head Contract...\n";
    $contract = PricingContract::create([
        'partner_group_id' => $group->group_id,
        'contract_type' => 'per_head',
        'value' => 0.00, // Required by DB
        'per_head_rate' => 200.00,
        'is_active' => true,
        'start_date' => now()->subDays(5)
    ]);
    echo "    Contract created: Per Head 200 DA\n";

    $result = $calculator->calculate($group, $activity, $plan, 5); // 5 people
    // Expected: 5 * 200 = 1000
    if ($result['final_price'] == 1000.00) {
        echo "    SUCCESS: Per-Head calculation correct (5 * 200 = 1000)\n";
    } else {
        echo "    FAILURE: Per-Head mismatch. Expected 1000, Got {$result['final_price']}\n";
    }

    // 4. Test Invoice Generation
    echo "\n[4] Testing Invoice Generation...\n";
    
    // Create Mock Staff
    $staffId = random_int(100000, 999999);
    \App\Models\Staff\Staff::forceCreate([
        'staff_id' => $staffId,
        'first_name' => 'Test',
        'last_name' => 'Staff',
        'username' => 'teststaff_' . $random,
        'password_hash' => 'hash'
    ]);
    
    // Create Mock Badge
    $badgeId = random_int(100000, 999999);
    \App\Models\Member\AccessBadge::forceCreate([
        'badge_id' => $badgeId,
        'badge_uid' => 'BADGE_' . $random,
        'status' => 'active'
    ]);

    // Create TimeSlot
    $slotId = random_int(100000, 999999);
    $weekday = \App\Models\Activity\Weekday::first();
    if (!$weekday) {
        $weekday = \App\Models\Activity\Weekday::forceCreate(['weekday_id' => 1, 'day_name' => 'Dimanche']);
    }

    \App\Models\Activity\TimeSlot::forceCreate([
        'slot_id' => $slotId,
        'weekday_id' => $weekday->weekday_id,
        'start_time' => '08:00',
        'end_time' => '10:00',
        'activity_id' => $activity->activity_id,
        'created_by' => $staffId // Optional
    ]);

    // Create Test Attendance
    echo "    Creating Attendance...\n";
    $attendance = \App\Models\Member\PartnerGroupAttendance::forceCreate([
        'partner_group_id' => $group->group_id,
        'badge_id' => $badgeId,
        'slot_id' => $slotId, // Link to TimeSlot
        'staff_id' => $staffId,
        'attendee_count' => 5,
        'access_time' => now(), // Today
        'access_decision' => 'granted'
    ]);
    
    echo "    Attendance created for 5 people.\n";

    // Generate Invoice
    $action = new GenerateGroupInvoiceAction($calculator);
    $invoice = $action->execute($group, now()->startOfMonth(), now()->endOfMonth());

    echo "    Invoice #{$invoice->invoice_number} generated.\n";
    echo "    Total Amount: {$invoice->total_amount}\n";

    if ($invoice->total_amount == 1000.00) {
        echo "    SUCCESS: Invoice total correct (1000.00)\n";
    } else {
        echo "    FAILURE: Invoice total mismatch. Expected 1000. Put {$invoice->total_amount}\n";
    }
    
    echo "\n--- Verification Complete ---\n";

} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    file_put_contents('verify_error.log', $e->__toString());
    echo $e->getTraceAsString();
} finally {
    echo "\n[Rollback] Cleaning up test data...\n";
    DB::rollBack();
}
