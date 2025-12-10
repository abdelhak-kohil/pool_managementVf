<?php
namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity\TimeSlot;
use App\Models\Activity\Activity;
use App\Models\Activity\Weekday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Staff\Staff;
use Illuminate\Support\Facades\Auth;

class TimeSlotController extends Controller
{
  

    public function index(Request $request)
    {
        $query = TimeSlot::with('activity','weekday','coach');

        if ($request->has('search') && $request->search != '') {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->whereHas('activity', function($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('weekday', function($q) use ($search) {
                    $q->whereRaw('LOWER(day_name) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereRaw('LOWER(assigned_group) LIKE ?', ["%{$search}%"])
                ->orWhere('start_time', 'like', "%{$search}%")
                ->orWhere('end_time', 'like', "%{$search}%");
            });
        }

        $slots = $query->orderBy('weekday_id')->orderBy('start_time')->paginate(10);
        
        if ($request->ajax()) {
            return view('timeslots.partials.table', compact('slots'))->render();
        }

        $weekdays = Weekday::orderBy('weekday_id')->get();
        return view('timeslots.index', compact('slots','weekdays'));
    }

    public function create()
    {
        $activities = Activity::where('is_active', true)->orderBy('name')->get();
        $weekdays = Weekday::orderBy('weekday_id')->get();
        $coaches = Staff::orderBy('first_name')->get(); // Get all staff for flexibility
        return view('timeslots.create', compact('activities','weekdays', 'coaches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'weekday_id' => 'required|integer|exists:weekdays,weekday_id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'activity_id' => 'nullable|exists:activities,activity_id',
            'assigned_group' => 'nullable|string|max:200',
            'capacity'   => 'nullable|integer|min:1',
            'coach_id'   => 'nullable|exists:staff,staff_id',
            'assistant_coach_id' => 'nullable|exists:staff,staff_id',
            'is_blocked' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        $data['created_by'] =Auth::user()->staff_id ?? 1; // Fallback to 1 if no auth

        TimeSlot::create($data);
        return redirect()->route('timeslots.index')->with('success','Créneau ajouté.');
    }

    public function edit(TimeSlot $timeslot)
    {
        $activities = Activity::where('is_active', true)->orderBy('name')->get();
        $weekdays = Weekday::orderBy('weekday_id')->get();
        $coaches = Staff::orderBy('first_name')->get();
        return view('timeslots.edit', ['slot' => $timeslot, 'activities' => $activities, 'weekdays' => $weekdays, 'coaches' => $coaches]);
    }

    public function update(Request $request, TimeSlot $timeslot)
    {
        $data = $request->validate([
            'weekday_id' => 'required|integer|exists:weekdays,weekday_id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'activity_id' => 'nullable|exists:activities,activity_id',
            'assigned_group' => 'nullable|string|max:200',
            'capacity'   => 'nullable|integer|min:1',
            'coach_id'   => 'nullable|exists:staff,staff_id',
            'assistant_coach_id' => 'nullable|exists:staff,staff_id',
            'is_blocked' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);
         $data['created_by'] =Auth::user()->staff_id ?? 1; // Fallback to 1 if no auth

        $timeslot->update($data);
        return redirect()->route('timeslots.index')->with('success','Créneau mis à jour.');
    }

    public function destroy(TimeSlot $timeslot)
    {
        $timeslot->delete();
        return redirect()->route('timeslots.index')->with('success','Créneau supprimé.');
    }

    /**
     * Weekly schedule grid view (visual)
     */
    public function schedule()
    {
        $weekdays = Weekday::orderBy('weekday_id')->get();
        // build time ranges present in DB (unique ranges)
        $ranges = TimeSlot::select('start_time','end_time')
            ->distinct()
            ->orderBy('start_time')
            ->get()
            ->map(function($r){
                return ['start' => substr($r->start_time,0,5), 'end' => substr($r->end_time,0,5), 'label' => substr($r->start_time,0,5) . ' — ' . substr($r->end_time,0,5)];
            })->toArray();

        // fetch all slots
        $slots = TimeSlot::with('activity')->get()->groupBy(function($s){
            return $s->weekday_id . '|' . substr($s->start_time,0,5) . '|' . substr($s->end_time,0,5);
        });

        // build lookup by weekday -> start_end
        $schedule = [];
        foreach ($slots as $key => $group) {
            // key example: "1|08:30|10:00"
            $parts = explode('|', $key);
            $weekday = $parts[0] ?? null;
            $start = $parts[1] ?? null;
            $end = $parts[2] ?? null;
            $slot = $group->first();
            $schedule[$weekday][$start . '|' . $end] = $slot;
        }

        return view('timeslots.schedule', compact('weekdays','ranges','schedule'));
    }
}
