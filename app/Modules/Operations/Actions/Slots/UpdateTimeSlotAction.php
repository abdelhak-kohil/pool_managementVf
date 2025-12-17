<?php

namespace App\Modules\Operations\Actions\Slots;

use App\Modules\Operations\DTOs\TimeSlotData;
use Illuminate\Support\Facades\DB;

class UpdateTimeSlotAction
{
    public function execute(int $slotId, TimeSlotData $data): void
    {
        DB::table('pool_schema.time_slots')
            ->where('slot_id', $slotId)
            ->update([
                'weekday_id' => $data->weekday_id,
                'start_time' => $data->start_time,
                'end_time'   => $data->end_time,
                // Note: Updating activity_id usually not allowed in simple drag-drop, but we can allow it
                // 'activity_id'=> $data->activity_id, 
            ]);
    }
}
