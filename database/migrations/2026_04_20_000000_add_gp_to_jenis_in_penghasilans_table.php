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
        Schema::table('penghasilans', function (Blueprint $table) {
            $table->enum('jenis', ['GP', 'AB', 'HR', 'BN', 'IN', 'TK', 'KG', 'LL'])
                ->comment('GP = Gaji Pokok, AB = Kehadiran, HR = THR, BN = Bonus, IN = Insentif, TK = Telkom, KG = Kenaikan Gaji, LL = Lain2')
                ->change();
        });
    }

    // ALTER TABLE `penghasilans` CHANGE `jenis` `jenis` ENUM('GP','AB','HR','BN','IN','TK','KG','LL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'GP = Gaji Pokok, AB = Kehadiran, HR = THR, BN = Bonus, IN = Insentif, TK = Telkom, KG = Kenaikan Gaji, LL = Lain2'; 

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penghasilans', function (Blueprint $table) {
            $table->enum('jenis', ['AB', 'HR', 'BN', 'IN', 'TK', 'KG', 'LL'])
                ->comment('AB = Kehadiran, HR = THR, BN = Bonus, IN = Insentif, TK = Telkom, KG = Kenaikan Gaji, LL = Lain2')
                ->change();
        });
    }
};
