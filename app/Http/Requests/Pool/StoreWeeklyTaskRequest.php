<?php

namespace App\Http\Requests\Pool;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeeklyTaskRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'pool_id' => 'required|exists:facilities,facility_id',
            'backwash_done' => 'boolean',
            'filter_cleaned' => 'boolean',
            'brushing_done' => 'boolean',
            'heater_checked' => 'boolean',
            'chemical_doser_checked' => 'boolean',
            'fittings_retightened' => 'boolean',
            'heater_tested' => 'boolean',
            'general_inspection_comment' => 'nullable|string',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'backwash_done' => $this->has('backwash_done'),
            'filter_cleaned' => $this->has('filter_cleaned'),
            'brushing_done' => $this->has('brushing_done'),
            'heater_checked' => $this->has('heater_checked'),
            'chemical_doser_checked' => $this->has('chemical_doser_checked'),
            'fittings_retightened' => $this->has('fittings_retightened'),
            'heater_tested' => $this->has('heater_tested'),
        ]);
    }
}
