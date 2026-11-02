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
        Schema::table('uang_phks', function (Blueprint $table) {
            $table->dropColumn('penggantian_hak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uang_phks', function (Blueprint $table) {
            $table->integer('penggantian_hak', false, true)->default(0)->after('masa_kerja');
        });
    }
};
