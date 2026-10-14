import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { authService } from '@/services/auth.js'
import { useAuthStore } from '@/stores/auth.js'
import { useLatihanStore } from '@/stores/latihan.js'
import { useMateriStore } from '@/stores/materi.js'
import { usePretestStore } from '@/stores/pretest.js'
import { useRiwayatStore } from '@/stores/riwayat.js'

// Store tidak tahu HTTP: service di-mock total (lihat §1.3.5 + §5.4).
vi.mock('@/services/auth.js', () => ({
  authService: {
    tokenTersimpan: vi.fn(),
    simpanToken: vi.fn(),
    me: vi.fn(),
    login: vi.fn(),
    register: vi.fn(),
    updateProfile: vi.fn(),
    logout: vi.fn(),
  },
}))

const userSiswa = {
  id: 1,
  nama: 'Budi',
  email: 'budi@example.com',
  role: 'siswa',
  created_at: '2026-09-25T00:00:00Z',
}

beforeEach(() => {
  setActivePinia(createPinia())
  vi.resetAllMocks()
  authService.tokenTersimpan.mockReturnValue(null)
  localStorage.clear()
})

describe('auth store', () => {
  it('fetchMe sukses mengisi user dan initialized', async () => {
    authService.tokenTersimpan.mockReturnValue('tok123')
    authService.me.mockResolvedValue(userSiswa)
    const auth = useAuthStore()
    await auth.fetchMe()
    expect(authService.me).toHaveBeenCalled()
    expect(auth.user).toEqual(userSiswa)
    expect(auth.initialized).toBe(true)
    expect(auth.isAuthenticated).toBe(true)
    expect(auth.isSiswa).toBe(true)
    expect(auth.isSuperAdmin).toBe(false)
  })

  it('fetchMe tanpa token tidak memanggil service', async () => {
    const auth = useAuthStore()
    await auth.fetchMe()
    expect(authService.me).not.toHaveBeenCalled()
    expect(auth.user).toBeNull()
    expect(auth.initialized).toBe(true)
  })

  it('fetchMe gagal mengosongkan user dan token', async () => {
    authService.tokenTersimpan.mockReturnValue('tok-basi')
    authService.me.mockRejectedValue({ response: { status: 401 } })
    const auth = useAuthStore()
    await auth.fetchMe()
    expect(auth.user).toBeNull()
    expect(auth.initialized).toBe(true)
    expect(auth.isAuthenticated).toBe(false)
    expect(authService.simpanToken).toHaveBeenCalledWith(null)
  })

  it('login menyimpan token dan user dari service', async () => {
    authService.login.mockResolvedValue({ user: userSiswa, token: 'tok123' })
    const auth = useAuthStore()
    const user = await auth.login({ email: 'budi@example.com', password: 'password123' })
    expect(authService.login).toHaveBeenCalledWith({
      email: 'budi@example.com',
      password: 'password123',
    })
    expect(user).toEqual(userSiswa)
    expect(auth.isAuthenticated).toBe(true)
    expect(authService.simpanToken).toHaveBeenCalledWith('tok123')
  })

  it('register meneruskan payload tanpa login', async () => {
    authService.register.mockResolvedValue(userSiswa)
    const auth = useAuthStore()
    const payload = { nama: 'Budi', email: 'budi@example.com', password: 'password123', konfirmasi: 'password123' }
    const result = await auth.register(payload)
    expect(authService.register).toHaveBeenCalledWith(payload)
    expect(result).toEqual(userSiswa)
    expect(auth.user).toBeNull()
  })

  it('updateProfile menggabung data server di atas payload', async () => {
    const auth = useAuthStore()
    authService.updateProfile.mockResolvedValue({ ...userSiswa, nama: 'Budi Baru' })
    const result = await auth.updateProfile({ nama: 'Budi Baru', email: 'budi@example.com' })
    expect(result.nama).toBe('Budi Baru')
    expect(auth.user.nama).toBe('Budi Baru')
  })

  it('logout mengosongkan user dan token meski request gagal', async () => {
    authService.tokenTersimpan.mockReturnValue('tok123')
    authService.logout.mockRejectedValue(new Error('network'))
    const auth = useAuthStore()
    auth.user = userSiswa
    await auth.logout()
    expect(auth.user).toBeNull()
    expect(authService.simpanToken).toHaveBeenCalledWith(null)
  })

  it('login akun kedua membuang data akun sebelumnya', async () => {
    // Sisa akun pertama: pretest berjalan + ID di localStorage.
    const pretest = usePretestStore()
    pretest.pretestId = 12
    pretest.tingkatId = 1
    localStorage.setItem('siap_osn_pretest_aktif', JSON.stringify({ id: 12, tingkatId: 1 }))

    authService.login.mockResolvedValue({ user: userSiswa, token: 'tok456' })
    const auth = useAuthStore()
    await auth.login({ email: 'budi@example.com', password: 'password123' })

    expect(pretest.idTersimpan()).toBeNull()
    expect(pretest.pretestId).toBeNull()
    expect(useLatihanStore().status).toBe('idle')
    expect(useMateriStore().daftar).toEqual([])
    expect(useRiwayatStore().items).toEqual([])
  })
})
