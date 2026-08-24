<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // For MySQL, we can use DB::statement to modify enum
        DB::statement("ALTER TABLE cutis MODIFY COLUMN jenis_form ENUM('CUTI', 'IJIN', 'CUTI_MASAL') NOT NULL DEFAULT 'CUTI'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE cutis MODIFY COLUMN jenis_form ENUM('CUTI', 'IJIN', 'UNPAID_LEAVE', 'CUTI_MASAL') NOT NULL DEFAULT 'CUTI'");
    }
};
