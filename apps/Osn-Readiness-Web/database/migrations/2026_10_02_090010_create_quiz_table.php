<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('materi_id')->unique()->constrained('materi')->restrictOnDelete();
            $table->string('nama_quiz', 150);
            $table->text('deskripsi')->nullable();
            $table->unsignedSmallInteger('jumlah_soal');
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE quiz ADD CONSTRAINT quiz_jumlah_soal_check
            CHECK (jumlah_soal >= 10)');
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz');
    }
};
