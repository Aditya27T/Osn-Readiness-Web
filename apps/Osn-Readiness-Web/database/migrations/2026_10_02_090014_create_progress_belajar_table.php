<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_belajar', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('materi_id')->constrained('materi')->restrictOnDelete();
            $table->string('status', 10)->default('belajar');
            $table->unsignedSmallInteger('persentase')->default(0);
            $table->timestampTz('tanggal_selesai')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'materi_id']);
        });

        DB::statement("ALTER TABLE progress_belajar ADD CONSTRAINT
            progress_status_check
            CHECK (status IN ('belajar', 'selesai'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_belajar');
    }
};
