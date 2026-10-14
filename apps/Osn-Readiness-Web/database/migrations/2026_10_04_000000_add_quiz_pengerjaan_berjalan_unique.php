<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sebelum index ini ada, dua request mulai yang bersamaan bisa
        // membuat dua pengerjaan berjalan untuk latihan yang sama. Sisakan
        // yang terbaru supaya index bisa dibuat; jawabannya ikut terhapus
        // lewat cascade.
        DB::statement(
            'DELETE FROM quiz_pengerjaan lama
             USING quiz_pengerjaan baru
             WHERE lama.user_id = baru.user_id
               AND lama.quiz_id = baru.quiz_id
               AND lama.disubmit_pada IS NULL
               AND baru.disubmit_pada IS NULL
               AND lama.id < baru.id'
        );

        DB::statement(
            'CREATE UNIQUE INDEX quiz_pengerjaan_berjalan_unique
             ON quiz_pengerjaan (user_id, quiz_id) WHERE disubmit_pada IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS quiz_pengerjaan_berjalan_unique');
    }
};
