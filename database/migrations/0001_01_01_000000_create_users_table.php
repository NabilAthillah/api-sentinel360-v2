<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('nric_fin_no');
            $table->string('mobile');
            $table->string('address');
            $table->date('briefing_date');
            $table->date('date_joined');
            $table->boolean('briefing_conducted');
            $table->boolean('q1');
            $table->string('a1')->nullable();
            $table->boolean('q2');
            $table->string('a2')->nullable();
            $table->boolean('q3');
            $table->string('a3')->nullable();
            $table->boolean('q4');
            $table->string('a4')->nullable();
            $table->boolean('q5');
            $table->string('a5')->nullable();
            $table->boolean('q6');
            $table->string('a6')->nullable();
            $table->boolean('q7');
            $table->string('a7')->nullable();
            $table->boolean('q8');
            $table->string('a8')->nullable();
            $table->boolean('q9');
            $table->string('a9')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('status', ['active', 'inactive', 'suspended']);
            $table->string('profile_image')->nullable();
            $table->dateTime('first_login')->nullable();
            $table->dateTime('last_login')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
