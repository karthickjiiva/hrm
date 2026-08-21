<?php

namespace App\Models;

use App\Models\BaseModel;
class Arrear extends BaseModel
{
    protected $table = 'arrears';

    protected $fillable = [
        'emp_code',
        'name',
        'amount',
    ];

    public $timestamps = true;
  
}
