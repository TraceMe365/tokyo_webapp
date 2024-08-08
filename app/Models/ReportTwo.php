<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTwo extends Model
{
    use HasFactory;

    protected $table = "report_two";

    protected $fillable = [
        'tokyo_location_name',
        'tokyo_pump_car_name',
        'tokyo_pump_site_id',
        'tokyo_pump_car_id',
        'tokyo_plant_out_time',
        'tokyo_site_in_time',
        'tokyo_first_truck_in_time',
        'tokyo_pump_idle_time',
        'tokyo_first_truck_in_name',
    ];  
}
