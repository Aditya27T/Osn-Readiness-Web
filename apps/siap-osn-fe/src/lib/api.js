import axios from 'axios'
import { ENDPOINTS } from '@/lib/endpoints.js'

// Kunci penyimpanan Bearer token Sanctum. Ditaruh di sini (bukan di
// stores/auth) agar tidak circular: api <- stores/auth <- router.
export const TOKEN_KEY = 'siap_osn_token'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: 15000,
  headers: { Accept: 'application/json' },
})

// Backend Laravel (Sanctum) memakai Bearer token, bukan cookie sesi.
// Token dibaca langsung dari localStorage agar tidak circular:
// api <- stores/auth <- router (lihat response interceptor di bawah).
api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// 401 dari endpoint ini adalah respons normal, bukan sesi kedaluwarsa:
// /auth/me (pengunjung anonim), /auth/login ("Email atau password salah"),
// /auth/logout (store sudah membersihkan sesi sendiri).
const TANPA_REDIRECT_401 = [ENDPOINTS.auth.me, ENDPOINTS.auth.login, ENDPOINTS.auth.logout]

// Beberapa request paralel bisa 401 bersamaan; cukup satu yang redirect.
let sedangRedirect = false

api.interceptors.response.use(
  (res) => res,
  async (err) => {
    const url = err.config?.url ?? ''
    const sesiHabis =
      err.response?.status === 401 && !TANPA_REDIRECT_401.some((p) => url.endsWith(p))
    if (sesiHabis && !sedangRedirect) {
      sedangRedirect = true
      try {
        // Import lazy agar tidak circular: api <- stores/auth <- router.
        const [{ default: router }, { useAuthStore }] = await Promise.all([
          import('@/router/index.js'),
          import('@/stores/auth.js'),
        ])
        if (router.currentRoute.value.name !== 'login') {
          const redirect = router.currentRoute.value.fullPath
          useAuthStore().$reset()
          await router.push({ name: 'login', query: { redirect } })
        }
      } finally {
        sedangRedirect = false
      }
    }
    return Promise.reject(err)
  },
)
