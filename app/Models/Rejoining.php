<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Scopes\CompanyScope;
use App\Casts\Hash;

class Rejoining extends BaseModel
{
    protected $table = 'rejoinings';

    protected $default = ['xid', 'rejoined_date', 'resignated_date', 'title', 'description'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'user_id', 'company_id'];

    protected $appends = ['xid', 'x_user_id', 'x_company_id'];

    protected $filterable = ['title','user_name'];

    protected $casts = [
        'user_id' => Hash::class . ':hash',
        'company_id' => Hash::class . ':hash',
    ];

    protected $hashableGetterFunctions = [
        'getXUserIdAttribute' => 'user_id',
        'getXCompanyIdAttribute' => 'company_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function user()
    {
        return $this->belongsTo(StaffMember::class, 'user_id', 'id');
    }

        public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
