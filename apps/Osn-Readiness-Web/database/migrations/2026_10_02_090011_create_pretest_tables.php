<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pretest', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tingkat_id')->constrained('tingkat_seleksi')->restrictOnDelete();
            $table->timestampTz('disubmit_pada')->nullable();
            $table->decimal('nilai', 5, 2)->nullable();
            $table->timestampTz('selesai_pada')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'tingkat_id']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX pretest_berjalan_unique
             ON pretest (user_id, tingkat_id) WHERE selesai_pada IS NULL'
        );

        Schema::create('pretest_jawaban', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pretest_id')->constrained('pretest')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soal')->restrictOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->unsignedTinyInteger('bobot');
            $table->string('jawaban_user', 255)->nullable();
            $table->boolean('status_benar')->nullable();
            $table->timestampsTz();
            $table->unique(['pretest_id', 'soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pretest_jawaban');
        Schema::dropIfExists('pretest');
    }
};
