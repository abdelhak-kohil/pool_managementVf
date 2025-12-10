<?php

namespace App\Http\Controllers\Pool;

use App\Http\Controllers\Controller;
use App\Models\Pool\Facility;
use App\Http\Requests\Pool\StoreFacilityRequest;
use App\Http\Requests\Pool\UpdateFacilityRequest;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index()
    {
        $facilities = Facility::orderBy('name')->get();
        return view('pool.facilities.index', compact('facilities'));
    }

    public function create()
    {
        return view('pool.facilities.create');
    }

    public function store(StoreFacilityRequest $request)
    {
        $facility = Facility::create($request->validated());

        return redirect()->route('pool.facilities.index')
            ->with('success', 'Bassin créé avec succès.');
    }

    public function show(Facility $facility)
    {
        return view('pool.facilities.show', compact('facility'));
    }

    public function edit(Facility $facility)
    {
        return view('pool.facilities.edit', compact('facility'));
    }

    public function update(UpdateFacilityRequest $request, Facility $facility)
    {
        $facility->update($request->validated());

        return redirect()->route('pool.facilities.index')
            ->with('success', 'Bassin mis à jour avec succès.');
    }

    public function destroy(Facility $facility)
    {
        // Check for dependencies before deleting
        if ($facility->waterTests()->exists() || $facility->incidents()->exists() || $facility->dailyTasks()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce bassin car il contient des données liées (tests, incidents, etc.). Désactivez-le plutôt.');
        }

        $facility->delete();

        return redirect()->route('pool.facilities.index')
            ->with('success', 'Bassin supprimé avec succès.');
    }
}
