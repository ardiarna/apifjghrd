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
        Schema::create('medical_rekaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('tahun', false, true);
            $table->integer('gaji', false, true);
            $table->integer('bln_1', false, true)->default(0);
            $table->integer('bln_2', false, true)->default(0);
            $table->integer('bln_3', false, true)->default(0);
            $table->integer('bln_4', false, true)->default(0);
            $table->integer('bln_5', false, true)->default(0);
            $table->integer('bln_6', false, true)->default(0);
            $table->integer('bln_7', false, true)->default(0);
            $table->integer('bln_8', false, true)->default(0);
            $table->integer('bln_9', false, true)->default(0);
            $table->integer('bln_10', false, true)->default(0);
            $table->integer('bln_11', false, true)->default(0);
            $table->integer('bln_12', false, true)->default(0);
            $table->unique(['karyawan_id', 'tahun'], 'medical_rekaps_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_rekaps');
    }
};
