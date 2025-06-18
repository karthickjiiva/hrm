<?php

namespace App\Http\Requests\Api\EmployeeLeaveMaster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\EmployeeLeaveMaster;
use Vinkla\Hashids\Facades\Hashids;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get xid from route (like 'M6q8vWzR')
        $xid = $this->route('employee_leave_master');
        
        // Decode the XID back to the actual database ID
        $decodedIds = Hashids::decode($xid);
        $actualId = !empty($decodedIds) ? $decodedIds[0] : null;
        
        // Double-check that the record exists
        if (!$actualId || !EmployeeLeaveMaster::find($actualId)) {
            abort(404, 'Employee leave master record not found');
        }

        return [
            'employee_id' => [
                'required',
                Rule::unique('employee_leave_masters', 'employee_id')->ignore($actualId),
            ],
            'sl' => 'nullable|numeric|min:0',
            'cl' => 'nullable|numeric|min:0',
            'el' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.unique' => 'Leave master record already exists for the selected employee.',
        ];
    }
}