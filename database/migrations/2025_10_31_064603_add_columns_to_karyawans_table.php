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
        Schema::table('karyawans', function (Blueprint $table) {
            $table->foreignId('uang_phk_id')
                ->after('phk_id')->nullable()
                ->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropColumn('uang_phk_id');
        });
    }
};

/*
ALTER TABLE karyawans
  ADD CONSTRAINT karyawans_uang_phk_id_foreign FOREIGN KEY (uang_phk_id) REFERENCES uang_phks (id) ON DELETE CASCADE ON UPDATE CASCADE;
*/
