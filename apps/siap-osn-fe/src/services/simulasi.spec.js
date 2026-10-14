import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/lib/api.js'
import { simulasiService } from '@/services/simulasi.js'

vi.mock('@/lib/api.js', () => ({
  TOKEN_KEY: 'siap_osn_token',
  api: { get: vi.fn(), post: vi.fn(), put: vi.fn() },
}))

beforeEach(() => {
  vi.resetAllMocks()
})

const SOAL_BE = {
  id: 501,
  materi_id: 3,
  level: 'sedang',
  tipe_soal: 'pilihan_ganda',
  pertanyaan: 'Mana yang benar?',
  pilihan_jawaban: { A: 'salah', B: 'benar' },
  gambar: null,
  konteks: null,
  urutan: 1,
  bobot: 5,
  jawaban_user: null,
}

describe('simulasiService', () => {
  it('syarat mengirim tingkat_id dan memetakan rincian', async () => {
    // Resource langsung: axios res.data = { data: { ... } } (tanpa message).
    api.get.mockResolvedValue({
      data: {
        data: {
          terpenuhi: false,
          alasan: 'belum_pretest',
          rincian: [
            { materi_id: 3, judul: 'Stack', prioritas: 1, selesai: false, nilai_latihan: null, batas: 75, latihan_belum_tersedia: false },
          ],
        },
      },
    })
    const hasil = await simulasiService.syarat({ tingkatId: 1 })
    expect(api.get).toHaveBeenCalledWith('/simulasi/syarat', {
      params: { tingkat_id: 1 },
      signal: undefined,
    })
    expect(hasil).toMatchObject({ terpenuhi: false, alasan: 'belum_pretest' })
    expect(hasil.rincian[0]).toMatchObject({ materiId: 3, batas: 75, selesai: false })
  })

  it('daftar mengirim tingkat_id dan memetakan kuota', async () => {
    api.get.mockResolvedValue({
      data: {
        message: 'OK',
        data: [
          { id: 2, nama_simulasi: 'Simulasi 1', jumlah_soal: 30, durasi_menit: 90, is_aktif: true, sisa_kuota: 1 },
          { id: 3, nama_simulasi: 'Simulasi 2', jumlah_soal: 20, durasi_menit: 60, is_aktif: false, sisa_kuota: 0 },
        ],
      },
    })
    const hasil = await simulasiService.daftar({ tingkatId: 2 })
    expect(api.get).toHaveBeenCalledWith('/simulasi', { params: { tingkat_id: 2 }, signal: undefined })
    expect(hasil).toHaveLength(2)
    expect(hasil[0]).toEqual({
      id: 2, nama: 'Simulasi 1', jumlahSoal: 30, durasiMenit: 90, aktif: true, sisaKuota: 1,
    })
    expect(hasil[1].aktif).toBe(false)
  })

  it('mulai mengembalikan paket pengerjaan dengan batas_pada', async () => {
    api.post.mockResolvedValue({
      data: {
        message: 'OK',
        data: {
          id: 11, simulasi_id: 2, pretest_id: 5,
          mulai_pada: '2026-10-06T02:00:00Z', batas_pada: '2026-10-06T03:30:00Z',
          soal: [SOAL_BE],
        },
      },
    })
    const paket = await simulasiService.mulai({ simulasiId: 2 })
    expect(api.post).toHaveBeenCalledWith('/simulasi/2/mulai', {}, { signal: undefined })
    expect(paket.jenis).toBe('pengerjaan')
    expect(paket.batasPada).toBe('2026-10-06T03:30:00Z')
    expect(paket.soal[0].opsi).toEqual([{ kode: 'A', teks: 'salah' }, { kode: 'B', teks: 'benar' }])
  })

  it('mulai menolak bentuk hasil', async () => {
    api.post.mockResolvedValue({
      data: { message: 'OK', data: { id: 11, simulasi_id: 2, nilai: 90, jawaban: [] } },
    })
    await expect(simulasiService.mulai({ simulasiId: 2 })).rejects.toThrow(/tidak berisi soal/)
  })

  it('mulai menerima paket menunggu tanpa melempar', async () => {
    api.post.mockResolvedValue({
      data: { message: 'OK', data: { id: 11, simulasi_id: 2, disubmit_pada: '2026-10-06T02:30:00Z', soal: [] } },
    })
    expect((await simulasiService.mulai({ simulasiId: 2 })).jenis).toBe('menunggu')
  })

  it('lihat bercabang pengerjaan vs hasil', async () => {
    api.get.mockResolvedValue({ data: { message: 'OK', data: { id: 11, soal: [] } } })
    expect((await simulasiService.lihat({ id: 11 })).jenis).toBe('pengerjaan')

    api.get.mockResolvedValue({
      data: {
        message: 'OK',
        data: { id: 11, simulasi_id: 2, nilai: 70, lulus: false, jumlah_benar: 21, jumlah_salah: 9, jawaban: [] },
      },
    })
    const paket = await simulasiService.lihat({ id: 11 })
    expect(paket.jenis).toBe('hasil')
    expect(paket.hasil).toMatchObject({ nilai: 70, lulus: false, jumlahBenar: 21, jumlahSalah: 9 })
  })

  it('simpanJawaban mengirim soal_id + jawaban (null bila dikosongkan)', async () => {
    api.put.mockResolvedValue({ data: { message: 'Jawaban tersimpan' } })
    await simulasiService.simpanJawaban({ id: 11, soalId: 501, jawaban: 'B' })
    expect(api.put).toHaveBeenCalledWith(
      '/hasil-simulasi/11/jawaban',
      { soal_id: 501, jawaban_user: 'B' },
      { signal: undefined },
    )
    await simulasiService.simpanJawaban({ id: 11, soalId: 501 })
    expect(api.put).toHaveBeenCalledWith(
      '/hasil-simulasi/11/jawaban',
      { soal_id: 501, jawaban_user: null },
      { signal: undefined },
    )
  })

  it('kumpulkan mem-post body kosong dan memetakan hasil', async () => {
    api.post.mockResolvedValue({
      data: {
        message: 'OK',
        data: {
          id: 11, simulasi_id: 2, nilai: 88.5, lulus: true,
          jawaban: [{ soal_id: 501, urutan: 1, bobot: 5, jawaban_user: 'B', status_benar: true }],
        },
      },
    })
    const hasil = await simulasiService.kumpulkan({ id: 11 })
    expect(api.post).toHaveBeenCalledWith('/hasil-simulasi/11/submit', {}, { signal: undefined })
    expect(hasil.nilai).toBe(88.5)
    expect(hasil.jawaban[0]).toMatchObject({ soalId: 501, benar: true })
  })

  it('review memetakan kunci dan pembahasan dari soal bersarang', async () => {
    api.get.mockResolvedValue({
      data: {
        message: 'OK',
        data: {
          id: 11, simulasi_id: 2, nilai: 88.5, lulus: true,
          soal: [
            {
              urutan: 1, bobot: 5, jawaban_user: 'B', status_benar: true,
              soal: { ...SOAL_BE, kunci_jawaban: 'B', pembahasan: 'Karena B benar.' },
            },
          ],
        },
      },
    })
    const hasil = await simulasiService.review({ id: 11 })
    expect(api.get).toHaveBeenCalledWith('/hasil-simulasi/11/review', { signal: undefined })
    expect(hasil.soal[0]).toMatchObject({ id: 501, kunci: 'B', pembahasan: 'Karena B benar.', benar: true })
  })

  it('review 409 SIMULASI_BELUM_DINILAI dilempar apa adanya', async () => {
    api.get.mockRejectedValue({
      response: { status: 409, data: { message: 'x', kode: 'SIMULASI_BELUM_DINILAI' } },
    })
    await expect(simulasiService.review({ id: 11 })).rejects.toMatchObject({
      response: { status: 409 },
    })
  })
})
