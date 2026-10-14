import { api } from '@/lib/api.js'
import { ENDPOINTS } from '@/lib/endpoints.js'
import { mapRiwayat } from '@/services/mappers/riwayat.js'

// Satu fungsi = satu endpoint. Respons paginasi Laravel dibaca langsung
// dari res.data (bukan unwrap) agar `meta` tidak hilang (lihat §1.3).
export const riwayatService = {
  // GET /riwayat. Semua filter opsional; tingkatId 0/None dikirim null
  // mengikuti controller (tingkat_id==0 -> null).
  async daftar({ jenis = null, tingkatId = null, perPage = 15, page = 1, namaTingkatById = {}, signal } = {}) {
    const params = { per_page: perPage, page }
    if (jenis) params.jenis = jenis
    if (tingkatId) params.tingkat_id = tingkatId
    const res = await api.get(ENDPOINTS.siswa.riwayat, { params, signal })
    const body = res?.data ?? {}
    const items = Array.isArray(body.data) ? body.data : []
    const meta = body.meta ?? {}
    return {
      items: items.map((item) => mapRiwayat(item, namaTingkatById)),
      total: meta.total ?? items.length,
      halaman: meta.current_page ?? page,
      perHalaman: meta.per_page ?? perPage,
      halamanTerakhir: meta.last_page ?? 1,
    }
  },
}
