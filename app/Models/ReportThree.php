<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportThree extends Model
{
    use HasFactory;

    protected $table = "report_three";

    protected $fillable = [
        'date',
        'plant',
        'scheduled_customer',
        'planned_dispatch_time',
        'reschedule_time',
        'cancel_reason'
    ];
}
