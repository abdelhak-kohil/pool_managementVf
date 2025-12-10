<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff\Staff;
use App\Models\Admin\Role;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = Staff::with(['role', 'latestAccessLog', 'badges'])->orderBy('staff_id', 'asc');

        if ($request->filled('role')) {
            $query->whereHas('role', function($q) use ($request) {
                $q->where('role_name', $request->role);
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $staff = $query->get();
        $roles = \App\Models\Admin\Role::all();

        return view('staff.index', compact('staff', 'roles'));
    }

    public function create()
    {
        $roles = Role::orderBy('role_name')->get();
        // Fetch available badges (not assigned to member or staff)
        $availableBadges = \App\Models\Member\AccessBadge::whereNull('member_id')
            ->whereNull('staff_id')
            ->where('status', 'active') // Assuming 'active' means usable
            ->get();
            
        return view('staff.create', compact('roles', 'availableBadges'));
    }

    public function store(Request $request)
    {
       $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:staff,username',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,role_id',
            // Badge must exist and be free (we filter in UI, validation double check is good but 'exists' is enough for now)
            'badge_uid' => 'nullable|exists:pool_schema.access_badges,badge_uid',
            'email' => 'nullable|email|max:100',
            'phone_number' => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:100',
            'hiring_date' => 'nullable|date',
            'salary_type' => 'nullable|in:hourly,monthly,per_session',
            'hourly_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $staff = new Staff();
        $staff->first_name = $validated['first_name'];
        $staff->last_name = $validated['last_name'];
        $staff->username = strtolower($validated['username']);
        $staff->password_hash = $validated['password'];
        $staff->role_id = $validated['role_id'];
        $staff->is_active = $request->has('is_active');
        $staff->email = $validated['email'] ?? null;
        $staff->phone_number = $validated['phone_number'] ?? null;
        $staff->specialty = $validated['specialty'] ?? null;
        $staff->hiring_date = $validated['hiring_date'] ?? null;
        $staff->salary_type = $validated['salary_type'] ?? null;
        $staff->hourly_rate = $validated['hourly_rate'] ?? null;
        $staff->notes = $validated['notes'] ?? null;
        $staff->created_at = now();
        $staff->save();

        if (!empty($validated['badge_uid'])) {
            // Find the existing badge and assign it
            $badge = \App\Models\Member\AccessBadge::where('badge_uid', $validated['badge_uid'])->first();
            if ($badge) {
                $badge->staff_id = $staff->staff_id;
                $badge->status = 'active'; // Ensure it's active
                $badge->issued_at = now();
                $badge->save();
            }
        }

        return redirect()->route('staff.index')->with('success', 'Membre du personnel ajouté avec succès.');
    }

    public function edit($id)
    {
        $staff = Staff::findOrFail($id);
        $roles = Role::orderBy('role_name')->get();
        return view('staff.edit', compact('staff', 'roles'));
    }

    public function update(Request $request, $id)
    {
       $staff = Staff::findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:staff,username,' . $id . ',staff_id',
            'password' => 'nullable|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,role_id',
            'badge_uid' => 'nullable|string|max:50|unique:access_badges,badge_uid,' . ($staff->badges->first()->badge_id ?? 'NULL') . ',badge_id',
            'email' => 'nullable|email|max:100',
            'phone_number' => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:100',
            'hiring_date' => 'nullable|date',
            'salary_type' => 'nullable|in:hourly,monthly,per_session',
            'hourly_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $staff->first_name = $request->first_name;
        $staff->last_name = $request->last_name;
        $staff->username = strtolower($request->username);
        $staff->role_id = $request->role_id;
        $staff->is_active = $request->has('is_active');
        $staff->email = $request->email;
        $staff->phone_number = $request->phone_number;
        $staff->specialty = $request->specialty;
        $staff->hiring_date = $request->hiring_date;
        $staff->salary_type = $request->salary_type;
        $staff->hourly_rate = $request->hourly_rate;
        $staff->notes = $request->notes;

        if ($request->filled('password')) {
            $staff->password_hash = $request->password;
        }

        $staff->save();

        // Update Badge
        if ($request->filled('badge_uid')) {
            $badge = $staff->badges()->first();
            if ($badge) {
                $badge->update(['badge_uid' => $request->badge_uid]);
            } else {
                \App\Models\AccessBadge::create([
                    'staff_id' => $staff->staff_id,
                    'badge_uid' => $request->badge_uid,
                    'status' => 'active',
                    'issued_at' => now(),
                ]);
            }
        } else {
            // Optional: Remove badge if field is cleared? 
            // For now, we keep it unless explicitly managed, but if user clears it, maybe we should delete?
            // Let's assume if they clear it, they want to remove it.
            if ($request->has('badge_uid') && is_null($request->badge_uid)) {
                 $staff->badges()->delete();
            }
        }

        return redirect()->route('staff.index')->with('success', 'Membre du personnel mis à jour avec succès.');
    }

    public function destroy($id)
    {
        $staff = Staff::findOrFail($id);
        $staff->delete();
        return redirect()->route('staff.index')->with('success', 'Membre du personnel supprimé.');
    }
    public function show($id)
    {
        $staff = Staff::with(['role', 'badges', 'latestAccessLog'])->findOrFail($id);
        
        // Fetch recent attendance (last 30 days)
        $attendances = \App\Models\Staff\Attendance::where('staff_id', $id)
            ->orderBy('date', 'desc')
            ->take(30)
            ->get();

        // Calculate Stats
        $totalPresent = $attendances->where('status', 'present')->count();
        $totalLate = $attendances->where('status', 'late')->count();
        $latePercentage = $totalPresent > 0 ? round(($totalLate / $totalPresent) * 100, 1) : 0;

        // Fetch upcoming and recent leaves
        $leaves = \App\Models\Staff\StaffLeave::where('staff_id', $id)
            ->orderBy('start_date', 'desc')
            ->take(10)
            ->get();

        return view('staff.show', compact('staff', 'attendances', 'leaves', 'latePercentage'));
    }
}
