<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->string('employee_id')->change();
        });

        // Migrate the existing data from employees.id to employees.employee_id
        DB::table('attendance_logs')
            ->join('employees', 'attendance_logs.employee_id', '=', 'employees.id')
            ->update(['attendance_logs.employee_id' => DB::raw('employees.employee_id')]);

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->foreign('employee_id')->references('employee_id')->on('employees')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        // Revert data back to employees.id
        DB::table('attendance_logs')
            ->join('employees', 'attendance_logs.employee_id', '=', 'employees.employee_id')
            ->update(['attendance_logs.employee_id' => DB::raw('employees.id')]);

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->change();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }
};
