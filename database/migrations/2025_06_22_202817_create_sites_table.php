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
        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('image')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mcst_number')->nullable();
            $table->string('managing_agent')->nullable();
            $table->string('person_in_charge')->nullable();
            $table->string('mobile')->nullable();
            $table->string('pic')->nullable();
            $table->foreign('pic')->references('id')->on('users');
            $table->string('address');
            $table->string('postal_code');
            $table->string('lat');
            $table->string('long');
            $table->string('organisation_chart')->nullable();
            $table->string('nfc_tag')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
