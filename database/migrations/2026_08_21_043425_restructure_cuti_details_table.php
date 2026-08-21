<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuti_details', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_khusus',
                'jenis_unpaid',
                'hak_cuti',
                'sudah_diambil',
                'cuti_masal',
                'belum_diambil',
                'sisa_hak_cuti',
                'sisa_cuti_tahun_lalu',
                'total_hak_cuti'
            ]);
        });
        
        Schema::table('cuti_details', function (Blueprint $table) {
            $table->bigInteger('jenis_cuti_khusus_id')->unsigned()->nullable()->after('kategori');
            $table->string('jenis_unpaid', 50)->nullable()->after('jenis_cuti_khusus_id');
        });
    }

    public function down(): void
    {
        // No down needed for now
    }
};
