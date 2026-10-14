<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tingkat_seleksi', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_tingkat', 100);
            $table->text('deskripsi')->nullable();
            $table->unsignedSmallInteger('urutan')->unique();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tingkat_seleksi');
    }
};
