import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { belajarService } from '@/services/belajar.js'
import { useMateriStore } from '@/stores/materi.js'

vi.mock('@/services/belajar.js', () => ({
  belajarService: { daftar: vi.fn(), detail: vi.fn(), tandaiSelesai: vi.fn() },
}))

beforeEach(() => {
  setActivePinia(createPinia())
  vi.resetAllMocks()
})

const DAFTAR = [{ id: 3, judul: 'Struktur Data', progress: null }]

describe('useMateriStore', () => {
  it('fetchDaftar menyimpan daftar per tingkat + cache singkat', async () => {
    belajarService.daftar.mockResolvedValue(DAFTAR)
    const materi = useMateriStore()
    await materi.fetchDaftar({ tingkatId: 1 })
    expect(materi.daftar).toHaveLength(1)
    expect(materi.tingkatId).toBe(1)
    await materi.fetchDaftar({ tingkatId: 1 })
    expect(belajarService.daftar).toHaveBeenCalledTimes(1)
    await materi.fetchDaftar({ tingkatId: 2 })
    expect(belajarService.daftar).toHaveBeenCalledTimes(2)
  })

  it('tandaiSelesai menyelaraskan daftar dan detail lokal', async () => {
    belajarService.daftar.mockResolvedValue([{ id: 3, judul: 'X', progress: null }])
    belajarService.tandaiSelesai.mockResolvedValue({ materiId: 3, status: 'selesai', persentase: 100, tanggalSelesai: 'T' })
    const materi = useMateriStore()
    await materi.fetchDaftar({ tingkatId: 1 })
    await materi.tandaiSelesai({ id: 3 })
    expect(materi.daftar[0].progress).toMatchObject({ status: 'selesai', persentase: 100 })
  })

  it('$reset mengosongkan state', async () => {
    belajarService.daftar.mockResolvedValue(DAFTAR)
    const materi = useMateriStore()
    await materi.fetchDaftar({ tingkatId: 1 })
    materi.$reset()
    expect(materi.daftar).toEqual([])
    expect(materi.tingkatId).toBeNull()
  })
})
