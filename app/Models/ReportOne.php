<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportOne extends Model
{
    use HasFactory;

    protected $table = "report_one";

    protected $fillable = [
        'tokyo_location_name',
        'tokyo_vehicle_name',
        'tokyo_vehicle_id',
        'tokyo_plant_in_time',
        'tokyo_plant_out_time',
        'tokyo_plant_duration',
        'tokyo_site_name',
        'tokyo_site_in_time',
        'tokyo_site_out_time',
        'tokyo_site_out_plan_in_duration',
    ];
}
