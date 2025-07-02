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
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('q1', ['1', '0'])->nullable();
            $table->string('a1')->nullable();
            $table->enum('q2', ['1', '0'])->nullable();
            $table->string('a2')->nullable();
            $table->enum('q3', ['1', '0'])->nullable();
            $table->string('a3')->nullable();
            $table->enum('q4', ['1', '0'])->nullable();
            $table->string('a4')->nullable();
            $table->enum('q5', ['1', '0'])->nullable();
            $table->string('a5')->nullable();
            $table->enum('q6', ['1', '0'])->nullable();
            $table->string('a6')->nullable();
            $table->enum('q7', ['1', '0'])->nullable();
            $table->string('a7')->nullable();
            $table->enum('q8', ['1', '0'])->nullable();
            $table->string('a8')->nullable();
            $table->enum('q9', ['1', '0'])->nullable();
            $table->string('a9')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            //
        });
    }
};
