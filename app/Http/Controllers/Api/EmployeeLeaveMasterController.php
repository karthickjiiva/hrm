<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\EmployeeLeaveMaster\IndexRequest;
use App\Http\Requests\Api\EmployeeLeaveMaster\StoreRequest;
use App\Http\Requests\Api\EmployeeLeaveMaster\UpdateRequest;
use App\Http\Requests\Api\EmployeeLeaveMaster\DeleteRequest;
use App\Models\EmployeeLeaveMaster;

class EmployeeLeaveMasterController extends ApiBaseController
{
    protected $model = EmployeeLeaveMaster::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    public function storing($employeeLeaveMaster)
    {
        $loggedUser = user();

        $employeeLeaveMaster->created_by = $loggedUser->id;

        return $employeeLeaveMaster;
    }

    public function updating($employeeLeaveMaster)
    {
        $loggedUser = user();

        $employeeLeaveMaster->updated_by = $loggedUser->id;

        return $employeeLeaveMaster;
    }
}
