<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\Member;

class MemberSubscriptionController extends Controller
{
    public function index()
    {
        $members = Member::with(['subscriptions.plan', 'subscriptions.allowedDays'])
            ->orderBy('first_name')
            ->get();

        return view('members.subscriptions', compact('members'));
    }
}
