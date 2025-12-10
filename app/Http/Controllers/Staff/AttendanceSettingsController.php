<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AttendanceSettingsController extends Controller
{
    /**
     * Show the settings form.
     */
    public function edit()
    {
        $settings = [
            'night_start' => Setting::get('attendance.night_start', '21:00'),
            'night_end' => Setting::get('attendance.night_end', '06:00'),
            'overtime_threshold' => Setting::get('attendance.overtime_threshold', 8),
        ];

        return view('staff.attendance.settings', compact('settings'));
    }

    /**
     * Update the settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'night_start' => 'required|date_format:H:i',
            'night_end' => 'required|date_format:H:i',
            'overtime_threshold' => 'required|numeric|min:0|max:24',
        ]);

        Setting::set('attendance.night_start', $validated['night_start']);
        Setting::set('attendance.night_end', $validated['night_end']);
        Setting::set('attendance.overtime_threshold', $validated['overtime_threshold']);

        return redirect()->route('staff.hr.settings.edit')->with('success', 'Paramètres mis à jour avec succès.');
    }
}
