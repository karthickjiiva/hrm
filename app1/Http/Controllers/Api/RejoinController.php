<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
// use App\Http\Requests\Api\Rejoin\IndexRequest;
use App\Http\Requests\Api\Rejoin\StoreRequest;
use App\Http\Requests\Api\Rejoin\UpdateRequest;
use App\Http\Requests\Api\Rejoin\DeleteRequest;
use App\Models\Rejoining;
use Examyou\RestAPI\ApiResponse;

class RejoinController extends ApiBaseController
{
    protected $model = Rejoining::class;

    // protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    protected function IndexRequest($query)
    {
        $loggedUser = user();
        $request = request();

        if ($loggedUser->ability('admin')) {
            $query = $this->applyVisibility($query, 'rejoinings');

            if ($request->has('user_id')) {
                $query = $query->where('user_id', $this->getIdFromHash($request->user_id));
            }
        } else {
            $query = $query->where('user_id', $loggedUser->id);
        }

        if ($request->has('rejoined_date')) {
            $query = $query->whereDate('rejoined_date', $request->rejoined_date);
        }

        return $query;
    }

    public function storing($rejoining)
    {
        $loggedUser = user();
        $rejoining->company_id = $loggedUser->company_id;

        return $rejoining;
    }

    public function updating($rejoining)
    {
        $loggedUser = user();
        $rejoining->company_id = $loggedUser->company_id;

        return $rejoining;
    }
}
