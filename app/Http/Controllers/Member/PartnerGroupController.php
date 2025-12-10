<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

use App\Models\Member\PartnerGroup;
use Illuminate\Http\Request;

class PartnerGroupController extends Controller
{
    public function index()
    {
        $groups = PartnerGroup::orderBy('name')->get();
        return view('partner_groups.index', compact('groups'));
    }

    public function create()
    {
        return view('partner_groups.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150|unique:partner_groups,name',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'notes' => 'nullable|string'
        ]);

        PartnerGroup::create($data);
        return redirect()->route('partner-groups.index')->with('success', 'Groupe partenaire ajouté.');
    }

    public function edit(PartnerGroup $partnerGroup)
    {
        return view('partner_groups.edit', compact('partnerGroup'));
    }

    public function update(Request $request, PartnerGroup $partnerGroup)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150|unique:partner_groups,name,' . $partnerGroup->group_id . ',group_id',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'notes' => 'nullable|string'
        ]);

        $partnerGroup->update($data);
        return redirect()->route('partner-groups.index')->with('success', 'Groupe partenaire mis à jour.');
    }

    public function destroy($id)
    {
        try {
            $group = PartnerGroup::findOrFail($id);
            $group->delete();

            return redirect()->route('partner-groups.index')
                             ->with('success', 'Groupe partenaire supprimé avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur suppression groupe partenaire : ' . $e->getMessage());
            return back()->with('error', 'Impossible de supprimer ce groupe partenaire.');
        }
    }
}
