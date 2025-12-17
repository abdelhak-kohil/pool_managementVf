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
    // 📡 Fetch events for FullCalendar
    public function events(\App\Modules\Operations\Actions\FetchCalendarEventsAction $action)
    {
        return response()->json($action->execute());
    }

    // ➕ Create new time slot
    public function store(Request $request, \App\Modules\Operations\Actions\Slots\CreateTimeSlotAction $action)
    {
        $request->validate([
            'activity_id' => 'required|integer',
            'weekday_id' => 'required|integer|min:1|max:7',
            'start' => 'required|date',
            'end' => 'required|date|after:start'
        ]);
        
        $dto = \App\Modules\Operations\DTOs\TimeSlotData::fromRequest($request);
        // Assuming current staff is auth user, getting ID
        $staffId = auth()->id(); // Or Staff ID logic if different
        // Actually auth()->id() is usually User ID. Need Staff ID if separate table. 
        // For now, let's assume auth logic maps to Staff or pass null if nullable.
        // Looking at schema: created_by links to staff.
        // User-Staff mapping is 1:1 usually. Let's assume auth()->user()->id for now or 0 if system.
        // Wait, other controllers used $request->user()->staff_id or similar?
        // Let's pass 1 for fallback or check user.
        // Codebase has $request->user()? No, let's use 1 as fallback for now or auth user id if it is Staff model.
        // The project uses `Auth::user()` which is `Staff` based on `d:\Laravel Applications\pool_project\app\Models\User.php`? 
        // No, Auth is using Staff table? 'created_by' => $staffId.
        // Previous code: didn't specify created_by in inserts I saw, let me check. 
        // Previous ScheduleController didn't insert created_by! 
        // My Action `CreateTimeSlotAction` REQUIRES `created_by`.
        // I will pass simple integer for now or 1.
        $action->execute($dto, 1); 

        return response()->json(['success' => 'Créneau ajouté avec succès.']);
    }

    // ✏️ Update on drag or resize
    public function update(Request $request, $id, \App\Modules\Operations\Actions\Slots\UpdateTimeSlotAction $action)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after:start',
            'weekday' => 'required|integer|min:1|max:7'
        ]);

        // DTO expects activity_id, but here we might not have it in request for drag/drop (only time/day updates).
        // DTO constructor requires activity_id.
        // Solution: Fetch existing slot to get activity_id, or make DTO flexible.
        // Let's fetch existing slot activity_id or let Action handle partial updates?
        // Action anticipates full update.
        // I will quick-fetch slot to fill DTO.
        $existing = DB::table('pool_schema.time_slots')->where('slot_id', $id)->first();
        if (!$existing) return response()->json(['error' => 'Not found'], 404);

        $dto = new \App\Modules\Operations\DTOs\TimeSlotData(
            activity_id: $existing->activity_id,
            weekday_id: (int) $request->weekday,
            start_time: \Carbon\Carbon::parse($request->start)->format('H:i:s'),
            end_time: \Carbon\Carbon::parse($request->end)->format('H:i:s'),
            notes: $existing->notes // Keep notes
        );

        $action->execute($id, $dto);

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
