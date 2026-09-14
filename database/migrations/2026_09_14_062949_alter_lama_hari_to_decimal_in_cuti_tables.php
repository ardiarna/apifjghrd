<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'jenis_cuti_khususs',
            'cic_jenis_cuti_khususs',
            'cuti_details',
            'cic_cuti_details',
            'cuti_masals',
            'cic_cuti_masals'
        ];

        foreach ($tables as $t) {
            if (Schema::hasTable($t)) {
                DB::statement("ALTER TABLE `{$t}` MODIFY `lama_hari` DECIMAL(8,2) NULL");
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'jenis_cuti_khususs',
            'cic_jenis_cuti_khususs',
            'cuti_details',
            'cic_cuti_details',
            'cuti_masals',
            'cic_cuti_masals'
        ];

        foreach ($tables as $t) {
            if (Schema::hasTable($t)) {
                DB::statement("ALTER TABLE `{$t}` MODIFY `lama_hari` INT NULL");
            }
        }
    }
};
