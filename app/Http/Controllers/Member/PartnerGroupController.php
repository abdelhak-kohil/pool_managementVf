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

        // Check if already exists
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
        $query = $partnerGroup->attendances()
            ->with(['badge', 'slot.weekday', 'staff'])
            ->orderBy('access_time', 'desc');

        if ($request->filled('date')) {
            $query->whereDate('access_time', $request->date);
        }

        $logs = $query->paginate(20);

        return view('partner_groups.attendance', compact('partnerGroup', 'logs'));
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

    public function storeSubscription(Request $request, PartnerGroup $partnerGroup)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,plan_id',
            'activity_id' => 'required|exists:activities,activity_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card,transfer,check',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::transaction(function () use ($validated, $partnerGroup) {
                // 1. Create Subscription
                $subscriptionId = DB::table('pool_schema.subscriptions')->insertGetId([
                    'partner_group_id' => $partnerGroup->group_id,
                    'member_id' => null,
                    'plan_id' => $validated['plan_id'],
                    'activity_id' => $validated['activity_id'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'status' => 'active',
                    'visits_per_week' => null, 
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'subscription_id');

                // 2. Create Payment
                DB::table('pool_schema.payments')->insert([
                    'subscription_id' => $subscriptionId,
                    'amount' => $validated['amount'],
                    'payment_method' => $validated['payment_method'],
                    'payment_date' => now(),
                    'received_by_staff_id' => Auth::user()->staff_id ?? null,
                    'notes' => $validated['notes'] ?? 'Paiement initial groupe',
                ]);
            });

            return back()->with('success', 'Abonnement et paiement créés avec succès.');

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error creating group subscription: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la création de l\'abonnement: ' . $e->getMessage());
        }
    }
}
