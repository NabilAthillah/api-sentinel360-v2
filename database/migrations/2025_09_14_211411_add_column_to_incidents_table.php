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
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('location')->nullable()->after('footage');
            $table->string('why_happened')->nullable()->after('location');
            $table->string('person_injured')->nullable()->after('why_happened');
            $table->string('remarks')->nullable()->after('person_injured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('location');
            $table->dropColumn('why_happened');
            $table->dropColumn('person_injured');
            $table->dropColumn('remarks');
        });
    }
};
