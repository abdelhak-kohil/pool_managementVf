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
}
