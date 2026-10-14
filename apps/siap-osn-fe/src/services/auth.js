import { api, TOKEN_KEY } from '@/lib/api.js'
import { ENDPOINTS } from '@/lib/endpoints.js'
import { mapUser } from '@/services/mappers/user.js'
import { unwrap } from '@/services/envelope.js'

// Token hidup di service (bukan store/lib): store menyimpan salinan
// reaktifnya saja. Satu-satunya pembaca/penulis localStorage token.
export const authService = {
  tokenTersimpan() {
    return localStorage.getItem(TOKEN_KEY)
  },

  simpanToken(t) {
    if (t) localStorage.setItem(TOKEN_KEY, t)
    else localStorage.removeItem(TOKEN_KEY)
  },

  // Satu fungsi = satu endpoint. Masuk & keluar bentuk FE (nama, role);
  // konversi Laravel (name, password_confirmation, UserResource) di sini.
  // GET /auth/me -> { message, data: user }
  async me() {
    return mapUser(unwrap(await api.get(ENDPOINTS.auth.me)))
  },

  // POST /auth/login {email, password} -> { message, data: {user, token} }
  async login({ email, password }) {
    const { user, token } = unwrap(await api.post(ENDPOINTS.auth.login, { email, password }))
    return { user: mapUser(user), token }
  },

  // Laravel wajib password_confirmation; FE memakai nama field `konfirmasi`.
  async register({ nama, email, password, konfirmasi }) {
    const res = await api.post(ENDPOINTS.auth.register, {
      name: nama,
      email,
      password,
      password_confirmation: konfirmasi,
    })
    return mapUser(unwrap(res)?.user)
  },

  // PUT /auth/profile {name, email} -> { message, data: user }
  async updateProfile({ nama, email }) {
    return mapUser(unwrap(await api.put(ENDPOINTS.auth.profile, { name: nama, email })))
  },

  async logout() {
    await api.post(ENDPOINTS.auth.logout)
  },
}
