<?php

namespace App\Http\Requests\Pool;

use Illuminate\Foundation\Http\FormRequest;

class StoreWaterTestRequest extends FormRequest
{
    public function authorize()
    {
        // Assuming any authenticated staff can submit, or restrict to 'technician'/'maintenance'
        return true; 
    }

    public function rules()
    {
        return [
            'pool_id' => 'required|exists:facilities,facility_id',
            'test_date' => 'required|date',
            'ph' => 'nullable|numeric|between:0,14',
            'chlorine_free' => 'nullable|numeric|min:0',
            'chlorine_total' => 'nullable|numeric|min:0',
            'bromine' => 'nullable|numeric|min:0',
            'alkalinity' => 'nullable|integer|min:0',
            'hardness' => 'nullable|integer|min:0',
            'salinity' => 'nullable|integer|min:0',
            'turbidity' => 'nullable|numeric|min:0',
            'temperature' => 'nullable|numeric|between:-10,50',
            'orp' => 'nullable|integer',
            'comments' => 'nullable|string|max:1000',
        ];
    }
}
