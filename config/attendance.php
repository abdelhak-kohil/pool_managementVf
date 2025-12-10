<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Night Shift Hours
    |--------------------------------------------------------------------------
    |
    | Define the start and end times for the night shift.
    | Presence during this interval will be calculated as "Night Hours".
    | Format: 'H:i' (24-hour format)
    |
    */
    'night_start' => '21:00',
    'night_end' => '06:00',

    /*
    |--------------------------------------------------------------------------
    | Overtime Threshold
    |--------------------------------------------------------------------------
    |
    | Number of working hours after which overtime is calculated.
    |
    */
    'overtime_threshold' => 8,
];
