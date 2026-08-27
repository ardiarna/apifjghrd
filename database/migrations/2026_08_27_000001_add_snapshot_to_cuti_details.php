<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuti_details', function (Blueprint $table) {
            $table->integer('snap_total_hak_cuti')->nullable()->after('lama_hari');
            $table->integer('snap_sudah_diambil')->nullable()->after('snap_total_hak_cuti');
            $table->integer('snap_cuti_masal')->nullable()->after('snap_sudah_diambil');
        });
    }

    public function down(): void
    {
        Schema::table('cuti_details', function (Blueprint $table) {
            $table->dropColumn(['snap_total_hak_cuti', 'snap_sudah_diambil', 'snap_cuti_masal']);
        });
    }
};
