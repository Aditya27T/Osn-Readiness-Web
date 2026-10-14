import { api } from '@/lib/api.js'
import { ENDPOINTS } from '@/lib/endpoints.js'
import { mapHasilSimulasi, mapReview, mapSyarat, mapSimulasi, paketSimulasi } from '@/services/mappers/simulasi.js'
import { unwrap } from '@/services/envelope.js'

// Satu fungsi = satu endpoint. Pola sama dengan services/latihan.js.
// Semua respons memakai unwrap; `syarat` membalas Resource langsung
// ({ data }) tanpa message, dan unwrap tetap aman untuk itu.
export const simulasiService = {
  // GET /simulasi/syarat?tingkat_id= -> { terpenuhi, alasan, rincian }
  async syarat({ tingkatId, signal } = {}) {
    return mapSyarat(unwrap(await api.get(ENDPOINTS.siswa.simulasiSyarat, {
      params: { tingkat_id: tingkatId },
      signal,
    })))
  },

  // GET /simulasi?tingkat_id= -> daftar simulasi + sisa kuota per putaran.
  async daftar({ tingkatId, signal } = {}) {
    const list = unwrap(await api.get(ENDPOINTS.siswa.simulasi, {
      params: { tingkat_id: tingkatId },
      signal,
    }))
    return (Array.isArray(list) ? list : []).map(mapSimulasi)
  },

  // POST /simulasi/{id}/mulai -> 201 (baru) / 200 (lanjutkan yang berjalan).
  // Batas waktu ada di `batasPada`, jadi reload tidak menambah waktu.
  async mulai({ simulasiId, signal } = {}) {
    const paket = paketSimulasi(
      unwrap(await api.post(ENDPOINTS.siswa.simulasiMulai(simulasiId), {}, { signal })),
    )
    // 'menunggu' mungkin terjadi bila submit sebelumnya gagal menilai.
    if (paket.jenis === 'hasil') throw new Error('Respons mulai simulasi tidak berisi soal')
    return paket
  },

  // GET /hasil-simulasi/{id} -> paket pengerjaan/menunggu atau paket hasil.
  async lihat({ id, signal } = {}) {
    return paketSimulasi(unwrap(await api.get(ENDPOINTS.siswa.hasilSimulasi(id), { signal })))
  },

  // PUT .../jawaban {soal_id, jawaban_user} -> { message } tanpa data.
  async simpanJawaban({ id, soalId, jawaban, signal } = {}) {
    await api.put(
      ENDPOINTS.siswa.hasilSimulasiJawaban(id),
      { soal_id: soalId, jawaban_user: jawaban ?? null },
      { signal },
    )
  },

  // POST .../submit (idempoten) -> hasil + status benar per soal.
  async kumpulkan({ id, signal } = {}) {
    return mapHasilSimulasi(
      unwrap(await api.post(ENDPOINTS.siswa.hasilSimulasiSubmit(id), {}, { signal })),
    )
  },

  // GET .../review -> hasil + kunci & pembahasan per soal.
  // 409 SIMULASI_BELUM_DINILAI dilempar apa adanya supaya view bisa
  // menampilkan pesan tunggu.
  async review({ id, signal } = {}) {
    return mapReview(unwrap(await api.get(ENDPOINTS.siswa.hasilSimulasiReview(id), { signal })))
  },
}
