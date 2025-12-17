<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Sales\Actions\AddPaymentAction;
use App\Modules\Sales\Actions\UpdateSubscriptionAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SubscriptionActionTest extends TestCase
{
     use \Illuminate\Foundation\Testing\DatabaseTransactions;

     protected $staffId;
     protected $memberId;
     protected $planId;
     protected $activityId;
     protected $subscriptionId;
     protected $price = 5000.00;

     protected function setUp(): void
     {
         parent::setUp();
         
         // 1. Setup Role & Staff
         $role = \App\Models\Admin\Role::firstOrCreate(['role_name' => 'Tester']);
         
         $this->staffId = DB::table('pool_schema.staff')->insertGetId([
            'first_name' => 'Sales', 'last_name' => 'Rep', 'username' => 'salesrep',
            'role_id' => $role->role_id, 'is_active' => true,
            'password_hash' => 'pw', 'salary_type' => 'hourly', 'hourly_rate' => 0
         ], 'staff_id');

         // 2. Setup Member
         $this->memberId = DB::table('pool_schema.members')->insertGetId([
             'first_name' => 'Sub', 'last_name' => 'Scriber', 'email' => 'sub@test.com',
             'created_at' => now(), 'created_by' => $this->staffId
         ], 'member_id');

         // 3. Setup Activity & Plan
         $this->activityId = DB::table('pool_schema.activities')->insertGetId(['name' => 'Swimming', 'is_active'=>true], 'activity_id');
         $this->planId = DB::table('pool_schema.plans')->insertGetId(['plan_name' => 'Monthly Swim', 'plan_type'=>'monthly_weekly', 'visits_per_week'=>3, 'is_active'=>true], 'plan_id');

         // 4. Setup Price
         DB::table('pool_schema.activity_plan_prices')->insert([
             'activity_id' => $this->activityId,
             'plan_id' => $this->planId,
             'price' => $this->price
         ]);

         // 5. Setup Subscription
         $this->subscriptionId = DB::table('pool_schema.subscriptions')->insertGetId([
             'member_id' => $this->memberId,
             'activity_id' => $this->activityId,
             'plan_id' => $this->planId,
             'start_date' => now(),
             'end_date' => now()->addMonth(),
             'status' => 'active',
             'visits_per_week' => 3,
             'created_by' => $this->staffId,
             'created_at' => now()
         ], 'subscription_id');
     }

     public function test_can_add_payment_valid_amount()
     {
         $action = new AddPaymentAction();
         // Pay partial
         $result = $action->execute($this->subscriptionId, 2000.00, 'cash', 'Partial Payment', $this->staffId);

         $this->assertTrue($result['success']);
         $this->assertEquals('2,000.00', $result['summary']['totalPaid']);
         $this->assertEquals('3,000.00', $result['summary']['remaining']);

         $this->assertDatabaseHas('pool_schema.payments', [
             'subscription_id' => $this->subscriptionId,
             'amount' => 2000.00,
             'payment_method' => 'cash'
         ]);
     }

     public function test_cannot_overpay_subscription()
     {
         $action = new AddPaymentAction();
         
         $this->expectException(ValidationException::class);
         // Try to pay 6000 when price is 5000
         $action->execute($this->subscriptionId, 6000.00, 'card', 'Too much', $this->staffId);
     }

     public function test_can_update_subscription_details()
     {
         // Get or Create 3 distinct weekdays. assuming order by id gives distinct usually
         $days = DB::table('pool_schema.weekdays')->orderBy('weekday_id')->limit(3)->get();
         
         if ($days->count() < 3) {
            // Need to create some
            $needed = 3 - $days->count();
            for ($i=0; $i<$needed; $i++) {
                try {
                    DB::table('pool_schema.weekdays')->insert([
                        'day_name' => 'Day'.$i, 
                        'day_number' => 10+$i
                    ]);
                } catch (\Throwable $e) {} // Ignore if exists
            }
            $days = DB::table('pool_schema.weekdays')->orderBy('weekday_id')->limit(3)->get();
         }

         $day1 = $days[0]->weekday_id;
         $day2 = $days[1]->weekday_id;
         $day3 = $days[2]->weekday_id;

         $slotId1 = DB::table('pool_schema.time_slots')->insertGetId(['activity_id'=>$this->activityId, 'weekday_id'=>$day1, 'start_time'=>'10:00', 'end_time'=>'11:00'], 'slot_id');
         $slotId2 = DB::table('pool_schema.time_slots')->insertGetId(['activity_id'=>$this->activityId, 'weekday_id'=>$day2, 'start_time'=>'11:00', 'end_time'=>'12:00'], 'slot_id');
         $slotId3 = DB::table('pool_schema.time_slots')->insertGetId(['activity_id'=>$this->activityId, 'weekday_id'=>$day3, 'start_time'=>'12:00', 'end_time'=>'13:00'], 'slot_id');

         $payload = [
             'member_id' => $this->memberId, // Usually not changed but passed in DTO if used, here array
             'activity_id' => $this->activityId,
             'plan_id' => $this->planId,
             'start_date' => now()->addDays(1)->toDateString(),
             'end_date' => now()->addDays(31)->toDateString(),
             'status' => 'paused',
             'slot_ids' => [$slotId1, $slotId2, $slotId3], // Needs 3 slots as per plan
             'new_payment_amount' => 0
         ];

         $action = new UpdateSubscriptionAction();
         
         $action->execute($this->subscriptionId, $payload, $this->staffId);

         $this->assertDatabaseHas('pool_schema.subscriptions', [
             'subscription_id' => $this->subscriptionId,
             'status' => 'paused',
         ]);

         // Check slots
         $this->assertDatabaseHas('pool_schema.subscription_slots', ['subscription_id'=>$this->subscriptionId, 'slot_id'=>$slotId1]);
     }
}
