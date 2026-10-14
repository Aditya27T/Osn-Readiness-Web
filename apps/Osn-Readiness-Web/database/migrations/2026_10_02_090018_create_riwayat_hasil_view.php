<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * View riwayat_hasil menggabungkan hasil pre-test, latihan, dan simulasi yang sudah selesai.
     * View ini hanya dibaca, jadi tidak ada timestamps dan tidak ada operasi tulis.
     *
     * Dijalankan paling akhir karena menunjuk ke pretest, quiz_pengerjaan, dan hasil_simulasi.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE VIEW riwayat_hasil AS
            SELECT 'pretest' AS jenis_hasil, p.id AS referensi_id, p.user_id,
                   p.tingkat_id, p.nilai, p.selesai_pada AS tanggal
            FROM pretest p
            WHERE p.selesai_pada IS NOT NULL
            UNION ALL
            SELECT 'latihan', qp.id, qp.user_id, m.tingkat_id, qp.nilai,
            qp.selesai_pada
            FROM quiz_pengerjaan qp
            JOIN quiz q ON q.id = qp.quiz_id
            JOIN materi m ON m.id = q.materi_id
            WHERE qp.selesai_pada IS NOT NULL
            UNION ALL
            SELECT 'simulasi', hs.id, hs.user_id, s.tingkat_id, hs.nilai,
            hs.selesai_pada
            FROM hasil_simulasi hs
            JOIN simulasi s ON s.id = hs.simulasi_id
            WHERE hs.selesai_pada IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS riwayat_hasil');
    }
};
