<?php

namespace App\Modules\Operations\Actions;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FetchCalendarEventsAction
{
    public function execute(): array
    {
        // Logic extracted from ScheduleController::events
        $slots = DB::table('pool_schema.time_slots as t')
            ->leftJoin('pool_schema.activities as a', 'a.activity_id', '=', 't.activity_id')
            ->leftJoin('pool_schema.weekdays as w', 'w.weekday_id', '=', 't.weekday_id')
            ->select(
                't.slot_id',
                't.weekday_id',
                't.start_time',
                't.end_time',
                'a.name as activity_name',
                'a.color_code',
                'w.day_name as weekday'
            )
            ->get();

        $events = [];
        foreach ($slots as $s) {
            $dayOffset = ($s->weekday_id - 1); // Monday=1
            $baseDate = Carbon::now()->startOfWeek()->addDays($dayOffset);

            $events[] = [
                'id' => $s->slot_id,
                'title' => $s->activity_name ?? 'Inconnu',
                'start' => $baseDate->format('Y-m-d') . 'T' . $s->start_time,
                'end'   => $baseDate->format('Y-m-d') . 'T' . $s->end_time,
                'backgroundColor' => $s->color_code ?? '#60a5fa',
                'borderColor' => $s->color_code ?? '#60a5fa',
                'extendedProps' => [
                    'weekday' => $s->weekday
                ]
            ];
        }

        return $events;
    }
}
