<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemetaan_materi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pretest_id')->constrained('pretest')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('materi_id')->constrained('materi')->restrictOnDelete();
            $table->unsignedSmallInteger('jumlah_soal');
            $table->unsignedSmallInteger('jumlah_benar');
            $table->unsignedSmallInteger('poin_didapat');
            $table->unsignedSmallInteger('poin_maksimal');
            $table->decimal('persentase', 5, 2);
            $table->unsignedSmallInteger('peringkat');
            $table->timestampsTz();
            $table->unique(['pretest_id', 'materi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemetaan_materi');
    }
};
