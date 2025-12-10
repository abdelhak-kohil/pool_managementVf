<?php

namespace App\Http\Requests\Pool;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyTaskRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'pool_id' => 'required|exists:facilities,facility_id',
            'pump_status' => 'nullable|string',
            'pressure_reading' => 'nullable|numeric',
            'skimmer_cleaned' => 'boolean',
            'vacuum_done' => 'boolean',
            'drains_checked' => 'boolean',
            'lighting_checked' => 'boolean',
            'debris_removed' => 'boolean',
            'drain_covers_inspected' => 'boolean',
            'clarity_test_passed' => 'boolean',
            'anomalies_comment' => 'nullable|string',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'skimmer_cleaned' => $this->has('skimmer_cleaned'),
            'vacuum_done' => $this->has('vacuum_done'),
            'drains_checked' => $this->has('drains_checked'),
            'lighting_checked' => $this->has('lighting_checked'),
            'debris_removed' => $this->has('debris_removed'),
            'drain_covers_inspected' => $this->has('drain_covers_inspected'),
            'clarity_test_passed' => $this->has('clarity_test_passed'),
        ]);
    }
}
