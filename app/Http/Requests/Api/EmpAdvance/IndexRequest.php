<?php

namespace App\Http\Requests\Api\EmpAdvance;

use App\Http\Requests\Api\BaseRequest;

class IndexRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }

    public function allowedFilters()
    {
        return [
            'employee.name',
        ];
    }
}