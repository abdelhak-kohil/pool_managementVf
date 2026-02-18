<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\Member\PartnerGroup;
use App\Models\Activity\TimeSlot;
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
        // 1. Validate Group Data
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:\App\Models\Member\PartnerGroup,name',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // 2. Create Group
                $group = PartnerGroup::create([
                    'name' => $validated['name'],
                    'contact_name' => $validated['contact_name'],
                    'contact_phone' => $validated['contact_phone'],
                    'email' => $validated['email'],
                    'notes' => $validated['notes'],
                ]);
            });

            return redirect()->route('partner-groups.index')->with('success', 'Groupe partenaire ajouté.');

        } catch (\Throwable $e) {
            Log::error('Error creating partner group: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(PartnerGroup $partnerGroup)
    {
        $partnerGroup->load(['badges', 'slots.slot.weekday', 'slots.slot.activity', 'subscriptions.plan']);
        
        // Fetch slots where Activity type is 'group' AND filter out those taken by OTHER groups
        // We include slots already assigned to THIS group so they remain visible
        $timeSlots = TimeSlot::with(['weekday', 'activity'])
            ->whereHas('activity', function ($q) {
                $q->where('access_type', 'group'); // Try 'group'
            })
            ->whereDoesntHave('partnerGroups', function ($query) use ($partnerGroup) {
                $query->where('partner_group_id', '!=', $partnerGroup->group_id);
            })
            ->orderBy('weekday_id')
            ->orderBy('start_time')
            ->get();
        

        
        // Fetch unassigned badges
        $availableBadges = \App\Models\Member\AccessBadge::whereNull('member_id')
            ->whereNull('staff_id')
            ->whereNull('partner_group_id')
            ->where('status', 'active') // Generally we only assign active badges
            ->orderBy('badge_uid')
            ->get();

        // Data for Subscription Form
        $plans = \App\Models\Finance\Plan::where('is_active', true)->orderBy('plan_name')->get();
        // Only show relevant activities (Group access)
        $activities = \App\Models\Activity\Activity::where('access_type', 'group')->orderBy('name')->get();

        return view('partner_groups.edit', compact('partnerGroup', 'timeSlots', 'availableBadges', 'plans', 'activities'));
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

    public function addBadge(Request $request, PartnerGroup $partnerGroup)
    {
        $request->validate([
            'badge_uid' => 'required|string|exists:access_badges,badge_uid',
            'status' => 'nullable|in:active,revoked'
        ]);

        $badge = \App\Models\Member\AccessBadge::where('badge_uid', $request->badge_uid)->first();

        if (!$badge) {
            return back()->with('error', 'Badge introuvable.');
        }

        // Safety check: ensure badge is not already assigned
        if ($badge->member_id || $badge->staff_id || $badge->partner_group_id) {
             return back()->with('error', 'Ce badge est déjà utilisé par un autre utilisateur ou groupe.');
        }

        $badge->update([
            'partner_group_id' => $partnerGroup->group_id,
            'status' => $request->input('status', 'active'),
            // Keep original issued_at or update if needed? usually keep original creation date or issued date
        ]);

        return back()->with('success', 'Badge associé au groupe.');
    }

    public function removeBadge(PartnerGroup $partnerGroup, \App\Models\Member\AccessBadge $badge)
    {
        if ($badge->partner_group_id !== $partnerGroup->group_id) {
            return back()->with('error', 'Action non autorisée.');
        }

        $badge->delete();
        return back()->with('success', 'Badge supprimé du groupe.');
    }

    public function addSlot(Request $request, PartnerGroup $partnerGroup)
    {
        $request->validate([
            'slot_id' => 'required|exists:time_slots,slot_id',
            'max_capacity' => 'required|integer|min:1'
        ]);

        $exists = \App\Models\Member\PartnerGroupSlot::where('partner_group_id', $partnerGroup->group_id)
            ->where('slot_id', $request->slot_id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Ce créneau est déjà assigné.');
        }

        \App\Models\Member\PartnerGroupSlot::create([
            'partner_group_id' => $partnerGroup->group_id,
            'slot_id' => $request->slot_id,
            'max_capacity' => $request->max_capacity
        ]);

        return back()->with('success', 'Créneau assigné au groupe.');
    }

    public function removeSlot(PartnerGroup $partnerGroup, \App\Models\Member\PartnerGroupSlot $slot)
    {
        if ($slot->partner_group_id !== $partnerGroup->group_id) {
            return back()->with('error', 'Action non autorisée.');
        }

        $slot->delete();
        return back()->with('success', 'Créneau horaire retiré du groupe.');
    }

    public function attendance(Request $request, PartnerGroup $partnerGroup)
    {
        // Removed as part of rollback
        abort(404);
    }
    
    public function toggleBadgeStatus(Request $request, PartnerGroup $partnerGroup, \App\Models\Member\AccessBadge $badge)
    {
        if ($badge->partner_group_id !== $partnerGroup->group_id) {
            return back()->with('error', 'Action non autorisée.');
        }

        $newStatus = $badge->status === 'active' ? 'revoked' : 'active';
        $badge->update(['status' => $newStatus]);

        $message = $newStatus === 'active' ? 'Badge activé.' : 'Badge révoqué.';
        return back()->with('success', $message);
    }
}

