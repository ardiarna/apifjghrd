<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Penambahan Indeks pada Kolom tanggal dan (tahun, bulan) di Tabel oncall_customers
 * 
 * Equivalent SQL (UP):
 * -------------------------------------------------------------------------
 * ALTER TABLE `oncall_customers` ADD INDEX `oncall_customers_tanggal_index` (`tanggal`);
 * ALTER TABLE `oncall_customers` ADD INDEX `oncall_customers_thn_bln_index` (`tahun`, `bulan`);
 * -------------------------------------------------------------------------
 * 
 * Equivalent SQL (DOWN):
 * -------------------------------------------------------------------------
 * ALTER TABLE `oncall_customers` DROP INDEX `oncall_customers_thn_bln_index`;
 * ALTER TABLE `oncall_customers` DROP INDEX `oncall_customers_tanggal_index`;
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('oncall_customers')) {
            Schema::table('oncall_customers', function (Blueprint $table) {
                $table->index('tanggal', 'oncall_customers_tanggal_index');
                $table->index(['tahun', 'bulan'], 'oncall_customers_thn_bln_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('oncall_customers')) {
            Schema::table('oncall_customers', function (Blueprint $table) {
                $table->dropIndex('oncall_customers_thn_bln_index');
                $table->dropIndex('oncall_customers_tanggal_index');
            });
        }
    }
};
