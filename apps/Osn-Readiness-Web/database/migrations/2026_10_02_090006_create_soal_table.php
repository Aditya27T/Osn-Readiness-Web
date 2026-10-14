<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soal', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tingkat_id')->constrained('tingkat_seleksi')->restrictOnDelete();
            $table->foreignId('materi_id')->constrained('materi')->restrictOnDelete();
            $table->foreignId('konteks_id')->nullable()->constrained('konteks_soal')->restrictOnDelete();
            $table->string('level', 10);
            $table->string('peruntukan', 10);
            $table->string('tipe_soal', 20);
            $table->text('pertanyaan');
            $table->jsonb('pilihan_jawaban')->nullable();
            $table->string('kunci_jawaban', 255);
            // Menyimpan nama file, bukan URL, supaya tetap benar bila domain berganti.
            $table->string('gambar')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['tingkat_id', 'peruntukan', 'level']);
            $table->index(['materi_id', 'peruntukan']);
        });

        DB::statement("ALTER TABLE soal ADD CONSTRAINT soal_level_check
            CHECK (level IN ('mudah', 'sedang', 'sulit'))");
        DB::statement("ALTER TABLE soal ADD CONSTRAINT soal_peruntukan_check
            CHECK (peruntukan IN ('pretest', 'latihan', 'simulasi'))");
        DB::statement("ALTER TABLE soal ADD CONSTRAINT soal_tipe_check
            CHECK (tipe_soal IN ('pilihan_ganda', 'isian'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('soal');
    }
};
