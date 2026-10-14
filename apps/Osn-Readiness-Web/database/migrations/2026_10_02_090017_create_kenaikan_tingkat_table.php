<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kenaikan_tingkat', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tingkat_asal_id')->constrained('tingkat_seleksi')->restrictOnDelete();
            $table->foreignId('tingkat_tujuan_id')->nullable()
                ->constrained('tingkat_seleksi')->restrictOnDelete();
            $table->string('status', 15);
            $table->text('keterangan')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'tingkat_asal_id']);
        });

        DB::statement("ALTER TABLE kenaikan_tingkat ADD CONSTRAINT
            kenaikan_status_check
            CHECK (status IN ('lulus', 'tidak_lulus'))");
        DB::statement(
            "CREATE UNIQUE INDEX kenaikan_lulus_unique
             ON kenaikan_tingkat (user_id, tingkat_asal_id) WHERE status = 'lulus'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('kenaikan_tingkat');
    }
};
