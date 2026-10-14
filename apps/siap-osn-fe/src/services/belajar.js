import { api } from '@/lib/api.js'
import { ENDPOINTS } from '@/lib/endpoints.js'
import { mapMateri, mapMateriDetail, mapProgressTersimpan } from '@/services/mappers/belajar.js'
import { unwrap } from '@/services/envelope.js'

// Satu fungsi = satu endpoint. Daftar/detail me-return Resource langsung
// ({data} tanpa message) — unwrap tetap bisa dipakai karena yang diambil
// hanya `data`.
export const belajarService = {
  // GET /materi?tingkat_id= -> daftar terurut server (wajib dulu).
  async daftar({ tingkatId = null, signal } = {}) {
    const params = {}
    if (tingkatId) params.tingkat_id = tingkatId
    const list = unwrap(await api.get(ENDPOINTS.siswa.materi, { params, signal }))
    return (Array.isArray(list) ? list : []).map(mapMateri)
  },

  // GET /materi/{id} -> detail + isi/file/gambar.
  async detail({ id, signal } = {}) {
    return mapMateriDetail(unwrap(await api.get(ENDPOINTS.siswa.materiDetail(id), { signal })))
  },

  // PUT /materi/{id}/progress {status: 'selesai'}.
  async tandaiSelesai({ id, signal } = {}) {
    return mapProgressTersimpan(
      unwrap(await api.put(ENDPOINTS.siswa.materiProgress(id), { status: 'selesai' }, { signal })),
    )
  },
}
