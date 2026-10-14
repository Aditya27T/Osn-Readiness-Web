import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { riwayatService } from '@/services/riwayat.js'
import { usePretestStore } from '@/stores/pretest.js'

// Daftar riwayat hasil (pretest/latihan/simulasi) + filter + paginasi.
// Dipakai halaman Riwayat dan cuplikan "Riwayat Terbaru" di dashboard.
export const JENIS_SEMUA = 'semua'

export const useRiwayatStore = defineStore('riwayat', () => {
  const items = ref([])
  const total = ref(0)
  const halaman = ref(1)
  const perHalaman = ref(15)
  const halamanTerakhir = ref(1)
  const jenis = ref(JENIS_SEMUA)
  const tingkatId = ref(null)
  const loading = ref(false)
  const error = ref(false)

  let inflight = null
  let controller = null

  const adaFilter = computed(() => jenis.value !== JENIS_SEMUA || tingkatId.value !== null)

  function lookupTingkat() {
    const map = {}
    for (const t of usePretestStore().tingkatList) map[t.id] = t.nama
    return map
  }

  function terapkanHasil(hasil, signal) {
    if (signal.aborted) return
    items.value = hasil.items
    total.value = hasil.total
    halaman.value = hasil.halaman
    perHalaman.value = hasil.perHalaman
    halamanTerakhir.value = hasil.halamanTerakhir
  }

  // Ambil halaman daftar sesuai filter saat ini (dipakai view + pagination).
  function fetchDaftar({ force = false } = {}) {
    if (inflight && !force) return inflight
    controller?.abort()
    controller = new AbortController()
    const { signal } = controller
    loading.value = true
    error.value = false

    inflight = riwayatService
      .daftar({
        jenis: jenis.value === JENIS_SEMUA ? null : jenis.value,
        tingkatId: tingkatId.value,
        perPage: perHalaman.value,
        page: halaman.value,
        namaTingkatById: lookupTingkat(),
        signal,
      })
      .then((hasil) => terapkanHasil(hasil, signal))
      .catch(() => {
        if (!signal.aborted) error.value = true
      })
      .finally(() => {
        if (signal.aborted) return
        loading.value = false
        inflight = null
      })

    return inflight
  }

  function aturJenis(v) {
    jenis.value = v
    halaman.value = 1
    return fetchDaftar({ force: true })
  }

  function aturTingkat(id) {
    tingkatId.value = id
    halaman.value = 1
    return fetchDaftar({ force: true })
  }

  function keHalaman(n) {
    const target = Math.min(Math.max(1, n), halamanTerakhir.value || 1)
    if (target === halaman.value) return Promise.resolve()
    halaman.value = target
    return fetchDaftar({ force: true })
  }

  // Cuplikan untuk dashboard: 5 terbaru tanpa filter.
  // (Dashboard memanggil ini; filter/pagination view tidak terganggu
  // karena hasilnya langsung dikembalikan, bukan disimpan.)
  async function terbaris({ jumlah = 5 } = {}) {
    const hasil = await riwayatService.daftar({
      perPage: jumlah,
      page: 1,
      namaTingkatById: lookupTingkat(),
    })
    return hasil.items
  }

  // Wajib dipanggil saat login/logout supaya riwayat akun lama tidak bocor.
  function $reset() {
    controller?.abort()
    controller = null
    inflight = null
    items.value = []
    total.value = 0
    halaman.value = 1
    perHalaman.value = 15
    halamanTerakhir.value = 1
    jenis.value = JENIS_SEMUA
    tingkatId.value = null
    loading.value = false
    error.value = false
  }

  return {
    items,
    total,
    halaman,
    perHalaman,
    halamanTerakhir,
    jenis,
    tingkatId,
    loading,
    error,
    adaFilter,
    fetchDaftar,
    aturJenis,
    aturTingkat,
    keHalaman,
    terbaris,
    $reset,
  }
})
