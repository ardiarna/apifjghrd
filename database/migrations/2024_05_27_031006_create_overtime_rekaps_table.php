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
        Schema::create('overtime_rekaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('tahun', false, true);
            $table->integer('fjg_1', false, true)->default(0);
            $table->integer('fjg_2', false, true)->default(0);
            $table->integer('fjg_3', false, true)->default(0);
            $table->integer('fjg_4', false, true)->default(0);
            $table->integer('fjg_5', false, true)->default(0);
            $table->integer('fjg_6', false, true)->default(0);
            $table->integer('fjg_7', false, true)->default(0);
            $table->integer('fjg_8', false, true)->default(0);
            $table->integer('fjg_9', false, true)->default(0);
            $table->integer('fjg_10', false, true)->default(0);
            $table->integer('fjg_11', false, true)->default(0);
            $table->integer('fjg_12', false, true)->default(0);
            $table->integer('cus_1', false, true)->default(0);
            $table->integer('cus_2', false, true)->default(0);
            $table->integer('cus_3', false, true)->default(0);
            $table->integer('cus_4', false, true)->default(0);
            $table->integer('cus_5', false, true)->default(0);
            $table->integer('cus_6', false, true)->default(0);
            $table->integer('cus_7', false, true)->default(0);
            $table->integer('cus_8', false, true)->default(0);
            $table->integer('cus_9', false, true)->default(0);
            $table->integer('cus_10', false, true)->default(0);
            $table->integer('cus_11', false, true)->default(0);
            $table->integer('cus_12', false, true)->default(0);
            $table->unique(['karyawan_id', 'tahun'], 'overtime_rekaps_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_rekaps');
    }
};
