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
        Schema::table('uang_phks', function (Blueprint $table) {
            $table->integer('sisa_cuti_hari', false, true)->default(0)->after('penggantian_hak');
            $table->integer('sisa_cuti_jumlah', false, true)->default(0)->after('sisa_cuti_hari');
            $table->integer('lain', false, true)->default(0)->after('sisa_cuti_jumlah');
            $table->integer('pot_kas', false, true)->default(0)->after('lain');
            $table->integer('pot_cuti_hari', false, true)->default(0)->after('pot_kas');
            $table->integer('pot_cuti_jumlah', false, true)->default(0)->after('pot_cuti_hari');
            $table->integer('pot_lain', false, true)->default(0)->after('pot_cuti_jumlah');
            $table->string('keterangan')->nullable()->after('pot_lain');
            $table->unique(['karyawan_id', 'tahun'], 'uang_phks_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uang_phks', function (Blueprint $table) {
            $table->dropUnique('uang_phks_unique');
            $table->dropColumn(['sisa_cuti_hari', 'sisa_cuti_jumlah', 'lain']);
            $table->dropColumn(['pot_kas', 'pot_cuti_hari', 'pot_cuti_jumlah', 'pot_lain', 'keterangan']);
        });
    }
};

/*
ALTER TABLE uang_phks
ADD COLUMN sisa_cuti_hari INT UNSIGNED NOT NULL DEFAULT 0 AFTER penggantian_hak,
ADD COLUMN sisa_cuti_jumlah INT UNSIGNED NOT NULL DEFAULT 0 AFTER sisa_cuti_hari,
ADD COLUMN lain INT UNSIGNED NOT NULL DEFAULT 0 AFTER sisa_cuti_jumlah,
ADD COLUMN pot_kas INT UNSIGNED NOT NULL DEFAULT 0 AFTER lain,
ADD COLUMN pot_cuti_hari INT UNSIGNED NOT NULL DEFAULT 0 AFTER pot_kas,
ADD COLUMN pot_cuti_jumlah INT UNSIGNED NOT NULL DEFAULT 0 AFTER pot_cuti_hari,
ADD COLUMN pot_lain INT UNSIGNED NOT NULL DEFAULT 0 AFTER pot_cuti_jumlah,
ADD COLUMN keterangan VARCHAR(255) NULL AFTER pot_lain,
ADD UNIQUE INDEX uang_phks_unique (karyawan_id, tahun);
*/
