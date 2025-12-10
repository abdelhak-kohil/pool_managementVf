<?php

namespace App\Http\Requests\Pool;

use Illuminate\Foundation\Http\FormRequest;

class StoreChemicalUsageRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'chemical_id' => 'required|exists:pool_chemical_stock,chemical_id',
            'quantity_used' => 'required|numeric|min:0.01',
            'usage_date' => 'required|date',
            'purpose' => 'nullable|string',
            'comments' => 'nullable|string',
        ];
    }
}
