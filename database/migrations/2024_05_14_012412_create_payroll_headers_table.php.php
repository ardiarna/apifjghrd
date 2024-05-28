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
        Schema::create('payroll_headers', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_awal');
            $table->date('tanggal_akhir');
            $table->smallInteger('tahun', false, true);
            $table->smallInteger('bulan', false, true);
            $table->integer('gaji', false, true)->default(0);
            $table->integer('uang_makan_jumlah', false, true)->default(0);
            $table->integer('overtime_fjg', false, true)->default(0);
            $table->integer('overtime_cus', false, true)->default(0);
            $table->integer('medical', false, true)->default(0);
            $table->integer('thr', false, true)->default(0);
            $table->integer('bonus', false, true)->default(0);
            $table->integer('insentif', false, true)->default(0);
            $table->integer('telkomsel', false, true)->default(0);
            $table->integer('lain', false, true)->default(0);
            $table->integer('pot_25_hari', false, true)->default(0);
            $table->integer('pot_25_jumlah', false, true)->default(0);
            $table->integer('pot_telepon', false, true)->default(0);
            $table->integer('pot_bensin', false, true)->default(0);
            $table->integer('pot_kas', false, true)->default(0);
            $table->integer('pot_cicilan', false, true)->default(0);
            $table->integer('pot_bpjs', false, true)->default(0);
            $table->integer('pot_cuti', false, true)->default(0);
            $table->integer('pot_lain', false, true)->default(0);
            $table->integer('total_diterima', false, true)->default(0);
            $table->enum('dikunci', ['Y', 'N'])->default('N');
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['tahun', 'bulan'], 'payroll_headers_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_headers');
    }
};
