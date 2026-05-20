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
        Schema::create('daily_work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('start_time'); // e.g. "07:30"
            $table->string('end_time');   // e.g. "08:30"
            $table->string('activity');   // e.g. "Development"
            $table->text('remarks')->nullable(); // detailed description
            $table->timestamps();

            $table->unique(['user_id', 'date', 'start_time']); // prevent double entry
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_work_logs');
    }
};
