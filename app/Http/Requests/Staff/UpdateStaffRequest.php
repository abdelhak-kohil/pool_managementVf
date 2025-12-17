<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $staffId = $this->route('staff'); // ID from route

        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pool_schema.staff', 'username')->ignore($staffId, 'staff_id'),
            ],
            'password' => 'nullable|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,role_id',
            
            // Badge Logic: Validate UID exists generally. 
            // In Action we will handle verifying if it's free or assigned to THIS staff.
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
