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
        Schema::table('potongans', function (Blueprint $table) {
            $table->enum('jenis', ['TB', 'TP', 'BN', 'KS', 'CC', 'BP', 'UL', 'KJ', 'LL'])
                ->comment('TB = Telat, TP = Telp, BN = Bensin, KS = Kas, CC = Cicilan, BP = Bpjs, UL = Cuti, KJ = Kompensasi, LL = Lain2')
                ->change();
            $table->decimal('hari', 4, 1, true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('potongans', function (Blueprint $table) {
            $table->enum('jenis', ['TB', 'TP', 'BN', 'KS', 'CC', 'BP', 'UL', 'LL'])
                ->comment('TB = Telat, TP = Telp, BN = Bensin, KS = Kas, CC = Cicilan, BP = Bpjs, UL = Cuti, LL = Lain2');
            $table->smallInteger('hari', false, true);
        });
    }
};
