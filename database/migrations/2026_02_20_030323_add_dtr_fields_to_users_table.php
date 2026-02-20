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
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_no')->unique()->after('id');
            $table->string('student_name')->after('student_no');
            $table->string('school')->nullable()->after('student_name');
            $table->unsignedInteger('required_hours')->default(0)->after('school');

            $table->string('company')->nullable()->after('required_hours');
            $table->string('department')->nullable()->after('company');
            $table->string('supervisor_name')->nullable()->after('department');
            $table->string('supervisor_position')->nullable()->after('supervisor_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'student_no','student_name','school','required_hours',
                'company','department','supervisor_name','supervisor_position'
            ]);
        });
    }
};
