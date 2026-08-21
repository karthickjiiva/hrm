<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class GratuityForm extends Model
{
    protected $table = 'gratuity_forms';

    protected $fillable = [
        'company_id', 'employee_id', 'filename', 'file_path',
        'date_of_joining', 'exit_date', 'total_service',
        'salary_on_exit', 'gratuity_amount', 'form_data', 'created_by',
    ];

    protected $casts = [
        'form_data'       => 'json',
        'date_of_joining' => 'date',
        'exit_date'       => 'date',
        'salary_on_exit'  => 'float',
        'gratuity_amount' => 'float',
    ];

    protected $hidden = ['company_id', 'created_by'];

    protected $appends = ['xid', 'created_at_formatted', 'full_url'];

    public function getXidAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y, h:i A') : null;
    }

    public function getFullUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    public function employee()
    {
        return $this->belongsTo(StaffMember::class, 'employee_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
