<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aturan_pemetaan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tingkat_id')->constrained('tingkat_seleksi')->restrictOnDelete();
            $table->string('parameter', 60);
            $table->string('ketentuan', 255);
            $table->timestampsTz();
            $table->unique(['tingkat_id', 'parameter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aturan_pemetaan');
    }
};
