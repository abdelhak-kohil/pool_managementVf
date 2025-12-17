<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Access\Actions\ScanBadgeAction;
use Illuminate\Support\Facades\DB;
use App\Modules\Access\DTOs\AccessResult;

class AccessActionTest extends TestCase
{
     use \Illuminate\Foundation\Testing\DatabaseTransactions;

     protected $scanAction;

     protected function setUp(): void
     {
         parent::setUp();
         $this->scanAction = app(ScanBadgeAction::class);
     }

     public function test_member_can_check_in_valid_subscription()
     {
         // 1. Create Active Member + Sub + Badge
         $memberId = DB::table('pool_schema.members')->insertGetId(['first_name'=>'Valid','last_name'=>'Mem'], 'member_id');
         $planId = DB::table('pool_schema.plans')->insertGetId(['plan_name'=>'Unlimited', 'plan_type'=>'unlimited', 'is_active'=>true], 'plan_id');
         $activityId = DB::table('pool_schema.activities')->insertGetId(['name'=>'Gym', 'is_active'=>true], 'activity_id');
         
         DB::table('pool_schema.subscriptions')->insert([
             'member_id'=>$memberId, 'plan_id'=>$planId, 'activity_id'=>$activityId, 
             'start_date'=>now()->subDay(), 'end_date'=>now()->addDay(), 'status'=>'active'
         ]);

         DB::table('pool_schema.access_badges')->insert([
             'badge_uid'=>'BADGE_VALID', 'member_id'=>$memberId, 'status'=>'active'
         ]);

         // 2. Scan
         $result = $this->scanAction->execute('BADGE_VALID');

         $this->assertTrue($result->isGranted);
         $this->assertStringContainsString('Bienvenue', $result->message);
     }

     public function test_member_denied_expired_subscription()
     {
         $memberId = DB::table('pool_schema.members')->insertGetId(['first_name'=>'Expired','last_name'=>'Mem'], 'member_id');
         $planId = DB::table('pool_schema.plans')->insertGetId(['plan_name'=>'Unlimited', 'plan_type'=>'unlimited', 'is_active'=>true], 'plan_id');
         $activityId = DB::table('pool_schema.activities')->insertGetId(['name'=>'Gym', 'is_active'=>true], 'activity_id');
         
         DB::table('pool_schema.subscriptions')->insert([
             'member_id'=>$memberId, 'plan_id'=>$planId, 'activity_id'=>$activityId, 
             'start_date'=>now()->subMonth(), 'end_date'=>now()->subDay(), 'status'=>'active' // Expired date
         ]);

         DB::table('pool_schema.access_badges')->insert([
             'badge_uid'=>'BADGE_EXPIRED', 'member_id'=>$memberId, 'status'=>'active'
         ]);

         $result = $this->scanAction->execute('BADGE_EXPIRED');

         $this->assertFalse($result->isGranted);
         // "Aucun abonnement actif." or "Aucun abonnement actif ou valide" from CheckInMemberAction
         $this->assertStringContainsString('actif', $result->message); 
     }

     public function test_staff_check_in_maintenance_restriction()
     {
         // 1. Setup 'Entretien' slot for NOW
         $enDay = now()->format('l');
         $frDayMap = ['Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi','Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi','Sunday'=>'Dimanche'];
         $frDay = $frDayMap[$enDay];

         $dayId = DB::table('pool_schema.weekdays')->where('day_name', $frDay)->value('weekday_id');
         if (!$dayId) {
             $dayId = DB::table('pool_schema.weekdays')->insertGetId(['day_name'=>$frDay, 'day_number'=>1], 'weekday_id');
         }

         $actId = DB::table('pool_schema.activities')->insertGetId(['name'=>'Entretien', 'is_active'=>true], 'activity_id');

         $slotId = DB::table('pool_schema.time_slots')->insertGetId([
             'activity_id'=>$actId, 'weekday_id'=>$dayId, 
             'start_time'=>now()->subMinute()->format('H:i'), 
             'end_time'=>now()->addMinute()->format('H:i')
         ], 'slot_id');

         // 2. Regular Staff (Not Maintenance)
         // Provide defaults for salary_type, hourly_rate, and password_hash
         $staffId = DB::table('pool_schema.staff')->insertGetId([
             'first_name'=>'Regular','last_name'=>'Joe','username'=>'regular','role_id'=>null,
             'salary_type'=>'hourly', 'hourly_rate'=>0, 'password_hash'=>'pw', 'is_active'=>true
         ], 'staff_id');
         
         DB::table('pool_schema.access_badges')->insert(['badge_uid'=>'BADGE_REGULAR', 'staff_id'=>$staffId, 'status'=>'active']);

         $result = $this->scanAction->execute('BADGE_REGULAR');
         $this->assertFalse($result->isGranted);
         $this->assertStringContainsString('maintenance', $result->message);

         // 3. Maintenance Staff
         $roleId = DB::table('pool_schema.roles')->insertGetId(['role_name'=>'Maintenance'], 'role_id');
         $maintId = DB::table('pool_schema.staff')->insertGetId([
             'first_name'=>'Fix','last_name'=>'It','username'=>'fixer','role_id'=>$roleId, 
             'salary_type'=>'hourly', 'hourly_rate'=>0, 'password_hash'=>'pw', 'is_active'=>true
         ], 'staff_id');
         
         DB::table('pool_schema.access_badges')->insert(['badge_uid'=>'BADGE_MAINT', 'staff_id'=>$maintId, 'status'=>'active']);

         $resultM = $this->scanAction->execute('BADGE_MAINT');
         $this->assertTrue($resultM->isGranted);
     }
}
