<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Penambahan Indeks pada Kolom tanggal_akhir di Tabel phks
 * 
 * Equivalent SQL (UP):
 * -------------------------------------------------------------------------
 * ALTER TABLE `phks` ADD INDEX `phks_tanggal_akhir_index` (`tanggal_akhir`);
 * -------------------------------------------------------------------------
 * 
 * Equivalent SQL (DOWN):
 * -------------------------------------------------------------------------
 * ALTER TABLE `phks` DROP INDEX `phks_tanggal_akhir_index`;
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('phks')) {
            Schema::table('phks', function (Blueprint $table) {
                $table->index('tanggal_akhir', 'phks_tanggal_akhir_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('phks')) {
            Schema::table('phks', function (Blueprint $table) {
                $table->dropIndex('phks_tanggal_akhir_index');
            });
        }
    }
};
