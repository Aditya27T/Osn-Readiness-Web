import { api } from '@/lib/api.js'
import { ENDPOINTS } from '@/lib/endpoints.js'
import { mapHasilLatihan, paketLatihan } from '@/services/mappers/latihan.js'
import { unwrap } from '@/services/envelope.js'

// Satu fungsi = satu endpoint. Pola sama dengan pre-test, tanpa timer
// dan tanpa pemilih tingkat (quiz sudah terikat satu materi).
export const latihanService = {
  // POST /quiz/{quiz}/mulai -> 201 (baru) atau 200 (lanjutkan).
  async mulai({ quizId, signal } = {}) {
    const paket = paketLatihan(unwrap(await api.post(ENDPOINTS.siswa.quizMulai(quizId), {}, { signal })))
    // 'menunggu' tidak mungkin dari mulai (belum ada submit), tapi jangan dilempar.
if (paket.jenis === 'hasil') throw new Error('Respons mulai latihan tidak berisi soal')
    return paket
  },

  // GET /quiz-pengerjaan/{id} -> paket pengerjaan atau hasil.
  async lihat({ id, signal } = {}) {
    return paketLatihan(unwrap(await api.get(ENDPOINTS.siswa.pengerjaan(id), { signal })))
  },

  // PUT .../jawaban {soal_id, jawaban_user} -> { message } tanpa data.
  async simpanJawaban({ id, soalId, jawaban, signal } = {}) {
    await api.put(
      ENDPOINTS.siswa.pengerjaanJawaban(id),
      { soal_id: soalId, jawaban_user: jawaban ?? null },
      { signal },
    )
  },

  // POST .../submit (body kosong, idempoten) -> hasil + status benar per soal.
  async kumpulkan({ id, signal } = {}) {
    return mapHasilLatihan(unwrap(await api.post(ENDPOINTS.siswa.pengerjaanSubmit(id), {}, { signal })))
  },
}
