<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cuti_details', function (Blueprint $table) {
            $table->integer('sisa_cuti_tahun_lalu')->nullable()->after('hak_cuti');
            $table->integer('total_hak_cuti')->nullable()->after('sisa_cuti_tahun_lalu');
        });
    }

    public function down()
    {
        Schema::table('cuti_details', function (Blueprint $table) {
            $table->dropColumn(['sisa_cuti_tahun_lalu', 'total_hak_cuti']);
        });
    }
};
