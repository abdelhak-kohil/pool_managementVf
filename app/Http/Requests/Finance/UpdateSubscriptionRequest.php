<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_id'       => 'required|exists:activities,activity_id',
            'plan_id'           => 'required|exists:plans,plan_id',
            'status'            => 'required|string|in:active,paused,expired,cancelled',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after_or_equal:start_date',
            
            // Slots 
            'slot_ids'          => 'required|array',
            'slot_ids.*'        => 'integer|exists:time_slots,slot_id', // Specified table for safety

            // Optional New Payment
            'new_payment_amount'  => 'nullable|numeric|min:0.01',
            'new_payment_method'  => 'nullable|string|in:cash,card,transfer',
            'new_payment_notes'   => 'nullable|string|max:255',
        ];
    }
}
