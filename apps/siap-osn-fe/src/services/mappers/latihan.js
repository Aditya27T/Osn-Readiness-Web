/*
 * Mapper pengerjaan latihan/quiz. Kontrak backend (terverifikasi 2026-10-05):
 * - POST /quiz/{quiz}/mulai (body kosong) -> 201 (baru) / 200 (lanjutkan)
 *   { message, data: { id (=pengerjaan), quiz_id, materi_id, materi_judul,
 *   disubmit_pada, selesai_pada, nilai, soal: [SoalPengerjaan] } }
 * - Bentuk soal SAMA dengan pre-test (tanpa kunci_jawaban) -> mapSoal
 *   dipakai ulang dari mappers/pretest.js.
 * - GET /quiz-pengerjaan/{id} -> paket pengerjaan yang sama.
 * - POST .../submit (body kosong, idempoten) -> { message, data: { id,
 *   quiz_id, materi_id, nilai, disubmit_pada, selesai_pada,
 *   jawaban: [{ soal_id, urutan, bobot, jawaban_user, status_benar }] } }
 *   (latihan tidak punya review pembahasan — itu khusus simulasi/P4).
 */

import { mapSoal } from '@/services/mappers/pretest.js'

const angka = (v) => {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

export function mapHasilLatihan(r) {
  return {
    id: r.id,
    quizId: r.quiz_id ?? null,
    materiId: r.materi_id ?? null,
    nilai: r.nilai == null || !Number.isFinite(Number(r.nilai)) ? null : Number(r.nilai),
    disubmitPada: r.disubmit_pada ?? null,
    selesaiPada: r.selesai_pada ?? null,
    jawaban: Array.isArray(r.jawaban)
      ? [...r.jawaban]
          .sort((a, b) => angka(a.urutan) - angka(b.urutan))
          .map((j) => ({
            soalId: j.soal_id,
            urutan: angka(j.urutan),
            bobot: angka(j.bobot),
            jawabanUser: j.jawaban_user ?? null,
            benar: Boolean(j.status_benar),
          }))
      : [],
  }
}

// GET pengerjaan bercabang seperti pre-test: ada `soal` -> kerjakan,
// ada `jawaban` -> hasil.
export function paketLatihan(r) {
  if (Array.isArray(r?.soal)) {
    return {
      // Sudah disubmit tapi belum dinilai -> jawaban terkunci, tunggu hasil.
      jenis: r.disubmit_pada ? 'menunggu' : 'pengerjaan',
      id: r.id,
      quizId: r.quiz_id ?? null,
      materiId: r.materi_id ?? null,
      materiJudul: r.materi_judul ?? '',
      soal: [...r.soal].sort((a, b) => angka(a.urutan) - angka(b.urutan)).map(mapSoal),
    }
  }
  return { jenis: 'hasil', hasil: mapHasilLatihan(r ?? {}) }
}
