<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class EmployeeType extends BaseModel
{
    protected $table = 'employee_types';

    protected $default =  ['id',
    'xid',
    'type',
];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'created_by'];

    protected $appends = ['xid'];

    protected $filterable = ['type', 'status'];

    protected $fillable = [
        'type',
        'basic_percent',
        'hra_percent',
        'allowance_percent',
        'food_allowance_percent',
        'pf_enabled',
        'pf_percentage',
        'pf_limit',
        'esi_enabled',
        'esi_percentage',
        'esi_limit',
        'prof_tax_enabled',
        'prof_tax_percentage',
        'prof_tax_limit',
        'tds_enabled',
        'tds_percentage',
        'tds_limit',
        'status',
        'created_by',
    ];

    protected $hashableGetterFunctions = [
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'is_deletable' => 'integer',
        'created_by' => Hash::class . ':hash',
        'basic_percent' => 'float',
        'hra_percent' => 'float',
        'allowance_percent' => 'float',
        'food_allowance_percent' => 'float',
        'pf_enabled' => 'boolean',
        'pf_percentage' => 'float',
        'pf_limit' => 'float',
        'esi_enabled' => 'boolean',
        'esi_percentage' => 'float',
        'esi_limit' => 'float',
        'prof_tax_enabled' => 'boolean',
        'prof_tax_percentage' => 'float',
        'prof_tax_limit' => 'float',
        'tds_enabled' => 'boolean',
        'tds_percentage' => 'float',
        'tds_limit' => 'float',
        'status' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function getEmployeeCountAttribute()
    {
        return [
            'employee_count' => StaffMember::where('employee_type_id', $this->id)->count(),
        ];
    }

    // public function getXidAttribute()
    // {
    //     return Hashids::encode($this->id);
    // }
}
