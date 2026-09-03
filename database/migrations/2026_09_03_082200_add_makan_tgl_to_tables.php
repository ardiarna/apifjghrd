<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penghasilans', function (Blueprint $table) {
            $table->date('tgl_awal')->nullable();
            $table->date('tgl_akhir')->nullable();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->date('makan_tgl_awal')->nullable();
            $table->date('makan_tgl_akhir')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('penghasilans', function (Blueprint $table) {
            $table->dropColumn('tgl_awal');
            $table->dropColumn('tgl_akhir');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('makan_tgl_awal');
            $table->dropColumn('makan_tgl_akhir');
        });
    }
};
