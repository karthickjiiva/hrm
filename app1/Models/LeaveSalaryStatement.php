<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveSalaryStatement extends Model
{
    protected $table = 'leave_salary_statements';

    protected $fillable = [
        'employee_id',
        'year',
        'new_gross',
        'earned_leave',
        'leave_salary'
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}