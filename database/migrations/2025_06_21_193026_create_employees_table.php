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
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('nric_fin_no');
            $table->date('briefing_date');
            $table->string('id_user');
            $table->foreign('id_user')->references('id')->on('users');
            $table->string('reporting_to')->nullable();
            $table->foreign('reporting_to')->references('id')->on('employees');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
