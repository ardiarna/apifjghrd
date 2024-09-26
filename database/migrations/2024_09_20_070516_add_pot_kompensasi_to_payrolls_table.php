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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->integer('pot_cuti_hari', false, true)->default(0)->after('pot_bpjs');
            $table->decimal('pot_kompensasi_jam', 4, 1, true)->default(0)->after('pot_cuti');
            $table->integer('pot_kompensasi_jumlah', false, true)->default(0)->after('pot_kompensasi_jam');
            $table->renameColumn('pot_cuti', 'pot_cuti_jumlah');
        });

        Schema::table('payroll_headers', function (Blueprint $table) {
            $table->integer('pot_cuti_hari', false, true)->default(0)->after('pot_bpjs');
            $table->decimal('pot_kompensasi_jam', 4, 1, true)->default(0)->after('pot_cuti');
            $table->integer('pot_kompensasi_jumlah', false, true)->default(0)->after('pot_kompensasi_jam');
            $table->renameColumn('pot_cuti', 'pot_cuti_jumlah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('pot_cuti_hari');
            $table->dropColumn('pot_kompensasi_jam');
            $table->dropColumn('pot_kompensasi_jumlah');
            $table->renameColumn('pot_cuti_jumlah', 'pot_cuti');
        });

        Schema::table('payroll_headers', function (Blueprint $table) {
            $table->dropColumn('pot_cuti_hari');
            $table->dropColumn('pot_kompensasi_jam');
            $table->dropColumn('pot_kompensasi_jumlah');
            $table->renameColumn('pot_cuti_jumlah', 'pot_cuti');
        });
    }
};
