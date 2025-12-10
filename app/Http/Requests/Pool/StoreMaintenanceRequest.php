<?php

namespace App\Http\Requests\Pool;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'equipment_id' => 'required|exists:pool_equipment,equipment_id',
            'task_type' => 'required|string',
            'scheduled_date' => 'required|date',
            'description' => 'nullable|string',
            'technician_id' => 'nullable|exists:staff,staff_id', // Can be assigned later
        ];
    }
}
