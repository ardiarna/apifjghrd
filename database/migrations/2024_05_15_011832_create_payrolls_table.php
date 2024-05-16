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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_header_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('karyawan_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('gaji', false, true);
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
            $table->integer('pot_cuti', false, true);
            $table->integer('pot_lain', false, true);
            $table->integer('total_diterima', false, true);
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['payroll_header_id', 'karyawan_id'], 'payrolls_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
