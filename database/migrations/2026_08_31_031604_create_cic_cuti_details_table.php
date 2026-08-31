<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cic_cuti_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cic_cuti_id')->constrained('cic_cutis')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('kategori', ['TAHUNAN', 'KHUSUS', 'UNPAID', 'GANTI_HARI_LIBUR', 'IJIN', 'CUTI_MASAL']);
            $table->foreignId('jenis_cuti_khusus_id')->nullable()->constrained('cic_jenis_cuti_khususs')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('jenis_unpaid', ['SUDAH_HABIS', 'SEBELUM_TIMBUL'])->nullable();
            $table->text('keterangan');
            $table->integer('lama_hari')->nullable();
            $table->integer('snap_total_hak_cuti')->nullable();
            $table->integer('snap_sudah_diambil')->nullable();
            $table->integer('snap_cuti_masal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cic_cuti_details');
    }
};
