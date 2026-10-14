import { ref } from 'vue'
import { defineStore } from 'pinia'
import { belajarService } from '@/services/belajar.js'

// Daftar + detail materi per tingkat. Daftar di-cache per tingkat;
// detail selalu diambil fresh agar progress selalu akurat.
const SEGAR_MS = 60_000

export const useMateriStore = defineStore('materi', () => {
  const tingkatId = ref(null)
  const daftar = ref([])
  const detail = ref(null)
  const loading = ref(false)
  const error = ref(false)

  let inflight = null
  let controller = null
  let cacheTingkatId = null
  let dimuatPada = 0

  function fetchDaftar({ tingkatId: tid, force = false } = {}) {
    if (inflight && !force) return inflight
    if (!force && tid === cacheTingkatId && daftar.value.length && Date.now() - dimuatPada < SEGAR_MS) {
      return Promise.resolve()
    }
    controller?.abort()
    controller = new AbortController()
    const { signal } = controller
    loading.value = true
    error.value = false

    inflight = belajarService
      .daftar({ tingkatId: tid, signal })
      .then((list) => {
        if (signal.aborted) return
        tingkatId.value = tid
        daftar.value = list
        cacheTingkatId = tid
        dimuatPada = Date.now()
      })
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

  async function fetchDetail({ id } = {}) {
    loading.value = true
    error.value = false
    try {
      detail.value = await belajarService.detail({ id })
      return detail.value
    } catch (err) {
      error.value = true
      throw err
    } finally {
      loading.value = false
    }
  }

  // PUT progress selesai; selaraskan daftar + detail lokal.
  async function tandaiSelesai({ id } = {}) {
    const tersimpan = await belajarService.tandaiSelesai({ id })
    const patch = { progress: { status: tersimpan.status, persentase: tersimpan.persentase, tanggalSelesai: tersimpan.tanggalSelesai } }
    const diDaftar = daftar.value.find((m) => m.id === id)
    if (diDaftar) Object.assign(diDaftar, patch)
    if (detail.value?.id === id) detail.value = { ...detail.value, ...patch }
    return tersimpan
  }

  function $reset() {
    controller?.abort()
    controller = null
    inflight = null
    cacheTingkatId = null
    dimuatPada = 0
    tingkatId.value = null
    daftar.value = []
    detail.value = null
    loading.value = false
    error.value = false
  }

  return { tingkatId, daftar, detail, loading, error, fetchDaftar, fetchDetail, tandaiSelesai, $reset }
})
