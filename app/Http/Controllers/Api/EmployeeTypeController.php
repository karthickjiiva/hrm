<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\EmployeeType\IndexRequest;
use App\Http\Requests\Api\EmployeeType\StoreRequest;
use App\Http\Requests\Api\EmployeeType\UpdateRequest;
use App\Http\Requests\Api\EmployeeType\DeleteRequest;
use App\Models\EmployeeType;

class EmployeeTypeController extends ApiBaseController
{
    protected $model = EmployeeType::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    public function storing($employeeType)
    {
        $loggedUser = user();

        $employeeType->created_by = $loggedUser->id;

        return $employeeType;
    }
}
