import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { latihanService } from '@/services/latihan.js'
import { STATUS_LATIHAN, useLatihanStore } from '@/stores/latihan.js'

vi.mock('@/services/latihan.js', () => ({
  latihanService: { mulai: vi.fn(), lihat: vi.fn(), simpanJawaban: vi.fn(), kumpulkan: vi.fn() },
}))

beforeEach(() => {
  setActivePinia(createPinia())
  vi.resetAllMocks()
  localStorage.clear()
})

const PAKET = {
  jenis: 'pengerjaan',
  id: 31,
  quizId: 7,
  materiId: 3,
  materiJudul: 'Struktur Data',
  soal: [{ id: 201, tipe: 'ganda', jawaban: null }],
}

const KODE_DIPROSES = () => ({
  response: { status: 503, data: { kode: 'HASIL_SEDANG_DIPROSES', message: 'x' } },
})

describe('useLatihanStore', () => {
  it('mulai menyimpan paket pengerjaan tanpa id tersimpan', async () => {
    latihanService.mulai.mockResolvedValue(PAKET)
    const latihan = useLatihanStore()
    await latihan.mulai({ quizId: 7 })
    expect(latihan.status).toBe(STATUS_LATIHAN.MENGERJAKAN)
    expect(latihan.pengerjaanId).toBe(31)
    expect(localStorage.getItem('siap_osn_latihan_aktif')).toBeNull()
  })

  it('lanjutkan bercabang: pengerjaan, menunggu, hasil', async () => {
    latihanService.lihat.mockResolvedValue(PAKET)
    const latihan = useLatihanStore()
    await latihan.lanjutkan({ id: 31 })
    expect(latihan.status).toBe(STATUS_LATIHAN.MENGERJAKAN)

    latihanService.lihat.mockResolvedValue({ jenis: 'menunggu', id: 31, quizId: 7, materiId: 3 })
    await latihan.lanjutkan({ id: 31 })
    expect(latihan.status).toBe(STATUS_LATIHAN.MENILAI)
    expect(latihan.quizId).toBe(7)

    latihanService.lihat.mockResolvedValue({ jenis: 'hasil', hasil: { id: 31, nilai: 88, jawaban: [] } })
    await latihan.lanjutkan({ id: 31 })
    expect(latihan.status).toBe(STATUS_LATIHAN.SELESAI)
    expect(latihan.hasil.nilai).toBe(88)
  })

  it('kumpulkan menyimpan hasil bernilai benar per soal', async () => {
    latihanService.mulai.mockResolvedValue(PAKET)
    latihanService.kumpulkan.mockResolvedValue({ id: 31, nilai: 90, jawaban: [{ soalId: 201, benar: true }] })
    const latihan = useLatihanStore()
    await latihan.mulai({ quizId: 7 })
    await latihan.kumpulkan()
    expect(latihan.status).toBe(STATUS_LATIHAN.SELESAI)
    expect(latihan.hasil.jawaban[0].benar).toBe(true)
  })

  it('kumpulkan 503 HASIL_SEDANG_DIPROSES -> status menilai dan cekHasil menyelesaikan', async () => {
    latihanService.mulai.mockResolvedValue(PAKET)
    latihanService.kumpulkan.mockRejectedValue(KODE_DIPROSES())
    const latihan = useLatihanStore()
    await latihan.mulai({ quizId: 7 })

    await expect(latihan.kumpulkan()).resolves.toBeNull()
    expect(latihan.status).toBe(STATUS_LATIHAN.MENILAI)
    expect(await latihan.cekHasil()).toBeNull()

    latihanService.kumpulkan.mockResolvedValue({ id: 31, nilai: 77, jawaban: [] })
    expect((await latihan.cekHasil())?.nilai).toBe(77)
    expect(latihan.status).toBe(STATUS_LATIHAN.SELESAI)
  })

  it('$reset membersihkan state dan kunci lama di localStorage', async () => {
    localStorage.setItem('siap_osn_latihan_aktif', '{"id":31}')
    latihanService.mulai.mockResolvedValue(PAKET)
    const latihan = useLatihanStore()
    await latihan.mulai({ quizId: 7 })
    latihan.$reset()
    expect(latihan.status).toBe(STATUS_LATIHAN.IDLE)
    expect(latihan.soal).toEqual([])
    expect(localStorage.getItem('siap_osn_latihan_aktif')).toBeNull()
  })
})
