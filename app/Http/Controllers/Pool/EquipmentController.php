<?php

namespace App\Http\Controllers\Pool;

use App\Http\Controllers\Controller;
use App\Models\Pool\PoolEquipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipment = PoolEquipment::orderBy('status', 'asc') // Warning/Failure first
            ->orderBy('next_due_date', 'asc')
            ->get();

        return view('pool.equipment.index', compact('equipment'));
    }

    public function create()
    {
        return view('pool.equipment.create');
    }

    public function store(\App\Http\Requests\Pool\StoreEquipmentRequest $request)
    {
        PoolEquipment::create($request->validated());

        return redirect()->route('pool.equipment.index')
            ->with('success', 'Équipement ajouté avec succès.');
    }

    public function show(PoolEquipment $equipment)
    {
        $equipment->load(['maintenanceLogs' => function($q) {
            $q->orderBy('scheduled_date', 'desc');
        }, 'incidents']);
        
        return view('pool.equipment.show', compact('equipment'));
    }

    public function edit(PoolEquipment $equipment)
    {
        return view('pool.equipment.edit', compact('equipment'));
    }

    public function update(\App\Http\Requests\Pool\UpdateEquipmentRequest $request, PoolEquipment $equipment)
    {
        $equipment->update($request->validated());

        return redirect()->route('pool.equipment.index')
            ->with('success', 'Équipement mis à jour avec succès.');
    }

    public function destroy(PoolEquipment $equipment)
    {
        if ($equipment->maintenanceLogs()->exists() || $equipment->incidents()->exists()) {
            return back()->with('error', 'Impossible de supprimer cet équipement car il a des historiques de maintenance ou d\'incidents.');
        }

        $equipment->delete();

        return redirect()->route('pool.equipment.index')
            ->with('success', 'Équipement supprimé avec succès.');
    }

    public function updateStatus(Request $request, PoolEquipment $equipment)
    {
        $request->validate([
            'status' => 'required|in:operational,warning,failure',
            'notes' => 'nullable|string'
        ]);

        $equipment->update([
            'status' => $request->status,
            'notes' => $request->notes
        ]);

        return back()->with('success', 'Statut de l\'équipement mis à jour.');
    }
}
