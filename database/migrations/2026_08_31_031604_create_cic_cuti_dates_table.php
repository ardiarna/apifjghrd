<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cic_cuti_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cic_cuti_detail_id')->constrained('cic_cuti_details')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cic_cuti_dates');
    }
};
