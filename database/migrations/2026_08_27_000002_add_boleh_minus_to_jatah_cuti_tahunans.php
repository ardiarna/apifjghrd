<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jatah_cuti_tahunans', function (Blueprint $table) {
            $table->enum('boleh_minus', ['Y', 'N'])->default('N')->after('total_cuti');
        });
    }

    public function down(): void
    {
        Schema::table('jatah_cuti_tahunans', function (Blueprint $table) {
            $table->dropColumn('boleh_minus');
        });
    }
};
