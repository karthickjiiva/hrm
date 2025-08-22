<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyLeaveSummary extends Model
{
    protected $fillable = [
        'employee_id','month','year',
        'opening_cl','opening_sl','opening_el',
        'earned_cl','earned_sl','earned_el',
        'availed_cl','availed_sl','availed_el',
        'closing_cl','closing_sl','closing_el',
        'total_availed','closing_balance_total','lop_days',
        'sandwich_days','half_days',
        'total_working_days','days_payable','loss_of_pay_days',
    ];
}

?>