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
        Schema::create('uang_phks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->smallInteger('tahun', false, true);
            $table->integer('kompensasi', false, true)->default(0);
            $table->integer('uang_pisah', false, true)->default(0);
            $table->integer('pesangon', false, true)->default(0);
            $table->integer('masa_kerja', false, true)->default(0);
            $table->integer('penggantian_hak', false, true)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uang_phks');
    }
};
