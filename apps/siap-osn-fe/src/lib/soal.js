// State jawaban per soal dipakai bersama oleh pre-test, latihan, dan simulasi
// supaya ketiganya menghitung "sudah ngisi / ragu / kosong" dengan aturan sama.

export function sudahDijawab(jawaban, i) {
  const j = jawaban?.[i]
  if (j === undefined || j === null) return false
  if (Array.isArray(j)) return j.length > 0
  if (typeof j === 'string') return j.trim().length > 0
  return true
}

function adalahRagu(ragu, i) {
  return Boolean(ragu?.[i])
}

// Ringkasan untuk panel nomor & modal konfirmasi.
// `kosong` = belum diisi dan tidak ditandai ragu, jadi inilah yang dicek
// sebelum mengizinkan submit.
export function hitungRingkasan(soal, jawaban, ragu) {
  const total = soal.length
  let terjawab = 0
  let raguCount = 0
  const kosong = []

  soal.forEach((_, i) => {
    const isi = sudahDijawab(jawaban, i)
    if (isi) terjawab += 1
    if (adalahRagu(ragu, i)) raguCount += 1
    if (!isi && !adalahRagu(ragu, i)) kosong.push(i)
  })

  return { total, terjawab, ragu: raguCount, belum: kosong.length, kosong }
}

export function kelasNomer(i, { aktif, ragu, jawaban }) {
  const isi = sudahDijawab(jawaban, i)
  const ditandai = adalahRagu(ragu, i)
  return {
    'n-open': i === aktif,
    'n-ragu': i !== aktif && ditandai,
    'n-done': i !== aktif && !ditandai && isi,
    'n-empty': i !== aktif && !ditandai && !isi,
  }
}

export function nomorSoal(i) {
  return String(i + 1).padStart(2, '0')
}
