<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\CRM\Actions\CreateMemberAction;
use App\Modules\CRM\Actions\UpdateMemberAction;
use App\Modules\CRM\Actions\DeleteMemberAction;
use App\Modules\CRM\DTOs\MemberData;
use App\Modules\Sales\DTOs\SubscriptionData;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MemberActionTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected $createAction;
    protected $updateAction;
    protected $deleteAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAction = app(CreateMemberAction::class);
        $this->updateAction = app(UpdateMemberAction::class);
        $this->deleteAction = app(DeleteMemberAction::class);
    }

    public function test_can_create_member_with_subscription_and_badge()
    {
        // Setup Dependencies
        $staffId = DB::table('pool_schema.staff')->insertGetId([
            'first_name'=>'Tester','last_name'=>'Bot','username'=>'testerbot','salary_type'=>'hourly','hourly_rate'=>0,'password_hash'=>'pw','is_active'=>true
        ], 'staff_id');
        
        $planId = DB::table('pool_schema.plans')->insertGetId(['plan_name'=>'Gold', 'plan_type'=>'monthly', 'is_active'=>true], 'plan_id');
        $activityId = DB::table('pool_schema.activities')->insertGetId(['name'=>'Swim', 'is_active'=>true], 'activity_id');
        
        // 1. Insert Price
        DB::table('pool_schema.activity_plan_prices')->insert([
            'plan_id' => $planId, 'activity_id' => $activityId, 'price' => 100.00
        ]);

        // 2. Insert Slot
        $weekdayId = DB::table('pool_schema.weekdays')->value('weekday_id');
        if (!$weekdayId) {
             // If table is empty, we can safely insert
             $weekdayId = DB::table('pool_schema.weekdays')->insertGetId(['day_name'=>'TestDay', 'day_number'=>1], 'weekday_id');
        }

        $slotId = DB::table('pool_schema.time_slots')->insertGetId([
            'activity_id'=>$activityId, 'weekday_id'=>$weekdayId, 'start_time'=>'08:00', 'end_time'=>'10:00', 'capacity'=>10
        ], 'slot_id');

        // Pre-create badge
        $badgeUid = 'BADGE_CRM_01';
        DB::table('pool_schema.access_badges')->insert(['badge_uid'=>$badgeUid, 'status'=>'active']);

        // Data
        $memberData = new MemberData(
            first_name: 'John',
            last_name: 'Doe',
            email: 'john@example.com',
            phone_number: '123456789',
            date_of_birth: Carbon::parse('1990-01-01'),
            address: '123 St',
            emergency_contact_name: 'Jane',
            emergency_contact_phone: '987',
            notes: null,
            health_conditions: null,
            badge_uid: $badgeUid,
            badge_status: 'active'
        );

        $subData = new SubscriptionData(
            member_id: 0, // Should be overwritten
            plan_id: $planId,
            activity_id: $activityId,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            status: 'active',
            slot_ids: [$slotId],
            amount: 100.00,
            payment_method: 'cash',
            staff_id: $staffId
        );

        // Execute
        $memberId = $this->createAction->execute($memberData, $subData, $staffId);

        // Assert Member
        $this->assertDatabaseHas('pool_schema.members', ['member_id' => $memberId, 'first_name' => 'John']);
        
        // Assert Badge
        $this->assertDatabaseHas('pool_schema.access_badges', ['badge_uid' => $badgeUid, 'member_id' => $memberId]);

        // Assert Subscription
        $this->assertDatabaseHas('pool_schema.subscriptions', ['member_id' => $memberId, 'plan_id' => $planId, 'status' => 'active']);
        
        // Assert Payment (created by CreateSubscriptionAction)
        $this->assertDatabaseHas('pool_schema.payments', ['amount' => 100.00]);
    }

    public function test_can_update_member_profile_and_reassign_badge()
    {
        // 1. Create Member
        $memberId = DB::table('pool_schema.members')->insertGetId(['first_name'=>'Old','last_name'=>'Name'], 'member_id');
        
        // 2. Old Badge
        $oldBadgeId = DB::table('pool_schema.access_badges')->insertGetId(['badge_uid'=>'BADGE_OLD', 'member_id'=>$memberId, 'status'=>'active'], 'badge_id');

        // 3. New Badge (unassigned)
        $newBadgeId = DB::table('pool_schema.access_badges')->insertGetId(['badge_uid'=>'BADGE_NEW', 'status'=>'active'], 'badge_id');

        // Update Data
        $memberData = new MemberData(
            first_name: 'New',
            last_name: 'Name',
            email: 'new@example.com',
            phone_number: '000',
            date_of_birth: null,
            address: null,
            emergency_contact_name: null,
            emergency_contact_phone: null,
            notes: 'Updated',
            health_conditions: null
        );

        // Execute
        $this->updateAction->execute($memberId, $memberData, $newBadgeId, [], null);

        // Assert
        $this->assertDatabaseHas('pool_schema.members', ['member_id' => $memberId, 'first_name' => 'New']);
        
        // Old Badge freed
        $this->assertDatabaseHas('pool_schema.access_badges', ['badge_id' => $oldBadgeId, 'member_id' => null]);
        
        // New Badge assigned
        $this->assertDatabaseHas('pool_schema.access_badges', ['badge_id' => $newBadgeId, 'member_id' => $memberId]);
    }

    public function test_delete_member_check()
    {
        $memberId = DB::table('pool_schema.members')->insertGetId(['first_name'=>'Del','last_name'=>'Test'], 'member_id');
        
        // 1. Success deletion (no subs, no history)
        $this->deleteAction->execute($memberId);
        $this->assertDatabaseMissing('pool_schema.members', ['member_id'=>$memberId]);

        // 2. Failure: Has Subscription
        $m2 = DB::table('pool_schema.members')->insertGetId(['first_name'=>'Del2','last_name'=>'Test'], 'member_id');
        $p = DB::table('pool_schema.plans')->insertGetId(['plan_name'=>'P','plan_type'=>'m', 'is_active'=>true], 'plan_id');
        $a = DB::table('pool_schema.activities')->insertGetId(['name'=>'A', 'is_active'=>true], 'activity_id');
        
        DB::table('pool_schema.subscriptions')->insert(['member_id'=>$m2, 'plan_id'=>$p, 'activity_id'=>$a, 'start_date'=>now(), 'end_date'=>now(), 'status'=>'active']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->deleteAction->execute($m2);
    }
}
