<?php

namespace App\Http\Requests\Api\EmployeeLeaveMaster;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\EmployeeLeaveMaster;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                // if you're using hashed IDs, remove this `exists` rule
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $exists = EmployeeLeaveMaster::where('employee_id', $value)->exists();

                    if ($exists) {
                        $fail('Leave master record already exists for the selected employee.');
                    }
                }
            ],
            'sl' => 'nullable|numeric|min:0',
            'cl' => 'nullable|numeric|min:0',
            'el' => 'nullable|numeric|min:0',
        ];
    }
}
