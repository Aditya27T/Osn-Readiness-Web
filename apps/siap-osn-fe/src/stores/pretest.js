import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { pretestService } from '@/services/pretest.js'
import { kodeError, statusError } from '@/lib/errors.js'

// State pengerjaan pre-test lintas halaman (soal + jawaban + hasil).
// Kontrak API + bentuk data: services/mappers/pretest.js.
//
// - Daftar tingkat di-cache singkat; paket soal selalu diambil fresh
//   (jawaban bisa berubah dari perangkat lain).
// - ID pretest berjalan disimpan di localStorage agar reload di tengah
//   ujian bisa resume (backend tidak punya endpoint "pretest aktif").

const KUNCI_PRETEST_AKTIF = 'siap_osn_pretest_aktif'
const SEGAR_TINGKAT_MS = 5 * 60_000

export const STATUS = {
  IDLE: 'idle',
  MEMILIH: 'memilih',
  MENGERJAKAN: 'mengerjakan',
  MENGUMPULKAN: 'mengumpulkan',
  MENILAI: 'menilai',
  SELESAI: 'selesai',
}

export const usePretestStore = defineStore('pretest', () => {
  const status = ref(STATUS.IDLE)
  const tingkatList = ref([])
  const tingkatId = ref(null)
  const pretestId = ref(null)
  const soal = ref([])
  const hasil = ref(null)
  const loading = ref(false)
  const error = ref(false)
  const simpanError = ref(false)

  let inflightTingkat = null
  let tingkatDimuatPada = 0
  let controller = null
  // Controller terpisah: muatTingkat boleh berjalan berdampingan dengan
  // lanjutkan/kumpulkan tanpa saling membatalkan.
  let controllerTingkat = null

  const jumlahTerjawab = computed(
    () => soal.value.filter((s) => s.jawaban !== null && s.jawaban !== undefined && s.jawaban !== '').length,
  )

  function bacaSimpanan() {
    try {
      return JSON.parse(localStorage.getItem(KUNCI_PRETEST_AKTIF) ?? 'null')
    } catch {
      return null
    }
  }

  function tulisSimpanan(v) {
    if (v) localStorage.setItem(KUNCI_PRETEST_AKTIF, JSON.stringify(v))
    else localStorage.removeItem(KUNCI_PRETEST_AKTIF)
  }

  function terapkanPengerjaan(paket) {
    pretestId.value = paket.id
    tingkatId.value = paket.tingkatId
    soal.value = paket.soal.map((s) => ({ ...s }))
    hasil.value = null
    status.value = STATUS.MENGERJAKAN
    tulisSimpanan({ id: paket.id, tingkatId: paket.tingkatId })
  }

  function sinyalBaru() {
    controller?.abort()
    controller = new AbortController()
    return controller.signal
  }

  async function muatTingkat({ force = false } = {}) {
    if (inflightTingkat) return inflightTingkat
    if (!force && tingkatList.value.length && Date.now() - tingkatDimuatPada < SEGAR_TINGKAT_MS) {
      return Promise.resolve()
    }
    controllerTingkat?.abort()
    controllerTingkat = new AbortController()
    const { signal } = controllerTingkat
    // `loading` tidak disentuh di sini: itu milik mulai/lanjutkan.
    inflightTingkat = pretestService
      .tingkat({ signal })
      .then((list) => {
        if (signal.aborted) return
        tingkatList.value = list
        tingkatDimuatPada = Date.now()
      })
      .catch(() => {
        if (!signal.aborted) error.value = true
      })
      .finally(() => {
        inflightTingkat = null
      })
    return inflightTingkat
  }

  // POST mulai: 201 (baru) atau 200 (resume milik tingkat itu).
  async function mulai({ tingkatId: tid } = {}) {
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      terapkanPengerjaan(await pretestService.mulai({ tingkatId: tid, signal }))
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  function terapkanPaket(paket) {
    if (paket.jenis === 'pengerjaan') return terapkanPengerjaan(paket)
    if (paket.jenis === 'menunggu') {
      pretestId.value = paket.id
      tingkatId.value = paket.tingkatId
      status.value = STATUS.MENILAI
      tulisSimpanan({ id: paket.id, tingkatId: paket.tingkatId })
      return
    }
    hasil.value = paket.hasil
    pretestId.value = paket.hasil.id
    tingkatId.value = paket.hasil.tingkatId
    status.value = STATUS.SELESAI
    tulisSimpanan(null)
  }

  // GET lihat: resume pengerjaan, menunggu nilai, atau langsung hasil.
  async function lanjutkan({ id } = {}) {
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      terapkanPaket(await pretestService.lihat({ id, signal }))
    } catch (err) {
      // ID milik akun lain / sudah terhapus: buang supaya tidak macet.
      if (statusError(err) === 404) tulisSimpanan(null)
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  // Autosave satu soal. Optimistis di lokal; kegagalan jaringan hanya
  // menandai simpanError agar view bisa memberi tahu user (tanpa toast tiap ketik).
  async function simpanJawaban({ soalId, jawaban }) {
    const target = soal.value.find((s) => s.id === soalId)
    if (target) target.jawaban = jawaban ?? null
    try {
      await pretestService.simpanJawaban({ id: pretestId.value, soalId, jawaban })
      simpanError.value = false
    } catch {
      simpanError.value = true
    }
  }

  // POST submit (idempoten). 503 HASIL_SEDANG_DIPROSES bukan kegagalan:
  // jawaban sudah terkunci di server, tinggal menunggu nilai.
  async function kumpulkan() {
    const signal = sinyalBaru()
    status.value = STATUS.MENGUMPULKAN
    error.value = false
    try {
      hasil.value = await pretestService.kumpulkan({ id: pretestId.value, signal })
      status.value = STATUS.SELESAI
      tulisSimpanan(null)
      return hasil.value
    } catch (err) {
      if (kodeError(err) === 'HASIL_SEDANG_DIPROSES') {
        status.value = STATUS.MENILAI
        return null
      }
      if (!signal.aborted) {
        error.value = true
        status.value = STATUS.MENGERJAKAN
      }
      throw err
    }
  }

  // Dipanggil berkala oleh view saat status MENILAI. Submit ulang aman
  // (idempoten) dan sekaligus memicu penilaian ulang di server.
  async function cekHasil() {
    if (status.value !== STATUS.MENILAI || !pretestId.value) return null
    try {
      hasil.value = await pretestService.kumpulkan({ id: pretestId.value })
      status.value = STATUS.SELESAI
      tulisSimpanan(null)
      return hasil.value
    } catch {
      return null // masih diproses; view mencoba lagi
    }
  }

  // Wajib dipanggil saat login/logout supaya pretest akun lama tidak bocor.
  function $reset() {
    controller?.abort()
    controller = null
    controllerTingkat?.abort()
    controllerTingkat = null
    inflightTingkat = null
    tingkatDimuatPada = 0
    status.value = STATUS.IDLE
    tingkatList.value = []
    tingkatId.value = null
    pretestId.value = null
    soal.value = []
    hasil.value = null
    loading.value = false
    error.value = false
    simpanError.value = false
    tulisSimpanan(null)
  }

  return {
    status,
    tingkatList,
    tingkatId,
    pretestId,
    soal,
    hasil,
    loading,
    error,
    simpanError,
    jumlahTerjawab,
    idTersimpan: bacaSimpanan,
    muatTingkat,
    mulai,
    lanjutkan,
    simpanJawaban,
    kumpulkan,
    cekHasil,
    $reset,
  }
})
