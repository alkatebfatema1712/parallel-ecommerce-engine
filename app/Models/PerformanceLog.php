<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceLog extends Model
{
    protected $fillable = [
        'method', 'path', 'status_code', 'duration_ms', 'memory_mb', 'user_key'
    ];
}
