<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\HR\Actions\CreateStaffAction;
use App\Modules\HR\Actions\UpdateStaffAction;
use App\Modules\HR\DTOs\StaffData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffActionTest extends TestCase
{
     use \Illuminate\Foundation\Testing\DatabaseTransactions;

     protected $roleId;

     protected function setUp(): void
     {
         parent::setUp();
         
         // Use any existing role to avoid sequence/constraint issues
         $role = DB::table('pool_schema.roles')->first();
         
         if ($role) {
             $this->roleId = $role->role_id;
         } else {
             // Fallback only if table is empty
             $this->roleId = DB::table('pool_schema.roles')->insertGetId(['role_name' => 'Tester'], 'role_id');
         }
     }

     public function test_can_create_staff_with_badge()
     {
         // Cleanup potentially stuck data
         DB::table('pool_schema.access_badges')->where('badge_uid', 'BADGE_001')->delete();

         // 1. Create a free badge
         $badgeId = DB::table('pool_schema.access_badges')->insertGetId([
             'badge_uid' => 'BADGE_001',
             'status' => 'active',
             'issued_at' => now(),
             // 'created_at' => now(), // Column does not exist
         ], 'badge_id');

         $dto = new StaffData(
            first_name: 'Jane',
            last_name: 'Doe',
            username: 'janedoe',
            role_id: $this->roleId,
            is_active: true,
            password: 'pass',
            email: 'jane@doe.com',
            phone_number: null,
            specialty: null,
            hiring_date: null,
            salary_type: null,
            hourly_rate: null,
            notes: null
        );

        $action = new CreateStaffAction();
        $staffId = $action->execute($dto, 'BADGE_001');

        $this->assertDatabaseHas('pool_schema.access_badges', [
            'badge_id' => $badgeId,
            'staff_id' => $staffId,
            'status' => 'active'
        ]);
     }

     public function test_staff_creation_fails_if_badge_taken()
     {
         // 1. Create badge assigned to someone (member or staff)
         $otherStaff = DB::table('pool_schema.staff')->insertGetId(['first_name'=>'Other','last_name'=>'Guy','username'=>'other','password_hash'=>'x','role_id'=>$this->roleId], 'staff_id');
         
         DB::table('pool_schema.access_badges')->insert([
             'badge_uid' => 'TAKEN_BADGE',
             'staff_id' => $otherStaff,
             'status' => 'active'
         ]);

         $dto = new StaffData(
             first_name: 'Thief',
             last_name: 'Bad',
             username: 'thief',
             role_id: $this->roleId,
             is_active: true,
             password: 'pass',
             email: null, phone_number:null, specialty:null, hiring_date:null, salary_type:null, hourly_rate:null, notes:null
         );

         $this->expectException(ValidationException::class);
         
         $action = new CreateStaffAction();
         $action->execute($dto, 'TAKEN_BADGE');
     }

     public function test_can_update_staff_and_swap_badge()
     {
         DB::table('pool_schema.access_badges')->whereIn('badge_uid', ['BADGE_A', 'BADGE_B'])->delete();

         $staffId = DB::table('pool_schema.staff')->insertGetId([
             'first_name'=>'OldName', 'last_name'=>'S', 'username'=>'oldname', 'password_hash'=>'p', 'role_id'=>$this->roleId
         ], 'staff_id');

         // Give them Badge A
         $badgeA = DB::table('pool_schema.access_badges')->insertGetId(['badge_uid'=>'BADGE_A', 'staff_id'=>$staffId, 'status'=>'active'],'badge_id');
         
         // Create Badge B (Available)
         $badgeB = DB::table('pool_schema.access_badges')->insertGetId(['badge_uid'=>'BADGE_B', 'status'=>'active'],'badge_id');

         // Update: Change Name and Swap Badge A -> Badge B
         $dto = new StaffData(
            first_name: 'NewName',
            last_name: 'S',
            username: 'newname',
            role_id: $this->roleId,
            is_active: true,
            password: null, // No change
            email: null, phone_number:null, specialty:null, hiring_date:null, salary_type:null, hourly_rate:null, notes:null
         );

         $action = new UpdateStaffAction();
         $action->execute($staffId, $dto, 'BADGE_B');

         // Assertions
         $this->assertDatabaseHas('pool_schema.staff', ['staff_id'=>$staffId, 'first_name'=>'NewName', 'username'=>'newname']);
         
         // Badge A should be free/inactive
         $this->assertDatabaseHas('pool_schema.access_badges', ['badge_id'=>$badgeA, 'staff_id'=>null, 'status'=>'inactive']);
         
         // Badge B should be assigned
         $this->assertDatabaseHas('pool_schema.access_badges', ['badge_id'=>$badgeB, 'staff_id'=>$staffId, 'status'=>'active']);
     }
     public function test_can_create_staff_without_badge()
     {
        $dto = new StaffData(
            first_name: 'John',
            last_name: 'Staff',
            username: 'johnstaff',
            role_id: $this->roleId,
            is_active: true,
            password: 'password123',
            email: 'john@staff.com',
            phone_number: '123456',
            specialty: 'Lifeguard',
            hiring_date: null,
            salary_type: 'hourly',
            hourly_rate: 15.00,
            notes: null
        );

        $action = new CreateStaffAction();
        $staffId = $action->execute($dto, null);

        $this->assertDatabaseHas('pool_schema.staff', [
            'staff_id' => $staffId,
            'username' => 'johnstaff',
            'email' => 'john@staff.com'
        ]);
     }
}
