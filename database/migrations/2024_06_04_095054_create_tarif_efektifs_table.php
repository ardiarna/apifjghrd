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
        Schema::create('tarif_efektifs', function (Blueprint $table) {
            $table->id();
            $table->enum('ter', ['A', 'B', 'C']);
            $table->integer('penghasilan', false, true);
            $table->decimal('persen', 5, 2, true);
            $table->timestamps();
            $table->unique(['ter', 'penghasilan'], 'tarif_efektifs_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarif_efektifs');
    }
};
