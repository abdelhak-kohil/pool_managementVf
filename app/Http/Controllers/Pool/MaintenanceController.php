<?php

namespace App\Http\Controllers\Pool;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pool\StoreMaintenanceRequest;
use App\Models\Pool\PoolMaintenance;
use App\Models\Pool\PoolEquipment;
use App\Models\Staff\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceController extends Controller
{
    public function index()
    {
        $tasks = PoolMaintenance::with(['equipment', 'technician'])
            ->orderBy('scheduled_date', 'asc')
            ->get();
            
        // Group by status for Kanban
        $kanban = [
            'scheduled' => $tasks->where('status', 'scheduled'),
            'in_progress' => $tasks->where('status', 'in_progress'),
            'completed' => $tasks->where('status', 'completed'),
        ];

        return view('pool.maintenance.index', compact('kanban'));
    }

    public function create()
    {
        $equipment = PoolEquipment::all();
        $technicians = Staff::all(); // Should filter by role ideally
        return view('pool.maintenance.create', compact('equipment', 'technicians'));
    }

    public function store(StoreMaintenanceRequest $request)
    {
        $data = $request->validated();
        if (empty($data['technician_id'])) {
            $user = Auth::user();
            $data['technician_id'] = $user->staff_id;
        }

        PoolMaintenance::create($data);

        return redirect()->route('pool.maintenance.index')
            ->with('success', 'Maintenance task scheduled.');
    }

    public function updateStatus(Request $request, PoolMaintenance $maintenance)
    {
        $request->validate([
            'status' => 'required|in:scheduled,in_progress,completed',
            'completed_date' => 'nullable|date',
            'used_parts' => 'nullable|string',
            'working_hours_spent' => 'nullable|numeric'
        ]);

        $data = $request->only(['status', 'used_parts', 'working_hours_spent']);
        
        if ($request->status === 'completed' && !$maintenance->completed_date) {
            $data['completed_date'] = now();
            
            // Update equipment last maintenance date
            $maintenance->equipment->update([
                'last_maintenance_date' => now(),
                'status' => 'operational' // Assume fixed if maintenance done
            ]);
        }

        $maintenance->update($data);

        return back()->with('success', 'Task status updated.');
    }
}
