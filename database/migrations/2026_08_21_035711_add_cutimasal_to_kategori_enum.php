<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE cuti_details MODIFY COLUMN kategori ENUM('TAHUNAN', 'KHUSUS', 'UNPAID', 'GANTI_HARI_LIBUR', 'IJIN', 'CUTI_MASAL') NOT NULL");
    }

    public function down(): void
    {
        // Revert not possible securely if CUTI_MASAL exists
    }
};
