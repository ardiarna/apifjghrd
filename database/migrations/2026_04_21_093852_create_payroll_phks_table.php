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
        Schema::create('payroll_phks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('tanggal_awal');
            $table->date('tanggal_akhir');
            $table->smallInteger('tahun', false, true);
            $table->smallInteger('bulan', false, true);
            $table->integer('gaji', false, true);
            $table->integer('kenaikan_gaji', false, true)->default(0);
            $table->enum('makan_harian', ['Y', 'N']);
            $table->integer('hari_makan', false, true);
            $table->integer('uang_makan_harian', false, true);
            $table->integer('uang_makan_jumlah', false, true);
            $table->integer('overtime_fjg', false, true);
            $table->integer('overtime_cus', false, true);
            $table->integer('medical', false, true);
            $table->integer('thr', false, true);
            $table->integer('bonus', false, true);
            $table->integer('insentif', false, true);
            $table->integer('telkomsel', false, true);
            $table->integer('lain', false, true);
            $table->integer('pot_25_hari', false, true);
            $table->integer('pot_25_jumlah', false, true);
            $table->integer('pot_telepon', false, true);
            $table->integer('pot_bensin', false, true);
            $table->integer('pot_kas', false, true);
            $table->integer('pot_cicilan', false, true);
            $table->integer('pot_bpjs', false, true);
            $table->integer('pot_cuti_hari', false, true)->default(0);
            $table->integer('pot_cuti_jumlah', false, true)->default(0);
            $table->decimal('pot_kompensasi_jam', 4, 1, true)->default(0);
            $table->integer('pot_kompensasi_jumlah', false, true)->default(0);
            $table->integer('pot_lain', false, true);
            $table->integer('total_diterima', false, true);
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['tahun', 'bulan', 'karyawan_id'], 'payroll_phks_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_phks');
    }
};
