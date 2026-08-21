<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuti_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuti_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('kategori', ['TAHUNAN', 'KHUSUS', 'UNPAID', 'GANTI_HARI_LIBUR', 'IJIN']);
            $table->string('jenis_khusus')->nullable();
            $table->string('jenis_unpaid')->nullable();
            $table->string('keterangan')->nullable();
            $table->integer('lama_hari')->nullable();
            
            // Snapshot for TAHUNAN
            $table->integer('hak_cuti')->nullable();
            $table->integer('sudah_diambil')->nullable();
            $table->integer('cuti_masal')->nullable();
            $table->integer('belum_diambil')->nullable();
            $table->integer('sisa_hak_cuti')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuti_details');
    }
};
