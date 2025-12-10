<?php

namespace App\Http\Requests\Pool;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonthlyTaskRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'facility_id' => 'required|exists:facilities,facility_id',
            'water_replacement_partial' => 'boolean',
            'full_system_inspection' => 'boolean',
            'chemical_dosing_calibration' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'water_replacement_partial' => $this->has('water_replacement_partial'),
            'full_system_inspection' => $this->has('full_system_inspection'),
            'chemical_dosing_calibration' => $this->has('chemical_dosing_calibration'),
        ]);
    }
}
