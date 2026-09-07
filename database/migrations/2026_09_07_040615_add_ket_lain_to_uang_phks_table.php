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
        Schema::table('uang_phks', function (Blueprint $table) {
            $table->string('ket_lain')->nullable()->after('keterangan');
            $table->string('ket_pot_lain')->nullable()->after('ket_lain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uang_phks', function (Blueprint $table) {
            $table->dropColumn(['ket_lain', 'ket_pot_lain']);
        });
    }
};
