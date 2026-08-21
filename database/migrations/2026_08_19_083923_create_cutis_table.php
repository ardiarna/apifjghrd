<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('jenis_form', ['CUTI', 'IJIN', 'UNPAID_LEAVE', 'CUTI_MASAL']);
            $table->string('keperluan');
            $table->date('tanggal_kembali')->nullable();
            $table->integer('tahun');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutis');
    }
};
