import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/lib/api.js'
import { siswaService } from '@/services/siswa.js'

vi.mock('@/lib/api.js', () => ({
  TOKEN_KEY: 'siap_osn_token',
  api: { get: vi.fn(), post: vi.fn(), put: vi.fn() },
}))

beforeEach(() => {
  vi.resetAllMocks()
})

function responsDashboard(data) {
  return { data: { message: 'OK', data } }
}

describe('siswaService', () => {
  it('dashboard memanggil path backend yang benar dan memetakan respons', async () => {
    api.get.mockResolvedValue(
      responsDashboard({
        tingkat_aktif_id: 1,
        tingkat_aktif: 'Kabupaten',
        tingkat: [
          {
            tingkat_id: 2,
            nama_tingkat: 'Provinsi',
            urutan: 2,
            tingkat_terbuka: false,
            tahap: 'BELUM_PRETEST',
            sudah_lulus: false,
            sisa_kuota_simulasi: null,
            syarat_simulasi: { terpenuhi: false, alasan: null, rincian: null },
            hasil_simulasi_terakhir: null,
          },
          {
            tingkat_id: 1,
            nama_tingkat: 'Kabupaten',
            urutan: 1,
            tingkat_terbuka: true,
            tahap: 'BELAJAR',
            sudah_lulus: false,
            sisa_kuota_simulasi: 2,
            syarat_simulasi: { terpenuhi: false, alasan: null, rincian: null },
            hasil_simulasi_terakhir: {
              id: 9,
              nilai: 85.5,
              lulus: true,
              selesai_pada: '2026-09-22T10:00:00Z',
            },
          },
        ],
      }),
    )
    const hasil = await siswaService.dashboard({})
    expect(api.get).toHaveBeenCalledWith('/dashboard', { signal: undefined })
    expect(hasil.preTestSelesai).toBe(true)
    expect(hasil.tingkatAktifId).toBe(1)
    expect(hasil.tingkat).toBe('Kabupaten')
    expect(hasil.tahap).toBe('BELAJAR')
    expect(hasil.tingkatan).toEqual([
      { nama: 'Kabupaten', terbuka: true },
      { nama: 'Provinsi', terbuka: false },
    ])
    expect(hasil.hasilTerakhir).toMatchObject({
      judul: 'Simulasi — Tingkat Kabupaten',
      nilai: 86,
      lulus: true,
    })
  })

  it('pre-test dianggap belum selesai bila tahap aktif BELUM_PRETEST', async () => {
    api.get.mockResolvedValue(
      responsDashboard({
        tingkat_aktif_id: 1,
        tingkat_aktif: 'Kabupaten',
        tingkat: [{ tingkat_id: 1, nama_tingkat: 'Kabupaten', urutan: 1, tingkat_terbuka: true, tahap: 'BELUM_PRETEST', hasil_simulasi_terakhir: null }],
      }),
    )
    const hasil = await siswaService.dashboard({})
    expect(hasil.preTestSelesai).toBe(false)
    expect(hasil.perluPretestUlang).toBe(false)
    expect(hasil.hasilTerakhir).toBeNull()
  })

  it('PUTARAN_HABIS menawarkan pre-test ulang', async () => {
    api.get.mockResolvedValue(
      responsDashboard({
        tingkat_aktif_id: 1,
        tingkat_aktif: 'Kabupaten',
        tingkat: [{ tingkat_id: 1, nama_tingkat: 'Kabupaten', urutan: 1, tingkat_terbuka: true, tahap: 'PUTARAN_HABIS', hasil_simulasi_terakhir: null }],
      }),
    )
    const hasil = await siswaService.dashboard({})
    // Tetap di himpunan "pre-test selesai" supaya materi/riwayat putaran lama
    // bisa dibuka, tapi dashboard harus gladly menawarkan putaran baru.
    expect(hasil.preTestSelesai).toBe(true)
    expect(hasil.perluPretestUlang).toBe(true)
  })

  it('dashboard kosong tetap aman dipakai UI', async () => {
    api.get.mockResolvedValue({ data: { message: 'OK', data: null } })
    const hasil = await siswaService.dashboard({})
    expect(hasil.preTestSelesai).toBe(false)
    expect(hasil.kompetensi).toEqual([])
    expect(hasil.tingkatan).toEqual([
      { nama: 'Kabupaten', terbuka: false },
      { nama: 'Provinsi', terbuka: false },
    ])
  })
})
