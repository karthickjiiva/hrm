<?php

namespace App\Http\Requests\Api\BankMaster;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
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
        'employee_id' => 'nullable|exists:users,id',
        'bank_name'   => 'nullable|string|max:255',
        'ifsc'        => 'nullable|string|max:20',
        'limit'       => 'nullable|integer|min:1|max:1000',
        'page'        => 'nullable|integer|min:1',
    ];
    }
}
