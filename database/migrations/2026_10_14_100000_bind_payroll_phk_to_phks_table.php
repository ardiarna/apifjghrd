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
        // 1. Add payroll_phk_id to phks table
        Schema::table('phks', function (Blueprint $table) {
            $table->unsignedBigInteger('payroll_phk_id')->nullable()->after('uang_phk_id');
        });

        // 2. Link existing payroll_phks records to phks
        DB::statement("UPDATE phks p JOIN payroll_phks pp ON pp.karyawan_id = p.karyawan_id SET p.payroll_phk_id = pp.id WHERE p.payroll_phk_id IS NULL;");

        // 3. Add foreign key
        Schema::table('phks', function (Blueprint $table) {
            $table->foreign('payroll_phk_id')->references('id')->on('payroll_phks')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phks', function (Blueprint $table) {
            $table->dropForeign(['payroll_phk_id']);
            $table->dropColumn('payroll_phk_id');
        });
    }
};
