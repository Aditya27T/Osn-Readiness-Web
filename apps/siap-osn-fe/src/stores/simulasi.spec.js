import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { simulasiService } from '@/services/simulasi.js'
import { STATUS_SIMULASI, useSimulasiStore, WAKTU_HABIS } from '@/stores/simulasi.js'

vi.mock('@/services/simulasi.js', () => ({
  simulasiService: {
    syarat: vi.fn(),
    daftar: vi.fn(),
    mulai: vi.fn(),
    lihat: vi.fn(),
    simpanJawaban: vi.fn(),
    kumpulkan: vi.fn(),
    review: vi.fn(),
  },
}))

beforeEach(() => {
  setActivePinia(createPinia())
  vi.resetAllMocks()
})

const PAKET_PENGERJAAN = {
  jenis: 'pengerjaan',
  id: 11,
  simulasiId: 2,
  pretestId: 5,
  mulaiPada: '2026-10-06T02:00:00Z',
  batasPada: '2026-10-06T03:30:00Z',
  soal: [{ id: 501, tipe: 'ganda', jawaban: null }],
}

const PAKET_MENUNGGU = {
  jenis: 'menunggu',
  id: 11,
  simulasiId: 2,
  pretestId: 5,
  mulaiPada: '2026-10-06T02:00:00Z',
  batasPada: '2026-10-06T03:30:00Z',
  soal: [],
}

const HASIL = { id: 11, simulasiId: 2, nilai: 88.5, lulus: true, jawaban: [{ soalId: 501, benar: true }] }

const KODE = (kode, status) => ({ response: { status, data: { kode, message: 'x' } } })

describe('useSimulasiStore', () => {
  it('muatRuang mengisi daftar dan syarat satu tingkat', async () => {
    simulasiService.daftar.mockResolvedValue([{ id: 2, nama: 'Simulasi 1' }])
    simulasiService.syarat.mockResolvedValue({ terpenuhi: false, alasan: 'belum_pretest', rincian: [] })
    const simulasi = useSimulasiStore()
    await simulasi.muatRuang({ tingkatId: 1 })
    expect(simulasiService.daftar).toHaveBeenCalledWith({ tingkatId: 1, signal: expect.anything() })
    expect(simulasiService.syarat).toHaveBeenCalledWith({ tingkatId: 1, signal: expect.anything() })
    expect(simulasi.daftar).toHaveLength(1)
    expect(simulasi.syarat.terpenuhi).toBe(false)
  })

  it('mulai menyimpan batas_pada dari server', async () => {
    simulasiService.mulai.mockResolvedValue(PAKET_PENGERJAAN)
    const simulasi = useSimulasiStore()
    await simulasi.mulai({ simulasiId: 2 })
    expect(simulasi.status).toBe(STATUS_SIMULASI.MENGERJAKAN)
    expect(simulasi.hasilId).toBe(11)
    expect(simulasi.simulasiId).toBe(2)
    expect(simulasi.batasPada).toBe('2026-10-06T03:30:00Z')
    expect(simulasi.soal).toHaveLength(1)
  })

  it('mulai menolak 409 SYARAT_SIMULASI_BELUM_TERPENUHI dengan error asli', async () => {
    simulasiService.mulai.mockRejectedValue(
      KODE('SYARAT_SIMULASI_BELUM_TERPENUHI', 409),
    )
    const simulasi = useSimulasiStore()
    await expect(simulasi.mulai({ simulasiId: 2 })).rejects.toMatchObject({
      response: { data: { kode: 'SYARAT_SIMULASI_BELUM_TERPENUHI' } },
    })
    expect(simulasi.error).toBe(true)
  })

  it('mulai saat submit sebelumnya gagal menilai -> status menilai', async () => {
    simulasiService.mulai.mockResolvedValue(PAKET_MENUNGGU)
    const simulasi = useSimulasiStore()
    await simulasi.mulai({ simulasiId: 2 })
    expect(simulasi.status).toBe(STATUS_SIMULASI.MENILAI)
    // Tidak kembali ke mode mengerjakan: soal kosong.
    expect(simulasi.soal).toEqual([])
    expect(simulasi.hasilId).toBe(11)
  })

  it('simpanJawaban mengembalikan waktu-habis saat server menutup percobaan', async () => {
    simulasiService.mulai.mockResolvedValue(PAKET_PENGERJAAN)
    simulasiService.simpanJawaban.mockRejectedValue(KODE('WAKTU_HABIS', 409))
    const simulasi = useSimulasiStore()
    await simulasi.mulai({ simulasiId: 2 })
    expect(await simulasi.simpanJawaban({ soalId: 501, jawaban: 'A' })).toBe(WAKTU_HABIS)
    // WAKTU_HABIS bukan kegagalan autosave biasa.
    expect(simulasi.simpanError).toBe(false)
  })

  it('simpanJawaban menandai simpanError untuk kegagalan lain', async () => {
    simulasiService.mulai.mockResolvedValue(PAKET_PENGERJAAN)
    simulasiService.simpanJawaban.mockResolvedValue(undefined)
    const simulasi = useSimulasiStore()
    await simulasi.mulai({ simulasiId: 2 })
    simulasiService.simpanJawaban.mockRejectedValue(new Error('putus'))
    expect(await simulasi.simpanJawaban({ soalId: 501, jawaban: 'B' })).toBeNull()
    expect(simulasi.simpanError).toBe(true)
    // Jawaban tetap tampil di layar walau simpan gagal.
    expect(simulasi.soal[0].jawaban).toBe('B')
  })

  it('kumpulkan menyimpan hasil dan mengosongkan soal', async () => {
    simulasiService.mulai.mockResolvedValue(PAKET_PENGERJAAN)
    simulasiService.kumpulkan.mockResolvedValue(HASIL)
    const simulasi = useSimulasiStore()
    await simulasi.mulai({ simulasiId: 2 })
    const hasil = await simulasi.kumpulkan()
    expect(hasil.nilai).toBe(88.5)
    expect(simulasi.status).toBe(STATUS_SIMULASI.SELESAI)
    expect(simulasi.soal).toEqual([])
  })

  it('kumpulkan 503 HASIL_SEDANG_DIPROSES -> status menilai, lalu cekHasil selesai', async () => {
    simulasiService.mulai.mockResolvedValue(PAKET_PENGERJAAN)
    simulasiService.kumpulkan.mockRejectedValue(KODE('HASIL_SEDANG_DIPROSES', 503))
    const simulasi = useSimulasiStore()
    await simulasi.mulai({ simulasiId: 2 })
    await expect(simulasi.kumpulkan()).resolves.toBeNull()
    expect(simulasi.status).toBe(STATUS_SIMULASI.MENILAI)
    expect(await simulasi.cekHasil()).toBeNull()

    simulasiService.kumpulkan.mockResolvedValue(HASIL)
    expect((await simulasi.cekHasil())?.nilai).toBe(88.5)
    expect(simulasi.status).toBe(STATUS_SIMULASI.SELESAI)
  })

  it('lanjutkan dari riwayat membuka hasil yang sudah selesai', async () => {
    simulasiService.lihat.mockResolvedValue({ jenis: 'hasil', hasil: HASIL })
    const simulasi = useSimulasiStore()
    await simulasi.lanjutkan({ id: 11 })
    expect(simulasi.status).toBe(STATUS_SIMULASI.SELESAI)
    expect(simulasi.hasil.nilai).toBe(88.5)
  })

  it('muatReview mengisi kunci dan pembahasan', async () => {
    simulasiService.review.mockResolvedValue({ nilai: 88.5, soal: [{ id: 501, kunci: 'B' }] })
    const simulasi = useSimulasiStore()
    await simulasi.muatReview({ id: 11 })
    expect(simulasiService.review).toHaveBeenCalledWith({ id: 11, signal: expect.anything() })
    expect(simulasi.review.soal[0].kunci).toBe('B')
  })

  it('muatReview 409 SIMULASI_BELUM_DINILAI melempar error asli', async () => {
    simulasiService.review.mockRejectedValue(KODE('SIMULASI_BELUM_DINILAI', 409))
    const simulasi = useSimulasiStore()
    await expect(simulasi.muatReview({ id: 11 })).rejects.toMatchObject({
      response: { data: { kode: 'SIMULASI_BELUM_DINILAI' } },
    })
    expect(simulasi.review).toBeNull()
  })

  it('$reset mengosongkan seluruh state', async () => {
    simulasiService.daftar.mockResolvedValue([{ id: 2 }])
    simulasiService.syarat.mockResolvedValue({ terpenuhi: true })
    simulasiService.mulai.mockResolvedValue(PAKET_PENGERJAAN)
    const simulasi = useSimulasiStore()
    await simulasi.muatRuang({ tingkatId: 1 })
    await simulasi.mulai({ simulasiId: 2 })
    simulasi.$reset()
    expect(simulasi.status).toBe(STATUS_SIMULASI.IDLE)
    expect(simulasi.daftar).toEqual([])
    expect(simulasi.syarat).toBeNull()
    expect(simulasi.soal).toEqual([])
    expect(simulasi.hasilId).toBeNull()
    expect(simulasi.batasPada).toBeNull()
  })
})
