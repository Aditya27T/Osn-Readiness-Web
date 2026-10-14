import { api } from '@/lib/api.js'
import { ENDPOINTS } from '@/lib/endpoints.js'
import { mapHasilPretest, mapTingkat, paketPretest } from '@/services/mappers/pretest.js'
import { unwrap } from '@/services/envelope.js'

// Satu fungsi = satu endpoint. Masuk & keluar bentuk FE;
// konversi Laravel di services/mappers/pretest.js.
export const pretestService = {
  // GET /tingkat -> daftar level + tahap (untuk pemilih tingkat manual).
  async tingkat({ signal } = {}) {
    const list = unwrap(await api.get(ENDPOINTS.siswa.tingkat, { signal }))
    return (Array.isArray(list) ? list : []).map(mapTingkat)
  },

  // POST /pretest {tingkat_id} -> 201 (baru) atau 200 (resume yang berjalan).
  async mulai({ tingkatId, signal } = {}) {
    const paket = paketPretest(unwrap(await api.post(ENDPOINTS.siswa.pretestMulai, { tingkat_id: tingkatId }, { signal })))
    // 'menunggu' tidak mungkin dari mulai (belum ada submit), tapi jangan dilempar.
if (paket.jenis === 'hasil') throw new Error('Respons mulai pre-test tidak berisi soal')
    return paket
  },

  // GET /pretest/{id} -> paket pengerjaan (resume) atau paket hasil.
  async lihat({ id, signal } = {}) {
    return paketPretest(unwrap(await api.get(ENDPOINTS.siswa.pretest(id), { signal })))
  },

  // PUT /pretest/{id}/jawaban {soal_id, jawaban_user} -> { message } tanpa data.
  // jawaban null = kosongkan jawaban soal itu.
  async simpanJawaban({ id, soalId, jawaban, signal } = {}) {
    await api.put(
      ENDPOINTS.siswa.pretestJawaban(id),
      { soal_id: soalId, jawaban_user: jawaban ?? null },
      { signal },
    )
  },

  // POST /pretest/{id}/submit (body kosong, idempoten) -> hasil.
  async kumpulkan({ id, signal } = {}) {
    return mapHasilPretest(unwrap(await api.post(ENDPOINTS.siswa.pretestSubmit(id), {}, { signal })))
  },
}
