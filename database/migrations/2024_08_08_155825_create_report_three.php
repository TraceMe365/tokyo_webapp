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
        Schema::create('report_three', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date')->nullable(true);
            $table->string('plant')->nullable(true);
            $table->string('scheduled_customer')->nullable(true);
            $table->string('planned_dispatch_time')->nullable(true);
            $table->string('reschedule_time')->nullable(true);
            $table->string('cancel_reason')->nullable(true);
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
        Schema::dropIfExists('report_three');
    }
};
