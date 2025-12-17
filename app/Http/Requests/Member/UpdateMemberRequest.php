<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'phone_number'  => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'badge_id'      => 'nullable|integer|exists:access_badges,badge_id',
            'photo'         => 'nullable|image|max:2048',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'notes'         => 'nullable|string',
            'health_conditions'=> 'nullable|string',

            // Subscriptions status update (Lite version)
            'subscriptions' => 'array|nullable',
            'subscriptions.*.status' => 'string|in:active,paused,expired,cancelled',
        ];
    }
}
