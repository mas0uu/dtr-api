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
        Schema::create('users', function (Blueprint $table) {
            $table->string('student_no')->unique();
            $table->string('student_name');
            $table->string('school')->nullable();
            $table->unsignedInteger('required_hours')->default(0);

            $table->string('company')->nullable();
            $table->string('department')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->string('supervisor_position')->nullable();

            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->id();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('student_no')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
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
