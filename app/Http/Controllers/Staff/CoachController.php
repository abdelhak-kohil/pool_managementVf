<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Staff\Staff;
use App\Models\Activity\TimeSlot;
use Carbon\Carbon;

class CoachController extends Controller
{
    /**
     * List all coaches with their session counts.
     */
    public function index()
    {
        // Fetch all staff members with role 'coach' (Active AND Inactive)
        $coaches = Staff::with('latestAccessLog')
            ->whereHas('role', function ($query) {
                $query->where('role_name', 'coach');
            })
            ->orderBy('first_name')
            ->get();

        // Calculate session counts and prepare fields for JSON/Frontend
        $coaches->transform(function ($coach) {
            $coach->session_count = TimeSlot::where('coach_id', $coach->staff_id)->count();
            
            // Append convenient attributes for JS
            $coach->full_name = $coach->full_name; // Triggers accessor
            $coach->avatar_initials = substr($coach->first_name, 0, 1) . substr($coach->last_name, 0, 1);
            
            if ($coach->latestAccessLog) {
                $coach->last_access_date = $coach->latestAccessLog->access_time->format('d/m/Y');
                $coach->last_access_time = $coach->latestAccessLog->access_time->format('H:i');
            } else {
                $coach->last_access_date = null;
                $coach->last_access_time = null;
            }

            // Route URLs
            $coach->view_url = route('coaches.show', $coach->staff_id);
            $coach->edit_url = route('coaches.edit', $coach->staff_id);
            $coach->destroy_url = route('coaches.destroy', $coach->staff_id);

            return $coach;
        });

        return view('admin.coaches.index', compact('coaches'));
    }

    /**
     * Show details for a specific coach + their planning.
     */
    public function show($id)
    {
        $coach = Staff::findOrFail($id);

        // Fetch assigned time slots for this coach
        $slots = TimeSlot::with(['activity', 'weekday'])
            ->where('coach_id', $id)
            ->get();

        // Format events for the calendar
        $events = [];
        foreach ($slots as $s) {
            // FullCalendar days: 0=Sunday, 1=Monday, ..., 6=Saturday
            // DB: 1=Monday, ..., 7=Sunday
            $dayOfWeek = $s->weekday_id % 7;

            $events[] = [
                'id' => $s->slot_id,
                'title' => $s->activity->name ?? 'Activité',
                'daysOfWeek' => [$dayOfWeek],
                'startTime' => $s->start_time,
                'endTime'   => $s->end_time,
                'backgroundColor' => $s->activity->color_code ?? '#3b82f6',
                'borderColor' => $s->activity->color_code ?? '#3b82f6',
                'extendedProps' => [
                    'weekday' => $s->weekday->day_name
                ]
            ];
        }

        return view('admin.coaches.show', compact('coach', 'events', 'slots'));
    }
    /**
     * Show the form for creating a new coach.
     */
    public function create()
    {
        // Fetch available badges (not assigned to member or staff)
        $availableBadges = \App\Models\Member\AccessBadge::whereNull('member_id')
            ->whereNull('staff_id')
            ->where('status', 'active')
            ->get();

        return view('admin.coaches.create', compact('availableBadges'));
    }

    /**
     * Store a newly created coach in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'specialty' => 'nullable|string|max:255',
            'hiring_date' => 'nullable|date',
            'salary_type' => 'required|in:fixed,per_hour,per_session',
            'hourly_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'badge_uid' => ['nullable', 'string', 'max:50', function ($attribute, $value, $fail) {
                $badge = \App\Models\Member\AccessBadge::where('badge_uid', $value)->first();
                if ($badge && ($badge->member_id || $badge->staff_id)) {
                    $fail('Ce badge est déjà utilisé par un autre membre ou staff.');
                }
            }],
        ]);

        // Find or create 'coach' role
        $role = \App\Models\Admin\Role::firstOrCreate(['role_name' => 'coach']);

        $coach = new Staff();
        $coach->first_name = $validated['first_name'];
        $coach->last_name = $validated['last_name'];
        $coach->username = strtolower($validated['first_name'] . '.' . $validated['last_name'] . rand(100, 999));
        $coach->password_hash = 'password'; // Default password, should be changed
        $coach->role_id = $role->role_id;
        $coach->is_active = true;
        
        // New fields
        $coach->phone_number = $validated['phone_number'];
        $coach->email = $validated['email'];
        $coach->specialty = $validated['specialty'];
        $coach->hiring_date = $validated['hiring_date'];
        $coach->salary_type = $validated['salary_type'];
        $coach->hourly_rate = $validated['hourly_rate'];
        $coach->notes = $validated['notes'];

        $coach->save();

        if (!empty($validated['badge_uid'])) {
            $existingBadge = \App\Models\Member\AccessBadge::where('badge_uid', $validated['badge_uid'])->first();
            
            if ($existingBadge) {
                $existingBadge->update([
                    'staff_id' => $coach->staff_id,
                    'status' => 'active',
                    'issued_at' => now(),
                ]);
            } else {
                \App\Models\Member\AccessBadge::create([
                    'staff_id' => $coach->staff_id,
                    'badge_uid' => $validated['badge_uid'],
                    'status' => 'active',
                    'issued_at' => now(),
                ]);
            }
        }

        return redirect()->route('coaches.index')->with('success', 'Coach ajouté avec succès.');
    }

    /**
     * Show the form for editing the specified coach.
     */
    public function edit($id)
    {
        $coach = Staff::findOrFail($id);
        
        // Fetch available badges (not assigned to member or staff)
        $availableBadges = \App\Models\Member\AccessBadge::whereNull('member_id')
            ->whereNull('staff_id')
            ->where('status', 'active')
            ->get();

        return view('admin.coaches.edit', compact('coach', 'availableBadges'));
    }

    /**
     * Update the specified coach in storage.
     */
    public function update(Request $request, $id)
    {
        $coach = Staff::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'specialty' => 'nullable|string|max:255',
            'hiring_date' => 'nullable|date',
            'salary_type' => 'required|in:fixed,per_hour,per_session',
            'hourly_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'badge_uid' => ['nullable', 'string', 'max:50', function ($attribute, $value, $fail) use ($coach) {
                // Check if this UID exists and is assigned to SOMEONE ELSE
                $badge = \App\Models\Member\AccessBadge::where('badge_uid', $value)->first();
                if ($badge) {
                    // If assigned to another staff
                    if ($badge->staff_id && $badge->staff_id != $coach->staff_id) {
                        $fail('Ce badge est déjà assigné à un autre membre du staff.');
                    }
                    // If assigned to a member
                    if ($badge->member_id) {
                        $fail('Ce badge est déjà assigné à un membre.');
                    }
                }
            }],
        ]);

        $coach->first_name = $validated['first_name'];
        $coach->last_name = $validated['last_name'];
        $coach->phone_number = $validated['phone_number'];
        $coach->email = $validated['email'];
        $coach->specialty = $validated['specialty'];
        $coach->hiring_date = $validated['hiring_date'];
        $coach->salary_type = $validated['salary_type'];
        $coach->hourly_rate = $validated['hourly_rate'];
        $coach->notes = $validated['notes'];
        $coach->is_active = $request->has('is_active');

        $coach->save();

        // Update Badge Logic
        // 1. If badge_uid is empty -> Detach any current badge
        if (empty($validated['badge_uid'])) {
            $currentBadge = $coach->badges()->first();
            if ($currentBadge) {
                $currentBadge->update(['staff_id' => null, 'status' => 'active']); // Make it available again
            }
        } 
        // 2. If badge_uid provided
        else {
            $newUid = $validated['badge_uid'];
            $currentBadge = $coach->badges()->first();

            // If user already has a badge
            if ($currentBadge) {
                // If UID changed
                if ($currentBadge->badge_uid !== $newUid) {
                    // Detach old
                    $currentBadge->update(['staff_id' => null]);
                    
                    // Attach new
                    $targetBadge = \App\Models\Member\AccessBadge::where('badge_uid', $newUid)->first();
                    if ($targetBadge) {
                        $targetBadge->update(['staff_id' => $coach->staff_id, 'status' => 'active', 'issued_at' => now()]);
                    } else {
                        \App\Models\Member\AccessBadge::create([
                            'staff_id' => $coach->staff_id,
                            'badge_uid' => $newUid,
                            'status' => 'active',
                            'issued_at' => now(),
                        ]);
                    }
                }
                // If UID same, do nothing
            } 
            // If user has NO badge yet
            else {
                $targetBadge = \App\Models\Member\AccessBadge::where('badge_uid', $newUid)->first();
                if ($targetBadge) {
                    $targetBadge->update(['staff_id' => $coach->staff_id, 'status' => 'active', 'issued_at' => now()]);
                } else {
                    \App\Models\Member\AccessBadge::create([
                        'staff_id' => $coach->staff_id,
                        'badge_uid' => $newUid,
                        'status' => 'active',
                        'issued_at' => now(),
                    ]);
                }
            }
        }

        return redirect()->route('coaches.index')->with('success', 'Coach mis à jour avec succès.');
    }

    /**
     * Remove the specified coach from storage.
     */
    public function destroy($id)
    {
        $coach = Staff::findOrFail($id);
        
        // Check if coach has assigned slots
        if (TimeSlot::where('coach_id', $id)->exists()) {
            return back()->with('error', 'Impossible de supprimer ce coach car il a des créneaux assignés.');
        }

        $coach->delete();

        return redirect()->route('coaches.index')->with('success', 'Coach supprimé avec succès.');
    }
}
