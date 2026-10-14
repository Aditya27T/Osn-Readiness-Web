import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { simulasiService } from '@/services/simulasi.js'
import { kodeError } from '@/lib/errors.js'

// State pengerjaan simulasi. Pola sama dengan stores/latihan.js dan
// stores/pretest.js (hasil Fase 2), dengan dua perbedaan penting:
//
// 1. Batas waktu berasal dari server (`batas_pada`), bukan dihitung di FE,
//    sehingga reload tidak menambah waktu.
// 2. Tidak ada ID di localStorage: POST /simulasi/{id}/mulai idempoten dan
//    dashboard memberi tahu lewat tahap SIMULASI_BERJALAN.

export const STATUS_SIMULASI = {
  IDLE: 'idle',
  MENGERJAKAN: 'mengerjakan',
  MENGUMPULKAN: 'mengumpulkan',
  MENILAI: 'menilai',
  SELESAI: 'selesai',
}

// Nilai balik simpanJawaban besides the default: view langsung
// mengumpulkan karena server sudah menutup percobaan.
export const WAKTU_HABIS = 'waktu-habis'

export const useSimulasiStore = defineStore('simulasi', () => {
  const status = ref(STATUS_SIMULASI.IDLE)
  const daftar = ref([])
  const syarat = ref(null)
  const tingkatId = ref(null)
  const simulasiId = ref(null)
  const hasilId = ref(null)
  const mulaiPada = ref(null)
  const batasPada = ref(null)
  const soal = ref([])
  const hasil = ref(null)
  const review = ref(null)
  const loading = ref(false)
  const error = ref(false)
  const simpanError = ref(false)

  let controller = null
  let controllerRuang = null

  const jumlahTerjawab = computed(
    () => soal.value.filter((s) => s.jawaban !== null && s.jawaban !== undefined && s.jawaban !== '').length,
  )

  function sinyalBaru() {
    controller?.abort()
    controller = new AbortController()
    return controller.signal
  }

  function terapkanPengerjaan(paket) {
    hasilId.value = paket.id
    simulasiId.value = paket.simulasiId
    mulaiPada.value = paket.mulaiPada
    batasPada.value = paket.batasPada
    soal.value = paket.soal.map((s) => ({ ...s }))
    hasil.value = null
    status.value = STATUS_SIMULASI.MENGERJAKAN
  }

  function terapkanPaket(paket) {
    if (paket.jenis === 'hasil') return terapkanHasil(paket.hasil)
    if (paket.jenis === 'pengerjaan') return terapkanPengerjaan(paket)
    // 'menunggu': sudah disubmit tapi nilai belum keluar. Paket ini tidak
    // membawa soal, dan sengaja tidak seeded ke `soal` supaya siswa tidak
    // kembali ke mode mengerjakan; cekHasil() yang menjemput nilainya.
    hasilId.value = paket.id
    simulasiId.value = paket.simulasiId
    mulaiPada.value = paket.mulaiPada
    batasPada.value = paket.batasPada
    soal.value = []
    status.value = STATUS_SIMULASI.MENILAI
  }

  function terapkanHasil(hasilBaru) {
    hasil.value = hasilBaru
    hasilId.value = hasilBaru.id
    simulasiId.value = hasilBaru.simulasiId
    soal.value = []
    status.value = STATUS_SIMULASI.SELESAI
  }

  // Lobi: daftar simulasi + syarat, satu tingkat. Controller terpisah dari
  // pengerjaan supaya pindah halaman tidak saling membatalkan.
  async function muatRuang({ tingkatId: tid } = {}) {
    if (tid != null) tingkatId.value = tid
    controllerRuang?.abort()
    controllerRuang = new AbortController()
    const { signal } = controllerRuang
    loading.value = true
    error.value = false
    try {
      const [daftarBaru, syaratBaru] = await Promise.all([
        simulasiService.daftar({ tingkatId: tingkatId.value, signal }),
        simulasiService.syarat({ tingkatId: tingkatId.value, signal }),
      ])
      if (signal.aborted) return
      daftar.value = daftarBaru
      syarat.value = syaratBaru
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  // POST mulai: 201 baru atau 200 melanjutkan yang sedang berjalan.
  async function mulai({ simulasiId: sid } = {}) {
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      terapkanPaket(await simulasiService.mulai({ simulasiId: sid, signal }))
      return status.value
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  // Membuka percobaan dari riwayat (referensi_id) atau dari halaman hasil.
  async function lanjutkan({ id } = {}) {
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      terapkanPaket(await simulasiService.lihat({ id, signal }))
      return status.value
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  // Autosave satu soal. Mengembalikan WAKTU_HABIS supaya view langsung
  // mengumpulkan; selain itu null (sukses atau gagal simpan biasa).
  async function simpanJawaban({ soalId, jawaban }) {
    const target = soal.value.find((s) => s.id === soalId)
    if (target) target.jawaban = jawaban ?? null
    try {
      await simulasiService.simpanJawaban({ id: hasilId.value, soalId, jawaban })
      simpanError.value = false
      return null
    } catch (err) {
      if (kodeError(err) === 'WAKTU_HABIS') return WAKTU_HABIS
      simpanError.value = true
      return null
    }
  }

  // POST submit (idempoten). 503 HASIL_SEDANG_DIPROSES bukan kegagalan.
  async function kumpulkan() {
    const signal = sinyalBaru()
    status.value = STATUS_SIMULASI.MENGUMPULKAN
    error.value = false
    try {
      const hasilBaru = await simulasiService.kumpulkan({ id: hasilId.value, signal })
      terapkanHasil(hasilBaru)
      return hasil.value
    } catch (err) {
      if (kodeError(err) === 'HASIL_SEDANG_DIPROSES') {
        status.value = STATUS_SIMULASI.MENILAI
        return null
      }
      if (!signal.aborted) {
        error.value = true
        status.value = STATUS_SIMULASI.MENGERJAKAN
      }
      throw err
    }
  }

  // Dipanggil berkala view saat status MENILAI: submit ulang idempoten
  // sekaligus memicu penilaian ulang di server.
  async function cekHasil() {
    if (status.value !== STATUS_SIMULASI.MENILAI || !hasilId.value) return null
    try {
      const hasilBaru = await simulasiService.kumpulkan({ id: hasilId.value })
      terapkanHasil(hasilBaru)
      return hasil.value
    } catch {
      return null // masih diproses; view mencoba lagi
    }
  }

  async function muatReview({ id } = {}) {
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      review.value = await simulasiService.review({ id: id ?? hasilId.value, signal })
      return review.value
    } catch (err) {
      review.value = null
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  function $reset() {
    controller?.abort()
    controller = null
    controllerRuang?.abort()
    controllerRuang = null
    status.value = STATUS_SIMULASI.IDLE
    daftar.value = []
    syarat.value = null
    tingkatId.value = null
    simulasiId.value = null
    hasilId.value = null
    mulaiPada.value = null
    batasPada.value = null
    soal.value = []
    hasil.value = null
    review.value = null
    loading.value = false
    error.value = false
    simpanError.value = false
  }

  return {
    status,
    daftar,
    syarat,
    tingkatId,
    simulasiId,
    hasilId,
    mulaiPada,
    batasPada,
    soal,
    hasil,
    review,
    loading,
    error,
    simpanError,
    jumlahTerjawab,
    muatRuang,
    mulai,
    lanjutkan,
    simpanJawaban,
    kumpulkan,
    cekHasil,
    muatReview,
    $reset,
  }
})
