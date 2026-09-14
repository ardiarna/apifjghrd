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
        Schema::create('cuti_masals', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun')->nullable();
            $table->string('keterangan')->nullable();
            $table->date('tanggal_kembali')->nullable();
            $table->integer('lama_hari')->nullable();
            $table->timestamps();
        });

        Schema::table('cutis', function (Blueprint $table) {
            $table->unsignedBigInteger('cuti_masal_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropColumn('cuti_masal_id');
        });
        Schema::dropIfExists('cuti_masals');
    }
};
