<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kompetensi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tingkat_id')->constrained('tingkat_seleksi')->restrictOnDelete();
            $table->string('nama_kompetensi', 150);
            $table->text('deskripsi')->nullable();
            $table->timestampsTz();
            $table->unique(['tingkat_id', 'nama_kompetensi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kompetensi');
    }
};
