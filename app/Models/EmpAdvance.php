<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Casts\Hash;
use App\Models\BaseModel;

class EmpAdvance extends BaseModel
{
    use HasFactory;

    protected $table = 'emp_advances';

    protected $fillable = [
        'employee_id',
        'advance_type',
        'amount',
        'deduct_month',
    ];

     protected $appends = ['xid']; 

    protected $casts = [
        'id' => Hash::class, 
    ];

     public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
   
}
