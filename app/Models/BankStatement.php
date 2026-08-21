<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankStatement extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'remark',
        'amount',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function bankMaster()
    {
        return $this->hasOne(BankMaster::class, 'employee_id', 'employee_id');
    }
}
?>