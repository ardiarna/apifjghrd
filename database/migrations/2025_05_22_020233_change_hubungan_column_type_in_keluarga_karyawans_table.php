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
        Schema::table('keluarga_karyawans', function (Blueprint $table) {
            $table->string('hubungan', 1)
                ->comment('S:Suami, I:Istri, A:Anak, M:Menantu, C:Cucu, O:Orangtua, T:Mertua, F:Famili Lain')
                ->change();
            // ALTER TABLE keluarga_karyawans
            // MODIFY COLUMN hubungan VARCHAR(1)
            // COMMENT 'S:Suami, I:Istri, A:Anak, M:Menantu, C:Cucu, O:Orangtua, T:Mertua, F:Famili Lain';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keluarga_karyawans', function (Blueprint $table) {
            $table->enum('hubungan', ['S', 'I', 'A'])->change();
        });
    }
};
