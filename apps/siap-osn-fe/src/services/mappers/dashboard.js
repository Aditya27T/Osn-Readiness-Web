/*
 * Bentuk data dashboard siswa versi FE. Semua konversi dari respons backend
 * terpusat di sini (dipakai services/siswa.js + stores/progress.js).
 *
 * Kontrak API backend (Laravel Osn-Readiness-Web, terverifikasi 2026-10-05):
 *
 *   GET /dashboard
 *   -> {
 *        data: {
 *          tingkat_aktif_id: 1,          // int|null
 *          tingkat_aktif: 'Kabupaten',   // string|null (nama_tingkat)
 *          tingkat: [                    // urut backend, belum tentu by urutan
 *            {
 *              tingkat_id: 1,
 *              nama_tingkat: 'Kabupaten',
 *              urutan: 1,
 *              tingkat_terbuka: true,
 *              tahap: 'BELUM_PRETEST',   // lihat TAHAP_* di bawah
 *              sudah_lulus: false,
 *              sisa_kuota_simulasi: 2,   // int|null
 *              syarat_simulasi: {         // dirinci penuh di P4 (simulasi)
 *                terpenuhi: false,
 *                alasan: null,
 *                rincian: null,
 *              },
 *              hasil_simulasi_terakhir: null, // { id, nilai, lulus, selesai_pada }
 *            },
 *          ],
 *        },
 *      }
 *
 * Field yang belum dikirim backend (materi, latihan, riwayat, streak)
 * default 0/[]/null; UI sudah punya kondisi kosong untuk semuanya.
 * Diisi bertahap: riwayat di P2, materi di P3.
 */

const angka = (v) => {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}
const daftar = (v) => (Array.isArray(v) ? v : [])

// Tahap tingkat aktif yang berarti pre-test tingkat itu sudah selesai.
// PRETEST_BERJALAN = baru mulai, belum submit -> popup pre-test tetap tampil.
const TAHAP_PRETEST_SELESAI = new Set([
  'BELAJAR',
  'SIAP_SIMULASI',
  'SIMULASI_BERJALAN',
  'PUTARAN_HABIS',
  'LULUS',
])

const TINGKATAN_DEFAULT = [
  { nama: 'Kabupaten', terbuka: false },
  { nama: 'Provinsi', terbuka: false },
]

export function dashboardKosong() {
  return {
    preTestSelesai: false,
    perluPretestUlang: false,
    tingkatAktifId: null,
    tingkat: null,
    tahap: null,
    streak: 0,
    materiSelesai: 0,
    materiTotal: 0,
    simulasiDiikuti: 0,
    rataRataNilai: 0,
    kompetensi: [],
    rekomendasi: [],
    riwayat: [],
    tingkatan: TINGKATAN_DEFAULT,
    hasilTerakhir: null,
  }
}

// Mengubah respons backend (snake_case, bisa kosong/null) jadi bentuk yang aman dipakai UI.
export function mapDashboard(raw) {
  const r = raw ?? {}
  const daftarTingkat = daftar(r.tingkat).sort((a, b) => angka(a.urutan) - angka(b.urutan))

  const aktif =
    daftarTingkat.find((t) => t.tingkat_id === r.tingkat_aktif_id) ?? daftarTingkat[0] ?? null

  const hasilAktif = aktif?.hasil_simulasi_terakhir ?? null
  const hasilLain = hasilAktif ?? daftarTingkat.map((t) => t.hasil_simulasi_terakhir).find(Boolean) ?? null
  const tingkatHasil =
    daftarTingkat.find((t) => t.hasil_simulasi_terakhir === hasilLain) ?? aktif ?? null

  return {
    preTestSelesai: aktif ? TAHAP_PRETEST_SELESAI.has(aktif.tahap) : false,
    // Kuota simulasi habis tanpa lulus: backend mengizinkan pre-test baru,
    // jadi dashboard harus menawarkan-nya — bukan considers selesai.
    perluPretestUlang: aktif?.tahap === 'PUTARAN_HABIS',
    tingkatAktifId: r.tingkat_aktif_id ?? aktif?.tingkat_id ?? null,
    tingkat: r.tingkat_aktif ?? aktif?.nama_tingkat ?? null,
    tahap: aktif?.tahap ?? null,
    streak: 0,
    materiSelesai: 0,
    materiTotal: 0,
    simulasiDiikuti: 0,
    rataRataNilai: 0,
    kompetensi: [],
    rekomendasi: [],
    riwayat: [],
    tingkatan: daftarTingkat.length
      ? daftarTingkat.map((t) => ({ nama: t.nama_tingkat, terbuka: Boolean(t.tingkat_terbuka) }))
      : TINGKATAN_DEFAULT,
    hasilTerakhir: hasilLain
      ? {
          id: hasilLain.id ?? null,
          judul: `Simulasi — Tingkat ${tingkatHasil?.nama_tingkat ?? ''}`.trim(),
          tanggal: hasilLain.selesai_pada ?? null,
          nilai:
            hasilLain.nilai == null || !Number.isFinite(Number(hasilLain.nilai))
              ? null
              : Math.round(Number(hasilLain.nilai)),
          lulus: Boolean(hasilLain.lulus),
        }
      : null,
  }
}
