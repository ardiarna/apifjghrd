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
        Schema::table('payroll_phks', function (Blueprint $table) {
            $table->date('tgl_gaji_awal')->nullable()->after('keterangan');
            $table->date('tgl_gaji_akhir')->nullable()->after('tgl_gaji_awal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_phks', function (Blueprint $table) {
            $table->dropColumn(['tgl_gaji_awal', 'tgl_gaji_akhir']);
        });
    }
};
