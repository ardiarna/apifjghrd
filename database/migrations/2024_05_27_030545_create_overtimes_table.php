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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('jenis', ['F', 'C'])
                ->comment('F = Fratekindo, C = Customer');
            $table->date('tanggal');
            $table->smallInteger('tahun', false, true);
            $table->smallInteger('bulan', false, true);
            $table->integer('jumlah', false, true);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
