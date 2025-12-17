<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Member Profile
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'phone_number'     => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:150',
            'date_of_birth'    => 'nullable|date',
            'address'          => 'nullable|string|max:255',
            'photo'            => 'nullable|image|max:2048',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'notes'            => 'nullable|string',
            'health_conditions'=> 'nullable|string',

            // Badge (Optional but usually required for new members in this flow)
            'badge_uid'        => 'required|string|exists:access_badges,badge_uid',
            'badge_status'     => 'required|string|in:active,inactive,lost',

            // Initial Subscription (Required in this specific Create Flow)
            'activity_id'      => 'required|exists:activities,activity_id',
            'plan_id'          => 'required|exists:plans,plan_id',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'status'           => 'required|string|in:active,paused',
            'slot_ids'         => 'required|array',
            'slot_ids.*'       => 'integer|distinct',
            'amount'           => 'required|numeric|min:0.01',
            'payment_method'   => 'required|string|in:cash,card,transfer',
        ];
    }
}
