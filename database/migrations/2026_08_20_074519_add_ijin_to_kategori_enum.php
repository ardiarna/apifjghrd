<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE cuti_details MODIFY COLUMN kategori ENUM('TAHUNAN', 'KHUSUS', 'UNPAID', 'GANTI_HARI_LIBUR', 'IJIN')");
    }

    public function down(): void
    {
        // Reverting this might drop IJIN rows or fail, but we'll provide the reverse for safety
        DB::statement("ALTER TABLE cuti_details MODIFY COLUMN kategori ENUM('TAHUNAN', 'KHUSUS', 'UNPAID', 'GANTI_HARI_LIBUR')");
    }
};
