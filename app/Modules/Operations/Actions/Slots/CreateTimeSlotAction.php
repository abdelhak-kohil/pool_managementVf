<?php

namespace App\Modules\Operations\Actions\Slots;

use App\Modules\Operations\DTOs\TimeSlotData;
use Illuminate\Support\Facades\DB;

class CreateTimeSlotAction
{
    public function execute(TimeSlotData $data, int $staffId): int
    {
        return DB::table('pool_schema.time_slots')->insertGetId([
            'weekday_id' => $data->weekday_id,
            'start_time' => $data->start_time,
            'end_time'   => $data->end_time,
            'activity_id'=> $data->activity_id,
            'notes'      => $data->notes,
            'created_by' => $staffId,
        ], 'slot_id');
    }
}
