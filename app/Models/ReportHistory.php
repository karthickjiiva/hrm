<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportHistory extends Model
{
    protected $table = 'report_histories';

    protected $fillable = [
        'report_name',
        'year',
        'filename',
        'path',
        'bank_filename',
        'bank_path'
    ];
}