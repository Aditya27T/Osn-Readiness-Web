<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_pengerjaan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('quiz')->restrictOnDelete();
            $table->timestampTz('disubmit_pada')->nullable();
            $table->decimal('nilai', 5, 2)->nullable();
            $table->timestampTz('selesai_pada')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'quiz_id']);
        });

        Schema::create('quiz_jawaban', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pengerjaan_id')->constrained('quiz_pengerjaan')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soal')->restrictOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->unsignedTinyInteger('bobot');
            $table->string('jawaban_user', 255)->nullable();
            $table->boolean('status_benar')->nullable();
            $table->timestampsTz();
            $table->unique(['pengerjaan_id', 'soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_jawaban');
        Schema::dropIfExists('quiz_pengerjaan');
    }
};
