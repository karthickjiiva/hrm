<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\BankMaster\IndexRequest;
use App\Http\Requests\Api\BankMaster\StoreRequest;
use App\Http\Requests\Api\BankMaster\UpdateRequest;
use App\Http\Requests\Api\BankMaster\DeleteRequest;
use App\Models\BankMaster;

class BankMasterController extends ApiBaseController
{
    protected $model = BankMaster::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    public function storing($bankMaster)
    {
        $loggedUser = user();

        $bankMaster->created_by = $loggedUser->id;

        return $bankMaster;
    }

    public function updating($bankMaster)
    {
        $loggedUser = user();

        $bankMaster->updated_by = $loggedUser->id;

        return $bankMaster;
    }
}
