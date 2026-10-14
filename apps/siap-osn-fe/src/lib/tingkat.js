// Tingkat yang fiturnya belum rilis.
//
// Dipakai view siswa untuk menandai kartu tingkat sebagai "Segera" (Coming Soon)
// alih-alih "Terkunci": bedanya, mengunci murni aturan game's (siswa belum
// memenuhi syarat), sedangkan coming soon berarti memang belum ada konten.
//
// Cocok dari `nama` tingkat (bukan id) supaya tetap aman kalau id berubah
// di backend. Hapus dari daftar ini begitu fiturnya siap tayang.

const POLA_SEGERA_DATANG = [/provinsi/i]

export function segeraDatang(tingkat) {
  const nama = typeof tingkat?.nama === 'string' ? tingkat.nama : ''
  return POLA_SEGERA_DATANG.some((pola) => pola.test(nama))
}
