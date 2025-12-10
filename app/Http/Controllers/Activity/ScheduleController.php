<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    // 🗓️ Display calendar view
    public function index()
    {
        $activities = DB::table('pool_schema.activities')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('schedule.index', compact('activities'));
    }

    // 📡 Fetch events for FullCalendar
    public function events()
    {
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

        // Build weekly recurring events
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

        return response()->json($events);
    }

    // ➕ Create new time slot
    public function store(Request $request)
    {
        $request->validate([
            'activity_id' => 'required|integer',
            'weekday_id' => 'required|integer|min:1|max:7',
            'start' => 'required|date',
            'end' => 'required|date|after:start'
        ]);
        


        $start = Carbon::parse($request->start)->format('H:i:s');
        $end = Carbon::parse($request->end)->format('H:i:s');


        DB::table('pool_schema.time_slots')->insert([
            'weekday_id' => $request->weekday_id,
            'start_time' => $start,
            'end_time' => $end,
            'activity_id' => $request->activity_id,
            'notes' => $request->notes
        ]);

        return response()->json(['success' => 'Créneau ajouté avec succès.']);
    }

    // ✏️ Update on drag or resize
    public function update(Request $request, $id)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after:start',
            'weekday' => 'required|integer|min:1|max:7'
        ]);

        $start = Carbon::parse($request->start)->format('H:i:s');
        $end = Carbon::parse($request->end)->format('H:i:s');

        DB::table('pool_schema.time_slots')
            ->where('slot_id', $id)
            ->update([
                'weekday_id' => $request->weekday,
                'start_time' => $start,
                'end_time' => $end
            ]);

        return response()->json(['success' => 'Créneau mis à jour avec succès.']);
    }


public function details($id)
{
    try {
        $slot = DB::table('pool_schema.time_slots as t')
            ->leftJoin('pool_schema.weekdays as w', 't.weekday_id', '=', 'w.weekday_id')
            ->leftJoin('pool_schema.activities as a', 't.activity_id', '=', 'a.activity_id')
            ->leftJoin('pool_schema.reservations as r', 'r.slot_id', '=', 't.slot_id')
            ->leftJoin('pool_schema.members as m', 'r.member_id', '=', 'm.member_id')
            ->leftJoin('pool_schema.partner_groups as g', 'r.partner_group_id', '=', 'g.group_id')
            ->leftJoin('pool_schema.staff as s', 't.created_by', '=', 's.staff_id')
            ->select(
                't.slot_id as id',
                'w.day_name',
                't.start_time',
                't.end_time',
                't.notes',
                'a.name as activity_name',
                'r.reservation_type',
                DB::raw("COALESCE(m.first_name, '') as member_first_name"),
                DB::raw("COALESCE(m.last_name, '') as member_last_name"),
                'm.phone_number as member_phone',
                'g.name',
                'g.contact_name',
                'g.contact_phone',
                DB::raw("CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) as created_by")
            )
            ->where('t.slot_id', $id)
            ->first();

        if (!$slot) {
            return response()->json(['error' => 'Créneau introuvable'], 404);
        }

        // ✅ Formatage propre des champs
        $details = [
            'id' => $slot->id,
            'day_name' => ucfirst($slot->day_name ?? 'Inconnu'),
            'start_time' => $slot->start_time ? substr($slot->start_time, 0, 5) : '—',
            'end_time' => $slot->end_time ? substr($slot->end_time, 0, 5) : '—',
            'activity_name' => $slot->activity_name ?? '—',
            'notes' => $slot->notes ?? '—',
            'reservation_type' => $slot->reservation_type ?? null,
            'member_name' => trim($slot->member_first_name . ' ' . $slot->member_last_name) ?: null,
            'member_phone' => $slot->member_phone ?? null,
            'group_name' => $slot->group_name ?? null,
            'contact_name' => $slot->contact_name ?? null,
            'contact_phone' => $slot->contact_phone ?? null,
            'created_by' => trim($slot->created_by) ?: 'Système',
            'updated_at' => null, // Column does not exist
        ];

        return response()->json($details, 200);

    } catch (\Exception $e) {
        Log::error('Erreur dans ScheduleController@details : ' . $e->getMessage());
        return response()->json(['error' => 'Erreur serveur : ' . $e->getMessage()], 500);
    }
}


    // ❌ Delete time slot
    public function destroy($id)
    {
        DB::table('pool_schema.time_slots')->where('slot_id', $id)->delete();
        return response()->json(['success' => 'Créneau supprimé.']);
    }
}
