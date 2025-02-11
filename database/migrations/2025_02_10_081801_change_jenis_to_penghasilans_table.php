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
            $table->enum('jenis', ['AB', 'HR', 'BN', 'IN', 'TK', 'KG', 'LL'])
                ->comment('AB = Kehadiran, HR = THR, BN = Bonus, IN = Insentif, TK = Telkom, KG = Kenaikan Gaji, LL = Lain2')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penghasilans', function (Blueprint $table) {
            $table->enum('jenis', ['AB', 'HR', 'BN', 'IN', 'TK', 'LL'])
                ->comment('AB = Kehadiran, HR = THR, BN = Bonus, IN = Insentif, TK = Telkom, LL = Lain2')
                ->change();
        });
    }
};
