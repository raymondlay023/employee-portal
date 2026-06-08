<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jpayroll_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('shift_date');
            $table->tinyInteger('alpha')->default(0);   // ABS — absent without leave (Alpa)
            $table->tinyInteger('telat')->default(0);   // LT  — late arrival (Telat)
            $table->tinyInteger('izin')->default(0);    // CT  — approved leave / cuti
            $table->tinyInteger('op')->default(0);      // OP  — other permitted absence
            $table->tinyInteger('hos')->default(0);     // HOS — hospitalized / sick leave
            $table->tinyInteger('wa')->default(0);      // WA  — work accident
            $table->tinyInteger('hoswa')->default(0);   // HOSWA — combined HOS + WA
            $table->timestamps();

            // One row per employee per shift date — safe to re-sync (upsert key)
            $table->unique(['employee_id', 'shift_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jpayroll_attendances');
    }
};
