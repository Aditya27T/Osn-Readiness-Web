import { describe, expect, it } from 'vitest'
import {
  ATURAN_FIELDS,
  mapAdminDashboard,
  mapAturanPemetaan,
  mapKecukupanBankSoal,
  mapKompetensi,
  mapKonteksSoal,
  mapLatihan,
  mapMateriAdmin,
  mapSimulasiAdmin,
  mapSiswa,
  mapSoalAdmin,
  mapTingkatAdmin,
} from '@/services/mappers/admin.js'

describe('mapAdminDashboard', () => {
  it('memetakan siswa aktif, pengerjaan, dan rata-rata per jenis', () => {
    const hasil = mapAdminDashboard({
      siswa_aktif: 8,
      pengerjaan_per_jenis: { pretest: 8, latihan: 12, simulasi: 5 },
      rata_rata_nilai_per_jenis: { pretest: 62.89, latihan: 83, simulasi: 9 },
      siswa_per_tingkat_aktif: [
        { tingkat_id: 2, nama_tingkat: 'Provinsi', urutan: 2, jumlah_siswa: 0 },
        { tingkat_id: 1, nama_tingkat: 'Kabupaten/Kota', urutan: 1, jumlah_siswa: 7 },
      ],
    })
    expect(hasil.siswaAktif).toBe(8)
    expect(hasil.pengerjaanPerJenis).toEqual([
      { jenis: 'pretest', label: 'Pre-test', jumlah: 8 },
      { jenis: 'latihan', label: 'Latihan', jumlah: 12 },
      { jenis: 'simulasi', label: 'Simulasi', jumlah: 5 },
    ])
    expect(hasil.rataRataNilai[0]).toEqual({ jenis: 'pretest', label: 'Pre-test', jumlah: 62.89, nilai: 62.89 })
    // Diurutkan menurut urutan tingkat.
    expect(hasil.siswaPerTingkat.map((t) => t.nama)).toEqual(['Kabupaten/Kota', 'Provinsi'])
    expect(hasil.siswaPerTingkat[0]).toMatchObject({ tingkatId: 1, jumlahSiswa: 7 })
  })

  it('respons kosong tidak melempar', () => {
    expect(mapAdminDashboard(null)).toMatchObject({ siswaAktif: 0, pengerjaanPerJenis: [] })
  })
})

describe('mapKecukupanBankSoal', () => {
  it('memetakan lima bagian laporan', () => {
    const hasil = mapKecukupanBankSoal({
      tingkat_id: 1,
      pretest_per_level: [{ level: 'mudah', tersedia: 240, kuota: 15, kurang: false }],
      pretest_per_materi: [{ materi_id: 1, judul: 'Stack', tersedia: 98, minimal: 2, kurang: true }],
      putaran_pretest: { putaran: 16, ambang: 2, kurang: false },
      simulasi_per_level: [
        {
          simulasi_id: 1, nama: 'Simulasi 1', is_aktif: true,
          per_level: [{ level: 'sulit', tersedia: 60, kuota: 9, kurang: false }],
        },
      ],
      latihan_per_materi: [
        { materi_id: 1, judul: 'Stack', punya_latihan: true, tersedia: 10, dibutuhkan: 10, kurang: false },
      ],
    })
    expect(hasil.tingkatId).toBe(1)
    expect(hasil.pretestPerLevel[0]).toEqual({ level: 'mudah', label: 'Mudah', tersedia: 240, kuota: 15, kurang: false })
    expect(hasil.pretestPerMateri[0].kurang).toBe(true)
    expect(hasil.putaranPretest).toEqual({ putaran: 16, ambang: 2, kurang: false })
    expect(hasil.simulasiPerLevel[0].perLevel[0].label).toBe('Sulit')
    expect(hasil.latihanPerMateri[0].punyaLatihan).toBe(true)
  })
})

describe('mapSiswa', () => {
  it('memetakan identitas, role, dan status aktif', () => {
    expect(
      mapSiswa({
        id: 3, name: 'Budi', email: 'budi@x.id', roles: ['siswa'],
        is_active: true, tingkat_aktif_id: 1, created_at: 'a', updated_at: 'b',
      }),
    ).toEqual({
      id: 3, nama: 'Budi', email: 'budi@x.id', roles: ['siswa'],
      aktif: true, tingkatAktifId: 1, dibuatPada: 'a', diperbaruiPada: 'b',
    })
  })

  it('roles kosong menjadi array kosong', () => {
    expect(mapSiswa({ id: 1, name: 'X', email: 'x@y.z' }).roles).toEqual([])
    expect(mapSiswa({ id: 1 }).aktif).toBe(false)
  })
})

describe('mapKompetensi / mapMateriAdmin / mapKonteksSoal', () => {
  it('memetakan field minimal tanpa error', () => {
    expect(mapKompetensi({ id: 1, tingkat_id: 1, nama_kompetensi: 'Struktur Data', deskripsi: 'Dasar' }))
      .toEqual({ id: 1, tingkatId: 1, nama: 'Struktur Data', deskripsi: 'Dasar' })
    expect(mapMateriAdmin({ id: 2, tingkat_id: 1, kompetensi_id: 1, urutan: 2, judul: 'Stack' }))
      .toMatchObject({ id: 2, urutan: 2, judul: 'Stack', isiMateri: '' })
    expect(mapKonteksSoal({ id: 3, tingkat_id: 1, judul: 'Cerita', isi_konteks: 'Isi' }))
      .toEqual({ id: 3, tingkatId: 1, judul: 'Cerita', isi: 'Isi' })
  })
})

describe('mapSoalAdmin', () => {
  it('memakai huruf kunci untuk tipe isian', () => {
    const hasil = mapSoalAdmin({
      id: 1, tingkat_id: 1, materi_id: 1, level: 'sulit', peruntukan: 'simulasi',
      tipe_soal: 'isian', pertanyaan: 'Berapa?', kunci_jawaban: '42',
      materi: { id: 1, judul: 'Stack' }, pembahasan: { isi_pembahasan: 'Penjelasan' },
    })
    expect(hasil.tipe).toBe('isian')
    expect(hasil.pilihan).toEqual({})
    expect(hasil.kunci).toBe('42')
    expect(hasil.materi).toEqual({ id: 1, judul: 'Stack' })
    expect(hasil.pembahasan).toBe('Penjelasan')
  })

  it('pilihan_ganda menyimpan pilihan sebagai objek huruf', () => {
    const hasil = mapSoalAdmin({
      id: 1, tipe_soal: 'pilihan_ganda', level: 'mudah', peruntukan: 'pretest',
      pilihan_jawaban: { A: 'salah', B: 'benar' }, kunci_jawaban: 'B',
    })
    expect(hasil.tipe).toBe('ganda')
    expect(hasil.pilihan).toEqual({ A: 'salah', B: 'benar' })
  })

  it('soal terhapus ditandai', () => {
    expect(mapSoalAdmin({ id: 1, deleted_at: '2026-10-06T00:00:00Z' }).terhapus).toBe(true)
    expect(mapSoalAdmin({ id: 1 }).terhapus).toBe(false)
  })
})

describe('mapLatihan / mapSimulasiAdmin / mapTingkatAdmin', () => {
  it('memetakan latihan', () => {
    expect(mapLatihan({ id: 1, materi_id: 2, nama_quiz: 'Latihan Stack', jumlah_soal: 10 }))
      .toEqual({
        id: 1, materiId: 2, nama: 'Latihan Stack', deskripsi: '', jumlahSoal: 10, materi: null,
      })
  })

  it('memetakan simulasi termasuk tingkatnya', () => {
    expect(
      mapSimulasiAdmin({
        id: 2, tingkat_id: 1, nama_simulasi: 'Simulasi 1', jumlah_soal: 30,
        durasi_menit: 120, is_aktif: true, tingkat: { id: 1, nama: 'Kabupaten/Kota' },
      }),
    ).toEqual({
      id: 2, tingkatId: 1, nama: 'Simulasi 1', deskripsi: '', jumlahSoal: 30,
      durasiMenit: 120, aktif: true, tingkat: { id: 1, nama: 'Kabupaten/Kota' },
    })
  })

  it('memetakan tingkat', () => {
    expect(mapTingkatAdmin({ id: 1, nama_tingkat: 'Kabupaten/Kota', deskripsi: 'Dasar', urutan: 1 }))
      .toEqual({ id: 1, nama: 'Kabupaten/Kota', deskripsi: 'Dasar', urutan: 1 })
  })
})

describe('mapAturanPemetaan', () => {
  it('memetakan 16 parameter tanpa kehilangan field', () => {
    const hasil = mapAturanPemetaan({
      tingkat_id: 1,
      bobot_mudah: 1, bobot_sedang: 2, bobot_sulit: 3,
      pretest_jumlah_soal: 30, pretest_persen_mudah: 50, pretest_persen_sedang: 30,
      pretest_persen_sulit: 20, pretest_min_soal_per_materi: 2, jumlah_materi_wajib: 3,
      latihan_min_soal: 10, latihan_min_nilai: 50,
      simulasi_persen_mudah: 30, simulasi_persen_sedang: 40, simulasi_persen_sulit: 30,
      simulasi_maks_percobaan: 3, passing_grade: 70,
    })
    expect(ATURAN_FIELDS).toHaveLength(16)
    expect(ATURAN_FIELDS.every((f) => f in hasil)).toBe(true)
    expect(hasil.tingkatId).toBe(1)
    expect(hasil.passing_grade).toBe(70)
  })

  it('field yang hilang jadi 0, bukan undefined', () => {
    const hasil = mapAturanPemetaan({ tingkat_id: 1 })
    expect(ATURAN_FIELDS.every((f) => typeof hasil[f] === 'number')).toBe(true)
  })
})
