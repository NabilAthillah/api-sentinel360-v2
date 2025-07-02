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
        Schema::create('employee_document', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('id_employee');
            $table->foreign('id_employee')->references('id')->on('employees');
            $table->string('id_document');
            $table->foreign('id_document')->references('id')->on('employee_documents');
            $table->string('path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_document');
    }
};
