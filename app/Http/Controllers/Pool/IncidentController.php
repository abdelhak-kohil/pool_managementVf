<?php

namespace App\Http\Controllers\Pool;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pool\StoreIncidentRequest;
use App\Models\Pool\PoolIncident;
use App\Models\Pool\PoolEquipment;
use App\Models\Pool\Facility;
use App\Models\Staff\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentController extends Controller
{
    public function index()
    {
        $incidents = PoolIncident::with(['equipment', 'pool', 'creator', 'assignee'])
            ->orderByRaw("CASE WHEN status = 'open' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pool.incidents.index', compact('incidents'));
    }

    public function create()
    {
        $equipment = PoolEquipment::all();
        $pools = Facility::where('active', true)->get();
        return view('pool.incidents.create', compact('equipment', 'pools'));
    }

    public function store(StoreIncidentRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();
        $data['created_by'] = $user->staff_id;

        PoolIncident::create($data);

        return redirect()->route('pool.incidents.index')
            ->with('success', 'Incident reported.');
    }

    public function show(PoolIncident $incident)
    {
        $technicians = Staff::all(); // Should filter by role
        return view('pool.incidents.show', compact('incident', 'technicians'));
    }

    public function update(Request $request, PoolIncident $incident)
    {
        $request->validate([
            'status' => 'required|in:open,assigned,in_progress,waiting_parts,resolved,closed',
            'assigned_to' => 'nullable|exists:pool_schema.staff,staff_id',
        ]);

        $incident->update([
            'status' => $request->status,
            'assigned_to' => $request->assigned_to,
        ]);

        return back()->with('success', 'Incident updated.');
    }
}
