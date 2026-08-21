<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedReport extends Model
{
    protected $fillable = ['report_type', 'year', 'month', 'filename', 'file_path', 'filters', 'created_by'];

    protected $casts = [
        'filters' => 'json',
    ];

protected $appends = ['created_at_formatted','full_url'];

public function getCreatedAtFormattedAttribute()
{
    return $this->created_at->format('d M Y');
}

public function getFullUrlAttribute()
{
    return asset('storage/' . $this->file_path);
}
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
?>