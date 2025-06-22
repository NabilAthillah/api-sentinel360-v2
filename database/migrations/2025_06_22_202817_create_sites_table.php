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
            $table->string('ma_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('company_name')->nullable();
            $table->string('address');
            $table->string('block')->nullable();
            $table->string('unit')->nullable();
            $table->string('postal_code');
            $table->string('lat');
            $table->string('long');
            $table->string('organisation_char')->nullable();
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
