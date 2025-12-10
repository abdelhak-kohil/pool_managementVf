<?php

namespace App\Http\Requests\Pool;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilityRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Authorization handled by middleware/policies
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:indoor_pool,outdoor_pool,spa,wading_pool',
            'volume_liters' => 'required|integer|min:0',
            'min_temperature' => 'nullable|numeric|min:0|max:50',
            'max_temperature' => 'nullable|numeric|min:0|max:50|gte:min_temperature',
            'capacity' => 'nullable|integer|min:0',
            'status' => 'required|in:operational,closed,under_maintenance',
            'active' => 'boolean',
        ];
    }
}
