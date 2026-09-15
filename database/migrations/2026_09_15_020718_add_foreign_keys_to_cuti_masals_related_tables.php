<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->foreign('cuti_masal_id')->references('id')->on('cuti_masals')->onUpdate('cascade')->onDelete('cascade');
        });
        
        Schema::table('cuti_masal_dates', function (Blueprint $table) {
            $table->foreign('cuti_masal_id')->references('id')->on('cuti_masals')->onUpdate('cascade')->onDelete('cascade');
        });
        
        Schema::table('cic_cutis', function (Blueprint $table) {
            $table->foreign('cic_cuti_masal_id')->references('id')->on('cic_cuti_masals')->onUpdate('cascade')->onDelete('cascade');
        });
        
        Schema::table('cic_cuti_masal_dates', function (Blueprint $table) {
            $table->foreign('cic_cuti_masal_id')->references('id')->on('cic_cuti_masals')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('cic_cuti_masal_dates', function (Blueprint $table) {
            $table->dropForeign(['cic_cuti_masal_id']);
        });
        
        Schema::table('cic_cutis', function (Blueprint $table) {
            $table->dropForeign(['cic_cuti_masal_id']);
        });
        
        Schema::table('cuti_masal_dates', function (Blueprint $table) {
            $table->dropForeign(['cuti_masal_id']);
        });
        
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropForeign(['cuti_masal_id']);
        });
    }
};
