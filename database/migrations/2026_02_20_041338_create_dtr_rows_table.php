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
        Schema::create('dtr_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dtr_month_id')->constrained('dtr_months')->cascadeOnDelete();

            $table->date('date')->nullable();
            $table->string('day')->nullable();

            $table->time('time_in')->nullable();
            $table->enum('time_in_meridiem', ['AM','PM'])->nullable();
            
            $table->time('time_out')->nullable();
            $table->enum('time_out_meridiem', ['AM','PM'])->nullable();

            $table->unsignedInteger('total_minutes')->default(0);
            $table->enum('status', ['draft','finished'])->default('draft');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtr_rows');
    }
};
