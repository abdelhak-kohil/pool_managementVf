<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Staff;
use App\Models\Staff\StaffLeave;
use App\Models\Staff\StaffSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffPlanningController extends Controller
{
    /**
     * Display the planning dashboard (Calendar).
     */
    public function index()
    {
        // Fetch all staff for the filter
        $staffMembers = Staff::where('is_active', true)->get();
        
        // We will load events via AJAX or pass initial data
        return view('staff.planning.index', compact('staffMembers'));
    }

    /**
     * Fetch events for FullCalendar.
     */
    public function events(Request $request)
    {
        // FullCalendar sends ISO8601 strings (e.g. 2023-11-27T00:00:00+01:00)
        // We need to convert them to Y-m-d for the database query
        $start = \Carbon\Carbon::parse($request->query('start'))->format('Y-m-d');
        $end = \Carbon\Carbon::parse($request->query('end'))->format('Y-m-d');
        $staffId = $request->query('staff_id');

        $search = $request->query('search');
        $type = $request->query('type');

        // --- SCHEDULES ---
        $query = StaffSchedule::with('staff')
            ->whereBetween('date', [$start, $end]);

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        if ($search) {
            $query->whereHas('staff', function($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%");
            });
        }

        if ($type) {
            if (in_array($type, ['work', 'meeting', 'training'])) {
                $query->where('type', $type);
            } elseif (in_array($type, ['vacation', 'sick', 'absence'])) {
                // If filtering by leave type, we want NO schedules
                $query->whereRaw('1 = 0'); 
            }
        }

        $schedules = $query->get();

        // --- LEAVES ---
        $leavesQuery = StaffLeave::with('staff')
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function($sub) use ($start, $end) {
                      $sub->where('start_date', '<', $start)
                          ->where('end_date', '>', $end);
                  });
            });

        if ($staffId) {
            $leavesQuery->where('staff_id', $staffId);
        }

        if ($search) {
            $leavesQuery->whereHas('staff', function($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%");
            });
        }

        if ($type) {
            if (in_array($type, ['vacation', 'sick', 'absence'])) {
                $leavesQuery->where('type', $type);
            } elseif (in_array($type, ['work', 'meeting', 'training'])) {
                // If filtering by schedule type, we want NO leaves
                $leavesQuery->whereRaw('1 = 0');
            }
        }

        $leaves = $leavesQuery->get();

        // Map Schedules
        $scheduleEvents = $schedules->map(function ($schedule) {
            return [
                'id' => 'schedule-' . $schedule->id, // Prefix ID to avoid collision
                'resourceId' => $schedule->staff_id,
                'title' => $schedule->staff->full_name,
                'start' => $schedule->date->format('Y-m-d') . 'T' . $schedule->start_time,
                'end' => $schedule->date->format('Y-m-d') . 'T' . $schedule->end_time,
                'color' => $this->getColorForType($schedule->type),
                'extendedProps' => [
                    'notes' => $schedule->notes,
                    'type' => $schedule->type,
                    'is_leave' => false
                ]
            ];
        });

        // Map Leaves
        $leaveEvents = $leaves->map(function ($leave) {
            return [
                'id' => 'leave-' . $leave->id,
                'resourceId' => $leave->staff_id,
                'title' => $leave->staff->full_name . ' (Congé)',
                'start' => $leave->start_date->format('Y-m-d'),
                'end' => $leave->end_date->copy()->addDay()->format('Y-m-d'), // Exclusive end date
                'allDay' => true,
                'color' => $this->getColorForLeaveType($leave->type),
                'extendedProps' => [
                    'notes' => $leave->reason,
                    'type' => $leave->type,
                    'status' => $leave->status,
                    'is_leave' => true
                ]
            ];
        });

        return response()->json($scheduleEvents->concat($leaveEvents));
    }


    private function getColorForLeaveType($type)
    {
        return match ($type) {
            'vacation' => '#8b5cf6', // purple
            'sick' => '#ef4444', // red
            'absence' => '#f97316', // orange
            default => '#6b7280', // gray
        };
    }

    private function getColorForType($type)
    {
        return match ($type) {
            'work' => '#3b82f6', // blue
            'meeting' => '#f59e0b', // orange
            'training' => '#10b981', // green
            default => '#6b7280', // gray
        };
    }

    /**
     * Store a new schedule.
     */
    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,staff_id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'type' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        StaffSchedule::create($validated);

        return response()->json(['success' => true, 'message' => 'Créneau ajouté.']);
    }

    /**
     * Delete a schedule.
     */
    public function destroySchedule($id)
    {
        $schedule = StaffSchedule::findOrFail($id);
        $schedule->delete();

        return response()->json(['success' => true, 'message' => 'Créneau supprimé.']);
    }

    // --- LEAVES ---

    public function indexLeaves()
    {
        $leaves = StaffLeave::with('staff')->orderBy('start_date', 'desc')->get();
        $staffMembers = Staff::where('is_active', true)->get();
        return view('staff.leaves.index', compact('leaves', 'staffMembers'));
    }

    public function storeLeave(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,staff_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        // Check for overlapping leaves
        // Overlap exists if: (StartA <= EndB) and (EndA >= StartB)
        $exists = StaffLeave::where('staff_id', $request->staff_id)
            ->where(function ($query) use ($request) {
                $query->where('start_date', '<=', $request->end_date)
                      ->where('end_date', '>=', $request->start_date);
            })
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['error' => 'Cet employé a déjà une absence ou un congé durant cette période.']);
        }

        StaffLeave::create($validated);

        return redirect()->back()->with('success', 'Congé enregistré.');
    }

    public function updateSchedule(Request $request, $id)
    {
        $schedule = StaffSchedule::findOrFail($id);

        $validated = $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date',
        ]);

        if ($request->start && $request->end) {
            $start = \Carbon\Carbon::parse($request->start);
            $end = \Carbon\Carbon::parse($request->end);

            $schedule->update([
                'date' => $start->format('Y-m-d'),
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Créneau mis à jour.']);
    }

    public function updateLeaveStatus(Request $request, $id)
    {
        $leave = StaffLeave::findOrFail($id);
        $leave->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }
    public function destroyLeave($id)
    {
        $leave = StaffLeave::findOrFail($id);
        $leave->delete();

        return response()->json(['success' => true, 'message' => 'Congé supprimé.']);
    }
}
