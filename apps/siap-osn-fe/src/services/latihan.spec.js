import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/lib/api.js'
import { latihanService } from '@/services/latihan.js'

vi.mock('@/lib/api.js', () => ({
  TOKEN_KEY: 'siap_osn_token',
  api: { get: vi.fn(), post: vi.fn(), put: vi.fn() },
}))

beforeEach(() => {
  vi.resetAllMocks()
})

const SOAL = {
  id: 201,
  materi_id: 3,
  level: 'mudah',
  tipe_soal: 'pilihan_ganda',
  pertanyaan: 'Apa itu queue?',
  pilihan_jawaban: { A: 'Tumpukan', B: 'Antrean' },
  gambar: null,
  konteks: null,
  urutan: 1,
  bobot: 5,
  jawaban_user: null,
}

describe('latihanService', () => {
  it('mulai mengembalikan paket pengerjaan', async () => {
    api.post.mockResolvedValue({
      data: { message: 'OK', data: { id: 31, quiz_id: 7, materi_id: 3, materi_judul: 'Struktur Data', soal: [SOAL] } },
    })
    const paket = await latihanService.mulai({ quizId: 7 })
    expect(api.post).toHaveBeenCalledWith('/quiz/7/mulai', {}, { signal: undefined })
    expect(paket.jenis).toBe('pengerjaan')
    expect(paket.materiJudul).toBe('Struktur Data')
    expect(paket.soal[0]).toMatchObject({ id: 201, tipe: 'ganda', opsi: [{ kode: 'A' }, { kode: 'B' }] })
  })

  it('lihat membedakan pengerjaan dan hasil', async () => {
    api.get.mockResolvedValue({ data: { message: 'OK', data: { id: 31, soal: [] } } })
    expect((await latihanService.lihat({ id: 31 })).jenis).toBe('pengerjaan')

    api.get.mockResolvedValue({
      data: {
        message: 'OK',
        data: {
          id: 31, quiz_id: 7, materi_id: 3, nilai: 90,
          jawaban: [{ soal_id: 201, urutan: 1, bobot: 5, jawaban_user: 'B', status_benar: true }],
        },
      },
    })
    const paket = await latihanService.lihat({ id: 31 })
    expect(paket.jenis).toBe('hasil')
    expect(paket.hasil.jawaban[0]).toMatchObject({ soalId: 201, benar: true })
  })

  it('lihat mengenali paket menunggu saat disubmit tapi belum dinilai', async () => {
    api.get.mockResolvedValue({
      data: {
        message: 'OK',
        data: { id: 31, quiz_id: 7, disubmit_pada: '2026-10-06T10:00:00Z', soal: [] },
      },
    })
    const paket = await latihanService.lihat({ id: 31 })
    expect(paket.jenis).toBe('menunggu')
    expect(paket.quizId).toBe(7)
  })

  it('simpanJawaban dan kumpulkan memakai path pengerjaan', async () => {
    api.put.mockResolvedValue({ data: { message: 'Jawaban tersimpan' } })
    await latihanService.simpanJawaban({ id: 31, soalId: 201, jawaban: 'B' })
    expect(api.put).toHaveBeenCalledWith(
      '/quiz-pengerjaan/31/jawaban',
      { soal_id: 201, jawaban_user: 'B' },
      { signal: undefined },
    )
    api.post.mockResolvedValue({ data: { message: 'OK', data: { id: 31, nilai: 90, jawaban: [] } } })
    const hasil = await latihanService.kumpulkan({ id: 31 })
    expect(api.post).toHaveBeenCalledWith('/quiz-pengerjaan/31/submit', {}, { signal: undefined })
    expect(hasil.nilai).toBe(90)
  })
})
