// Angka tetap pre-test untuk sisi siswa.
//
// Nilai ini belum datang dari backend (endpoint /tingkat tidak mengirim
// durasi pre-test), jadi satu-satunya sumbernya di sini supaya popup,
// halaman pre-test, dan catatan durasi tidak berbeda sendiri-sendiri.
// Kalau backend nanti mengirim durasi, ganti pemanggilnya dengan nilai server.

export const DURASI_PRETEST_MENIT = 60
export const DURASI_PRETEST_DETIK = DURASI_PRETEST_MENIT * 60
export const JUMLAH_SOAL_PRETEST = 20
export const TIPE_SOAL_PRETEST = 3
