import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { adminService } from '@/services/admin.js'
import { ATURAN_FIELDS } from '@/services/mappers/admin.js'
import { statusError } from '@/lib/errors.js'

// State admin. Satu store untuk semua modul, dengan state dipisah per modul
// supaya tidak saling menimpa: admin berpindah halaman sering, dan tiap
// modul punya daftar + paginasi sendiri.
//
// Pola yang dipakai di sini:
// - daftar berpaginasi: `daftarX`, `metaX` (dipakai bersama semua modul,
//   jadi `meta` = { halaman, perHalaman, halamanTerakhir, total }),
// - aksi tulis selalu optimistis-then-refresh: setelah create/update/delete
//   sukses, daftar yang sedang tampil dimuat ulang (`{ force: true }`),
// - 409 `*_MASIH_DIGUNAKAN` dan 422 per kolom dilempar apa adanya supaya view
//   bisa menampilkannya lewat pesanError() / pesanField().

export const MODUL = {
  SISWA: 'siswa',
  KOMPETENSI: 'kompetensi',
  MATERI: 'materi',
  KONTEKS: 'konteks',
  SOAL: 'soal',
  LATIHAN: 'latihan',
  SIMULASI: 'simulasi',
  TINGKAT: 'tingkat',
}

// Daftar modul yang dipaginasi (yang lain bukan paginasi Resource).
const BERPAGINASI = new Set([MODUL.SISWA, MODUL.SOAL, MODUL.LATIHAN, MODUL.SIMULASI])

const metaKosong = () => ({ halaman: 1, perHalaman: 15, halamanTerakhir: 1, total: 0 })

export const useAdminStore = defineStore('admin', () => {
  /* ---------- Dashboard & bank soal ---------- */
  const ringkasan = ref(null)
  const kecukupan = ref(null)
  const tingkatKecukupanId = ref(null)

  /* ---------- Per modul ---------- */
  const daftar = ref({}) // { [modul]: item[] }
  const meta = ref({}) // { [modul]: { halaman, ... } }
  const detail = ref(null)
  const aturan = ref(null)

  /* ---------- Status ---------- */
  const loading = ref(false)
  const error = ref(false)
  const menyimpan = ref(false)

  let controller = null

  function sinyalBaru() {
    controller?.abort()
    controller = new AbortController()
    return controller.signal
  }

  // Waktu muat terakhir per modul, untuk cache 30 detik di muatDaftar.
  const dimuatPada = {}

  const daftarModul = (m) => daftar.value[m] ?? []
  const metaModul = (m) => meta.value[m] ?? metaKosong()

  /* ---------- Dashboard ---------- */
  async function muatRingkasan() {
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      ringkasan.value = await adminService.dashboard({ signal })
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  // Bank soal selalu per tingkat, jadi tingkat ikut disimpan supaya form
  // dan tombol "cek lagi" tahu sedang查看 tingkat yang mana.
  async function muatKecukupan({ tingkatId, force = false } = {}) {
    if (!force && kecukupan.value && kecukupan.value.tingkatId === tingkatId) return
    const signal = sinyalBaru()
    tingkatKecukupanId.value = tingkatId
    loading.value = true
    error.value = false
    try {
      kecukupan.value = await adminService.kecukupanBankSoal({ tingkatId, signal })
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  // Ringkasan bank soal dalam satu angka untuk banner di lobi admin.
  // Dihitung di store supaya view tidak perlu menghitung ulang.
  const bankSoalRingkas = computed(() => {
    const k = kecukupan.value
    if (!k) return null
    const semua = [
      ...k.pretestPerLevel,
      ...k.pretestPerMateri,
      ...k.latihanPerMateri,
      ...k.simulasiPerLevel.flatMap((s) => s.perLevel),
    ]
    const kurang = semua.filter((x) => x.kurang).length
      + (k.putaranPretest.kurang ? 1 : 0)
      + k.latihanPerMateri.filter((x) => !x.punyaLatihan).length
    return { total: semua.length, kurang }
  })

  /* ---------- Daftar modul ---------- */
  /**
   * @param {string} modul salah satu MODUL
   * @param {object} opsi { halaman, perHalaman, force, ...filter }
   *   filter spesifik modul: tingkatId (kompetensi), kompetensiId (materi),
   *   search/filter lain diteruskan apa adanya ke service.
   */
  async function muatDaftar(modul, { halaman = 1, perHalaman = 15, force = false, ...filter } = {}) {
    const saatIni = meta.value[modul] ?? metaKosong()
    // Cache halaman yang sama selama 30 detik, kecuali dipaksa.
    if (!force && saatIni.halaman === halaman && saatIni.perHalaman === perHalaman
      && Date.now() - (dimuatPada[modul] ?? 0) < 30_000 && daftarModul(modul).length) {
      return
    }
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      const hasil = await ambil(modul, { halaman, perHalaman, signal, filter })
      daftar.value = { ...daftar.value, [modul]: hasil.items }
      meta.value = {
        ...meta.value,
        [modul]: {
          halaman: hasil.halaman,
          perHalaman: hasil.perHalaman,
          halamanTerakhir: hasil.halamanTerakhir,
          total: hasil.total,
        },
      }
      dimuatPada[modul] = Date.now()
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  // Dispatcher ke service. Modul berpaginasi mengembalikan { items, ...meta },
  // modul lain array biasa yang dibungkus supaya bentuknya seragam.
  async function ambil(modul, { halaman, perHalaman, signal, filter }) {
    switch (modul) {
      case MODUL.SISWA:
        return adminService.daftarSiswa({ halaman, perPage: perHalaman, signal })
      case MODUL.SOAL:
        return adminService.daftarSoal({ halaman, perPage: perHalaman, signal })
      case MODUL.LATIHAN:
        return adminService.daftarLatihan({ halaman, perPage: perHalaman, signal })
      case MODUL.SIMULASI:
        return adminService.daftarSimulasiAdmin({ halaman, perPage: perHalaman, signal })
      case MODUL.KOMPETENSI: {
        const items = await adminService.daftarKompetensi({
          tingkatId: filter.tingkatId ?? null,
          signal,
        })
        return { items, total: items.length, halaman: 1, perHalaman: items.length, halamanTerakhir: 1 }
      }
      case MODUL.MATERI: {
        const items = await adminService.daftarMateriAdmin({
          kompetensiId: filter.kompetensiId ?? null,
          signal,
        })
        return { items, total: items.length, halaman: 1, perHalaman: items.length, halamanTerakhir: 1 }
      }
      case MODUL.KONTEKS: {
        const items = await adminService.daftarKonteksSoal({ signal })
        return { items, total: items.length, halaman: 1, perHalaman: items.length, halamanTerakhir: 1 }
      }
      case MODUL.TINGKAT: {
        const items = await adminService.daftarTingkatAdmin({ signal })
        return { items, total: items.length, halaman: 1, perHalaman: items.length, halamanTerakhir: 1 }
      }
      default:
        throw new Error(`Modul admin tidak dikenal: ${modul}`)
    }
  }

  async function keHalaman(modul, halaman, filter = {}) {
    return muatDaftar(modul, { ...filter, halaman, force: true })
  }

  /* ---------- Soal & pembahasan ---------- */
  async function muatSoal({ id, signal: s } = {}) {
    const signal = s ?? sinyalBaru()
    loading.value = true
    try {
      detail.value = await adminService.soal({ id, signal })
      return detail.value
    } catch (err) {
      detail.value = null
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  // Pembahasan opsional: 404 = belum ada, bukan galat.
  async function muatPembahasan({ id }) {
    try {
      return await adminService.pembahasan({ id })
    } catch (err) {
      if (statusError(err) === 404) return null
      throw err
    }
  }

  /* ---------- Aturan pemetaan ---------- */
  async function muatAturan({ tingkatId, force = false } = {}) {
    if (!force && aturan.value?.tingkatId === tingkatId) return aturan.value
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      aturan.value = await adminService.aturanPemetaan({ tingkatId, signal })
      return aturan.value
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  // Validasi lokal sebelum kirim: backend tetap acuan, tapi ini menahan
  // kesalahan yang jelas (persen tidak berjumlah 100) sebelum round-trip.
  const aturanValid = computed(() => {
    const a = aturan.value
    if (!a) return { valid: true, pesan: '' }
    const jumlah = (x, y, z) => x + y + z
    if (jumlah(a.pretest_persen_mudah, a.pretest_persen_sedang, a.pretest_persen_sulit) !== 100) {
      return { valid: false, pesan: 'Tiga persen level pre-test harus berjumlah 100.' }
    }
    if (jumlah(a.simulasi_persen_mudah, a.simulasi_persen_sedang, a.simulasi_persen_sulit) !== 100) {
      return { valid: false, pesan: 'Tiga persen level simulasi harus berjumlah 100.' }
    }
    return { valid: true, pesan: '' }
  })

  async function simpanAturan() {
    if (!aturan.value) return null
    if (!aturanValid.value.valid) throw new Error(aturanValid.value.pesan)
    const payload = Object.fromEntries(ATURAN_FIELDS.map((f) => [f, Number(aturan.value[f])]))
    menyimpan.value = true
    try {
      aturan.value = await adminService.simpanAturanPemetaan({
        tingkatId: aturan.value.tingkatId,
        payload,
      })
      return aturan.value
    } finally {
      menyimpan.value = false
    }
  }

  /* ---------- Tulis generik ---------- */
  /**
   * Bungkus satu aksi tulis: set menyimpan, jalankan, lalu segarkan daftar
   * yang sedang tampil. Error diteruskan apa adanya.
   */
  async function jalankan({ aksi, muatUlang = null }) {
    menyimpan.value = true
    try {
      const hasil = await aksi()
      if (muatUlang) await muatUlang()
      return hasil
    } finally {
      menyimpan.value = false
    }
  }

  function $reset() {
    controller?.abort()
    controller = null
    ringkasan.value = null
    kecukupan.value = null
    tingkatKecukupanId.value = null
    daftar.value = {}
    meta.value = {}
    detail.value = null
    aturan.value = null
    loading.value = false
    error.value = false
    menyimpan.value = false
    for (const k of Object.keys(dimuatPada)) delete dimuatPada[k]
  }

  return {
    ringkasan,
    kecukupan,
    tingkatKecukupanId,
    bankSoalRingkas,
    daftar,
    meta,
    detail,
    aturan,
    aturanValid,
    loading,
    error,
    menyimpan,
    MODUL,
    BERPAGINASI,
    ATURAN_FIELDS,
    daftarModul,
    metaModul,
    muatRingkasan,
    muatKecukupan,
    muatDaftar,
    keHalaman,
    muatSoal,
    muatPembahasan,
    muatAturan,
    simpanAturan,
    jalankan,
    $reset,
  }
})
