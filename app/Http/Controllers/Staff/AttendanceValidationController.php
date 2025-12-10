<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceValidationController extends Controller
{
    /**
     * Display a listing of pending attendances.
     */
    public function index()
    {
        $pendingAttendances = Attendance::with('staff')
            ->pending()
            ->orderBy('date', 'desc')
            ->get();

        return view('staff.attendance.validation', compact('pendingAttendances'));
    }

    /**
     * Validate (approve) an attendance record.
     */
    public function validateAttendance($id)
    {
        $attendance = Attendance::findOrFail($id);
        $oldData = $attendance->toArray();
        
        $attendance->update([
            'validation_status' => 'validated',
            'validation_date' => Carbon::now(),
            'validated_by' => auth()->id(),
        ]);

        $this->logAudit($attendance, 'VALIDATE', $oldData);

        return redirect()->back()->with('success', 'Pointage validé avec succès.');
    }

    /**
     * Reject an attendance record.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $attendance = Attendance::findOrFail($id);
        $oldData = $attendance->toArray();

        $attendance->update([
            'validation_status' => 'rejected',
            'validation_date' => Carbon::now(),
            'validated_by' => auth()->id(),
            'admin_comments' => $request->reason,
        ]);

        $this->logAudit($attendance, 'REJECT', $oldData);

        return redirect()->back()->with('success', 'Pointage rejeté.');
    }

    /**
     * Correct and validate an attendance record.
     */
    public function correct(Request $request, $id)
    {
        $request->validate([
             'mid_check_in' => 'required|date_format:H:i',
             'mid_check_out' => 'required|date_format:H:i',
             'correction_reason' => 'required|string|max:255',
        ]);

        $attendance = Attendance::findOrFail($id);
        $oldData = $attendance->toArray();
        $date = $attendance->date->format('Y-m-d');

        $checkIn = Carbon::createFromFormat('Y-m-d H:i', "$date {$request->mid_check_in}");
        $checkOut = Carbon::createFromFormat('Y-m-d H:i', "$date {$request->mid_check_out}");
        
        $durationHours = $checkIn->diffInMinutes($checkOut) / 60;
        $workingHours = round($durationHours, 2);

        $attendance->update([
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'working_hours' => $workingHours,
            'validation_status' => 'corrected', 
            'validation_date' => Carbon::now(),
            'validated_by' => auth()->id(),
            'correction_reason' => $request->correction_reason,
        ]);

        $this->logAudit($attendance, 'CORRECT', $oldData);

        return redirect()->back()->with('success', 'Pointage corrigé et validé.');
    }

    private function logAudit($model, $action, $oldData = [])
    {
        // Simple manual logging to pool_schema.audit_log
        \Illuminate\Support\Facades\DB::table('pool_schema.audit_log')->insert([
            'table_name' => 'staff_attendance',
            'record_id' => $model->attendance_id,
            'action' => $action,
            'changed_by_staff_id' => auth()->id(), // assuming user id maps or is compatible, strictly might need staff_id mapping
            'change_timestamp' => Carbon::now(),
            'old_data_jsonb' => json_encode($oldData),
            'new_data_jsonb' => json_encode($model->fresh()->toArray()),
        ]);
    }
}
