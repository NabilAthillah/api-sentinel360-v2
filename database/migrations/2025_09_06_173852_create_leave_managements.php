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
        Schema::create('leave_managements', function (Blueprint $table) {
            $table->id();
            $table->string('id_user');
            $table->foreign('id_user')->references('id')->on('users');
            $table->string('type');
            $table->date('from');
            $table->date('to');
            $table->string('total');
            $table->string('reason')->nullable();
            $table->enum('status', ['active', 'deactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_managements');
    }
};
