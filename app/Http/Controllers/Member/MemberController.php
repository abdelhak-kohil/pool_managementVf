<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Member\Member;
use App\Models\Finance\Plan;
use App\Models\Finance\Subscription;
use App\Models\Member\AccessBadge;
use App\Models\Admin\AccessLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Finance\Payment;
use App\Models\Activity\Activity;


class MemberController extends Controller
{
    public function index()
    {
        $members = Member::with(['subscriptions.plan', 'accessBadge'])
            ->orderBy('first_name')
            ->paginate(10);

        return view('members.index', compact('members'));
    }

  public function create()
{
    $plans = DB::table('pool_schema.plans')->get();
    $weekdays = DB::table('pool_schema.weekdays')->get();

    $freeBadges = DB::table('pool_schema.access_badges')
        ->whereNull('member_id')
        ->where('status', '!=', 'blocked')
        ->get();
    
    $activities = Activity::orderBy('name')->get();

    return view('members.create', compact('plans', 'weekdays', 'freeBadges', 'activities'));
}




public function store(Request $request)
{
    DB::beginTransaction();
    try {
        // 1️⃣ Validation Common
        $request->validate([
            // Member
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'phone_number'     => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:150',
            'date_of_birth'    => 'nullable|date',
            'address'          => 'nullable|string|max:255',
            'badge_uid'        => 'required|string|exists:access_badges,badge_uid',
            'badge_status'     => 'required|string|in:active,inactive,lost',
            'photo'            => 'nullable|image|max:2048',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'notes'            => 'nullable|string',
            'health_conditions'=> 'nullable|string',

            // Subscription & Payment
            'activity_id'      => 'required|exists:activities,activity_id',
            'plan_id'          => 'required|exists:plans,plan_id',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'status'           => 'required|string|in:active,paused',
            'slot_ids'         => 'required|array',
            'slot_ids.*'       => 'integer|distinct',
            'amount'           => 'required|numeric|min:0.01',
            'payment_method'   => 'required|string|in:cash,card,transfer',
        ]);

        $user = Auth::user();
        $createdBy = $user->staff_id ?? null; // Staff ID for creator fields

        // 2️⃣ Create Member
        $member = Member::create([
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'phone_number' => $request->phone_number,
            'email'        => $request->email,
            'date_of_birth'=> $request->date_of_birth,
            'address'      => $request->address,
            'photo_path'   => $request->file('photo') ? $request->file('photo')->store('members/photos', 'public') : null,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'notes'        => $request->notes,
            'health_conditions' => $request->health_conditions,
            'created_by'   => $createdBy,
            'updated_by'   => $createdBy,
        ]);

        // 3️⃣ Assign Badge
        $badge = AccessBadge::where('badge_uid', $request->badge_uid)->first();
        if ($badge) {
            $badge->update([
                'member_id' => $member->member_id,
                'status'    => $request->badge_status,
            ]);
        }

        // 4️⃣ Validate Plan & Slots (Logic copied from SubscriptionController)
        $plan = DB::table('pool_schema.plans')->where('plan_id', $request->plan_id)->first();
        $planType = $plan->plan_type;
        $requiredSlotsCount = $planType === 'monthly_weekly' ? (int) $plan->visits_per_week : 1;

        // Date Restrictions (Simplified for brevity, but crucial checks)
        $start = \Carbon\Carbon::parse($request->start_date);
        
        if ($planType === 'monthly_weekly') {
            if ($start->day !== 1) throw new \Exception('Les abonnements mensuels doivent commencer le 1er du mois.');
        }
        if ($planType === 'per_visit') {
            if (!$start->isSameDay(now())) throw new \Exception('Les abonnements à la séance doivent être pour aujourd’hui.');
        }

        // Validate Slot Count
        $selectedSlots = $request->slot_ids ?? [];
        if (count($selectedSlots) !== $requiredSlotsCount) {
             throw new \Exception("Ce plan requiert exactement {$requiredSlotsCount} créneau(x).");
        }

        // Validate Slots Existence & Blocked Status
        $slots = DB::table('pool_schema.time_slots')->whereIn('slot_id', $selectedSlots)->get();
        if ($slots->count() !== count($selectedSlots)) throw new \Exception('Un ou plusieurs créneaux sont introuvables.');

        foreach ($slots as $s) {
            if ($s->is_blocked) throw new \Exception("Le créneau {$s->slot_id} est bloqué.");
            
            // Per-Visit: Strict "Currently Active" check (as per frontend logic)
            if ($planType === 'per_visit') {
                $slotStart = \Carbon\Carbon::parse($s->start_time);
                $slotEnd = \Carbon\Carbon::parse($s->end_time);

                // Align dates to today (since per_visit is for today)
                $startDateTime = now()->setTimeFrom($slotStart);
                $endDateTime = now()->setTimeFrom($slotEnd);
                $now = now();

                // 1. Cannot be before start time
                if ($now->lt($startDateTime)) {
                    throw new \Exception("Le créneau de {$s->start_time} n'a pas encore commencé. (Attendez l'heure de début)");
                }

                // 2. Cannot be after end time
                if ($now->gt($endDateTime)) {
                    throw new \Exception("Le créneau de {$s->start_time} est déjà terminé.");
                }
            }
        }

        // 5️⃣ Price Validation
        $activityPlan = DB::table('pool_schema.activity_plan_prices')
            ->where('plan_id', $request->plan_id)
            ->where('activity_id', $request->activity_id)
            ->first();
            
        if (!$activityPlan) throw new \Exception('Aucun tarif trouvé pour cette combinaison.');
        
        if ($request->amount > $activityPlan->price) {
            throw new \Exception('Le montant payé dépasse le prix de l’abonnement.');
        }

        // 6️⃣ Prevent Overlapping Monthly Subscriptions
        if ($planType === 'monthly_weekly') {
             // Check against $member->member_id
             // Since member is new, they have no OTHER subscriptions generally, 
             // BUT in weird race conditions or re-use logic it's good practice.
             // Here it's a new member, so overlap is impossible unless we allow multiple subs on creation?
             // We only create one here. So skipping complex overlap check for NEW member.
        }

        // 7️⃣ Create Subscription
        $subscription = Subscription::create([
            'member_id'       => $member->member_id,
            'plan_id'         => $request->plan_id,
            'activity_id'     => $request->activity_id,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'status'          => $request->status,
            'visits_per_week' => $planType === 'monthly_weekly' ? $plan->visits_per_week : null,
            'created_by'      => $createdBy,
            'updated_by'      => $createdBy,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        $subscriptionId = $subscription->subscription_id;

        // 8️⃣ Insert Slots
        $weekdayIds = [];
        foreach ($slots as $s) {
            DB::table('pool_schema.subscription_slots')->insert([
                'subscription_id' => $subscriptionId,
                'slot_id'         => $s->slot_id,
                'created_at'      => now(),
            ]);
            $weekdayIds[] = $s->weekday_id;
        }

        // 9️⃣ Insert Allowed Days (if monthly)
        if ($planType === 'monthly_weekly') {
            $weekdayIds = array_unique($weekdayIds);
            foreach ($weekdayIds as $wd) {
                DB::table('pool_schema.subscription_allowed_days')->insert([
                    'subscription_id' => $subscriptionId,
                    'weekday_id'      => $wd,
                ]);
            }
        }

        // 🔟 Create Payment
        Payment::create([
            'subscription_id'      => $subscriptionId,
            'amount'               => $request->amount,
            'payment_method'       => $request->payment_method,
            'notes'                => $request->notes, // Shared notes field
            'received_by_staff_id' => $createdBy,
            'payment_date'         => now(),
        ]);

        DB::commit();

        return redirect()
            ->route('members.index')
            ->with('success', 'Membre et abonnement créés avec succès !');

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Erreur création membre: '.$e->getMessage());
        return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
    }
}

    public function edit($id)
    {
        $member = Member::with(['subscriptions.plan', 'accessBadge'])->findOrFail($id);
        $plans = Plan::where('is_active', true)->get();
        $weekdays = DB::table('pool_schema.weekdays')->get();
        //dd($member);
        // Fetch only free (unassigned) badges
    $badges = DB::table('pool_schema.access_badges')
        ->whereNull('member_id')
        ->where('status', '!=', 'blocked')
        ->get();


        return view('members.edit', compact('member', 'plans', 'weekdays','badges'));
    }

  /**
     * Update a member, their badge, and subscription statuses.
     */
    public function update(Request $request, $id)
{
    $request->validate([
        'first_name'    => 'required|string|max:100',
        'last_name'     => 'required|string|max:100',
        'phone_number'  => 'nullable|string|max:20',
        'address'       => 'nullable|string|max:255',
        'date_of_birth' => 'nullable|date',
        'badge_id'      => 'nullable|integer|exists:access_badges,badge_id',

        // Only the status is editable in this screen
        'subscriptions' => 'array|nullable',
        'subscriptions.*.status' => 'string|in:active,paused,expired,cancelled',
        'photo'            => 'nullable|image|max:2048',
        'emergency_contact_name' => 'nullable|string|max:100',
        'emergency_contact_phone' => 'nullable|string|max:20',
        'notes'            => 'nullable|string',
        'health_conditions'=> 'nullable|string',
    ]);

    DB::beginTransaction();

    try {
        $member = Member::with(['subscriptions', 'accessBadge'])->findOrFail($id);
        $user = Auth::user();
        $staffId = $user->staff_id ?? null;

        /**
         * 1️⃣ UPDATE MEMBER INFORMATION
         */
        $member->update([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'phone_number'  => $request->phone_number,
            'address'       => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'notes'        => $request->notes,
            'health_conditions' => $request->health_conditions,
            'updated_by'    => $staffId,
            'updated_at'    => now(),
        ]);

        if ($request->hasFile('photo')) {
            if ($member->photo_path) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $member->update(['photo_path' => $request->file('photo')->store('members/photos', 'public')]);
        }

        /**
         * 2️⃣ HANDLE BADGE CHANGE
         */
        $requestedBadgeId = $request->badge_id;

        if ($requestedBadgeId) {
            $currentBadge = $member->accessBadge;

            if (!$currentBadge || $currentBadge->badge_id != $requestedBadgeId) {

                if ($currentBadge) {
                    DB::table('pool_schema.access_badges')
                        ->where('badge_id', $currentBadge->badge_id)
                        ->update([
                            'member_id' => null,
                            'status'    => 'inactive',
                        ]);
                }

                $newBadge = DB::table('pool_schema.access_badges')
                    ->lockForUpdate()
                    ->where('badge_id', $requestedBadgeId)
                    ->first();

                if (!$newBadge || (!is_null($newBadge->member_id) && $newBadge->member_id != $member->member_id)) {
                    DB::rollBack();
                    return back()->with('error', 'Le badge sélectionné est déjà attribué.')->withInput();
                }

                DB::table('pool_schema.access_badges')
                    ->where('badge_id', $requestedBadgeId)
                    ->update([
                        'member_id' => $member->member_id,
                        'status'    => 'active',
                        'issued_at' => now(),
                    ]);
            }
        }

        /**
         * 3️⃣ UPDATE SUBSCRIPTION STATUSES
         */
        if ($request->filled('subscriptions')) {

            // Apply status changes
            foreach ($request->subscriptions as $subId => $subData) {
                $sub = Subscription::find($subId);

                if ($sub && $sub->member_id == $member->member_id) {
                    $sub->update([
                        'status'      => $subData['status'],
                        'updated_by'  => $staffId,
                        'updated_at'  => now(),
                    ]);
                }
            }

            /**
             * 4️⃣ PREVENT MULTIPLE ACTIVE monthly_weekly SUBSCRIPTIONS
             */
            $activeWeeklyCount = DB::table('pool_schema.subscriptions as s')
                ->join('pool_schema.plans as p', 'p.plan_id', '=', 's.plan_id')
                ->where('s.member_id', $member->member_id)
                ->where('s.status', 'active')
                ->where('p.plan_type', 'monthly_weekly')
                ->count();

            if ($activeWeeklyCount > 1) {
                DB::rollBack();
                return back()->with('error',
                    "Impossible : un membre ne peut pas avoir plus d’un abonnement actif de type 'mensuel/hebdomadaire'."
                )->withInput();
            }
        }

        DB::commit();

        return redirect()->route('members.index')
            ->with('success', 'Membre mis à jour avec succès.');

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Erreur Member update: '.$e->getMessage());

        return back()->with('error', 'Erreur lors de la mise à jour du membre.')->withInput();
    }
}


/**
     * AJAX live search for members (returns HTML partial)
     */
    public function search(Request $request)
    {
        try {
            $q = trim((string) $request->query('q', ''));

            $membersQuery = Member::with(['subscriptions.plan', 'accessBadge', 'createdBy', 'updatedBy']);

            if ($q !== '') {
                // Use ILIKE for Postgres (case-insensitive)
                $membersQuery->where(function ($builder) use ($q) {
                    $builder->where('first_name', 'ILIKE', "%{$q}%")
                            ->orWhere('last_name', 'ILIKE', "%{$q}%")
                            ->orWhere('email', 'ILIKE', "%{$q}%")
                            ->orWhere('phone_number', 'ILIKE', "%{$q}%")
                            ->orWhereHas('subscriptions.plan', function ($p) use ($q) {
                                $p->where('plan_name', 'ILIKE', "%{$q}%");
                            });
                });
            }

            $members = $membersQuery->orderBy('first_name')
                                    ->paginate(10)
                                    ->withQueryString();

            $html = view('members.partials.table', compact('members'))->render();

            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            // Log detailed error for debugging (stack trace included)
            Log::error('Members::search error: '.$e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Server error while searching members.'], 500);
        }
    }

public function show($id)
{
    // Just redirect to index if someone tries /members/{id}
    return redirect()->route('members.index');
}

    public function destroy($id)
    {
        try {
            $member = Member::findOrFail($id);

            // 🛡️ Prevent deletion if member has subscriptions
            if ($member->subscriptions()->count() > 0) {
                return back()->with('error', 'Impossible de supprimer ce membre car il possède des abonnements. Veuillez les supprimer ou les archiver d’abord.');
            }

            // 🛡️ Prevent deletion if member has access logs (history)
            if ($member->accessLogs()->count() > 0) {
                return back()->with('error', 'Impossible de supprimer ce membre car il possède un historique d’accès. Veuillez le désactiver à la place.');
            }

            if ($member->accessBadge) {
                $member->accessBadge->delete();
            }

            $member->delete();

            return redirect()->route('members.index')->with('success', 'Membre supprimé avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur suppression membre: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }


    //////////////////////////////
    public function info($id)
{
    $member = Member::with([
        'accessbadge',
        'subscriptions.plan',
        'subscriptions.payments.staff',
        'subscriptions.weekdays',
        'accessLogs.activity',
        'accessLogs.subscription.plan',
        'accessLogs.slot',
        'createdBy',
        'updatedBy'
    ])->findOrFail($id);

    $reservations = DB::table('pool_schema.reservations')
        ->join('pool_schema.time_slots', 'reservations.slot_id', '=', 'time_slots.slot_id')
        ->leftJoin('pool_schema.activities', 'time_slots.activity_id', '=', 'activities.activity_id')
        ->where('reservations.member_id', $id)
        ->orderByDesc('reservations.reserved_at')
        ->select(
            'reservations.*',
            'time_slots.start_time',
            'time_slots.end_time',
            'activities.name as activity_name'
        )
        ->get();
    

    return view('members.info', compact('member', 'reservations'));
}








/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*

public function details($id)
{
    $member = Member::with(['subscriptions.plan', 'accessBadge'])->find($id);

    if (!$member) {
        return response()->json(['success' => false, 'message' => 'Member not found.']);
    }

    $subscription = $member->subscriptions->first();
    $badge = $member->accessBadge;

    $allowedDays = [];
    if ($subscription) {
        $allowedDays = DB::table('pool_schema.subscription_allowed_days')
            ->join('pool_schema.weekdays', 'weekdays.weekday_id', '=', 'subscription_allowed_days.weekday_id')
            ->where('subscription_allowed_days.subscription_id', $subscription->subscription_id)
            ->pluck('day_name')
            ->toArray();
    }

    // Audit info: created/updated details
    $createdBy = DB::table('pool_schema.audit_log AS a')
        ->leftJoin('pool_schema.staff AS s', 'a.changed_by_staff_id', '=', 's.staff_id')
        ->select(DB::raw("concat(s.first_name, ' ', s.last_name) as changed_by"))
        ->where('a.record_id', $member->member_id)
        ->where('a.action', 'INSERT')
        ->first();

    $updatedBy = DB::table('pool_schema.audit_log AS a')
        ->leftJoin('pool_schema.staff AS s', 'a.changed_by_staff_id', '=', 's.staff_id')
        ->select(DB::raw("concat(s.first_name, ' ', s.last_name) as changed_by"))
        ->where('a.record_id', $member->member_id)
        ->where('a.action', 'UPDATE')
        ->orderByDesc('a.change_timestamp')
        ->first();

    $auditLogs = [];
    if (Auth::user()->role->role_name === 'admin') {
        $auditLogs = DB::table('pool_schema.audit_log AS a')
            ->leftJoin('pool_schema.staff AS s', 'a.changed_by_staff_id', '=', 's.staff_id')
            ->select(
                'a.table_name',
                'a.action',
                'a.change_timestamp',
                DB::raw("concat(s.first_name, ' ', s.last_name) as changed_by")
            )
            ->where(function ($q) use ($member, $subscription, $badge) {
                $q->where('a.record_id', $member->member_id);
                if ($subscription) $q->orWhere('a.record_id', $subscription->subscription_id);
                if ($badge) $q->orWhere('a.record_id', $badge->badge_id);
            })
            ->orderByDesc('a.change_timestamp')
            ->limit(10)
            ->get();
    }

    return response()->json([
        'success' => true,
        'member' => [
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'email' => $member->email,
            'phone_number' => $member->phone_number,
            'created_at' => $member->created_at,
            'updated_at' => $member->updated_at,
            'created_by' => $createdBy->changed_by ?? 'System',
            'updated_by' => $updatedBy->changed_by ?? 'System',
        ],
        'subscription' => $subscription ? [
            'plan_name' => $subscription->plan->plan_name ?? null,
            'status' => $subscription->status,
            'start_date' => $subscription->start_date,
            'end_date' => $subscription->end_date,
            'allowed_days' => $allowedDays,
        ] : null,
        'badge' => $badge ? [
            'badge_uid' => $badge->badge_uid,
            'status' => $badge->status,
            'issued_at' => $badge->issued_at,
        ] : null,
        'audit' => $auditLogs,
    ]);
}


*/

}
