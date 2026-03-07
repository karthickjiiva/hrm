<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;
use App\Models\LeaveType;
use App\Models\StaffMember;

class Leave extends BaseModel
{
    protected $table = 'leaves';
    
    protected $default = [
        'xid', 
        'start_date', 
        'end_date', 
        'leave_date',
        'is_half_day', 
        'half_day_type', 
        'leave_type', 
        'is_paid', 
        'reason', 
        'status'
    ];
    
    protected $guarded = ['id', 'status', 'created_at', 'updated_at'];
    
    protected $hidden = ['id', 'user_id', 'leave_type_id'];
    
    protected $appends = ['xid', 'x_user_id'];
    
    protected $filterable = ['status', 'leave_type', 'is_paid', 'is_half_day'];
    
    protected $hashableGetterFunctions = [
        'getXUserIdAttribute' => 'user_id',
        // 'getXLeaveTypeIdAttribute' => 'leave_type_id',
    ];
    
    protected $casts = [
        'is_paid' => 'integer',
        'is_half_day' => 'integer',
        'leave_type' => 'integer',
        'user_id' => Hash::class . ':hash',
        'leave_type_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'leave_date' => 'date',
    ];

    // Leave type constants
    const LEAVE_TYPES = [
        1 => 'SL', // Sick Leave
        2 => 'CL', // Casual Leave  
        3 => 'EL', // Earned Leave
    ];

    // Half day type constants
    const HALF_DAY_TYPES = [
        'morning' => 'Morning',
        'evening' => 'Evening',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    // Relationships
    public function leaveType()
    {
        return $this->hasOne(LeaveType::class, 'id', 'leave_type_id');
    }

    public function user()
    {
        return $this->belongsTo(StaffMember::class, 'user_id', 'id');
    }

    // Accessors
    public function getLeaveTypeNameAttribute()
    {
        return self::LEAVE_TYPES[$this->leave_type] ?? 'Unknown';
    }

    public function getHalfDayTypeNameAttribute()
    {
        return self::HALF_DAY_TYPES[$this->half_day_type] ?? null;
    }

    // Mutators
    public function setLeaveDateAttribute($value)
    {
        $this->attributes['leave_date'] = $value ? date('Y-m-d', strtotime($value)) : null;
    }

    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = $value ? date('Y-m-d', strtotime($value)) : null;
    }

    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value ? date('Y-m-d', strtotime($value)) : null;
    }

    // Scopes
    public function scopeByMonth($query, $month, $year = null)
    {
        $year = $year ?: date('Y');
        return $query->whereYear('leave_date', $year)
                    ->whereMonth('leave_date', $month);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->where('start_date', '>=', $startDate)
                    ->where('end_date', '<=', $endDate);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByLeaveType($query, $leaveType)
    {
        return $query->where('leave_type', $leaveType);
    }

    public function scopeByLeaveTypeId($query, $leaveTypeId)
    {
        return $query->where('leave_type_id', $leaveTypeId);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', 1);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', 0);
    }

    public function scopeHalfDay($query)
    {
        return $query->where('is_half_day', 1);
    }

    public function scopeFullDay($query)
    {
        return $query->where('is_half_day', 0);
    }

    // Helper methods
    public static function getLeaveTypes()
    {
        return self::LEAVE_TYPES;
    }

    public static function getHalfDayTypes()
    {
        return self::HALF_DAY_TYPES;
    }

    public function isHalfDay()
    {
        return $this->is_half_day == 1;
    }

    public function isPaid()
    {
        return $this->is_paid == 1;
    }

    public function isSingleDay()
    {
        return $this->leave_date && $this->end_date && 
               $this->leave_date->format('Y-m-d') === $this->end_date->format('Y-m-d');
    }

    public function isMultiDay()
    {
        return $this->leave_date && $this->end_date && 
               $this->leave_date->format('Y-m-d') !== $this->end_date->format('Y-m-d');
    }

    // Validation rules method (can be used in controller)
    public static function validationRules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'leave_date' => 'nullable|date',
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'leave_type' => 'nullable|integer|in:1,2,3',
            'is_half_day' => 'nullable|integer|in:0,1',
            'half_day_type' => 'required_if:is_half_day,1|in:morning,evening',
            'is_paid' => 'required|integer|in:0,1',
            'reason' => 'nullable|string|max:500',
        ];
    }

    // Calculate leave days for a month
    public static function calculateLeaveDaysForMonth($userId, $month, $year = null)
    {
        $year = $year ?: date('Y');
        
        $leaves = self::byUser($userId)
                     ->byMonth($month, $year)
                     ->get();
        
        $totalDays = 0;
        foreach ($leaves as $leave) {
            $totalDays += $leave->is_half_day ? 0.5 : 1;
        }
        
        return $totalDays;
    }
}