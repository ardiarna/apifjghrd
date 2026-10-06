<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE cutis MODIFY tanggal_kembali VARCHAR(255) NULL");
        DB::statement("ALTER TABLE cic_cutis MODIFY tanggal_kembali VARCHAR(255) NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE cutis MODIFY tanggal_kembali DATE NULL");
        DB::statement("ALTER TABLE cic_cutis MODIFY tanggal_kembali DATE NULL");
    }
};
