<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekomendasi_materi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pretest_id')->constrained('pretest')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('materi_id')->constrained('materi')->restrictOnDelete();
            $table->unsignedSmallInteger('prioritas');
            $table->timestampsTz();
            $table->unique(['pretest_id', 'materi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_materi');
    }
};
