/*
 * Mapper domain pre-test. Kontrak backend (terverifikasi 2026-10-05):
 * - GET /tingkat -> { message, data: [{ id, nama_tingkat, deskripsi,
 *     urutan, tingkat_terbuka, tahap }] }
 * - POST /pretest {tingkat_id} -> 201/200 { message, data: pretest }
 * - GET /pretest/{id} -> pretest berjalan (dengan `soal`) ATAU hasil
 *   (dengan `pemetaan`, tanpa `soal`)
 * - Soal: { id, materi_id, level, tipe_soal: 'pilihan_ganda'|'isian',
 *     pertanyaan, pilihan_jawaban: {A: teks, ...}|null, gambar, konteks,
 *     urutan, bobot, jawaban_user }
 * - Hasil: { id, tingkat_id, nilai, disubmit_pada, selesai_pada,
 *     pemetaan: [{ materi_id, jumlah_soal, jumlah_benar, poin_didapat,
 *     poin_maksimal, persentase, peringkat }],
 *     materi_wajib: [{ materi_id, prioritas }] }
 */

const angka = (v) => {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}
const daftar = (v) => (Array.isArray(v) ? v : [])

export function mapTingkat(r) {
  return {
    id: r.id,
    nama: r.nama_tingkat ?? '',
    deskripsi: r.deskripsi ?? '',
    urutan: angka(r.urutan),
    terbuka: Boolean(r.tingkat_terbuka),
    tahap: r.tahap ?? null,
  }
}

// pilihan_jawaban backend berupa object {A: teks, ...} -> array berurutan.
// isian tidak punya opsi -> [].
function mapOpsi(pilihan) {
  if (!pilihan || typeof pilihan !== 'object') return []
  return Object.entries(pilihan)
    .sort(([a], [b]) => String(a).localeCompare(String(b)))
    .map(([kode, teks]) => ({ kode: String(kode), teks: teks ?? '' }))
}

export function mapSoal(r) {
  const tipe = r.tipe_soal === 'isian' ? 'isian' : 'ganda'
  return {
    id: r.id,
    materiId: r.materi_id ?? null,
    level: r.level ?? null,
    tipe,
    pertanyaan: r.pertanyaan ?? '',
    opsi: tipe === 'ganda' ? mapOpsi(r.pilihan_jawaban) : [],
    gambar: r.gambar ?? null,
    konteks: r.konteks
      ? {
          judul: r.konteks.judul ?? '',
          isi: r.konteks.isi_konteks ?? '',
          gambar: r.konteks.gambar ?? null,
        }
      : null,
    urutan: angka(r.urutan),
    bobot: angka(r.bobot),
    jawaban: r.jawaban_user ?? null,
  }
}

export function mapHasilPretest(r) {
  return {
    id: r.id,
    tingkatId: r.tingkat_id ?? null,
    nilai: r.nilai == null || !Number.isFinite(Number(r.nilai)) ? null : Number(r.nilai),
    disubmitPada: r.disubmit_pada ?? null,
    selesaiPada: r.selesai_pada ?? null,
    pemetaan: daftar(r.pemetaan).map((p) => ({
      materiId: p.materi_id,
      jumlahSoal: angka(p.jumlah_soal),
      jumlahBenar: angka(p.jumlah_benar),
      poinDidapat: angka(p.poin_didapat),
      poinMaksimal: angka(p.poin_maksimal),
      persentase: p.persentase == null ? null : Number(p.persentase),
    })),
    materiWajib: daftar(r.materi_wajib).map((m) => ({
      materiId: m.materi_id,
      prioritas: angka(m.prioritas),
    })),
  }
}

// GET pretest/{id} bercabang: ada `soal` -> paket pengerjaan,
// ada `pemetaan` -> paket hasil. Dipakai store untuk branching.
export function paketPretest(r) {
  if (Array.isArray(r?.soal)) {
    return {
      // Sudah disubmit tapi belum dinilai -> jawaban terkunci, tunggu hasil.
      jenis: r.disubmit_pada ? 'menunggu' : 'pengerjaan',
      id: r.id,
      tingkatId: r.tingkat_id ?? null,
      soal: [...r.soal].sort((a, b) => angka(a.urutan) - angka(b.urutan)).map(mapSoal),
    }
  }
  return { jenis: 'hasil', hasil: mapHasilPretest(r ?? {}) }
}
