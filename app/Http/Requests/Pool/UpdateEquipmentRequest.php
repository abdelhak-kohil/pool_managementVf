<?php

namespace App\Http\Requests\Pool;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:pump,filter,heater,chemical_doser,cleaning_robot,other',
            'serial_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'install_date' => 'nullable|date',
            'status' => 'required|in:operational,warning,failure',
            'next_due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
