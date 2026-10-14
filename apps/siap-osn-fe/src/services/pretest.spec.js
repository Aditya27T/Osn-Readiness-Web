import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/lib/api.js'
import { pretestService } from '@/services/pretest.js'

vi.mock('@/lib/api.js', () => ({
  TOKEN_KEY: 'siap_osn_token',
  api: { get: vi.fn(), post: vi.fn(), put: vi.fn() },
}))

beforeEach(() => {
  vi.resetAllMocks()
})

const SOAL_BE = {
  id: 101,
  materi_id: 5,
  level: 'mudah',
  tipe_soal: 'pilihan_ganda',
  pertanyaan: 'Apa itu stack?',
  pilihan_jawaban: { B: 'Antrean', A: 'Tumpukan', D: 'Pohon', C: 'Graf' },
  gambar: null,
  konteks: null,
  urutan: 2,
  bobot: 10,
  jawaban_user: 'A',
}

describe('pretestService', () => {
  it('tingkat memetakan daftar level', async () => {
    api.get.mockResolvedValue({
      data: {
        message: 'OK',
        data: [{ id: 1, nama_tingkat: 'Kabupaten', deskripsi: 'Dasar', urutan: 1, tingkat_terbuka: true, tahap: 'BELUM_PRETEST' }],
      },
    })
    const hasil = await pretestService.tingkat({})
    expect(api.get).toHaveBeenCalledWith('/tingkat', { signal: undefined })
    expect(hasil).toEqual([
      { id: 1, nama: 'Kabupaten', deskripsi: 'Dasar', urutan: 1, terbuka: true, tahap: 'BELUM_PRETEST' },
    ])
  })

  it('mulai mengirim tingkat_id dan mengurutkan soal + opsi', async () => {
    api.post.mockResolvedValue({
      data: { message: 'OK', data: { id: 12, tingkat_id: 1, soal: [SOAL_BE] } },
    })
    const paket = await pretestService.mulai({ tingkatId: 1 })
    expect(api.post).toHaveBeenCalledWith('/pretest', { tingkat_id: 1 }, { signal: undefined })
    expect(paket.jenis).toBe('pengerjaan')
    expect(paket.soal[0]).toMatchObject({
      id: 101,
      tipe: 'ganda',
      jawaban: 'A',
      opsi: [
        { kode: 'A', teks: 'Tumpukan' },
        { kode: 'B', teks: 'Antrean' },
        { kode: 'C', teks: 'Graf' },
        { kode: 'D', teks: 'Pohon' },
      ],
    })
  })

  it('lihat membedakan paket pengerjaan dan paket hasil', async () => {
    api.get.mockResolvedValue({ data: { message: 'OK', data: { id: 12, soal: [] } } })
    expect((await pretestService.lihat({ id: 12 })).jenis).toBe('pengerjaan')

    api.get.mockResolvedValue({
      data: {
        message: 'OK',
        data: {
          id: 12,
          tingkat_id: 1,
          nilai: 82.5,
          pemetaan: [{ materi_id: 5, jumlah_soal: 6, jumlah_benar: 4, poin_didapat: 40, poin_maksimal: 60, persentase: 66.67 }],
          materi_wajib: [{ materi_id: 7, prioritas: 1 }],
        },
      },
    })
    const paket = await pretestService.lihat({ id: 12 })
    expect(paket.jenis).toBe('hasil')
    expect(paket.hasil.nilai).toBe(82.5)
    expect(paket.hasil.pemetaan[0]).toMatchObject({ materiId: 5, jumlahBenar: 4, persentase: 66.67 })
    expect(paket.hasil.materiWajib).toEqual([{ materiId: 7, prioritas: 1 }])
  })

  it('lihat mengenali paket menunggu saat disubmit tapi belum dinilai', async () => {
    api.get.mockResolvedValue({
      data: {
        message: 'OK',
        data: { id: 12, tingkat_id: 1, disubmit_pada: '2026-10-06T10:00:00Z', soal: [] },
      },
    })
    const paket = await pretestService.lihat({ id: 12 })
    expect(paket.jenis).toBe('menunggu')
    expect(paket.id).toBe(12)
    expect(paket.tingkatId).toBe(1)
  })

  it('simpanJawaban mengirim soal_id + jawaban (null bila dikosongkan)', async () => {
    api.put.mockResolvedValue({ data: { message: 'Jawaban tersimpan' } })
    await pretestService.simpanJawaban({ id: 12, soalId: 101, jawaban: 'B' })
    expect(api.put).toHaveBeenCalledWith('/pretest/12/jawaban', { soal_id: 101, jawaban_user: 'B' }, { signal: undefined })
    await pretestService.simpanJawaban({ id: 12, soalId: 101 })
    expect(api.put).toHaveBeenCalledWith('/pretest/12/jawaban', { soal_id: 101, jawaban_user: null }, { signal: undefined })
  })

  it('kumpulkan mem-post body kosong dan memetakan hasil', async () => {
    api.post.mockResolvedValue({
      data: { message: 'OK', data: { id: 12, tingkat_id: 1, nilai: 70, pemetaan: [], materi_wajib: [] } },
    })
    const hasil = await pretestService.kumpulkan({ id: 12 })
    expect(api.post).toHaveBeenCalledWith('/pretest/12/submit', {}, { signal: undefined })
    expect(hasil.nilai).toBe(70)
  })
})
