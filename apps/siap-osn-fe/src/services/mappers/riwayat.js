/*
 * Mapper riwayat hasil. Kontrak backend (terverifikasi 2026-10-05):
 * - GET /riwayat?jenis=&tingkat_id=&per_page=&page= -> paginasi Laravel
 *   { data: [{ jenis_hasil: 'pretest'|'latihan'|'simulasi', referensi_id,
 *   tingkat_id, nilai: float|null, tanggal: ISO|null }], meta: {...} }
 *   (Resource langsung: TANPA envelope {message}.)
 * - Judul disusun di sini dari jenis + nama tingkat (lookup dari store).
 */

const JENIS_JUDUL = {
  pretest: 'Pre-Test',
  latihan: 'Latihan',
  simulasi: 'Simulasi',
}

export function mapRiwayat(r, namaTingkatById = {}) {
  const jenis = r.jenis_hasil ?? null
  const namaTingkat = namaTingkatById[r.tingkat_id] ?? ''
  return {
    jenis,
    referensiId: r.referensi_id ?? null,
    tingkatId: r.tingkat_id ?? null,
    nilai: r.nilai == null || !Number.isFinite(Number(r.nilai)) ? null : Number(r.nilai),
    tanggal: r.tanggal ?? null,
    judul: `${JENIS_JUDUL[jenis] ?? 'Hasil'}${namaTingkat ? ` — Tingkat ${namaTingkat}` : ''}`,
  }
}
