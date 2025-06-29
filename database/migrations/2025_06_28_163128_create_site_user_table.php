<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_user', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('id_employee');
            $table->foreign('id_employee')->references('id')->on('employees');
            $table->string('id_site');
            $table->foreign('id_site')->references('id')->on('sites');

            $table->string('shift');
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_user');
    }
};
