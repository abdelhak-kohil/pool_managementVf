<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff\Staff;
use App\Models\Activity\TimeSlot;
use App\Models\Activity\Activity;
use App\Models\Activity\Weekday;
use Carbon\Carbon;

class CoachPlanningController extends Controller
{
    /**
     * Show the weekly planning view.
     */
    public function index(Request $request)
    {
        $coaches = Staff::coaches()->where('is_active', true)->orderBy('first_name')->get();
        $activities = Activity::orderBy('name')->get();
        $weekdays = Weekday::orderBy('weekday_id')->get();

        // Fetch slots with relationships
        $slots = TimeSlot::with(['coach', 'assistantCoach', 'activity', 'weekday'])
            ->orderBy('weekday_id')
            ->orderBy('start_time')
            ->get();

        return view('admin.coaches.planning', compact('coaches', 'activities', 'weekdays', 'slots'));
    }

    /**
     * Update a slot's assignment (AJAX).
     */
    /**
     * Update a slot's assignment (AJAX).
     */
    public function updateSlot(Request $request)
    {
        // Convert empty strings to null
        $data = $request->all();
        $data['coach_id'] = $data['coach_id'] ?: null;
        $data['assistant_coach_id'] = $data['assistant_coach_id'] ?: null;
        $request->merge($data);

        $validated = $request->validate([
            'slot_id' => 'required|exists:time_slots,slot_id',
            'coach_id' => 'nullable|exists:staff,staff_id',
            'assistant_coach_id' => 'nullable|exists:staff,staff_id',
        ]);

        try {
            $slot = TimeSlot::findOrFail($validated['slot_id']);

            // Check for conflicts (Coach cannot be in two places at once)
            if ($validated['coach_id']) {
                $conflict = TimeSlot::where('slot_id', '!=', $slot->slot_id)
                    ->where('weekday_id', $slot->weekday_id)
                    ->where('coach_id', $validated['coach_id'])
                    ->where(function ($q) use ($slot) {
                        // Overlap logic: (StartA < EndB) and (EndA > StartB)
                        $q->where('start_time', '<', $slot->end_time)
                          ->where('end_time', '>', $slot->start_time);
                    })
                    ->exists();

                if ($conflict) {
                    return response()->json(['error' => 'Ce coach est déjà assigné à un autre créneau sur cette plage horaire.'], 422);
                }
            }

            // Check if activity is "Entretien" or "Vide"
            if ($slot->activity && in_array(strtolower($slot->activity->name), ['entretien', 'vide'])) {
                return response()->json(['error' => 'Impossible d\'assigner un coach à une activité "Entretien" ou "Vide".'], 422);
            }

            $slot->coach_id = $validated['coach_id'];
            $slot->assistant_coach_id = $validated['assistant_coach_id'];
            $slot->save();

            return response()->json(['success' => true, 'message' => 'Planning mis à jour.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur serveur : ' . $e->getMessage()], 500);
        }
    }
}
