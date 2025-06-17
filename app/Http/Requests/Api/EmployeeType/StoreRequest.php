<?php

namespace App\Http\Requests\Api\EmployeeType;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|max:191',
            'basic_percent' => 'nullable|numeric|min:0|max:100',
            'hra_percent' => 'nullable|numeric|min:0|max:100',
            'allowance_percent' => 'nullable|numeric|min:0|max:100',
            'food_allowance_percent' => 'nullable|numeric|min:0|max:100',

            'pf_enabled' => 'nullable|boolean',
            'pf_percentage' => 'nullable|numeric|min:0|max:100',
            'pf_limit' => 'nullable|numeric|min:0',

            'esi_enabled' => 'nullable|boolean',
            'esi_percentage' => 'nullable|numeric|min:0|max:100',
            'esi_limit' => 'nullable|numeric|min:0',

            'prof_tax_enabled' => 'nullable|boolean',
            'prof_tax_percentage' => 'nullable|numeric|min:0|max:100',
            'prof_tax_limit' => 'nullable|numeric|min:0',

            'tds_enabled' => 'nullable|boolean',
            'tds_percentage' => 'nullable|numeric|min:0|max:100',
            'tds_limit' => 'nullable|numeric|min:0',

            'status' => 'required|in:active,inactive',
        ];
    }
}
