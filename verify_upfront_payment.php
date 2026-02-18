<?php

use App\Models\Member\PartnerGroup;
use App\Models\Finance\Plan;
use Illuminate\Support\Facades\DB;
use App\Models\Finance\PricingContract;
use App\Models\Finance\Payment;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Starting Upfront Payment Verification ---\n";

DB::transaction(function () {
    // Setup Group
    $group = PartnerGroup::create(['name' => 'Payment Group ' . uniqid()]);
    $plan = Plan::firstOrCreate(['plan_name' => 'Generic Plan', 'visits_per_week' => 0], ['duration_months' => 12]);

    echo "Testing Contract Types for Automated Payment...\n";

    // Define scenarios: Type -> Price -> Expect Payment?
    $scenarios = [
        ['type' => 'fixed_package', 'value' => 10000, 'expect_payment' => true],
        ['type' => 'flat_fee', 'value' => 5000, 'expect_payment' => true],
        ['type' => 'fixed_price', 'value' => 200, 'expect_payment' => true],
        ['type' => 'discount', 'value' => 10, 'price' => 0, 'expect_payment' => false], // Free Discount
        ['type' => 'discount', 'value' => 20, 'price' => 5000, 'expect_payment' => true], // Paid Discount Contract
    ];

    $controller = app(\App\Http\Controllers\Member\PartnerGroupController::class);

    foreach ($scenarios as $s) {
        echo " -> Testing {$s['type']} with value {$s['value']}... ";
        
        $request = new \Illuminate\Http\Request();
        $request->replace([
            'contract_type' => $s['type'],
            'value' => $s['value'],
            'price' => $s['price'] ?? null, // Pass price if defined
            'plan_id' => $plan->plan_id,
            'start_date' => now()->toDateString(),
            // Specifics
            'total_sessions' => ($s['type'] === 'fixed_package' ? 10 : null),
            'attendees_per_session' => 1,
        ]);

        // Manually invoke storeContract (simulating request)
        // We can't easily invoke controller method directly due to Redirect return.
        // Instead we use the Refactored private logic by reflection OR just check DB after "storeContract" call via route?
        // Simpler: Reuse the Logic we know is there? No, verify the CODE running.
        // We will call the public method and catch the redirect exception or ignore return.
        
        try {
            // We need to mock validation or pass valid data
            $response = $controller->storeContract($request, $group->group_id);
            if ($response->getSession()->has('errors')) {
                 echo "Validation Errors: " . json_encode($response->getSession()->get('errors')->all()) . "\n";
            }
            if ($response->getSession()->has('error')) {
                 echo "Controller Error: " . $response->getSession()->get('error') . "\n";
            }
        } catch (\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }

        // Verify latest contract and payment
        $contract = PricingContract::where('partner_group_id', $group->group_id)->latest('contract_id')->first();
        $subscriptionId = $contract->subscription_id;
        
        $payment = DB::table('pool_schema.payments')->where('subscription_id', $subscriptionId)->first();

        if ($s['expect_payment']) {
            if (!$payment) {
                throw new Exception("FAILED: Expected Payment not found for {$s['type']}");
            }
            $expectedAmount = $s['price'] ?? $s['value'];
            if ($payment->amount != $expectedAmount) {
                 throw new Exception("FAILED: Payment amount mismatch. Expected {$expectedAmount}, got {$payment->amount}");
            }
            echo "OK (Payment ID: {$payment->payment_id})\n";
        } else {
            if ($payment) {
                 throw new Exception("FAILED: Unexpected Payment found for {$s['type']}");
            }
            echo "OK (No Payment)\n";
        }
    }
    
    echo "--- Verification SUCCESS ---\n";
});
