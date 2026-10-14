import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/lib/api.js'
import { authService } from '@/services/auth.js'

// Service diuji dengan lib/api di-mock (lihat §1.3.5 + §5.4).
vi.mock('@/lib/api.js', () => ({
  TOKEN_KEY: 'siap_osn_token',
  api: { get: vi.fn(), post: vi.fn(), put: vi.fn() },
}))

const userLaravel = {
  id: 1,
  name: 'Budi',
  email: 'budi@example.com',
  roles: ['siswa'],
  created_at: '2026-09-25T00:00:00Z',
}

beforeEach(() => {
  vi.resetAllMocks()
  localStorage.clear()
})

describe('authService', () => {
  it('me mengembalikan user bentuk FE', async () => {
    api.get.mockResolvedValue({ data: { message: 'OK', data: userLaravel } })
    const user = await authService.me()
    expect(api.get).toHaveBeenCalledWith('/auth/me')
    expect(user).toEqual({
      id: 1,
      nama: 'Budi',
      email: 'budi@example.com',
      role: 'siswa',
      created_at: '2026-09-25T00:00:00Z',
    })
  })

  it('login mengembalikan user + token', async () => {
    api.post.mockResolvedValue({
      data: { message: 'Login berhasil', data: { user: userLaravel, token: 'tok123' } },
    })
    const res = await authService.login({ email: 'budi@example.com', password: 'password123' })
    expect(api.post).toHaveBeenCalledWith('/auth/login', {
      email: 'budi@example.com',
      password: 'password123',
    })
    expect(res).toEqual({ user: expect.objectContaining({ nama: 'Budi' }), token: 'tok123' })
  })

  it('register memetakan ke field Laravel', async () => {
    api.post.mockResolvedValue({ data: { message: 'Registrasi berhasil', data: { user: userLaravel } } })
    const user = await authService.register({
      nama: 'Budi',
      email: 'budi@example.com',
      password: 'password123',
      konfirmasi: 'password123',
    })
    expect(api.post).toHaveBeenCalledWith('/auth/register', {
      name: 'Budi',
      email: 'budi@example.com',
      password: 'password123',
      password_confirmation: 'password123',
    })
    expect(user.nama).toBe('Budi')
  })

  it('updateProfile mengirim name/email', async () => {
    api.put.mockResolvedValue({ data: { message: 'OK', data: userLaravel } })
    await authService.updateProfile({ nama: 'Budi', email: 'budi@example.com' })
    expect(api.put).toHaveBeenCalledWith('/auth/profile', {
      name: 'Budi',
      email: 'budi@example.com',
    })
  })

  it('tokenTersimpan/simpanToken baca-tulis localStorage', () => {
    expect(authService.tokenTersimpan()).toBeNull()
    authService.simpanToken('tok123')
    expect(authService.tokenTersimpan()).toBe('tok123')
    authService.simpanToken(null)
    expect(authService.tokenTersimpan()).toBeNull()
  })
})
