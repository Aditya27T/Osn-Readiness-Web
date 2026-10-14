import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { belajarService } from '@/services/belajar.js'
import { riwayatService } from '@/services/riwayat.js'
import { siswaService } from '@/services/siswa.js'
import { useProgressStore } from '@/stores/progress.js'

vi.mock('@/services/siswa.js', () => ({
  siswaService: { dashboard: vi.fn() },
}))

vi.mock('@/services/riwayat.js', () => ({
  riwayatService: { daftar: vi.fn(async () => ({ items: [], total: 0 })) },
}))

vi.mock('@/services/belajar.js', () => ({
  belajarService: { daftar: vi.fn(async () => []), detail: vi.fn(), tandaiSelesai: vi.fn() },
}))

vi.mock('@/stores/pretest.js', () => ({
  usePretestStore: () => ({
    tingkatList: [{ id: 1, nama: 'Kabupaten', terbuka: true }],
    muatTingkat: vi.fn(async () => {}),
  }),
}))

vi.mock('@/stores/riwayat.js', () => ({
  useRiwayatStore: () => ({ terbaris: vi.fn(async () => []) }),
}))

const hasil = { preTestSelesai: false, materiSelesai: 0, materiTotal: 0 }

beforeEach(() => {
  setActivePinia(createPinia())
  vi.resetAllMocks()
  riwayatService.daftar.mockResolvedValue({ items: [], total: 0 })
})

describe('progress store', () => {
  it('fetchDashboard menyimpan hasil dan menandai loaded', async () => {
    siswaService.dashboard.mockResolvedValue(hasil)
    const progress = useProgressStore()
    await progress.fetchDashboard()
    expect(progress.data).toMatchObject({
      ...hasil,
      riwayat: [],
      kompetensi: [],
      rekomendasi: [],
      simulasiDiikuti: 0,
      rataRataNilai: 0,
    })
    expect(progress.loaded).toBe(true)
    expect(progress.loading).toBe(false)
  })

  it('statistik simulasi dihitung dari riwayat, bukan nol placeholder', async () => {
    siswaService.dashboard.mockResolvedValue(hasil)
    riwayatService.daftar.mockResolvedValue({
      items: [{ nilai: 60 }, { nilai: 80 }, { nilai: 100 }],
      total: 3,
    })
    const progress = useProgressStore()
    await progress.fetchDashboard()
    await vi.waitFor(() => expect(progress.data.simulasiDiikuti).toBe(3))
    expect(riwayatService.daftar).toHaveBeenCalledWith(
      expect.objectContaining({ jenis: 'simulasi', perPage: 100 }),
    )
    expect(progress.data.rataRataNilai).toBe(80)
  })

  it('nilai simulasi null diabaikan saat menghitung rata-rata', async () => {
    siswaService.dashboard.mockResolvedValue(hasil)
    riwayatService.daftar.mockResolvedValue({
      items: [{ nilai: 50 }, { nilai: null }, { nilai: 70 }],
      total: 3,
    })
    const progress = useProgressStore()
    await progress.fetchDashboard()
    await vi.waitFor(() => expect(progress.data.simulasiDiikuti).toBe(3))
    expect(progress.data.rataRataNilai).toBe(60)
  })

  it('data segar tidak memicu request ulang kecuali force', async () => {
    siswaService.dashboard.mockResolvedValue(hasil)
    const progress = useProgressStore()
    await progress.fetchDashboard()
    await progress.fetchDashboard()
    expect(siswaService.dashboard).toHaveBeenCalledTimes(1)
    await progress.fetchDashboard({ force: true })
    expect(siswaService.dashboard).toHaveBeenCalledTimes(2)
  })

  it('gagal request menandai error tanpa menghapus data lama', async () => {
    siswaService.dashboard.mockRejectedValue(new Error('putus'))
    const progress = useProgressStore()
    await progress.fetchDashboard()
    expect(progress.error).toBe(true)
    expect(progress.loaded).toBe(false)
  })

  it('markPreTestCompleted membuka akses pre-test di dashboard', () => {
    const progress = useProgressStore()
    progress.markPreTestCompleted()
    expect(progress.data.preTestSelesai).toBe(true)
  })

  it('flag optimistis dari markPreTestCompleted ditimpa data server berikutnya', async () => {
    // markPreTestCompleted() memberi update optimistis sampai fetch berikutnya;
    // setelah itu server yang berkuasa (turnover baru bisa mengunci lagi).
    siswaService.dashboard.mockResolvedValue({ ...hasil, preTestSelesai: true })
    const progress = useProgressStore()
    progress.markPreTestCompleted()
    await progress.fetchDashboard()
    expect(progress.data.preTestSelesai).toBe(true)

    siswaService.dashboard.mockResolvedValue({ ...hasil, preTestSelesai: false })
    await progress.fetchDashboard({ force: true })
    expect(progress.data.preTestSelesai).toBe(false)
  })

  it('fetchDashboard mengisi statistik materi dari tingkat terbuka', async () => {
    siswaService.dashboard.mockResolvedValue(hasil)
    belajarService.daftar.mockResolvedValue([
      { id: 1, judul: 'A', wajib: true, prioritas: 1, progress: { status: 'selesai' }, nilaiTerbaik: 80 },
      { id: 2, judul: 'B', wajib: false, prioritas: null, progress: null, nilaiTerbaik: null },
    ])
    const progress = useProgressStore()
    await progress.fetchDashboard()
    await vi.waitFor(() => expect(progress.data.materiTotal).toBe(2))
    expect(progress.data.materiSelesai).toBe(1)
    expect(progress.data.kompetensi).toEqual([{ nama: 'A', skor: 80, target: null }])
    expect(progress.data.rekomendasi).toEqual([{ judul: 'A', sub: 'Prioritas 1', badge: 'Wajib' }])
  })

  it('$reset mengosongkan state', async () => {
    siswaService.dashboard.mockResolvedValue(hasil)
    const progress = useProgressStore()
    await progress.fetchDashboard()
    progress.$reset()
    expect(progress.loaded).toBe(false)
    expect(progress.error).toBe(false)
  })
})
