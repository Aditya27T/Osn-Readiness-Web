import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { latihanService } from '@/services/latihan.js'
import { kodeError } from '@/lib/errors.js'

// State pengerjaan latihan/quiz. Pola sama dengan pre-test, tanpa pemilih
// tingkat dan tanpa timer (quiz terikat satu materi, backend tanpa durasi).
// Tidak ada ID di localStorage: POST /quiz/{id}/mulai sudah idempoten dan
// mengembalikan pengerjaan berjalan, jadi `mulai` saja yang dipakai resume.
const KUNCI_LATIHAN_LAMA = 'siap_osn_latihan_aktif'

export const STATUS_LATIHAN = {
  IDLE: 'idle',
  MENGERJAKAN: 'mengerjakan',
  MENGUMPULKAN: 'mengumpulkan',
  MENILAI: 'menilai',
  SELESAI: 'selesai',
}

export const useLatihanStore = defineStore('latihan', () => {
  const status = ref(STATUS_LATIHAN.IDLE)
  const quizId = ref(null)
  const materiId = ref(null)
  const materiJudul = ref('')
  const pengerjaanId = ref(null)
  const soal = ref([])
  const hasil = ref(null)
  const loading = ref(false)
  const error = ref(false)
  const simpanError = ref(false)

  let controller = null

  const jumlahTerjawab = computed(
    () => soal.value.filter((s) => s.jawaban !== null && s.jawaban !== undefined && s.jawaban !== '').length,
  )

  function terapkanPaket(paket) {
    if (paket.jenis === 'pengerjaan') return terapkanPengerjaan(paket)
    if (paket.jenis === 'menunggu') {
      pengerjaanId.value = paket.id
      quizId.value = paket.quizId
      materiId.value = paket.materiId
      materiJudul.value = paket.materiJudul
      status.value = STATUS_LATIHAN.MENILAI
      return
    }
    hasil.value = paket.hasil
    pengerjaanId.value = paket.hasil.id
    status.value = STATUS_LATIHAN.SELESAI
  }

  function terapkanPengerjaan(paket) {
    pengerjaanId.value = paket.id
    quizId.value = paket.quizId
    materiId.value = paket.materiId
    materiJudul.value = paket.materiJudul
    soal.value = paket.soal.map((s) => ({ ...s }))
    hasil.value = null
    status.value = STATUS_LATIHAN.MENGERJAKAN
  }

  function sinyalBaru() {
    controller?.abort()
    controller = new AbortController()
    return controller.signal
  }

  // POST mulai: idempoten — kembalikan pengerjaan berjalan bila ada.
  async function mulai({ quizId: qid } = {}) {
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      terapkanPengerjaan(await latihanService.mulai({ quizId: qid, signal }))
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  async function lanjutkan({ id } = {}) {
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      terapkanPaket(await latihanService.lihat({ id, signal }))
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  async function simpanJawaban({ soalId, jawaban }) {
    const target = soal.value.find((s) => s.id === soalId)
    if (target) target.jawaban = jawaban ?? null
    try {
      await latihanService.simpanJawaban({ id: pengerjaanId.value, soalId, jawaban })
      simpanError.value = false
    } catch {
      simpanError.value = true
    }
  }

// POST submit (idempoten). 503 HASIL_SEDANG_DIPROSES bukan kegagalan:
  // jawaban sudah terkunci di server, tinggal menunggu nilai.
  async function kumpulkan() {
    const signal = sinyalBaru()
    status.value = STATUS_LATIHAN.MENGUMPULKAN
    error.value = false
    try {
      hasil.value = await latihanService.kumpulkan({ id: pengerjaanId.value, signal })
      status.value = STATUS_LATIHAN.SELESAI
      return hasil.value
    } catch (err) {
      if (kodeError(err) === 'HASIL_SEDANG_DIPROSES') {
        status.value = STATUS_LATIHAN.MENILAI
        return null
      }
      if (!signal.aborted) {
        error.value = true
        status.value = STATUS_LATIHAN.MENGERJAKAN
      }
      throw err
    }
  }

  // Dipanggil berkala oleh view saat status MENILAI.
  async function cekHasil() {
    if (status.value !== STATUS_LATIHAN.MENILAI || !pengerjaanId.value) return null
    try {
      hasil.value = await latihanService.kumpulkan({ id: pengerjaanId.value })
      status.value = STATUS_LATIHAN.SELESAI
      return hasil.value
    } catch {
      return null // masih diproses; view mencoba lagi
    }
  }

  function $reset() {
    controller?.abort()
    controller = null
    status.value = STATUS_LATIHAN.IDLE
    quizId.value = null
    materiId.value = null
    materiJudul.value = ''
    pengerjaanId.value = null
    soal.value = []
    hasil.value = null
    loading.value = false
    error.value = false
    simpanError.value = false
    // Kunci lama tidak dipakai lagi; dibersihkan sekali jalan.
    localStorage.removeItem(KUNCI_LATIHAN_LAMA)
  }

  return {
    status,
    quizId,
    materiId,
    materiJudul,
    pengerjaanId,
    soal,
    hasil,
    loading,
    error,
    simpanError,
    jumlahTerjawab,
    mulai,
    lanjutkan,
    simpanJawaban,
    kumpulkan,
    cekHasil,
    $reset,
  }
})
