import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/lib/api.js'
import { adminService } from '@/services/admin.js'

vi.mock('@/lib/api.js', () => ({
  TOKEN_KEY: 'siap_osn_token',
  api: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
}))

beforeEach(() => {
  vi.resetAllMocks()
})

const PAGINASI = {
  data: [{ id: 3, name: 'Budi', email: 'budi@x.id', roles: ['siswa'], is_active: true, tingkat_aktif_id: 1 }],
  meta: { current_page: 2, per_page: 5, last_page: 4, total: 18 },
  links: {},
}

describe('adminService · A1 dashboard & bank soal', () => {
  it('dashboard memetakan ringkasan', async () => {
    api.get.mockResolvedValue({
      data: {
        message: 'OK',
        data: {
          siswa_aktif: 8,
          pengerjaan_per_jenis: { pretest: 8, latihan: 12, simulasi: 5 },
          rata_rata_nilai_per_jenis: { pretest: 62.89, latihan: 83, simulasi: 9 },
          siswa_per_tingkat_aktif: [
            { tingkat_id: 1, nama_tingkat: 'Kabupaten/Kota', urutan: 1, jumlah_siswa: 7 },
          ],
        },
      },
    })
    const hasil = await adminService.dashboard({})
    expect(api.get).toHaveBeenCalledWith('/admin/dashboard', { signal: undefined })
    expect(hasil.siswaAktif).toBe(8)
    expect(hasil.pengerjaanPerJenis).toHaveLength(3)
  })

  it('kecukupanBankSoal mengirim tingkat_id', async () => {
    api.get.mockResolvedValue({
      data: { message: 'OK', data: { tingkat_id: 1, pretest_per_level: [], pretest_per_materi: [], putaran_pretest: { putaran: 16, ambang: 2, kurang: false }, simulasi_per_level: [], latihan_per_materi: [] } },
    })
    const hasil = await adminService.kecukupanBankSoal({ tingkatId: 1 })
    expect(api.get).toHaveBeenCalledWith('/admin/bank-soal/kecukupan', {
      params: { tingkat_id: 1 },
      signal: undefined,
    })
    expect(hasil.tingkatId).toBe(1)
  })
})

describe('adminService · A2 siswa', () => {
  it('daftarSiswa membaca paginasi dari meta', async () => {
    api.get.mockResolvedValue({ data: PAGINASI })
    const hasil = await adminService.daftarSiswa({ halaman: 2, perPage: 5 })
    expect(api.get).toHaveBeenCalledWith('/admin/siswa', {
      params: { page: 2, per_page: 5 },
      signal: undefined,
    })
    expect(hasil.items[0]).toMatchObject({ nama: 'Budi', aktif: true })
    expect(hasil.total).toBe(18)
    expect(hasil.halaman).toBe(2)
    expect(hasil.halamanTerakhir).toBe(4)
  })

  it('daftarSiswa aman saat meta tidak ada', async () => {
    api.get.mockResolvedValue({ data: { data: [{ id: 1, name: 'A' }] } })
    const hasil = await adminService.daftarSiswa({})
    expect(hasil.total).toBe(1)
    expect(hasil.halamanTerakhir).toBe(1)
  })

  it('ubahSiswa mengirim payload', async () => {
    api.put.mockResolvedValue({ data: { message: 'OK', data: PAGINASI.data[0] } })
    await adminService.ubahSiswa({ id: 3, payload: { name: 'Budi Baru' } })
    expect(api.put).toHaveBeenCalledWith('/admin/siswa/3', { name: 'Budi Baru' }, { signal: undefined })
  })

  it('nonaktifkanSiswa POST body kosong', async () => {
    api.post.mockResolvedValue({ data: { message: 'OK', data: { ...PAGINASI.data[0], is_active: false } } })
    const hasil = await adminService.nonaktifkanSiswa({ id: 3 })
    expect(api.post).toHaveBeenCalledWith('/admin/siswa/3/deactivate', {}, { signal: undefined })
    expect(hasil.aktif).toBe(false)
  })

  it('hapusSiswa memanggil DELETE', async () => {
    api.delete.mockResolvedValue({ data: { message: 'OK' } })
    await adminService.hapusSiswa({ id: 3 })
    expect(api.delete).toHaveBeenCalledWith('/admin/siswa/3', { signal: undefined })
  })
})

describe('adminService · A3 kompetensi, materi, unggah gambar', () => {
  it('daftarKompetensi mengirim tingkat_id hanya bila ada', async () => {
    api.get.mockResolvedValue({ data: { data: [{ id: 1, nama_kompetensi: 'Stack' }] } })
    await adminService.daftarKompetensi({})
    expect(api.get).toHaveBeenCalledWith('/admin/kompetensi', { params: {}, signal: undefined })
    await adminService.daftarKompetensi({ tingkatId: 1 })
    expect(api.get).toHaveBeenCalledWith('/admin/kompetensi', { params: { tingkat_id: 1 }, signal: undefined })
  })

  it('daftarMateriAdmin mengirim kompetensi_id opsional', async () => {
    api.get.mockResolvedValue({ data: { data: [{ id: 1, judul: 'Stack' }] } })
    await adminService.daftarMateriAdmin({ kompetensiId: 2 })
    expect(api.get).toHaveBeenCalledWith('/admin/materi', { params: { kompetensi_id: 2 }, signal: undefined })
  })

  it('unggahGambarMateri memakai FormData dan multipart', async () => {
    api.post.mockResolvedValue({ data: { message: 'OK', data: { path: 'materi/abc.png' } } })
    const file = new File(['x'], 'gambar.png', { type: 'image/png' })
    const hasil = await adminService.unggahGambarMateri({ file })
    const [, form] = api.post.mock.calls[0]
    expect(api.post.mock.calls[0][0]).toBe('/admin/materi/gambar')
    expect(form).toBeInstanceOf(FormData)
    expect(form.get('gambar')).toBe(file)
    expect(api.post.mock.calls[0][2].headers['Content-Type']).toBe('multipart/form-data')
    expect(hasil).toEqual({ path: 'materi/abc.png' })
  })
})

describe('adminService · A4 konteks, soal, pembahasan', () => {
  it('daftarSoal membaca paginasi', async () => {
    api.get.mockResolvedValue({ data: PAGINASI })
    const hasil = await adminService.daftarSoal({ perPage: 5 })
    expect(api.get).toHaveBeenCalledWith('/admin/soal', { params: { page: 1, per_page: 5 }, signal: undefined })
    expect(hasil.total).toBe(18)
  })

  it('tambahSoal mengirim payload apa adanya', async () => {
    api.post.mockResolvedValue({ data: { message: 'OK', data: { id: 1, tipe_soal: 'isian' } } })
    await adminService.tambahSoal({ payload: { kunci_jawaban: '42' } })
    expect(api.post).toHaveBeenCalledWith('/admin/soal', { kunci_jawaban: '42' }, { signal: undefined })
  })

  it('simpanPembahasan POST isi_pembahasan', async () => {
    api.post.mockResolvedValue({ data: { message: 'OK', data: { id: 1, isi_pembahasan: 'Penjelasan' } } })
    const hasil = await adminService.simpanPembahasan({ id: 1, isiPembahasan: 'Penjelasan' })
    expect(api.post).toHaveBeenCalledWith('/admin/soal/1/pembahasan', {
      isi_pembahasan: 'Penjelasan',
    }, { signal: undefined })
    expect(hasil.isi_pembahasan).toBe('Penjelasan')
  })

  it('pembahasan 404 dilempar apa adanya (bukan jadi null)', async () => {
    api.get.mockRejectedValue({ response: { status: 404, data: { message: 'Belum ada' } } })
    await expect(adminService.pembahasan({ id: 1 })).rejects.toMatchObject({
      response: { status: 404 },
    })
  })

  it('hapusPembahasan memanggil DELETE', async () => {
    api.delete.mockResolvedValue({ data: { message: 'OK' } })
    await adminService.hapusPembahasan({ id: 1 })
    expect(api.delete).toHaveBeenCalledWith('/admin/soal/1/pembahasan', { signal: undefined })
  })
})

describe('adminService · A5 latihan & simulasi', () => {
  it('daftarLatihan dan daftarSimulasiAdmin membaca paginasi', async () => {
    api.get.mockResolvedValue({ data: PAGINASI })
    const latihan = await adminService.daftarLatihan({})
    expect(api.get).toHaveBeenCalledWith('/admin/latihan', { params: { page: 1, per_page: 15 }, signal: undefined })
    expect(latihan.items).toHaveLength(1)

    const simulasi = await adminService.daftarSimulasiAdmin({})
    expect(api.get).toHaveBeenCalledWith('/admin/simulasi', { params: { page: 1, per_page: 15 }, signal: undefined })
    expect(simulasi.total).toBe(18)
  })

  it('tambahSimulasi mengirim is_aktif', async () => {
    api.post.mockResolvedValue({ data: { message: 'OK', data: { id: 2, is_aktif: true } } })
    await adminService.tambahSimulasi({ payload: { is_aktif: true, jumlah_soal: 30 } })
    expect(api.post).toHaveBeenCalledWith('/admin/simulasi', { is_aktif: true, jumlah_soal: 30 }, { signal: undefined })
  })
})

describe('adminService · A6 tingkat & aturan pemetaan', () => {
  it('daftarTingkatAdmin memetakan daftar tingkat', async () => {
    api.get.mockResolvedValue({ data: { data: [{ id: 1, nama_tingkat: 'Kabupaten/Kota', urutan: 1 }] } })
    const hasil = await adminService.daftarTingkatAdmin({})
    expect(api.get).toHaveBeenCalledWith('/admin/tingkat', { signal: undefined })
    expect(hasil[0].nama).toBe('Kabupaten/Kota')
  })

  it('aturanPemetaan membaca 16 parameter', async () => {
    api.get.mockResolvedValue({
      data: { message: 'OK', data: { tingkat_id: 1, bobot_mudah: 1, passing_grade: 70 } },
    })
    const hasil = await adminService.aturanPemetaan({ tingkatId: 1 })
    expect(api.get).toHaveBeenCalledWith('/admin/tingkat/1/aturan-pemetaan', { signal: undefined })
    expect(hasil.passing_grade).toBe(70)
    expect(hasil.pretest_jumlah_soal).toBe(0)
  })

  it('simpanAturanPemetaan PUT payload', async () => {
    api.put.mockResolvedValue({ data: { message: 'OK', data: { tingkat_id: 1, passing_grade: 60 } } })
    const hasil = await adminService.simpanAturanPemetaan({ tingkatId: 1, payload: { passing_grade: 60 } })
    expect(api.put).toHaveBeenCalledWith('/admin/tingkat/1/aturan-pemetaan', { passing_grade: 60 }, { signal: undefined })
    expect(hasil.passing_grade).toBe(60)
  })
})
