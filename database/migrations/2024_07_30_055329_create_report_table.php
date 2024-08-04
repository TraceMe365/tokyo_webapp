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
        Schema::create('report_one', function (Blueprint $table) {
            $table->id();
            $table->string('tokyo_location_name');
            $table->string('tokyo_vehicle_name');
            $table->string('tokyo_vehicle_id');
            $table->datetime('tokyo_plant_in_time')->nullable(true);
            $table->datetime('tokyo_plant_out_time')->nullable(true);
            $table->string('tokyo_plant_duration')->nullable(true);
            $table->string('tokyo_site_name')->nullable(true);
            $table->datetime('tokyo_site_in_time')->nullable(true);
            $table->datetime('tokyo_site_out_time')->nullable(true);
            $table->time('tokyo_site_duration')->nullable(true);
            $table->string('tokyo_site_out_plan_in_duration')->nullable(true);
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
        Schema::dropIfExists('report_one');
    }
};
