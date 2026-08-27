<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jatah_cuti_tahunans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('karyawan_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('tahun', 4);
            $table->integer('jumlah_cuti')->default(0);
            $table->integer('plus_tahun_lalu')->default(0);
            $table->integer('min_tahun_lalu')->default(0);
            $table->integer('total_cuti')->default(0);
            $table->timestamps();
            $table->unique(['karyawan_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jatah_cuti_tahunans');
    }
};
