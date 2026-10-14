<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konteks_soal', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tingkat_id')->constrained('tingkat_seleksi')->restrictOnDelete();
            $table->string('judul', 200);
            $table->text('isi_konteks');
            $table->string('gambar')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konteks_soal');
    }
};
