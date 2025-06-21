<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->integer('grace_period');
            $table->integer('geo_fencing');
            $table->time('day_shift_start_time');
            $table->time('day_shift_end_time');
            $table->time('night_shift_start_time');
            $table->time('night_shift_end_time');
            $table->time('relief_day_shift_start_time');
            $table->time('relief_day_shift_end_time');
            $table->time('relief_night_shift_start_time');
            $table->time('relief_night_shift_end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
