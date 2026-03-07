<?php
namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeLoan extends BaseModel 
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'amount',
        'monthly_amount',
        'tenure',
        'start_month',
        'end_month',
    ];

  protected $casts = [
    'amount' => 'float',
    'monthly_amount' => 'float',
    'tenure' => 'integer',
];
protected $appends = ['xid'];
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function repayments()
    {
        return $this->hasMany(EmployeeLoanRepayment::class);
    }

    public static function filterable()
{
    return [
        'employee.name',
        'amount',
        'tenure',
        'start_month',
        'end_month',
        'employee_id',  
    ];
}

}
