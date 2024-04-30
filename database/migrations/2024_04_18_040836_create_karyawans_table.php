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
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nik')->nullable();
            $table->string('nomor_ktp');
            $table->date('tanggal_masuk')->nullable();
            $table->foreignId('agama_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('jabatan_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('divisi_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('alamat_ktp');
            $table->string('alamat_tinggal')->nullable();
            $table->string('telepon');
            $table->string('email')->nullable();
            $table->enum('kawin', ['Y', 'N'])->nullable();
            $table->foreignId('status_kerja_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('pendidikan_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('pendidikan_almamater')->nullable();
            $table->string('pendidikan_jurusan')->nullable();
            $table->enum('aktif', ['Y', 'N']);
            $table->string('nomor_kk')->nullable();
            $table->string('nomor_paspor')->nullable();
            $table->timestamps();
            $table->unique(['nama', 'nomor_ktp'], 'karyawans_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};
