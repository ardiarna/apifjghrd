<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_cuti_khususs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nama', 100);
            $table->integer('lama_hari');
            $table->string('satuan', 10)->default('hari');
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // Seed data default
        DB::table('jenis_cuti_khususs')->insert([
            ['nama' => 'Melahirkan',    'lama_hari' => 3,  'satuan' => 'bulan', 'urutan' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama' => 'Menikah',       'lama_hari' => 3,  'satuan' => 'hari',  'urutan' => 2, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama' => 'Baptis',        'lama_hari' => 3,  'satuan' => 'hari',  'urutan' => 3, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama' => 'Khitanan',      'lama_hari' => 3,  'satuan' => 'hari',  'urutan' => 4, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama' => 'Anak Menikah',  'lama_hari' => 3,  'satuan' => 'hari',  'urutan' => 5, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama' => 'Ibadah Haji',   'lama_hari' => 40, 'satuan' => 'hari',  'urutan' => 6, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama' => 'Ibadah Umroh',  'lama_hari' => 14, 'satuan' => 'hari',  'urutan' => 7, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama' => 'Sakit Lama',    'lama_hari' => 30, 'satuan' => 'hari',  'urutan' => 8, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_cuti_khususs');
    }
};
