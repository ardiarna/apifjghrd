<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add uang_phk_id to phks table
        Schema::table('phks', function (Blueprint $table) {
            $table->unsignedBigInteger('uang_phk_id')->nullable()->after('status_phk_id');
        });

        // 2. Migrate existing relationship from karyawans & uang_phks to phks
        DB::statement("UPDATE phks p JOIN karyawans k ON k.phk_id = p.id SET p.uang_phk_id = k.uang_phk_id WHERE k.uang_phk_id IS NOT NULL;");
        DB::statement("UPDATE phks p JOIN uang_phks u ON u.karyawan_id = p.karyawan_id SET p.uang_phk_id = u.id WHERE p.uang_phk_id IS NULL;");

        // 3. Add foreign key to phks.uang_phk_id
        Schema::table('phks', function (Blueprint $table) {
            $table->foreign('uang_phk_id')->references('id')->on('uang_phks')->onDelete('set null')->onUpdate('cascade');
        });

        // 4. Drop foreign key and column from karyawans table
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropForeign(['uang_phk_id']);
            $table->dropColumn('uang_phk_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->foreignId('uang_phk_id')->nullable()->after('phk_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });

        DB::statement("UPDATE karyawans k JOIN phks p ON k.phk_id = p.id SET k.uang_phk_id = p.uang_phk_id WHERE p.uang_phk_id IS NOT NULL;");

        Schema::table('phks', function (Blueprint $table) {
            $table->dropForeign(['uang_phk_id']);
            $table->dropColumn('uang_phk_id');
        });
    }
};
