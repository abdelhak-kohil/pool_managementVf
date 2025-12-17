<?php

namespace App\Modules\Operations\DTOs;

use Illuminate\Http\Request;
use Carbon\Carbon;

class TimeSlotData
{
    public function __construct(
        public int $activity_id,
        public int $weekday_id, // 1=Monday
        public string $start_time,
        public string $end_time,
        public ?string $notes = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            activity_id: (int) $request->input('activity_id'),
            weekday_id: (int) $request->input('weekday_id'), // Or 'weekday' input depending on front-end
            start_time: Carbon::parse($request->input('start'))->format('H:i:s'),
            end_time: Carbon::parse($request->input('end'))->format('H:i:s'),
            notes: $request->input('notes')
        );
    }
}
