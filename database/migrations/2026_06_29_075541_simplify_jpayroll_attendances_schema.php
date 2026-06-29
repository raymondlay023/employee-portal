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
        Schema::table('jpayroll_attendances', function (Blueprint $table) {
            $table->dropColumn(['op', 'hos', 'wa', 'hoswa']);
            $table->tinyInteger('sakit')->default(0)->after('izin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jpayroll_attendances', function (Blueprint $table) {
            $table->dropColumn('sakit');
            $table->tinyInteger('op')->default(0);
            $table->tinyInteger('hos')->default(0);
            $table->tinyInteger('wa')->default(0);
            $table->tinyInteger('hoswa')->default(0);
        });
    }
};
