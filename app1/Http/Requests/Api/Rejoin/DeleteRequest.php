<?php

namespace App\Http\Requests\Api\Rejoin;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * You can replace this with actual authorization logic if needed.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Add custom auth check if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * For delete, usually no validation is required.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
