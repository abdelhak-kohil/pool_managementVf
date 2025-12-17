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




    public function store(\App\Http\Requests\Member\StoreMemberRequest $request, \App\Modules\CRM\Actions\CreateMemberAction $createMemberAction)
    {
        try {
            $user = Auth::user();
            $staffId = $user->staff_id ?? $user->user_id ?? null;

            // Prepare DTOs
            $memberData = \App\Modules\CRM\DTOs\MemberData::fromRequest($request);
            // Re-use Subscription DTO logic (passing null for member_id as it's generated inside action)
            // But wait, DTO fromRequest expects direct inputs. 
            // We need to ensure we pass the right inputs or manually construct it if field names differ.
            // Fortunately, field names align (plan_id, activity_id, etc.) with StoreMemberRequest which contains all fields.
            // But we must be careful with 'member_id' which is 0 or null initially.
            $subscriptionData = \App\Modules\Sales\DTOs\SubscriptionData::fromRequest($request, $staffId);

            $createMemberAction->execute($memberData, $subscriptionData, $staffId);

            return redirect()
                ->route('members.index')
                ->with('success', 'Membre et abonnement créés avec succès !');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Erreur création membre (store): '.$e->getMessage());
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $member = Member::with(['subscriptions.plan', 'accessBadge'])->findOrFail($id);
        $plans = Plan::where('is_active', true)->get();
        $weekdays = DB::table('pool_schema.weekdays')->get();
        
        $badges = DB::table('pool_schema.access_badges')
            ->whereNull('member_id')
            ->where('status', '!=', 'blocked')
            ->get();

        return view('members.edit', compact('member', 'plans', 'weekdays','badges'));
    }

    public function update(\App\Http\Requests\Member\UpdateMemberRequest $request, $id, \App\Modules\CRM\Actions\UpdateMemberAction $action)
    {
        try {
            $user = Auth::user();
            $staffId = $user->staff_id ?? $user->user_id ?? null;

             $memberData = \App\Modules\CRM\DTOs\MemberData::fromRequest($request);

            $action->execute(
                $id,
                $memberData,
                $request->badge_id,
                $request->input('subscriptions', []),
                $staffId
            );

            return redirect()->route('members.index')
                ->with('success', 'Membre mis à jour avec succès.');

        } catch (\Illuminate\Validation\ValidationException $e) {
             return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Erreur Member update: '.$e->getMessage());
            return back()->withInput()->with('error', 'Erreur lors de la mise à jour du membre.');
        }
    }

    public function search(Request $request)
    {
        try {
            $q = trim((string) $request->query('q', ''));

            $membersQuery = Member::with(['subscriptions.plan', 'accessBadge', 'createdBy', 'updatedBy']);

            if ($q !== '') {
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
             Log::error('Members::search error: '.$e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Server error while searching members.'], 500);
        }
    }

public function show($id)
{
    // Just redirect to index if someone tries /members/{id}
    return redirect()->route('members.index');
}

    public function destroy($id, \App\Modules\CRM\Actions\DeleteMemberAction $action)
    {
        try {
            $action->execute($id);

            return redirect()->route('members.index')->with('success', 'Membre supprimé avec succès.');

        } catch (\Illuminate\Validation\ValidationException $e) {
             // Action throws ValidationException for logic business rules (has subs, history)
             return back()->with('error', $e->validator->errors()->first()); 
             // Or $e->getMessage() if it was generic, but ValidationException messages are in validator
             // Actually ValidationException::withMessages creates a validator.
             // Let's grab the first error.
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
