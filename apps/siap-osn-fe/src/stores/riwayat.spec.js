import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { riwayatService } from '@/services/riwayat.js'
import { useRiwayatStore } from '@/stores/riwayat.js'

vi.mock('@/services/riwayat.js', () => ({
  riwayatService: { daftar: vi.fn() },
}))

vi.mock('@/stores/pretest.js', () => ({
  usePretestStore: () => ({ tingkatList: [{ id: 1, nama: 'Kabupaten' }] }),
}))

beforeEach(() => {
  setActivePinia(createPinia())
  vi.resetAllMocks()
})

const HALAMAN = {
  items: [{ jenis: 'simulasi', judul: 'Simulasi — Tingkat Kabupaten', nilai: 80 }],
  total: 22,
  halaman: 1,
  perHalaman: 15,
  halamanTerakhir: 2,
}

describe('useRiwayatStore', () => {
  it('fetchDaftar menyimpan items + meta', async () => {
    riwayatService.daftar.mockResolvedValue(HALAMAN)
    const riwayat = useRiwayatStore()
    await riwayat.fetchDaftar()
    expect(riwayat.items).toHaveLength(1)
    expect(riwayat.total).toBe(22)
    expect(riwayat.halamanTerakhir).toBe(2)
    expect(riwayat.error).toBe(false)
  })

  it('ganti filter dan halaman memicu fetch ulang', async () => {
    riwayatService.daftar.mockImplementation(async ({ page }) => ({ ...HALAMAN, halaman: page }))
    const riwayat = useRiwayatStore()
    await riwayat.fetchDaftar()
    await riwayat.keHalaman(2)
    expect(riwayat.halaman).toBe(2)
    expect(riwayatService.daftar).toHaveBeenCalledWith(expect.objectContaining({ page: 2 }))
    await riwayat.aturJenis('pretest')
    expect(riwayat.halaman).toBe(1)
    expect(riwayatService.daftar).toHaveBeenCalledWith(expect.objectContaining({ jenis: 'pretest', page: 1 }))
  })

  it('gagal request menandai error', async () => {
    riwayatService.daftar.mockRejectedValue(new Error('putus'))
    const riwayat = useRiwayatStore()
    await riwayat.fetchDaftar()
    expect(riwayat.error).toBe(true)
  })

  it('terbaris mengembalikan items tanpa mengubah state filter', async () => {
    riwayatService.daftar.mockResolvedValue({ ...HALAMAN, items: [{ jenis: 'x', judul: 'Y' }] })
    const riwayat = useRiwayatStore()
    const items = await riwayat.terbaris({ jumlah: 5 })
    expect(items).toHaveLength(1)
    expect(riwayat.items).toEqual([])
    expect(riwayatService.daftar).toHaveBeenCalledWith(
      expect.objectContaining({ perPage: 5, page: 1 }),
    )
  })

  it('$reset mengosongkan filter dan pagination', async () => {
    riwayatService.daftar.mockResolvedValue(HALAMAN)
    const riwayat = useRiwayatStore()
    await riwayat.fetchDaftar()
    riwayat.$reset()
    expect(riwayat.items).toEqual([])
    expect(riwayat.jenis).toBe('semua')
    expect(riwayat.halaman).toBe(1)
  })
})
