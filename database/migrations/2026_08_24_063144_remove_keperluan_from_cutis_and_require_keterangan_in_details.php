<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropColumn('keperluan');
        });
        
        DB::statement('ALTER TABLE cuti_details MODIFY COLUMN keterangan TEXT NOT NULL');
    }

    public function down()
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->text('keperluan')->nullable();
        });
        
        DB::statement('ALTER TABLE cuti_details MODIFY COLUMN keterangan TEXT NULL');
    }
};
