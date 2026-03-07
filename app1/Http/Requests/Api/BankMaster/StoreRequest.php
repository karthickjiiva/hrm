<?php

namespace App\Http\Requests\Api\BankMaster;

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
            'account_number' => 'required|string|max:50',
            'bank_name'      => 'required|string|max:191',
            'ifsc'           => 'required|string|max:20',
            'micr'           => 'nullable|string|max:20',
            'employee_id'    => 'required|exists:users,id',
        ];
    }
}
