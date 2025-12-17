<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:staff,username', // Specify schema/table
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,role_id',
            
            // Badge logic: Expecting an existing badge UID to assign
            'badge_uid' => 'nullable|exists:access_badges,badge_uid',
            
            'email' => 'nullable|email|max:100',
            'phone_number' => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:100',
            'hiring_date' => 'nullable|date',
            'salary_type' => 'nullable|in:hourly,monthly,per_session',
            'hourly_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }
}
