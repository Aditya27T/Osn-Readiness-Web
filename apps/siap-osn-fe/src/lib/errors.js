// Cara membaca error backend terpusat di sini (jangan parsing di view).
//
// - pesanError: pesan umum dari envelope ({ message }) atau fallback.
//   Error tanpa respons (offline/timeout), 429, dan 5xx (pesan Laravel
//   berbahasa Inggris, mis. "Server Error") diganti pesan Indonesia.
// - pesanField: pesan validasi per field. Laravel: 422 +
//   { errors: { email: [...] } } (pesan Inggris) -> diterjemahkan ke
//   Bahasa Indonesia bila ada padanannya. Backend Go lama: 400 +
//   { message: "Email sudah terdaftar" }.
//   Kembali null bila bukan error field yang diminta.
const TERJEMAHAN_FIELD = {
  'The email has already been taken.': 'Email sudah terdaftar',
}

// Kode error bisnis backend ({ message, kode, detail }) -> Bahasa Indonesia.
// Dicek sebelum pesan message agar user selalu dapat kalimat yang jelas,
// termasuk untuk 503 yang pesannya teknis.
const TERJEMAHAN_KODE = {
  TINGKAT_TERKUNCI: 'Tingkat ini belum terbuka untukmu',
  SUDAH_LULUS: 'Kamu sudah lulus tingkat ini',
  PUTARAN_MASIH_BERJALAN: 'Selesaikan putaran yang sedang berjalan dulu',
  BANK_SOAL_TIDAK_CUKUP: 'Bank soal belum mencukupi, coba lagi nanti',
  SUDAH_DISUBMIT: 'Jawaban sudah dikumpulkan dan tidak bisa diubah',
  HASIL_SEDANG_DIPROSES: 'Hasil sedang dinilai, coba lagi sebentar',
  SYARAT_SIMULASI_BELUM_TERPENUHI: 'Syarat mengikuti simulasi belum terpenuhi',
  KUOTA_SIMULASI_HABIS: 'Kuota simulasi sudah habis',
  WAKTU_HABIS: 'Waktu pengerjaan sudah habis',
  BELUM_PRETEST: 'Selesaikan pre-test dulu untuk membuka fitur ini',
  SIMULASI_BELUM_DINILAI: 'Simulasi sebelumnya masih dinilai, coba lagi sebentar',
  LAYANAN_HITUNG_SALAH_KONFIGURASI: 'Penilaian sedang bermasalah, hubungi admin',
}

// Kode bisnis backend ({ kode }) atau null.
export function kodeError(err) {
  return err?.response?.data?.kode ?? null
}

// Status HTTP atau null (error jaringan/abort).
export function statusError(err) {
  return err?.response?.status ?? null
}

export function pesanError(err, fallback = 'Terjadi kesalahan, coba lagi') {
  if (err?.code === 'ECONNABORTED' || err?.code === 'ETIMEDOUT') {
    return 'Server terlalu lama merespons, coba lagi'
  }
  if (err?.isAxiosError && !err.response && err.code !== 'ERR_CANCELED') {
    return 'Tidak dapat terhubung ke server. Periksa koneksi internet.'
  }
  const status = err?.response?.status
  if (status === 429) return 'Terlalu banyak percobaan, tunggu sebentar lalu coba lagi'
  const kode = err?.response?.data?.kode
  if (kode && TERJEMAHAN_KODE[kode]) return TERJEMAHAN_KODE[kode]
  if (status >= 500) return fallback
  return err?.response?.data?.message ?? fallback
}

export function pesanField(err, field) {
  const status = err?.response?.status
  if (status !== 400 && status !== 422) return null
  const dariLaravel = err?.response?.data?.errors?.[field]?.[0]
  if (dariLaravel) return TERJEMAHAN_FIELD[dariLaravel] ?? dariLaravel
  if (field === 'email' && err?.response?.data?.message === 'Email sudah terdaftar') {
    return 'Email sudah terdaftar'
  }
  return null
}
