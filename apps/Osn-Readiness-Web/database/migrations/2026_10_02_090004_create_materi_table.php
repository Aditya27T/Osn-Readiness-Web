<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tingkat_id')->constrained('tingkat_seleksi')->restrictOnDelete();
            $table->foreignId('kompetensi_id')->constrained('kompetensi')->restrictOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->longText('isi_materi');
            $table->string('file_materi')->nullable();
            $table->timestampsTz();
            $table->unique(['tingkat_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materi');
    }
};
