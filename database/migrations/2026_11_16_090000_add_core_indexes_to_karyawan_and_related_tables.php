<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Penambahan Indeks Kunci pada Tabel Karyawan dan Relasi Terkait
 * 
 * Equivalent SQL (UP):
 * -------------------------------------------------------------------------
 * -- 1. Tabel karyawans
 * ALTER TABLE `karyawans` ADD INDEX `karyawans_aktif_staf_index` (`aktif`, `staf`);
 * ALTER TABLE `karyawans` ADD INDEX `karyawans_tanggal_lahir_index` (`tanggal_lahir`);
 * ALTER TABLE `karyawans` ADD INDEX `karyawans_area_id_aktif_index` (`area_id`, `aktif`);
 * ALTER TABLE `karyawans` ADD INDEX `karyawans_status_kerja_id_aktif_index` (`status_kerja_id`, `aktif`);
 * 
 * -- 2. Tabel training_karyawans
 * ALTER TABLE `training_karyawans` ADD INDEX `training_karyawans_karyawan_tgl_awal_index` (`karyawan_id`, `tanggal_awal`);
 * 
 * -- 3. Tabel perjanjian_kerjas
 * ALTER TABLE `perjanjian_kerjas` ADD INDEX `perjanjian_kerjas_karyawan_tgl_awal_index` (`karyawan_id`, `tanggal_awal`);
 * 
 * -- 4. Tabel cic_karyawans
 * ALTER TABLE `cic_karyawans` ADD INDEX `cic_karyawans_aktif_staf_index` (`aktif`, `staf`);
 * ALTER TABLE `cic_karyawans` ADD INDEX `cic_karyawans_tanggal_lahir_index` (`tanggal_lahir`);
 * ALTER TABLE `cic_karyawans` ADD INDEX `cic_karyawans_area_id_aktif_index` (`area_id`, `aktif`);
 * 
 * -- Catatan riwayat migration (jika dieksekusi manual):
 * INSERT INTO `migrations` (`migration`, `batch`)
 * SELECT '2026_11_16_090000_add_core_indexes_to_karyawan_and_related_tables', COALESCE(MAX(`batch`), 0) + 1
 * FROM `migrations`;
 * -------------------------------------------------------------------------
 * 
 * Equivalent SQL (DOWN):
 * -------------------------------------------------------------------------
 * ALTER TABLE `karyawans` DROP INDEX `karyawans_aktif_staf_index`;
 * ALTER TABLE `karyawans` DROP INDEX `karyawans_tanggal_lahir_index`;
 * ALTER TABLE `karyawans` DROP INDEX `karyawans_area_id_aktif_index`;
 * ALTER TABLE `karyawans` DROP INDEX `karyawans_status_kerja_id_aktif_index`;
 * ALTER TABLE `training_karyawans` DROP INDEX `training_karyawans_karyawan_tgl_awal_index`;
 * ALTER TABLE `perjanjian_kerjas` DROP INDEX `perjanjian_kerjas_karyawan_tgl_awal_index`;
 * ALTER TABLE `cic_karyawans` DROP INDEX `cic_karyawans_aktif_staf_index`;
 * ALTER TABLE `cic_karyawans` DROP INDEX `cic_karyawans_tanggal_lahir_index`;
 * ALTER TABLE `cic_karyawans` DROP INDEX `cic_karyawans_area_id_aktif_index`;
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Indeks pada tabel karyawans
        if (Schema::hasTable('karyawans')) {
            Schema::table('karyawans', function (Blueprint $table) {
                $table->index(['aktif', 'staf'], 'karyawans_aktif_staf_index');
                $table->index('tanggal_lahir', 'karyawans_tanggal_lahir_index');
                $table->index(['area_id', 'aktif'], 'karyawans_area_id_aktif_index');
                $table->index(['status_kerja_id', 'aktif'], 'karyawans_status_kerja_id_aktif_index');
            });
        }

        // 2. Indeks pada tabel training_karyawans
        if (Schema::hasTable('training_karyawans')) {
            Schema::table('training_karyawans', function (Blueprint $table) {
                $table->index(['karyawan_id', 'tanggal_awal'], 'training_karyawans_karyawan_tgl_awal_index');
            });
        }

        // 3. Indeks pada tabel perjanjian_kerjas
        if (Schema::hasTable('perjanjian_kerjas')) {
            Schema::table('perjanjian_kerjas', function (Blueprint $table) {
                $table->index(['karyawan_id', 'tanggal_awal'], 'perjanjian_kerjas_karyawan_tgl_awal_index');
            });
        }

        // 4. Indeks pada tabel cic_karyawans
        if (Schema::hasTable('cic_karyawans')) {
            Schema::table('cic_karyawans', function (Blueprint $table) {
                $table->index(['aktif', 'staf'], 'cic_karyawans_aktif_staf_index');
                $table->index('tanggal_lahir', 'cic_karyawans_tanggal_lahir_index');
                $table->index(['area_id', 'aktif'], 'cic_karyawans_area_id_aktif_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cic_karyawans')) {
            Schema::table('cic_karyawans', function (Blueprint $table) {
                $table->dropIndex('cic_karyawans_aktif_staf_index');
                $table->dropIndex('cic_karyawans_tanggal_lahir_index');
                $table->dropIndex('cic_karyawans_area_id_aktif_index');
            });
        }

        if (Schema::hasTable('perjanjian_kerjas')) {
            Schema::table('perjanjian_kerjas', function (Blueprint $table) {
                $table->dropIndex('perjanjian_kerjas_karyawan_tgl_awal_index');
            });
        }

        if (Schema::hasTable('training_karyawans')) {
            Schema::table('training_karyawans', function (Blueprint $table) {
                $table->dropIndex('training_karyawans_karyawan_tgl_awal_index');
            });
        }

        if (Schema::hasTable('karyawans')) {
            Schema::table('karyawans', function (Blueprint $table) {
                $table->dropIndex('karyawans_aktif_staf_index');
                $table->dropIndex('karyawans_tanggal_lahir_index');
                $table->dropIndex('karyawans_area_id_aktif_index');
                $table->dropIndex('karyawans_status_kerja_id_aktif_index');
            });
        }
    }
};
