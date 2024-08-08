<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_two', function (Blueprint $table) {
            $table->id();
            $table->string('tokyo_location_name')->nullable(true);
            $table->string('tokyo_pump_site_id')->nullable(true);
            $table->string('tokyo_site_name')->nullable(true);
            $table->string('tokyo_pump_car_name');
            $table->string('tokyo_pump_car_id');
            $table->datetime('tokyo_plant_out_time')->nullable(true);
            $table->datetime('tokyo_site_in_time')->nullable(true);
            $table->string('tokyo_first_truck_in_name')->nullable(true);
            $table->datetime('tokyo_first_truck_in_time')->nullable(true);
            $table->string('tokyo_pump_idle_time')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_two');
    }
};
