import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/lib/api.js'
import { riwayatService } from '@/services/riwayat.js'

vi.mock('@/lib/api.js', () => ({
  TOKEN_KEY: 'siap_osn_token',
  api: { get: vi.fn(), post: vi.fn(), put: vi.fn() },
}))

beforeEach(() => {
  vi.resetAllMocks()
})

describe('riwayatService', () => {
  it('daftar membaca items + meta paginasi langsung dari body', async () => {
    api.get.mockResolvedValue({
      data: {
        data: [
          { jenis_hasil: 'simulasi', referensi_id: 12, tingkat_id: 1, nilai: 85.5, tanggal: '2026-09-22T10:00:00Z' },
          { jenis_hasil: 'pretest', referensi_id: 7, tingkat_id: 2, nilai: null, tanggal: null },
        ],
        meta: { current_page: 2, per_page: 15, last_page: 4, total: 50 },
      },
    })
    const hasil = await riwayatService.daftar({ page: 2, namaTingkatById: { 1: 'Kabupaten', 2: 'Provinsi' } })
    expect(api.get).toHaveBeenCalledWith('/riwayat', { params: { per_page: 15, page: 2 }, signal: undefined })
    expect(hasil.total).toBe(50)
    expect(hasil.halaman).toBe(2)
    expect(hasil.halamanTerakhir).toBe(4)
    expect(hasil.items[0]).toMatchObject({
      jenis: 'simulasi',
      judul: 'Simulasi — Tingkat Kabupaten',
      nilai: 85.5,
    })
    expect(hasil.items[1].nilai).toBeNull()
  })

  it('filter hanya dikirim bila diisi', async () => {
    api.get.mockResolvedValue({ data: { data: [], meta: {} } })
    await riwayatService.daftar({ jenis: 'pretest', tingkatId: 1, perPage: 5 })
    expect(api.get).toHaveBeenCalledWith(
      '/riwayat',
      { params: { per_page: 5, page: 1, jenis: 'pretest', tingkat_id: 1 }, signal: undefined },
    )
  })

  it('body tanpa data tetap aman', async () => {
    api.get.mockResolvedValue({ data: {} })
    const hasil = await riwayatService.daftar({})
    expect(hasil.items).toEqual([])
    expect(hasil.total).toBe(0)
  })
})
