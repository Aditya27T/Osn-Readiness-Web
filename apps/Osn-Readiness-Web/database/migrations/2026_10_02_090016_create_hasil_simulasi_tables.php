<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_simulasi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('simulasi_id')->constrained('simulasi')->restrictOnDelete();
            $table->foreignId('pretest_id')->constrained('pretest')->cascadeOnDelete();
            $table->timestampTz('mulai_pada');
            $table->timestampTz('batas_pada');
            $table->timestampTz('disubmit_pada')->nullable();
            $table->timestampTz('selesai_pada')->nullable();
            $table->decimal('nilai', 5, 2)->nullable();
            $table->unsignedSmallInteger('jumlah_benar')->nullable();
            $table->unsignedSmallInteger('jumlah_salah')->nullable();
            $table->boolean('lulus')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'pretest_id']);
            $table->index(['disubmit_pada', 'batas_pada']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX hasil_simulasi_berjalan_unique
             ON hasil_simulasi (user_id) WHERE selesai_pada IS NULL'
        );

        Schema::create('hasil_simulasi_jawaban', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hasil_simulasi_id')->constrained('hasil_simulasi')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soal')->restrictOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->unsignedTinyInteger('bobot');
            $table->string('jawaban_user', 255)->nullable();
            $table->boolean('status_benar')->nullable();
            $table->timestampsTz();
            $table->unique(['hasil_simulasi_id', 'soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_simulasi_jawaban');
        Schema::dropIfExists('hasil_simulasi');
    }
};
