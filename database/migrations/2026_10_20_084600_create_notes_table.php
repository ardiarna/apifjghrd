<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->text('teks');
            $table->timestamps();
        });

        DB::table('notes')->insert([
            'kode' => 'NOTE_SLIP_PHK',
            'nama' => 'Note Slip PHK',
            'teks' => "Hak Karyawan sesuai dengan Peraturan Pemerintah No.35 Tahun 2021 yang berlaku &\ntercantum dalam Peraturan Perusahaan (PP) Pasal 31 (tiga puluh satu) tentang Hak Karyawan\nserta Kompensasi yang sudah dapat Pengesahan dari Kementrian Ketenagakerjaan RI",
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
