<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Penambahan Indeks pada Kolom tahun di Tabel uang_phks, cuti_masals, dan cic_cuti_masals
 * 
 * Equivalent SQL (UP):
 * -------------------------------------------------------------------------
 * ALTER TABLE `uang_phks` ADD INDEX `uang_phks_tahun_index` (`tahun`);
 * ALTER TABLE `cuti_masals` ADD INDEX `cuti_masals_tahun_index` (`tahun`);
 * ALTER TABLE `cic_cuti_masals` ADD INDEX `cic_cuti_masals_tahun_index` (`tahun`);
 * -------------------------------------------------------------------------
 * 
 * Equivalent SQL (DOWN):
 * -------------------------------------------------------------------------
 * ALTER TABLE `uang_phks` DROP INDEX `uang_phks_tahun_index`;
 * ALTER TABLE `cuti_masals` DROP INDEX `cuti_masals_tahun_index`;
 * ALTER TABLE `cic_cuti_masals` DROP INDEX `cic_cuti_masals_tahun_index`;
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('uang_phks')) {
            Schema::table('uang_phks', function (Blueprint $table) {
                $table->index('tahun', 'uang_phks_tahun_index');
            });
        }

        if (Schema::hasTable('cuti_masals')) {
            Schema::table('cuti_masals', function (Blueprint $table) {
                $table->index('tahun', 'cuti_masals_tahun_index');
            });
        }

        if (Schema::hasTable('cic_cuti_masals')) {
            Schema::table('cic_cuti_masals', function (Blueprint $table) {
                $table->index('tahun', 'cic_cuti_masals_tahun_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cic_cuti_masals')) {
            Schema::table('cic_cuti_masals', function (Blueprint $table) {
                $table->dropIndex('cic_cuti_masals_tahun_index');
            });
        }

        if (Schema::hasTable('cuti_masals')) {
            Schema::table('cuti_masals', function (Blueprint $table) {
                $table->dropIndex('cuti_masals_tahun_index');
            });
        }

        if (Schema::hasTable('uang_phks')) {
            Schema::table('uang_phks', function (Blueprint $table) {
                $table->dropIndex('uang_phks_tahun_index');
            });
        }
    }
};
