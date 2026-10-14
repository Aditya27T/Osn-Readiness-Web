import { api } from '@/lib/api.js'
import { ENDPOINTS } from '@/lib/endpoints.js'
import { mapDashboard } from '@/services/mappers/dashboard.js'
import { unwrap } from '@/services/envelope.js'

export const siswaService = {
  // `signal` (AbortController) dipakai store untuk membatalkan request
  // milik akun lama saat logout/ganti akun.
  async dashboard({ signal } = {}) {
    return mapDashboard(unwrap(await api.get(ENDPOINTS.siswa.dashboard, { signal })))
  },
}
