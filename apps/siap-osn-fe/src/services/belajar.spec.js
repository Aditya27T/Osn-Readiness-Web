import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/lib/api.js'
import { belajarService } from '@/services/belajar.js'

vi.mock('@/lib/api.js', () => ({
  TOKEN_KEY: 'siap_osn_token',
  api: { get: vi.fn(), post: vi.fn(), put: vi.fn() },
}))

beforeEach(() => {
  vi.resetAllMocks()
})

const BARIS = {
  id: 3,
  tingkat_id: 1,
  kompetensi_id: 2,
  urutan: 1,
  judul: 'Struktur Data',
  deskripsi: 'Dasar-dasar',
  wajib: true,
  prioritas: 1,
  progress: { status: 'belajar', persentase: 0, tanggal_selesai: null },
  nilai_latihan_terbaik: 80,
  quiz_id: 9,
  latihan_belum_tersedia: false,
}

describe('belajarService', () => {
  it('daftar memetakan baris materi', async () => {
    api.get.mockResolvedValue({ data: { data: [BARIS] } })
    const hasil = await belajarService.daftar({ tingkatId: 1 })
    expect(api.get).toHaveBeenCalledWith('/materi', { params: { tingkat_id: 1 }, signal: undefined })
    expect(hasil[0]).toMatchObject({
      id: 3,
      judul: 'Struktur Data',
      wajib: true,
      prioritas: 1,
      progress: { status: 'belajar', persentase: 0, tanggalSelesai: null },
      nilaiTerbaik: 80,
      latihanTersedia: true,
      quizId: 9,
    })
  })

  it('quiz_id null saat latihan belum tersedia', async () => {
    api.get.mockResolvedValue({ data: { data: [{ ...BARIS, quiz_id: null, latihan_belum_tersedia: true }] } })
    const hasil = await belajarService.daftar({ tingkatId: 1 })
    expect(hasil[0].quizId).toBeNull()
    expect(hasil[0].latihanTersedia).toBe(false)
  })

  it('detail menambah isi/file/gambar', async () => {
    api.get.mockResolvedValue({
      data: { data: { ...BARIS, isi_materi: '<p>Halo</p>', file_materi: 'x.pdf', gambar: null } },
    })
    const hasil = await belajarService.detail({ id: 3 })
    expect(api.get).toHaveBeenCalledWith('/materi/3', { signal: undefined })
    expect(hasil.isi).toBe('<p>Halo</p>')
    expect(hasil.fileUrl).toMatch(/\/storage\/x\.pdf$/)
    expect(hasil.gambarUrl).toBeNull()
  })

  it('tandaiSelesai mengirim status selesai', async () => {
    api.put.mockResolvedValue({
      data: { message: 'Progress tersimpan', data: { materi_id: 3, status: 'selesai', persentase: 100, tanggal_selesai: '2026-10-01T00:00:00Z' } },
    })
    const hasil = await belajarService.tandaiSelesai({ id: 3 })
    expect(api.put).toHaveBeenCalledWith('/materi/3/progress', { status: 'selesai' }, { signal: undefined })
    expect(hasil).toMatchObject({ materiId: 3, status: 'selesai', persentase: 100 })
  })
})
