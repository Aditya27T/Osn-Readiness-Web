<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulasi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tingkat_id')->constrained('tingkat_seleksi')->restrictOnDelete();
            $table->string('nama_simulasi', 150);
            $table->text('deskripsi')->nullable();
            $table->unsignedSmallInteger('jumlah_soal');
            $table->unsignedSmallInteger('durasi_menit');
            $table->boolean('is_aktif')->default(false);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulasi');
    }
};
