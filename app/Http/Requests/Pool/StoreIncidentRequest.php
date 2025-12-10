<?php

namespace App\Http\Requests\Pool;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'required|in:low,medium,high,critical',
            'equipment_id' => 'nullable|exists:pool_equipment,equipment_id',
            'pool_id' => 'nullable|exists:facilities,facility_id',
        ];
    }
}
