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

    public function store(\App\Http\Requests\Staff\StoreStaffRequest $request, \App\Modules\HR\Actions\CreateStaffAction $action)
    {
        try {
            $data = \App\Modules\HR\DTOs\StaffData::fromRequest($request);
            
            $action->execute($data, $request->badge_uid);

            return redirect()->route('staff.index')->with('success', 'Membre du personnel ajouté avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
             return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $staff = Staff::findOrFail($id);
        $roles = Role::orderBy('role_name')->get();
        return view('staff.edit', compact('staff', 'roles'));
    }

    public function update(\App\Http\Requests\Staff\UpdateStaffRequest $request, $id, \App\Modules\HR\Actions\UpdateStaffAction $action)
    {
        try {
            $data = \App\Modules\HR\DTOs\StaffData::fromRequest($request);
            
            // Note: fromRequest usually grabs 'password' from input. 
            // In update scenarios, if password is null/empty in input, we typically don't want to change it.
            // My DTO handles this? 
            // DTO: public ?string $password. 
            // fromRequest: password: $request->input('password').
            // If empty in form => null in DTO.
            // Action: if ($data->password) { update } else { ignore }.
            // This logic holds.

            $action->execute($id, $data, $request->badge_uid);

            return redirect()->route('staff.index')->with('success', 'Membre du personnel mis à jour avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
             return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
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
