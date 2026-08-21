<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeLoanRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_loan_id',
        'repayment_month',
        'amount',
        'status',
        'remarks', 
        'is_final',
    ];

    protected $casts = [
        'repayment_month' => 'date',
        'is_final' => 'boolean',
    ];

    public function loan()
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }
}
