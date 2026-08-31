<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cic_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cic_karyawan_id')->constrained('cic_karyawans')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('jenis_form', ['CUTI', 'IJIN', 'CUTI_MASAL'])->default('CUTI');
            $table->date('tanggal_kembali')->nullable();
            $table->integer('tahun');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cic_cutis');
    }
};
