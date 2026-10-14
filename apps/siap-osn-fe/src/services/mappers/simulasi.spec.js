import { describe, expect, it } from 'vitest'
import {
  mapHasilSimulasi,
  mapReview,
  mapSyarat,
  mapSimulasi,
  paketSimulasi,
} from '@/services/mappers/simulasi.js'

describe('mapSyarat', () => {
  it('memetakan terpenuhi, alasan, dan rincian per materi wajib', () => {
    const hasil = mapSyarat({
      terpenuhi: false,
      alasan: 'belum_pretest',
      rincian: [
        { materi_id: 3, judul: 'Stack', prioritas: 1, selesai: true, nilai_latihan: 85, batas: 75, latihan_belum_tersedia: false },
        { materi_id: 4, judul: 'Queue', prioritas: null, selesai: false, nilai_latihan: null, batas: 80, latihan_belum_tersedia: true },
      ],
    })
    expect(hasil.terpenuhi).toBe(false)
    expect(hasil.alasan).toBe('belum_pretest')
    expect(hasil.rincian[0]).toEqual({
      materiId: 3, judul: 'Stack', prioritas: 1, selesai: true,
      nilaiLatihan: 85, batas: 75, latihanTersedia: true,
    })
    // null -> 0 untuk prioritas, null untuk nilai, latihanTersedia false
    expect(hasil.rincian[1]).toMatchObject({ prioritas: 0, nilaiLatihan: null, latihanTersedia: false })
  })

  it('bentuk hilang tidak membuat mapper melempar', () => {
    expect(mapSyarat(null)).toEqual({ terpenuhi: false, alasan: null, rincian: [] })
    expect(mapSyarat({}).rincian).toEqual([])
  })
})

describe('mapSimulasi', () => {
  it('memetakan nama, jumlah soal, durasi, dan sisa kuota', () => {
    expect(
      mapSimulasi({
        id: 2, nama_simulasi: 'Simulasi Kabupaten 1', jumlah_soal: 30,
        durasi_menit: 90, is_aktif: true, sisa_kuota: 2,
      }),
    ).toEqual({
      id: 2, nama: 'Simulasi Kabupaten 1', jumlahSoal: 30,
      durasiMenit: 90, aktif: true, sisaKuota: 2,
    })
  })

  it('sisa_kuota null tetap null, bukan 0', () => {
    expect(mapSimulasi({ id: 2, sisa_kuota: null }).sisaKuota).toBeNull()
    expect(mapSimulasi({ id: 2 }).nama).toBe('')
  })
})

describe('paketSimulasi', () => {
  const SOAL = {
    id: 501, materi_id: 3, level: 'sedang', tipe_soal: 'pilihan_ganda',
    pertanyaan: 'BerapaIKA?', pilihan_jawaban: { A: '1', B: '2' },
    gambar: null, konteks: null, urutan: 2, bobot: 5, jawaban_user: 'B',
  }

  it('paket pengerjaan memuat batas_pada dari server', () => {
    const paket = paketSimulasi({
      id: 11, simulasi_id: 2, pretest_id: 5,
      mulai_pada: '2026-10-06T02:00:00Z', batas_pada: '2026-10-06T03:30:00Z',
      soal: [SOAL],
    })
    expect(paket.jenis).toBe('pengerjaan')
    expect(paket.batasPada).toBe('2026-10-06T03:30:00Z')
    expect(paket.pretestId).toBe(5)
    expect(paket.soal[0]).toMatchObject({ id: 501, opsi: [{ kode: 'A' }, { kode: 'B' }], jawaban: 'B' })
  })

  it('disubmit_pada terisi -> jenis menunggu', () => {
    const paket = paketSimulasi({
      id: 11, simulasi_id: 2, disubmit_pada: '2026-10-06T02:30:00Z', soal: [],
    })
    expect(paket.jenis).toBe('menunggu')
    expect(paket.id).toBe(11)
  })

  it('tanpa soal -> paket hasil', () => {
    const paket = paketSimulasi({ id: 11, simulasi_id: 2, nilai: 88.5, jumlah_benar: 27, lulus: true, jawaban: [] })
    expect(paket.jenis).toBe('hasil')
    expect(paket.hasil).toMatchObject({ nilai: 88.5, jumlahBenar: 27, lulus: true })
  })
})

describe('mapHasilSimulasi', () => {
  it('mengurutkan jawaban per urutan dan menjaga null belum dinilai', () => {
    const hasil = mapHasilSimulasi({
      id: 11, simulasi_id: 2, pretest_id: 5, nilai: null, jumlah_benar: null,
      jumlah_salah: null, lulus: null, jawaban: [
        { soal_id: 9, urutan: 3, bobot: 5, jawaban_user: 'A', status_benar: false },
        { soal_id: 7, urutan: 1, bobot: 5, jawaban_user: 'C', status_benar: true },
      ],
    })
    expect(hasil.nilai).toBeNull()
    expect(hasil.lulus).toBeNull()
    expect(hasil.jawaban.map((j) => j.urutan)).toEqual([1, 3])
    expect(hasil.jawaban[0]).toMatchObject({ soalId: 7, jawabanUser: 'C', benar: true })
  })
})

describe('mapReview', () => {
  it('soal bersarang jadi datar + kunci + pembahasan', () => {
    const hasil = mapReview({
      id: 11, simulasi_id: 2, nilai: 88.5, lulus: true,
      soal: [
        {
          urutan: 1, bobot: 5, jawaban_user: 'B', status_benar: true,
          soal: {
            id: 501, materi_id: 3, level: 'sedang', tipe_soal: 'pilihan_ganda',
            pertanyaan: 'Mana yang benar?', pilihan_jawaban: { A: 'salah', B: 'benar' },
            kunci_jawaban: 'B', pembahasan: 'Penjelasan singkat.', gambar: null, konteks: null,
          },
        },
      ],
    })
    expect(hasil.nilai).toBe(88.5)
    expect(hasil.soal[0]).toMatchObject({
      id: 501, urutan: 1, bobot: 5, jawaban: 'B', benar: true,
      kunci: 'B', pembahasan: 'Penjelasan singkat.',
    })
    expect(hasil.soal[0].opsi.map((o) => o.kode)).toEqual(['A', 'B'])
    // mapHasilSimulasiApplied tanpa jawaban supaya tidak dobel
    expect(hasil.jawaban).toEqual([])
  })

  it('soal kosong aman', () => {
    expect(mapReview({ id: 11 }).soal).toEqual([])
  })
})
