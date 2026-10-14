import { api } from '@/lib/api.js'
import { ENDPOINTS } from '@/lib/endpoints.js'
import {
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
import { unwrap } from '@/services/envelope.js'

// Satu fungsi = satu endpoint. Semua butuh token Super Admin; 403 dilempar
// apa adanya supaya view bisa membedakan "belum login" dari "bukan admin".
//
// Daftar berpaginasi (/admin/siswa, /admin/soal, /admin/latihan,
// /admin/simulasi) memakai envelope Laravel { data, meta, links }, jadi
// `res.data` dibaca langsung — bukan lewat unwrap seperti endpoint siswa.

function halamanPaginasi(res, perPage, halaman) {
  const body = res?.data ?? {}
  const items = Array.isArray(body.data) ? body.data : []
  const meta = body.meta ?? {}
  return {
    items,
    total: meta.total ?? items.length,
    halaman: meta.current_page ?? halaman,
    perHalaman: meta.per_page ?? perPage,
    halamanTerakhir: meta.last_page ?? 1,
  }
}

export const adminService = {
  /* ---------- A1 · Dashboard & bank soal ---------- */
  // GET /admin/dashboard -> ringkasan siswa aktif, pengerjaan, rata-rata nilai.
  async dashboard({ signal } = {}) {
    return mapAdminDashboard(unwrap(await api.get(ENDPOINTS.admin.dashboard, { signal })))
  },

  // GET /admin/bank-soal/kecukupan?tingkat_id= -> lima bagian laporan.
  async kecukupanBankSoal({ tingkatId, signal } = {}) {
    return mapKecukupanBankSoal(
      unwrap(await api.get(ENDPOINTS.admin.bankSoalKecukupan, {
        params: { tingkat_id: tingkatId },
        signal,
      })),
    )
  },

  /* ---------- A2 · Siswa ---------- */
  // GET /admin/siswa -> paginated.
  async daftarSiswa({ halaman = 1, perPage = 15, signal } = {}) {
    const res = await api.get(ENDPOINTS.admin.siswa, {
      params: { page: halaman, per_page: perPage },
      signal,
    })
    const { items, ...paginasi } = halamanPaginasi(res, perPage, halaman)
    return { items: items.map(mapSiswa), ...paginasi }
  },

  async siswa({ id, signal } = {}) {
    return mapSiswa(unwrap(await api.get(ENDPOINTS.admin.siswaDetail(id), { signal })))
  },

  // PUT /admin/siswa/{id} -> ubah nama, email, tingkat_aktif_id.
  async ubahSiswa({ id, payload, signal } = {}) {
    return mapSiswa(unwrap(await api.put(ENDPOINTS.admin.siswaDetail(id), payload, { signal })))
  },

  // POST /admin/siswa/{id}/deactivate -> nonaktifkan (soft: is_active=false).
  async nonaktifkanSiswa({ id, signal } = {}) {
    return mapSiswa(unwrap(await api.post(ENDPOINTS.admin.siswaDeactivate(id), {}, { signal })))
  },

  async hapusSiswa({ id, signal } = {}) {
    await api.delete(ENDPOINTS.admin.siswaDetail(id), { signal })
  },

  /* ---------- A3 · Kompetensi & materi ---------- */
  async daftarKompetensi({ tingkatId = null, signal } = {}) {
    const params = tingkatId ? { tingkat_id: tingkatId } : {}
    const list = unwrap(await api.get(ENDPOINTS.admin.kompetensi, { params, signal }))
    return (Array.isArray(list) ? list : []).map(mapKompetensi)
  },

  async tambahKompetensi({ payload, signal } = {}) {
    return mapKompetensi(unwrap(await api.post(ENDPOINTS.admin.kompetensi, payload, { signal })))
  },

  async ubahKompetensi({ id, payload, signal } = {}) {
    return mapKompetensi(unwrap(await api.put(ENDPOINTS.admin.kompetensiDetail(id), payload, { signal })))
  },

  async hapusKompetensi({ id, signal } = {}) {
    await api.delete(ENDPOINTS.admin.kompetensiDetail(id), { signal })
  },

  async daftarMateriAdmin({ kompetensiId = null, signal } = {}) {
    const params = kompetensiId ? { kompetensi_id: kompetensiId } : {}
    const list = unwrap(await api.get(ENDPOINTS.admin.materi, { params, signal }))
    return (Array.isArray(list) ? list : []).map(mapMateriAdmin)
  },

  async tambahMateri({ payload, signal } = {}) {
    return mapMateriAdmin(unwrap(await api.post(ENDPOINTS.admin.materi, payload, { signal })))
  },

  async ubahMateri({ id, payload, signal } = {}) {
    return mapMateriAdmin(unwrap(await api.put(ENDPOINTS.admin.materiDetail(id), payload, { signal })))
  },

  async hapusMateri({ id, signal } = {}) {
    await api.delete(ENDPOINTS.admin.materiDetail(id), { signal })
  },

  // POST /admin/materi/gambar -> multipart. Membalas { path } tanpa domain;
  // URL penuh dibangun FE (lihat mappers/belajar.js urlStorage).
  async unggahGambarMateri({ file, signal } = {}) {
    const form = new FormData()
    form.append('gambar', file)
    return unwrap(await api.post(ENDPOINTS.admin.materiGambar, form, {
      signal,
      headers: { 'Content-Type': 'multipart/form-data' },
    }))
  },

  /* ---------- A4 · Cerita soal, soal, pembahasan ---------- */
  async daftarKonteksSoal({ signal } = {}) {
    const list = unwrap(await api.get(ENDPOINTS.admin.konteksSoal, { signal }))
    return (Array.isArray(list) ? list : []).map(mapKonteksSoal)
  },

  async tambahKonteksSoal({ payload, signal } = {}) {
    return mapKonteksSoal(unwrap(await api.post(ENDPOINTS.admin.konteksSoal, payload, { signal })))
  },

  async ubahKonteksSoal({ id, payload, signal } = {}) {
    return mapKonteksSoal(unwrap(await api.put(ENDPOINTS.admin.konteksSoalDetail(id), payload, { signal })))
  },

  async hapusKonteksSoal({ id, signal } = {}) {
    await api.delete(ENDPOINTS.admin.konteksSoalDetail(id), { signal })
  },

  // GET /admin/soal -> paginated. Admin melihat kunci jawaban.
  async daftarSoal({ halaman = 1, perPage = 15, signal } = {}) {
    const res = await api.get(ENDPOINTS.admin.soal, {
      params: { page: halaman, per_page: perPage },
      signal,
    })
    const { items, ...paginasi } = halamanPaginasi(res, perPage, halaman)
    return { items: items.map(mapSoalAdmin), ...paginasi }
  },

  async soal({ id, signal } = {}) {
    return mapSoalAdmin(unwrap(await api.get(ENDPOINTS.admin.soalDetail(id), { signal })))
  },

  // Tipe ganda mengirim pilihan_jawaban + kunci; tipe isian hanya kunci.
  async tambahSoal({ payload, signal } = {}) {
    return mapSoalAdmin(unwrap(await api.post(ENDPOINTS.admin.soal, payload, { signal })))
  },

  async ubahSoal({ id, payload, signal } = {}) {
    return mapSoalAdmin(unwrap(await api.put(ENDPOINTS.admin.soalDetail(id), payload, { signal })))
  },

  async hapusSoal({ id, signal } = {}) {
    await api.delete(ENDPOINTS.admin.soalDetail(id), { signal })
  },

  // Pembahasan: 404 dari backend bila belum ada — view yang menanganinya,
  // jadi service ini melempar apa adanya.
  async pembahasan({ id, signal } = {}) {
    return unwrap(await api.get(ENDPOINTS.admin.soalPembahasan(id), { signal }))
  },

  async simpanPembahasan({ id, isiPembahasan, signal } = {}) {
    return unwrap(await api.post(ENDPOINTS.admin.soalPembahasan(id), {
      isi_pembahasan: isiPembahasan,
    }, { signal }))
  },

  async hapusPembahasan({ id, signal } = {}) {
    await api.delete(ENDPOINTS.admin.soalPembahasan(id), { signal })
  },

  /* ---------- A5 · Latihan & simulasi ---------- */
  async daftarLatihan({ halaman = 1, perPage = 15, signal } = {}) {
    const res = await api.get(ENDPOINTS.admin.latihan, {
      params: { page: halaman, per_page: perPage },
      signal,
    })
    const { items, ...paginasi } = halamanPaginasi(res, perPage, halaman)
    return { items: items.map(mapLatihan), ...paginasi }
  },

  async tambahLatihan({ payload, signal } = {}) {
    return mapLatihan(unwrap(await api.post(ENDPOINTS.admin.latihan, payload, { signal })))
  },

  async ubahLatihan({ id, payload, signal } = {}) {
    return mapLatihan(unwrap(await api.put(ENDPOINTS.admin.latihanDetail(id), payload, { signal })))
  },

  async hapusLatihan({ id, signal } = {}) {
    await api.delete(ENDPOINTS.admin.latihanDetail(id), { signal })
  },

  async daftarSimulasiAdmin({ halaman = 1, perPage = 15, signal } = {}) {
    const res = await api.get(ENDPOINTS.admin.simulasi, {
      params: { page: halaman, per_page: perPage },
      signal,
    })
    const { items, ...paginasi } = halamanPaginasi(res, perPage, halaman)
    return { items: items.map(mapSimulasiAdmin), ...paginasi }
  },

  async tambahSimulasi({ payload, signal } = {}) {
    return mapSimulasiAdmin(unwrap(await api.post(ENDPOINTS.admin.simulasi, payload, { signal })))
  },

  async ubahSimulasi({ id, payload, signal } = {}) {
    return mapSimulasiAdmin(unwrap(await api.put(ENDPOINTS.admin.simulasiDetail(id), payload, { signal })))
  },

  async hapusSimulasi({ id, signal } = {}) {
    await api.delete(ENDPOINTS.admin.simulasiDetail(id), { signal })
  },

  /* ---------- A6 · Tingkat & aturan pemetaan ---------- */
  async daftarTingkatAdmin({ signal } = {}) {
    const list = unwrap(await api.get(ENDPOINTS.admin.tingkat, { signal }))
    return (Array.isArray(list) ? list : []).map(mapTingkatAdmin)
  },

  async tingkat({ id, signal } = {}) {
    return mapTingkatAdmin(unwrap(await api.get(ENDPOINTS.admin.tingkatDetail(id), { signal })))
  },

  // Tingkat hanya boleh diubah nama + deskripsi (tidak bisa tambah/hapus).
  async ubahTingkat({ id, payload, signal } = {}) {
    return mapTingkatAdmin(unwrap(await api.put(ENDPOINTS.admin.tingkatDetail(id), payload, { signal })))
  },

  async aturanPemetaan({ tingkatId, signal } = {}) {
    return mapAturanPemetaan(
      unwrap(await api.get(ENDPOINTS.admin.aturanPemetaan(tingkatId), { signal })),
    )
  },

  // Semua 16 parameter wajib dikirim; backend menolak kalau ada yang kurang.
  async simpanAturanPemetaan({ tingkatId, payload, signal } = {}) {
    return mapAturanPemetaan(
      unwrap(await api.put(ENDPOINTS.admin.aturanPemetaan(tingkatId), payload, { signal })),
    )
  },
}
