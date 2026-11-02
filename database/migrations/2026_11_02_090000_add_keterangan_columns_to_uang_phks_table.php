<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `uang_phks`
              ADD COLUMN `ket_kompensasi` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `keterangan`,
              ADD COLUMN `ket_uang_pisah` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `ket_kompensasi`,
              MODIFY COLUMN `ket_pesangon` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `ket_uang_pisah`,
              ADD COLUMN `ket_masa_kerja` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `ket_pesangon`
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE `uang_phks`
              DROP COLUMN `ket_kompensasi`,
              DROP COLUMN `ket_uang_pisah`,
              DROP COLUMN `ket_masa_kerja`,
              MODIFY COLUMN `ket_pesangon` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `keterangan`
        ");
    }
};
