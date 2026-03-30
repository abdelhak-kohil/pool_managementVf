<?php

namespace App\Http\Controllers\Pool;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pool\StoreChemicalUsageRequest;
use App\Models\Pool\PoolChemical;
use App\Models\Pool\PoolChemicalUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChemicalController extends Controller
{
    public function index()
    {
        $chemicals = PoolChemical::all();
        $usageHistory = PoolChemicalUsage::with(['chemical', 'technician'])
            ->orderBy('usage_date', 'desc')
            ->paginate(15);

        return view('pool.chemicals.index', compact('chemicals', 'usageHistory'));
    }

    public function storeUsage(StoreChemicalUsageRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();
        $data['technician_id'] = $user->staff_id;

        $chemical = PoolChemical::findOrFail($data['chemical_id']);

        if ($chemical->quantity_available < $data['quantity_used']) {
            return back()->with('error', 'Insufficient stock.');
        }

        // Deduct stock
        $chemical->decrement('quantity_available', $data['quantity_used']);
        $chemical->touch('last_updated');

        PoolChemicalUsage::create($data);

        return back()->with('success', 'Chemical usage recorded.');
    }

    public function updateStock(Request $request, PoolChemical $chemical)
    {
        $request->validate([
            'quantity_added' => 'required|numeric|min:0',
        ]);

        $chemical->increment('quantity_available', $request->quantity_added);
        $chemical->touch('last_updated');

        return back()->with('success', 'Stock updated.');
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'unit' => 'required|string|max:20',
            'minimum_threshold' => 'required|numeric|min:0',
            'quantity_available' => 'required|numeric|min:0',
        ]);

        PoolChemical::create($data);

        return back()->with('success', 'Produit chimique ajouté avec succès.');
    }

    public function update(Request $request, PoolChemical $chemical)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'unit' => 'required|string|max:20',
            'minimum_threshold' => 'required|numeric|min:0',
        ]);

        $chemical->update($data);

        return back()->with('success', 'Produit chimique mis à jour avec succès.');
    }

    public function destroy(PoolChemical $chemical)
    {
        if ($chemical->usages()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce produit car il possède un historique de consommation.');
        }

        $chemical->delete();

        return back()->with('success', 'Produit chimique supprimé avec succès.');
    }
}
