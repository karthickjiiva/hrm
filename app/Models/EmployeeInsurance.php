<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeInsurance extends Model
{
    protected $table = 'employee_insurances';

    protected $guarded = ['id'];

    protected $casts = [
        'spouse_dob' => 'date',
        'child1_dob' => 'date',
        'child2_dob' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}