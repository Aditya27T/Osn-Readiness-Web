<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useProgressStore } from '@/stores/progress.js'
import { usePretestStore, STATUS } from '@/stores/pretest.js'
import PengerjaanSoal from '@/components/soal/PengerjaanSoal.vue'
import UserMenu from '@/components/UserMenu.vue'
import { pesanError } from '@/lib/errors.js'
import { DURASI_PRETEST_MENIT, DURASI_PRETEST_DETIK } from '@/lib/pretest.js'
import { hitungRingkasan } from '@/lib/soal.js'

// true = tombol kanan selalu "Selesai" (seperti pada tampilan modal konfirmasi)
const props = defineProps({
  tampilkanSelesai: { type: Boolean, default: false },
  judul: { type: String, default: 'Pre-Test' },
  subjudul: { type: String, default: 'Pemetaan kompetensi awal.' },
  // true = langsung tampilkan kondisi "Koneksi Terputus" (untuk pratinjau)
  simulasiOffline: { type: Boolean, default: false },
  // status awal koneksi untuk pratinjau: 'online' | 'offline' | 'sinkron' | 'terhubung'
  statusAwal: { type: String, default: 'online' },
})

const router = useRouter()
const toast = useToast()
const progress = useProgressStore()
const pretest = usePretestStore()

const petunjuk = {
  judul: 'Petunjuk Pengerjaan',
  isi: 'Pre-test ini memetakan kompetensi awalmu: waktu terbatas dan jawaban tidak bisa diubah setelah dikumpulkan. Setiap jawaban tersimpan otomatis ke server.',
  tipe: [
    { nama: 'Pilihan Ganda', kelas: 'tipe-green', desc: 'Pilih satu jawaban yang paling tepat dari beberapa opsi.' },
    { nama: 'Isian', kelas: 'tipe-red', desc: 'Tulis jawabanmu dengan kalimat sendiri, jelaskan langkah dan alasannya.' },
  ],
}

/* ---------- Sumber soal: store (backend) ---------- */
const soal = computed(() => pretest.soal)
const total = computed(() => soal.value.length)
const bolehMengerjakan = computed(() => pretest.status === STATUS.MENGERJAKAN)

/* ---------- Polling nilai (status assessing) ---------- */
let timerPolling = null
function stopPolling() {
  clearInterval(timerPolling)
  timerPolling = null
}
function mulaiPolling() {
  stopPolling()
  timerPolling = setInterval(async () => {
    const hasil = await pretest.cekHasil()
    if (!hasil) return
    stopPolling()
    progress.markPreTestCompleted()
    await progress.fetchDashboard({ force: true }).catch(() => {})
    router.push({ name: 'siswa.pemetaan', params: { id: pretest.pretestId } })
  }, 5000)
}

/* ---------- State awal: pilih tingkat ---------- */
const tingkatDipilih = ref(null)
const memuatAwal = ref(true)
const galatAwal = ref('')
const mengirim = ref(false)

/* ---------- State pengerjaan (UI lokal) ---------- */
const aktif = ref(0)

// Jawaban per indeks soal (disalin dari store saat mulai/resume).
// ganda: kode opsi ('A'), isian: string bebas. Ragu-ragu murni lokal.
const jawaban = reactive({})
const ragu = reactive({})

const ringkasan = computed(() => hitungRingkasan(soal.value, jawaban, ragu))
// Tombol "Selesai" di soal terakhir, atau di semua soal bila prop ini aktif.
const tombolSelesai = computed(() => props.tampilkanSelesai || aktif.value === total.value - 1)

/* ---------- Mulai / resume ---------- */
function seedJawaban() {
  for (const k of Object.keys(jawaban)) delete jawaban[k]
  for (const k of Object.keys(ragu)) delete ragu[k]
  soal.value.forEach((s, i) => {
    jawaban[i] = s.jawaban ?? null
  })
  aktif.value = 0
}

function pilihDefaultTingkat() {
  const aktifId = progress.data.tingkatAktifId
  const daftar = pretest.tingkatList
  const cocok = daftar.find((t) => t.id === aktifId && t.terbuka) ?? daftar.find((t) => t.terbuka) ?? null
  tingkatDipilih.value = cocok?.id ?? null
}

async function siapkanAwal() {
  memuatAwal.value = true
  galatAwal.value = ''
  // Store masih hangat (pindah halaman tanpa reload) -> langsung pakai.
  if (pretest.status === STATUS.MENGERJAKAN && pretest.soal.length) {
    seedJawaban()
    memuatAwal.value = false
    return
  }
  // Resume bila ada ID tersimpan (reload di tengah ujian).
  const tersimpan = pretest.idTersimpan()
  if (tersimpan?.id) {
    try {
      await pretest.lanjutkan({ id: tersimpan.id })
    } catch {
      // ID sudah dibuang store (404) -> lanjut ke pemilih tingkat di bawah.
      if (pretest.idTersimpan()) {
        galatAwal.value = 'Tidak dapat membuka pre-test tersimpan. Coba lagi.'
        memuatAwal.value = false
        return
      }
    }
    if (pretest.status === STATUS.SELESAI) {
      router.replace({ name: 'siswa.pemetaan', params: { id: pretest.pretestId } })
      return
    }
    if (pretest.status === STATUS.MENILAI) {
      mulaiPolling()
      memuatAwal.value = false
      return
    }
    if (pretest.status === STATUS.MENGERJAKAN) {
      seedJawaban()
      memuatAwal.value = false
      return
    }
  }
  // Belum ada yang berjalan -> pemilih tingkat manual.
  try {
    await pretest.muatTingkat()
    await progress.fetchDashboard().catch(() => {})
    pilihDefaultTingkat()
  } catch {
    galatAwal.value = 'Tidak dapat memuat daftar tingkat. Periksa koneksi lalu coba lagi.'
  }
  memuatAwal.value = false
}

function cobaLagi() {
  siapkanAwal()
}

async function mulaiDipilih() {
  if (!tingkatDipilih.value || pretest.loading) return
  try {
    await pretest.mulai({ tingkatId: tingkatDipilih.value })
    seedJawaban()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal memulai pre-test', detail: pesanError(err), life: 4000 })
  }
}

/* ---------- Kumpulkan ---------- */
// `paksa` = siswa menekan "Kumpulkan yang Kosong" di modal.
async function kumpulkan({ paksa } = {}) {
  if (mengirim.value || pretest.status !== STATUS.MENGERJAKAN) return
  if (!paksa && ringkasan.value.belum > 0) return
  mengirim.value = true
  try {
    const hasil = await pretest.kumpulkan()
    if (!hasil) {
      // 503 HASIL_SEDANG_DIPROSES: jawaban sudah terkunci, tunggu nilai.
      mulaiPolling()
      return
    }
    progress.markPreTestCompleted()
    await progress.fetchDashboard({ force: true }).catch(() => {})
    router.push({ name: 'siswa.pemetaan', params: { id: pretest.pretestId } })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal mengumpulkan', detail: pesanError(err), life: 4000 })
  } finally {
    mengirim.value = false
  }
}

/* ---------- Aksi jawab + autosave (debounce per soal) ---------- */
const timerSimpan = {}
function jadwalSimpan(i) {
  clearTimeout(timerSimpan[i])
  timerSimpan[i] = setTimeout(() => {
    const target = soal.value[i]
    if (!target || pretest.status !== STATUS.MENGERJAKAN) return
    const nilai = jawaban[i]
    pretest.simpanJawaban({ soalId: target.id, jawaban: nilai === '' ? null : (nilai ?? null) })
  }, 800)
}
function pilihGanda(kode) {
  if (pretest.status !== STATUS.MENGERJAKAN) return
  jawaban[aktif.value] = kode
  jadwalSimpan(aktif.value)
}
function isiUraian(v) {
  if (pretest.status !== STATUS.MENGERJAKAN) return
  jawaban[aktif.value] = v
  jadwalSimpan(aktif.value)
}
function toggleRagu() {
  if (pretest.status !== STATUS.MENGERJAKAN) return
  ragu[aktif.value] = !ragu[aktif.value]
}
function pindah(i) {
  if (i < 0 || i >= total.value) return
  aktif.value = i
}

/* ---------- Timer (60 menit) ---------- */
// Durasi pre-test. Timer hanya berjalan saat soal dikerjakan, jadi memilih
// tingkat tidak memotong waktu siswa.
const sisaDetik = ref(DURASI_PRETEST_DETIK)
// Format mm:ss, sama seperti PengerjaanSoal. Header state awal memakainya
// dengan label "Durasi :", jadi angkanya belum countdown selama memilih
// tingkat; baru turun begitu soal dikerjakan.
const waktuTampil = computed(() => {
  const totalDetik = sisaDetik.value
  const m = Math.floor(totalDetik / 60)
  const s = totalDetik % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})
let timer = null
function mulaiTimer() {
  clearInterval(timer)
  timer = setInterval(() => {
    if (!bolehMengerjakan.value) return
    if (sisaDetik.value > 0) {
      sisaDetik.value--
      // Batas waktu client-side (backend tidak enforce): habis -> kumpulkan otomatis.
      if (sisaDetik.value === 0 && pretest.status === STATUS.MENGERJAKAN) {
        kumpulkan({ paksa: true })
      }
    }
  }, 1000)
}
onMounted(() => {
  siapkanAwal()
  mulaiTimer()
})
onBeforeUnmount(() => {
  clearInterval(timer)
  stopPolling()
  for (const k of Object.keys(timerSimpan)) clearTimeout(timerSimpan[k])
})

/* ---------- Status koneksi ---------- */
// koneksi: 'online' | 'offline' | 'sinkron' | 'terhubung'
// alur: offline -> sinkron (menyinkronkan) -> terhubung (berhasil) -> online
const koneksi = ref(props.simulasiOffline ? 'offline' : props.statusAwal)
const offlineTampil = ref(koneksi.value !== 'online')
let timerSinkron = null

const teksToast = computed(() => ({
  offline: { judul: 'Koneksi Terputus !!!', isi: 'Jawaban tetap tersimpan lokal secara aman di memori perangkat kamu...' },
  sinkron: { judul: 'Koneksi Kembali !!!', isi: 'Sedang menyinkronkan kembali...' },
  terhubung: { judul: 'Berhasil Terkoneksi !!!', isi: 'Berhasil terhubung ke jaringan...' },
}[koneksi.value] || { judul: '', isi: '' }))

function setOffline() {
  clearTimeout(timerSinkron)
  koneksi.value = 'offline'
  offlineTampil.value = true
}
function setOnline() {
  koneksi.value = 'sinkron'
  offlineTampil.value = true
  clearTimeout(timerSinkron)
  // 1) menyinkronkan  ->  2) berhasil terhubung  ->  3) notifikasi dan efek buram hilang
  timerSinkron = setTimeout(() => {
    koneksi.value = 'terhubung'
    timerSinkron = setTimeout(() => {
      koneksi.value = 'online'
      offlineTampil.value = false
    }, 2500)
  }, 2500)
}
function tutupToast() {
  clearTimeout(timerSinkron)
  offlineTampil.value = false
}

onMounted(() => {
  window.addEventListener('offline', setOffline)
  window.addEventListener('online', setOnline)
  if (!navigator.onLine) setOffline()
})
onBeforeUnmount(() => {
  clearTimeout(timerSinkron)
  window.removeEventListener('offline', setOffline)
  window.removeEventListener('online', setOnline)
})
</script>

<template>
  <div class="page" :class="{ offline: offlineTampil }">
    <!-- Header state awal. Saat soal dikerjakan, headerProvided oleh
         PengerjaanSoal (v-else di bawah), jadi jangan render dua kali. -->
    <header v-if="!bolehMengerjakan" class="header">
      <div class="header-left">
        <h1>{{ judul }}</h1>
        <p>{{ subjudul }}</p>
      </div>

      <div class="header-right">
        <span class="timer-label">Durasi :</span>
        <span class="timer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
            <circle cx="12" cy="13" r="8" /><path d="M12 9v4l2 2M9 2h6" />
          </svg>
          {{ waktuTampil }}
        </span>

        <UserMenu />
      </div>
    </header>

    <!-- STATUS AWAL: PILIH TINGKAT / MEMUAT / GALAT -->
    <section
      v-if="!bolehMengerjakan && pretest.status !== STATUS.MENILAI"
      class="mulai-wrap"
    >
      <div class="card mulai">
        <template v-if="memuatAwal">
          <h2>Menyiapkan pre-test…</h2>
          <p class="mulai-desc">Menghubungi server.</p>
        </template>
        <template v-else-if="galatAwal">
          <h2>Gagal memuat pre-test</h2>
          <p class="mulai-desc">{{ galatAwal }}</p>
          <button class="btn btn-mulai" @click="cobaLagi">Coba Lagi</button>
        </template>
        <template v-else>
          <h2>Pilih Tingkat Pre-Test</h2>
          <p class="mulai-desc">
            Pre-test dikerjakan per tingkat. Tingkat yang terbuka bisa dimulai.
          </p>
          <p class="mulai-desc">
            Durasi <b>{{ DURASI_PRETEST_MENIT }} menit</b>. Timer baru mulai berjalan setelah kamu
            menekan "Mulai Pre-Test".
          </p>
          <div class="tingkat-list">
            <button
              v-for="t in pretest.tingkatList"
              :key="t.id"
              class="tingkat-opsi"
              :class="{ aktif: tingkatDipilih === t.id }"
              :disabled="!t.terbuka"
              @click="tingkatDipilih = t.id"
            >
              <b>{{ t.nama }}</b>
              <span>{{ t.terbuka ? 'Terbuka' : 'Terkunci' }}</span>
            </button>
          </div>
          <button
            class="btn btn-mulai"
            :disabled="!tingkatDipilih || pretest.loading"
            @click="mulaiDipilih"
          >
            {{ pretest.loading ? 'Memulai…' : 'Mulai Pre-Test' }}
          </button>
        </template>
      </div>
    </section>

    <!-- STATUS MENILAI: jawaban terkunci, nilai belum keluar -->
    <section v-else-if="pretest.status === STATUS.MENILAI" class="mulai-wrap">
      <div class="card mulai menilai">
        <h2>Jawabanmu sedang dinilai</h2>
        <p class="mulai-desc">
          Jawabanmu sudah dikumpulkan dan sedang dinilai. Halaman ini akan berpindah otomatis
          setelah nilai siap.
        </p>
      </div>
    </section>

    <!-- PENGERJAAN -->
    <PengerjaanSoal
      v-else
      :judul="judul"
      :subjudul="subjudul"
      :soal="soal"
      :jawaban="jawaban"
      :ragu="ragu"
      :aktif="aktif"
      :sisa-detik="sisaDetik"
      :timer-label="bolehMengerjakan ? 'Sisa Waktu :' : 'Durasi :'"
      :timer-aktif="bolehMengerjakan"
      :simpan-error="pretest.simpanError"
      :tombol-selesai="tombolSelesai"
      :petunjuk="petunjuk"
      :mengirim="mengirim"
      modal-judul="Yakin ingin mengumpulkan pre-test?"
      modal-peringatan="Setelah pre-test dikumpulkan, jawaban tidak dapat diubah kembali."
      @pilih-ganda="pilihGanda"
      @isi-jawaban="isiUraian"
      @toggle-ragu="toggleRagu"
      @pindah="pindah"
      @kumpulkan="kumpulkan"
    />

    <!-- NOTIFIKASI KONEKSI -->
    <div v-if="offlineTampil" class="overlay-offline"></div>
    <transition name="toast">
      <div v-if="offlineTampil" class="toast" :class="'toast-' + koneksi" role="alert">
        <span class="toast-icon">
          <svg v-if="koneksi === 'terhubung'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12.5l4.5 4.5L19 7.5" />
          </svg>
          <svg v-else width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 9a15 15 0 0 1 20 0" /><path d="M5.5 12.6a10 10 0 0 1 13 0" /><path d="M9 16.2a5 5 0 0 1 6 0" /><circle cx="12" cy="19.5" r=".8" fill="#fff" />
          </svg>
        </span>
        <div class="toast-text">
          <strong>{{ teksToast.judul }}</strong>
          <p>{{ teksToast.isi }}</p>
        </div>
        <button class="toast-close" aria-label="Tutup notifikasi" @click="tutupToast">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round">
            <path d="M5 5l14 14M19 5L5 19" />
          </svg>
        </button>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f5f8fd;
  color: #0f172a;
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
}
button { font-family: inherit; cursor: pointer; }

/* Header state awal (pilih tingkat / memuat / menilai). Tampilannya sama
   dengan header PengerjaanSoal supaya tidak terlihat melompat saat mulai. */
.header {
  background: #fff;
  border-bottom: 1px solid #e6ebf3;
  padding: 18px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}
.header h1 { margin: 0; font-size: 20px; font-weight: 600; color: #0b1220; }
.header p { margin: 4px 0 0; font-size: 14px; color: #8a94a6; }
.header-right { display: flex; align-items: center; gap: 10px; }
.timer-label { font-size: 14px; color: #8a94a6; }
.timer {
  display: inline-flex; align-items: center; gap: 7px;
  background: #fde9ec; color: #d91c4a;
  font-weight: 600; font-size: 13.5px;
  padding: 7px 14px; border-radius: 999px;
}

/* Layar awal: pilih tingkat */
.card {
  background: #fff;
  border: 1px solid #e8ecf3;
  border-radius: 22px;
  box-shadow: 0 2px 10px rgba(30, 50, 100, 0.04);
}
.mulai-wrap { flex: 1; width: 100%; max-width: 1440px; margin: 0 auto; padding: 26px 28px 34px; }
.mulai { padding: 40px 36px; max-width: 640px; }
.mulai h2 { margin: 0; font-size: 19px; font-weight: 600; color: #0b1220; }
.mulai-desc { margin: 10px 0 22px; font-size: 14px; line-height: 1.6; color: #4b5563; }
.tingkat-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
.tingkat-opsi {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px; background: #fff; border: 1px solid #e3e8f1; border-radius: 10px;
  font-size: 15px; color: #1e293b; text-align: left;
}
.tingkat-opsi b { font-weight: 600; }
.tingkat-opsi span { font-size: 12.5px; color: #6b7280; }
.tingkat-opsi.aktif { border-color: #3b6fe0; background: #f6f9ff; }
.tingkat-opsi:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-mulai { background: #1e3a8a; border: 0; color: #fff; height: 44px; padding: 0 28px; font-size: 15px; font-weight: 600; border-radius: 10px; }
.mulai.menilai { max-width: 520px; text-align: center; }

/* Koneksi terputus */
.overlay-offline {
  position: fixed; inset: 0; z-index: 40;
  background: rgba(210, 217, 230, 0.55);
  backdrop-filter: blur(3px);
}
.page.offline :deep(.footer) { position: relative; z-index: 41; filter: blur(1.5px); }
.toast {
  position: fixed; top: 103px; right: 55px; z-index: 60;
  display: flex; align-items: center; gap: 14px;
  border-radius: 12px; border: 1px solid;
}
.toast-offline {
  width: 382px; padding: 18px 18px 18px 22px;
  background: #fff1f2; border-color: #e0383e;
  box-shadow: 0 8px 24px rgba(190, 30, 45, 0.12);
}
.toast-offline .toast-icon { background: #c8322e; }
.toast-offline .toast-text p, .toast-offline .toast-close { color: #b91c1c; }
.toast-sinkron {
  top: 102px; width: 316px; padding: 12px 16px 12px 22px;
  background: #fffbea; border-color: #f2b705;
  box-shadow: 0 8px 24px rgba(180, 120, 0, 0.12);
}
.toast-sinkron .toast-icon { background: #e8a30a; }
.toast-sinkron .toast-text p, .toast-sinkron .toast-close { color: #b45309; }
.toast-terhubung {
  top: 102px; width: 316px; padding: 12px 16px 12px 22px;
  background: #ecfdf5; border-color: #86efc0;
  box-shadow: 0 8px 24px rgba(16, 150, 90, 0.12);
}
.toast-terhubung .toast-icon { background: #12b76a; width: 28px; height: 28px; border-radius: 50%; }
.toast-terhubung .toast-text p, .toast-terhubung .toast-close { color: #0f7a4a; }
.toast-icon {
  width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
  display: grid; place-items: center;
}
.toast-text { flex: 1; min-width: 0; }
.toast-text strong { display: block; font-size: 13px; font-weight: 700; color: #14532d; margin-bottom: 2px; }
.toast-text p { margin: 0; font-size: 12.5px; line-height: 1.4; }
.toast-close { align-self: flex-start; background: none; border: 0; padding: 2px; margin-top: -2px; }
.toast-enter-active, .toast-leave-active { transition: opacity 0.2s, transform 0.2s; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(-8px); }

@media (max-width: 1000px) {
  .mulai-wrap { padding: 20px; }
}
</style>
