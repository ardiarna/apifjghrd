<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cic_jatah_cuti_tahunans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cic_karyawan_id')->constrained('cic_karyawans')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('tahun', 4);
            $table->integer('jumlah_cuti')->default(0);
            $table->integer('plus_tahun_lalu')->default(0);
            $table->integer('min_tahun_lalu')->default(0);
            $table->integer('total_cuti')->default(0);
            $table->enum('boleh_minus', ['Y', 'N'])->default('N');
            $table->timestamps();
            $table->unique(['cic_karyawan_id', 'tahun'], 'cic_jatah_cuti_tahunans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cic_jatah_cuti_tahunans');
    }
};
