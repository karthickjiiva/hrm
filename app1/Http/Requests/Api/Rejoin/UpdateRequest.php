<?php

namespace App\Http\Requests\Api\Rejoin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Update as needed based on authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required',
            'title' => 'required|string|max:191',
            'description' => 'required|string',
            'resignated_date' => 'required|date',
            'rejoined_date' => 'required|date|after_or_equal:resignated_date',
        ];
    }
}
