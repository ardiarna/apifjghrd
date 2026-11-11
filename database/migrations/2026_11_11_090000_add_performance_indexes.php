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
        // 1. Cuti Dates (Index Kolom Tanggal)
        if (Schema::hasTable('cuti_dates')) {
            Schema::table('cuti_dates', function (Blueprint $table) {
                $table->index('tanggal', 'cuti_dates_tanggal_index');
            });
        }

        if (Schema::hasTable('cic_cuti_dates')) {
            Schema::table('cic_cuti_dates', function (Blueprint $table) {
                $table->index('tanggal', 'cic_cuti_dates_tanggal_index');
            });
        }

        if (Schema::hasTable('cuti_masal_dates')) {
            Schema::table('cuti_masal_dates', function (Blueprint $table) {
                $table->index('tanggal', 'cuti_masal_dates_tanggal_index');
            });
        }

        if (Schema::hasTable('cic_cuti_masal_dates')) {
            Schema::table('cic_cuti_masal_dates', function (Blueprint $table) {
                $table->index('tanggal', 'cic_cuti_masal_dates_tanggal_index');
            });
        }

        // 2. Overtimes & Medicals (Tanggal & Composite Index: tahun, bulan, karyawan_id)
        if (Schema::hasTable('overtimes')) {
            Schema::table('overtimes', function (Blueprint $table) {
                $table->index('tanggal', 'overtimes_tanggal_index');
                $table->index(['tahun', 'bulan', 'karyawan_id'], 'overtimes_thn_bln_karyawan_index');
            });
        }

        if (Schema::hasTable('medicals')) {
            Schema::table('medicals', function (Blueprint $table) {
                $table->index('tanggal', 'medicals_tanggal_index');
                $table->index(['tahun', 'bulan', 'karyawan_id'], 'medicals_thn_bln_karyawan_index');
            });
        }

        // 3. Potongans & Penghasilans (Tanggal & Composite Index: tahun, bulan, karyawan_id)
        if (Schema::hasTable('potongans')) {
            Schema::table('potongans', function (Blueprint $table) {
                $table->index('tanggal', 'potongans_tanggal_index');
                $table->index(['tahun', 'bulan', 'karyawan_id'], 'potongans_thn_bln_karyawan_index');
            });
        }

        if (Schema::hasTable('penghasilans')) {
            Schema::table('penghasilans', function (Blueprint $table) {
                $table->index('tanggal', 'penghasilans_tanggal_index');
                $table->index(['tahun', 'bulan', 'karyawan_id'], 'penghasilans_thn_bln_karyawan_index');
            });
        }

        // 4. Cutis & Cic Cutis (Tahun & Composite Index: karyawan_id, tahun)
        if (Schema::hasTable('cutis')) {
            Schema::table('cutis', function (Blueprint $table) {
                $table->index('tahun', 'cutis_tahun_index');
                $table->index(['karyawan_id', 'tahun'], 'cutis_karyawan_tahun_index');
            });
        }

        if (Schema::hasTable('cic_cutis')) {
            Schema::table('cic_cutis', function (Blueprint $table) {
                $table->index('tahun', 'cic_cutis_tahun_index');
                $table->index(['cic_karyawan_id', 'tahun'], 'cic_cutis_karyawan_tahun_index');
            });
        }

        // 5. Jatah Cuti Tahunans (Tahun Index)
        if (Schema::hasTable('jatah_cuti_tahunans')) {
            Schema::table('jatah_cuti_tahunans', function (Blueprint $table) {
                $table->index('tahun', 'jatah_cuti_tahunans_tahun_index');
            });
        }

        if (Schema::hasTable('cic_jatah_cuti_tahunans')) {
            Schema::table('cic_jatah_cuti_tahunans', function (Blueprint $table) {
                $table->index('tahun', 'cic_jatah_cuti_tahunans_tahun_index');
            });
        }

        // 6. Cuti Details (Composite Index: cuti_id, kategori)
        if (Schema::hasTable('cuti_details')) {
            Schema::table('cuti_details', function (Blueprint $table) {
                $table->index(['cuti_id', 'kategori'], 'cuti_details_cuti_kategori_index');
            });
        }

        if (Schema::hasTable('cic_cuti_details')) {
            Schema::table('cic_cuti_details', function (Blueprint $table) {
                $table->index(['cic_cuti_id', 'kategori'], 'cic_cuti_details_cuti_kategori_index');
            });
        }

        // 7. Keluarga Karyawans (Composite Index: karyawan_id, hubungan)
        if (Schema::hasTable('keluarga_karyawans')) {
            Schema::table('keluarga_karyawans', function (Blueprint $table) {
                $table->index(['karyawan_id', 'hubungan'], 'keluarga_karyawans_karyawan_hubungan_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('keluarga_karyawans')) {
            Schema::table('keluarga_karyawans', function (Blueprint $table) {
                $table->dropIndex('keluarga_karyawans_karyawan_hubungan_index');
            });
        }

        if (Schema::hasTable('cic_cuti_details')) {
            Schema::table('cic_cuti_details', function (Blueprint $table) {
                $table->dropIndex('cic_cuti_details_cuti_kategori_index');
            });
        }

        if (Schema::hasTable('cuti_details')) {
            Schema::table('cuti_details', function (Blueprint $table) {
                $table->dropIndex('cuti_details_cuti_kategori_index');
            });
        }

        if (Schema::hasTable('cic_jatah_cuti_tahunans')) {
            Schema::table('cic_jatah_cuti_tahunans', function (Blueprint $table) {
                $table->dropIndex('cic_jatah_cuti_tahunans_tahun_index');
            });
        }

        if (Schema::hasTable('jatah_cuti_tahunans')) {
            Schema::table('jatah_cuti_tahunans', function (Blueprint $table) {
                $table->dropIndex('jatah_cuti_tahunans_tahun_index');
            });
        }

        if (Schema::hasTable('cic_cutis')) {
            Schema::table('cic_cutis', function (Blueprint $table) {
                $table->dropIndex('cic_cutis_tahun_index');
                $table->dropIndex('cic_cutis_karyawan_tahun_index');
            });
        }

        if (Schema::hasTable('cutis')) {
            Schema::table('cutis', function (Blueprint $table) {
                $table->dropIndex('cutis_tahun_index');
                $table->dropIndex('cutis_karyawan_tahun_index');
            });
        }

        if (Schema::hasTable('penghasilans')) {
            Schema::table('penghasilans', function (Blueprint $table) {
                $table->dropIndex('penghasilans_tanggal_index');
                $table->dropIndex('penghasilans_thn_bln_karyawan_index');
            });
        }

        if (Schema::hasTable('potongans')) {
            Schema::table('potongans', function (Blueprint $table) {
                $table->dropIndex('potongans_tanggal_index');
                $table->dropIndex('potongans_thn_bln_karyawan_index');
            });
        }

        if (Schema::hasTable('medicals')) {
            Schema::table('medicals', function (Blueprint $table) {
                $table->dropIndex('medicals_tanggal_index');
                $table->dropIndex('medicals_thn_bln_karyawan_index');
            });
        }

        if (Schema::hasTable('overtimes')) {
            Schema::table('overtimes', function (Blueprint $table) {
                $table->dropIndex('overtimes_tanggal_index');
                $table->dropIndex('overtimes_thn_bln_karyawan_index');
            });
        }

        if (Schema::hasTable('cic_cuti_masal_dates')) {
            Schema::table('cic_cuti_masal_dates', function (Blueprint $table) {
                $table->dropIndex('cic_cuti_masal_dates_tanggal_index');
            });
        }

        if (Schema::hasTable('cuti_masal_dates')) {
            Schema::table('cuti_masal_dates', function (Blueprint $table) {
                $table->dropIndex('cuti_masal_dates_tanggal_index');
            });
        }

        if (Schema::hasTable('cic_cuti_dates')) {
            Schema::table('cic_cuti_dates', function (Blueprint $table) {
                $table->dropIndex('cic_cuti_dates_tanggal_index');
            });
        }

        if (Schema::hasTable('cuti_dates')) {
            Schema::table('cuti_dates', function (Blueprint $table) {
                $table->dropIndex('cuti_dates_tanggal_index');
            });
        }
    }
};

// -- 1. Cuti Dates (Index Kolom Tanggal)
// ALTER TABLE `cuti_dates` ADD INDEX `cuti_dates_tanggal_index` (`tanggal`);
// ALTER TABLE `cic_cuti_dates` ADD INDEX `cic_cuti_dates_tanggal_index` (`tanggal`);
// ALTER TABLE `cuti_masal_dates` ADD INDEX `cuti_masal_dates_tanggal_index` (`tanggal`);
// ALTER TABLE `cic_cuti_masal_dates` ADD INDEX `cic_cuti_masal_dates_tanggal_index` (`tanggal`);

// -- 2. Overtimes & Medicals (Tanggal & Composite: tahun, bulan, karyawan_id)
// ALTER TABLE `overtimes` ADD INDEX `overtimes_tanggal_index` (`tanggal`);
// ALTER TABLE `overtimes` ADD INDEX `overtimes_thn_bln_karyawan_index` (`tahun`, `bulan`, `karyawan_id`);

// ALTER TABLE `medicals` ADD INDEX `medicals_tanggal_index` (`tanggal`);
// ALTER TABLE `medicals` ADD INDEX `medicals_thn_bln_karyawan_index` (`tahun`, `bulan`, `karyawan_id`);

// -- 3. Potongans & Penghasilans (Tanggal & Composite: tahun, bulan, karyawan_id)
// ALTER TABLE `potongans` ADD INDEX `potongans_tanggal_index` (`tanggal`);
// ALTER TABLE `potongans` ADD INDEX `potongans_thn_bln_karyawan_index` (`tahun`, `bulan`, `karyawan_id`);

// ALTER TABLE `penghasilans` ADD INDEX `penghasilans_tanggal_index` (`tanggal`);
// ALTER TABLE `penghasilans` ADD INDEX `penghasilans_thn_bln_karyawan_index` (`tahun`, `bulan`, `karyawan_id`);

// -- 4. Cutis & Cic Cutis (Tahun & Composite: karyawan_id, tahun)
// ALTER TABLE `cutis` ADD INDEX `cutis_tahun_index` (`tahun`);
// ALTER TABLE `cutis` ADD INDEX `cutis_karyawan_tahun_index` (`karyawan_id`, `tahun`);

// ALTER TABLE `cic_cutis` ADD INDEX `cic_cutis_tahun_index` (`tahun`);
// ALTER TABLE `cic_cutis` ADD INDEX `cic_cutis_karyawan_tahun_index` (`cic_karyawan_id`, `tahun`);

// -- 5. Jatah Cuti Tahunans (Tahun)
// ALTER TABLE `jatah_cuti_tahunans` ADD INDEX `jatah_cuti_tahunans_tahun_index` (`tahun`);
// ALTER TABLE `cic_jatah_cuti_tahunans` ADD INDEX `cic_jatah_cuti_tahunans_tahun_index` (`tahun`);

// -- 6. Cuti Details (Composite: cuti_id, kategori)
// ALTER TABLE `cuti_details` ADD INDEX `cuti_details_cuti_kategori_index` (`cuti_id`, `kategori`);
// ALTER TABLE `cic_cuti_details` ADD INDEX `cic_cuti_details_cuti_kategori_index` (`cic_cuti_id`, `kategori`);

// -- 7. Keluarga Karyawans (Composite: karyawan_id, hubungan)
// ALTER TABLE `keluarga_karyawans` ADD INDEX `keluarga_karyawans_karyawan_hubungan_index` (`karyawan_id`, `hubungan`);

// -- 8. Catat riwayat ke tabel migrations Laravel agar tidak dieksekusi ulang saat php artisan migrate dijalankan
// INSERT INTO `migrations` (`migration`, `batch`)
// SELECT '2026_11_11_090000_add_performance_indexes', COALESCE(MAX(`batch`), 0) + 1
// FROM `migrations`;

