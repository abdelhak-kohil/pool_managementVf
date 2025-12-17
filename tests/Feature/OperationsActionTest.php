<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Operations\Actions\Slots\CreateTimeSlotAction;
use App\Modules\Operations\Actions\Reservations\CreateReservationAction;
use App\Modules\Operations\DTOs\TimeSlotData;
use App\Modules\Operations\DTOs\ReservationData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class OperationsActionTest extends TestCase
{
    use DatabaseTransactions;

    protected $createSlot;
    protected $createRes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSlot = app(CreateTimeSlotAction::class);
        $this->createRes = app(CreateReservationAction::class);
    }

    public function test_can_create_slot_and_reservation()
    {
        // 1. Create Activity
        $activityId = DB::table('pool_schema.activities')->insertGetId(['name'=>'AquaGym_Ops','is_active'=>true], 'activity_id');
        
        // 2. Create Slot
        $weekdayId = DB::table('pool_schema.weekdays')->value('weekday_id'); 
        if(!$weekdayId) $weekdayId = DB::table('pool_schema.weekdays')->insertGetId(['day_name'=>'Mon','day_number'=>1],'weekday_id');

        $slotData = new TimeSlotData(
            activity_id: $activityId,
            weekday_id: $weekdayId,
            start_time: '14:00:00',
            end_time: '15:00:00'
        );
        $slotId = $this->createSlot->execute($slotData, 1);
        
        $this->assertDatabaseHas('pool_schema.time_slots', ['slot_id'=>$slotId]);

        // 3. Create Reservation
        $memberId = DB::table('pool_schema.members')->insertGetId(['first_name'=>'Ops','last_name'=>'User'], 'member_id');

        $resData = new ReservationData(
            slot_id: $slotId,
            reservation_type: 'member_private',
            member_id: $memberId,
            partner_group_id: null,
            notes: 'Test Reservation'
        );

        $resId = $this->createRes->execute($resData);

        $this->assertDatabaseHas('pool_schema.reservations', ['reservation_id'=>$resId, 'status'=>'confirmed']);
    }

    public function test_reservation_blocked_for_maintenance()
    {
        // 1. Create Maintenance Activity
        $maintId = DB::table('pool_schema.activities')->insertGetId(['name'=>'Entretien','is_active'=>true], 'activity_id');
        
        // 2. Create Slot
        $weekdayId = DB::table('pool_schema.weekdays')->first()->weekday_id;
        $slotData = new TimeSlotData($maintId, $weekdayId, '00:00', '01:00');
        $slotId = $this->createSlot->execute($slotData, 1);

        // 3. Try Reservation
        $resData = new ReservationData($slotId, 'member_private', 1, null, null);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->createRes->execute($resData);
    }
}
