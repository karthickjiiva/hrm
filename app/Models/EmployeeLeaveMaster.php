<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class EmployeeLeaveMaster extends BaseModel
{
    protected $table = 'employee_leave_masters';

    protected $default = ['xid', 'sl', 'cl', 'el', 'employee_id',];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'created_by', 'updated_by'];

    protected $appends = ['xid', 'x_employee_id', 'employee_name', 'x_created_by', 'x_updated_by'];

    protected $with = ['employee'];

    protected $filterable = ['sl', 'cl', 'el'];

    protected $fillable = [
        'employee_id',
        'sl',
        'cl',
        'el',
        'created_by',
        'updated_by',
    ];

    protected $hashableGetterFunctions = [
        'getXEmployeeIdAttribute' => 'employee_id',
        'getXCreatedByAttribute' => 'created_by',
        'getXUpdatedByAttribute' => 'updated_by',
    ];

    protected $casts = [
        // 'employee_id' => Hash::class . ':hash',
        'created_by'  => Hash::class . ':hash',
        'updated_by'  => Hash::class . ':hash',
        'sl' => 'float',
        'cl' => 'float',
        'el' => 'float',
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
