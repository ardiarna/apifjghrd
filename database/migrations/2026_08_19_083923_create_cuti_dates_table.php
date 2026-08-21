<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuti_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuti_detail_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuti_dates');
    }
};
