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
        Schema::create('phks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('tanggal_awal');
            $table->date('tanggal_akhir');
            $table->foreignId('status_kerja_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('status_phk_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phks');
    }
};
