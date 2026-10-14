/*
 * Mapper belajar/materi. Kontrak backend (terverifikasi 2026-10-05):
 * - GET /materi?tingkat_id= -> { data: [baris] } (Resource langsung,
 *   TANPA envelope {message}; sudah terurut wajib+prioritas di server)
 * - Baris: { id, tingkat_id, kompetensi_id, urutan, judul, deskripsi,
 *     wajib, prioritas, progress: null|{status, persentase,
 *     tanggal_selesai}, nilai_latihan_terbaik, latihan_belum_tersedia }
 * - GET /materi/{id} -> baris + { isi_materi, file_materi, gambar }
 *   (nama file mentah, bukan URL — URL dibangun di sini)
 * - PUT /materi/{id}/progress {status} -> { message, data: { materi_id,
 *     status, persentase, tanggal_selesai } }
 */

const basisApi = () => String(import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/api\/?$/, '')
export const urlStorage = (nama) => (nama ? `${basisApi()}/storage/${nama}` : null)

export function mapProgress(p) {
  if (!p) return null
  return {
    status: p.status ?? null,
    persentase: p.persentase == null ? 0 : Number(p.persentase),
    tanggalSelesai: p.tanggal_selesai ?? null,
  }
}

export function mapMateri(r) {
  return {
    id: r.id,
    tingkatId: r.tingkat_id ?? null,
    kompetensiId: r.kompetensi_id ?? null,
    urutan: r.urutan ?? 0,
    judul: r.judul ?? '',
    deskripsi: r.deskripsi ?? '',
    wajib: Boolean(r.wajib),
    prioritas: r.prioritas ?? null,
    progress: mapProgress(r.progress),
    nilaiTerbaik: r.nilai_latihan_terbaik == null ? null : Number(r.nilai_latihan_terbaik),
    latihanTersedia: !r.latihan_belum_tersedia,
    quizId: r.quiz_id ?? null,
  }
}

export function mapMateriDetail(r) {
  return {
    ...mapMateri(r),
    isi: r.isi_materi ?? '',
    fileUrl: urlStorage(r.file_materi),
    gambarUrl: urlStorage(r.gambar),
  }
}

export function mapProgressTersimpan(r) {
  return {
    materiId: r.materi_id,
    status: r.status ?? null,
    persentase: r.persentase == null ? 0 : Number(r.persentase),
    tanggalSelesai: r.tanggal_selesai ?? null,
  }
}
