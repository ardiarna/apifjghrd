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
        Schema::create('upahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('gaji', false, true)->default(0);
            $table->integer('uang_makan', false, true)->default(0);
            $table->enum('makan_harian', ['Y', 'N'])->default('Y');
            $table->enum('overtime', ['Y', 'N'])->default('N');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upahs');
    }
};
