<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    protected $fillable = [
        'report_date', 
        'total_orders',
        'total_revenue',
        'memory_used'];
}
