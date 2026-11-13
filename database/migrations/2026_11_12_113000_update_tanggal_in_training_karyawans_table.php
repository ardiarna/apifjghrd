<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Equivalent SQL:
     * ALTER TABLE `training_karyawans` CHANGE COLUMN `tanggal` `tanggal_awal` DATE NULL DEFAULT NULL;
     * ALTER TABLE `training_karyawans` ADD COLUMN `tanggal_akhir` DATE NULL DEFAULT NULL AFTER `tanggal_awal`;
     */
    public function up(): void
    {
        Schema::table('training_karyawans', function (Blueprint $table) {
            $table->renameColumn('tanggal', 'tanggal_awal');
        });

        Schema::table('training_karyawans', function (Blueprint $table) {
            $table->date('tanggal_akhir')->nullable()->after('tanggal_awal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Equivalent SQL:
     * ALTER TABLE `training_karyawans` DROP COLUMN `tanggal_akhir`;
     * ALTER TABLE `training_karyawans` CHANGE COLUMN `tanggal_awal` `tanggal` DATE NULL DEFAULT NULL;
     */
    public function down(): void
    {
        Schema::table('training_karyawans', function (Blueprint $table) {
            $table->dropColumn('tanggal_akhir');
        });

        Schema::table('training_karyawans', function (Blueprint $table) {
            $table->renameColumn('tanggal_awal', 'tanggal');
        });
    }
};
