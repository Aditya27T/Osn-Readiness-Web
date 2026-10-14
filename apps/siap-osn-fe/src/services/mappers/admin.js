/*
 * Mapper domain admin. Kontrak backend (Osn-Readiness-Web, diverifikasi
 * 2026-10-06 lewat curl ke empat proses dev yang hidup):
 * - GET /admin/dashboard -> { message, data: { siswa_aktif: int,
 *   pengerjaan_per_jenis: { pretest, latihan, simulasi },
 *   siswa_per_tingkat_aktif: [{ tingkat_id, nama_tingkat, urutan,
 *   jumlah_siswa }],
 *   rata_rata_nilai_per_jenis: { pretest, latihan, simulasi } } }
 * - GET /admin/bank-soal/kecukupan?tingkat_id= -> { message, data: {
 *   tingkat_id, pretest_per_level: [{ level, tersedia, kuota, kurang }],
 *   pretest_per_materi: [{ materi_id, judul, tersedia, minimal, kurang }],
 *   putaran_pretest: { putaran, ambang, kurang },
 *   simulasi_per_level: [{ simulasi_id, nama, is_aktif, per_level: [...] }],
 *   latihan_per_materi: [{ materi_id, judul, punya_latihan, tersedia,
 *   dibutuhkan, kurang }] } }
 * - GET /admin/siswa -> Resource + paginasi Laravel: data: [{ id, name,
 *   email, roles, is_active, tingkat_aktif_id, created_at, updated_at }]
 * - GET /admin/kompetensi -> [{ id, tingkat_id, nama_kompetensi, deskripsi }]
 * - GET /admin/materi -> [{ id, tingkat_id, kompetensi_id, urutan, judul,
 *   deskripsi, isi_materi, file_materi, gambar, id_sumber }]
 * - GET /admin/konteks-soal -> [{ id, tingkat_id, judul, isi_konteks }]
 * - GET /admin/soal (paginated) -> [{ id, id_sumber, tingkat_id, materi_id,
 *   konteks_id, level, peruntukan, tipe_soal, pertanyaan, pilihan_jawaban,
 *   kunci_jawaban, gambar, materi: {id, judul}, konteks, pembahasan,
 *   deleted_at }]
 * - GET /admin/latihan -> [{ id, materi_id, nama_quiz, deskripsi,
 *   jumlah_soal, materi }]
 * - GET /admin/simulasi -> [{ id, tingkat_id, nama_simulasi, deskripsi,
 *   jumlah_soal, durasi_menit, is_aktif, tingkat: {id, nama} }]
 * - GET /admin/tingkat -> [{ id, nama_tingkat, deskripsi, urutan }]
 * - GET /admin/tingkat/{id}/aturan-pemetaan -> 16 parameter aturan
 *
 * Catatan: admin Unlike siswa, /admin/soal & /admin/siswa memakai envelope
 * Laravel { data, meta, links } untuk daftar berpaginasi.
 */

const angka = (v) => {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}
const nilaiAtauNull = (v) => (v == null || !Number.isFinite(Number(v)) ? null : Number(v))
const daftar = (v) => (Array.isArray(v) ? v : [])

// Backend memakai satu kata untuk level; FE memakai label Bahasa Indonesia.
export const LEVEL = { MUDAH: 'mudah', SEDANG: 'sedang', SULIT: 'sulit' }
export const PERUNTUKAN = { PRETEST: 'pretest', LATIHAN: 'latihan', SIMULASI: 'simulasi' }

const LEVEL_LABEL = { mudah: 'Mudah', sedang: 'Sedang', sulit: 'Sulit' }
const PERUNTUKAN_LABEL = { pretest: 'Pre-test', latihan: 'Latihan', simulasi: 'Simulasi' }

export const labelLevel = (v) => LEVEL_LABEL[v] ?? v ?? '—'
export const labelPeruntukan = (v) => PERUNTUKAN_LABEL[v] ?? v ?? '—'

/* ---------- Dashboard ---------- */
export function mapAdminDashboard(r) {
  const d = r ?? {}
  const perJenis = d.pengerjaan_per_jenis ?? {}
  const rata = d.rata_rata_nilai_per_jenis ?? {}
  const urut = (o) =>
    Object.entries(o).map(([jenis, jumlah]) => ({
      jenis,
      label: PERUNTUKAN_LABEL[jenis] ?? jenis,
      jumlah: angka(jumlah),
    }))
  return {
    siswaAktif: angka(d.siswa_aktif),
    pengerjaanPerJenis: urut(perJenis),
    rataRataNilai: urut(rata).map((x) => ({ ...x, nilai: nilaiAtauNull(x.jumlah) })),
    siswaPerTingkat: daftar(d.siswa_per_tingkat_aktif)
      .map((t) => ({
        tingkatId: t.tingkat_id,
        nama: t.nama_tingkat ?? '',
        urutan: angka(t.urutan),
        jumlahSiswa: angka(t.jumlah_siswa),
      }))
      .sort((a, b) => a.urutan - b.urutan),
  }
}

/* ---------- Kecukupan bank soal ---------- */
function perLevel(v) {
  return daftar(v).map((x) => ({
    level: x.level,
    label: labelLevel(x.level),
    tersedia: angka(x.tersedia),
    kuota: angka(x.kuota),
    kurang: Boolean(x.kurang),
  }))
}

export function mapKecukupanBankSoal(r) {
  const d = r ?? {}
  return {
    tingkatId: d.tingkat_id ?? null,
    pretestPerLevel: perLevel(d.pretest_per_level),
    pretestPerMateri: daftar(d.pretest_per_materi).map((x) => ({
      materiId: x.materi_id,
      judul: x.judul ?? '',
      tersedia: angka(x.tersedia),
      minimal: angka(x.minimal),
      kurang: Boolean(x.kurang),
    })),
    putaranPretest: {
      putaran: angka(d.putaran_pretest?.putaran),
      ambang: angka(d.putaran_pretest?.ambang),
      kurang: Boolean(d.putaran_pretest?.kurang),
    },
    simulasiPerLevel: daftar(d.simulasi_per_level).map((x) => ({
      simulasiId: x.simulasi_id,
      nama: x.nama ?? '',
      aktif: Boolean(x.is_aktif),
      perLevel: perLevel(x.per_level),
    })),
    latihanPerMateri: daftar(d.latihan_per_materi).map((x) => ({
      materiId: x.materi_id,
      judul: x.judul ?? '',
      punyaLatihan: Boolean(x.punya_latihan),
      tersedia: angka(x.tersedia),
      dibutuhkan: angka(x.dibutuhkan),
      kurang: Boolean(x.kurang),
    })),
  }
}

/* ---------- Siswa ---------- */
export function mapSiswa(r) {
  return {
    id: r.id,
    nama: r.name ?? '',
    email: r.email ?? '',
    roles: daftar(r.roles),
    aktif: Boolean(r.is_active),
    tingkatAktifId: r.tingkat_aktif_id ?? null,
    dibuatPada: r.created_at ?? null,
    diperbaruiPada: r.updated_at ?? null,
  }
}

/* ---------- Kompetensi ---------- */
export function mapKompetensi(r) {
  return {
    id: r.id,
    tingkatId: r.tingkat_id ?? null,
    nama: r.nama_kompetensi ?? '',
    deskripsi: r.deskripsi ?? '',
  }
}

/* ---------- Materi ---------- */
export function mapMateriAdmin(r) {
  return {
    id: r.id,
    tingkatId: r.tingkat_id ?? null,
    kompetensiId: r.kompetensi_id ?? null,
    urutan: angka(r.urutan),
    judul: r.judul ?? '',
    deskripsi: r.deskripsi ?? '',
    isiMateri: r.isi_materi ?? '',
    fileMateri: r.file_materi ?? null,
    gambar: r.gambar ?? null,
    idSumber: r.id_sumber ?? null,
  }
}

/* ---------- Konteks soal ---------- */
export function mapKonteksSoal(r) {
  return {
    id: r.id,
    tingkatId: r.tingkat_id ?? null,
    judul: r.judul ?? '',
    isi: r.isi_konteks ?? '',
  }
}

/* ---------- Soal ---------- */
// Admin melihat kunci jawaban dan pembahasan; itu memang hak aksesnya.
export function mapSoalAdmin(r) {
  return {
    id: r.id,
    idSumber: r.id_sumber ?? null,
    tingkatId: r.tingkat_id ?? null,
    materiId: r.materi_id ?? null,
    konteksId: r.konteks_id ?? null,
    level: r.level,
    peruntukan: r.peruntukan,
    tipe: r.tipe_soal === 'isian' ? 'isian' : 'ganda',
    pertanyaan: r.pertanyaan ?? '',
    // Backend mengirim objek {A: teks, ...}; FE tetap memakainya sebagai objek
    // supaya form edit bisa mengunci huruf yang ada.
    pilihan: r.pilihan_jawaban && typeof r.pilihan_jawaban === 'object' ? r.pilihan_jawaban : {},
    kunci: r.kunci_jawaban ?? null,
    gambar: r.gambar ?? null,
    materi: r.materi ? { id: r.materi.id, judul: r.materi.judul } : null,
    konteks: r.konteks ? { id: r.konteks.id, judul: r.konteks.judul } : null,
    pembahasan: r.pembahasan?.isi_pembahasan ?? null,
    terhapus: Boolean(r.deleted_at),
  }
}

/* ---------- Latihan ---------- */
export function mapLatihan(r) {
  return {
    id: r.id,
    materiId: r.materi_id ?? null,
    nama: r.nama_quiz ?? '',
    deskripsi: r.deskripsi ?? '',
    jumlahSoal: angka(r.jumlah_soal),
    materi: r.materi ? { id: r.materi.id, judul: r.materi.judul } : null,
  }
}

/* ---------- Simulasi ---------- */
export function mapSimulasiAdmin(r) {
  return {
    id: r.id,
    tingkatId: r.tingkat_id ?? null,
    nama: r.nama_simulasi ?? '',
    deskripsi: r.deskripsi ?? '',
    jumlahSoal: angka(r.jumlah_soal),
    durasiMenit: angka(r.durasi_menit),
    aktif: Boolean(r.is_aktif),
    tingkat: r.tingkat ? { id: r.tingkat.id, nama: r.tingkat.nama } : null,
  }
}

/* ---------- Tingkat ---------- */
export function mapTingkatAdmin(r) {
  return {
    id: r.id,
    nama: r.nama_tingkat ?? '',
    deskripsi: r.deskripsi ?? '',
    urutan: angka(r.urutan),
  }
}

/* ---------- Aturan pemetaan ---------- */
// 16 parameter. Nama field dibiarkan persis sama supaya form tidak perlu
// peta tambahan; view yang menyusun groupings-nya.
export const ATURAN_FIELDS = [
  'bobot_mudah', 'bobot_sedang', 'bobot_sulit',
  'pretest_jumlah_soal', 'pretest_persen_mudah', 'pretest_persen_sedang', 'pretest_persen_sulit',
  'pretest_min_soal_per_materi', 'jumlah_materi_wajib', 'latihan_min_soal', 'latihan_min_nilai',
  'simulasi_persen_mudah', 'simulasi_persen_sedang', 'simulasi_persen_sulit',
  'simulasi_maks_percobaan', 'passing_grade',
]

export function mapAturanPemetaan(r) {
  const d = r ?? {}
  return {
    tingkatId: d.tingkat_id ?? null,
    ...Object.fromEntries(ATURAN_FIELDS.map((f) => [f, angka(d[f])])),
  }
}

