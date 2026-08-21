<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class BankMaster extends BaseModel
{
    protected $table = 'bank_masters';

    protected $default = ['xid', 'bank_name', 'account_number'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'created_by', 'updated_by'];

    protected $appends = ['xid', 'x_employee_id', 'employee_name', 'x_created_by', 'x_updated_by'];

    protected $with = ['employee'];
    


    protected $filterable = ['bank_name', 'account_number', 'ifsc'];

    protected $fillable = [
        'account_number',
        'bank_name',
        'ifsc',
        'micr',
        'employee_id',
        'created_by',
        'updated_by',
    ];

    protected $hashableGetterFunctions = [
        'getXEmployeeIdAttribute' => 'employee_id',
        'getXCreatedByAttribute' => 'created_by',
        'getXUpdatedByAttribute' => 'updated_by',
    ];

    protected $casts = [
        'employee_id' => Hash::class . ':hash',
        'created_by'  => Hash::class . ':hash',
        'updated_by'  => Hash::class . ':hash',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function getEmployeeNameAttribute()
    {
        return $this->employee?->name ?? null;
    }

    /**
     * Relationship to the User model (employee).
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
    

    /**
     * Created by user relation.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updated by user relation.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
