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
        Schema::create('pointers', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('nfc_tag')->unique();
            $table->string('remarks')->nullable();
            $table->string('id_route');
            $table->foreign('id_route')->references('id')->on('routes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pointers');
    }
};
