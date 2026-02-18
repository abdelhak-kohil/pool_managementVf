<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission check can be added here or kept in middleware
        return true; 
    }

    public function rules(): array
    {
        return [
            'member_id'        => 'required_without:partner_group_id|nullable|exists:members,member_id',
            'partner_group_id' => 'required_without:member_id|nullable|exists:partner_groups,group_id',
            'activity_id'  => 'required|exists:activities,activity_id',
            'plan_id'      => 'required|exists:plans,plan_id',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'status'       => 'required|string|in:active,paused,expired,cancelled',
            'slot_ids'     => 'required|array',
            'slot_ids.*'   => 'integer|distinct',
            'amount'       => 'required|numeric|min:0.01',
            'payment_method'=> 'required|string|in:cash,card,transfer',
            'notes'        => 'nullable|string|max:255',
        ];
    }
}
