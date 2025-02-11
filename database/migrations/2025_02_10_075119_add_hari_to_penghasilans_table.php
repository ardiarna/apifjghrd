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
            $table->decimal('hari', 4, 1, true)->default(0)->after('bulan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penghasilans', function (Blueprint $table) {
            $table->dropColumn('hari');
        });
    }
};
