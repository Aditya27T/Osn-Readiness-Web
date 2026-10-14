<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { usePretestStore } from '@/stores/pretest.js'
import { useProgressStore } from '@/stores/progress.js'
import { STATUS_SIMULASI, useSimulasiStore, WAKTU_HABIS } from '@/stores/simulasi.js'
import PengerjaanSoal from '@/components/soal/PengerjaanSoal.vue'
import UserMenu from '@/components/UserMenu.vue'
import { pesanError } from '@/lib/errors.js'
import { hitungRingkasan } from '@/lib/soal.js'

// Halaman ujian simulasi. Kerangka soal + autosave mengikuti PengerjaanSoal
// (sama persis dengan pre-test); yang berbeda: timer dihitung dari
// `batas_pada` server (reload tidak menambah waktu) dan setelah selesai selalu
// pindah ke halaman hasil.

const route = useRoute()
const router = useRouter()
const toast = useToast()
const simulasi = useSimulasiStore()
const pretest = usePretestStore()
const progress = useProgressStore()

const petunjuk = {
  judul: 'Aturan Simulasi',
  isi: 'Simulasi memakai waktu batas dari server dan dikumpulkan otomatis saat waktu habis. Navigasi bebas antarsoal; jawaban tersimpan otomatis setiap kali kamu mengisi.',
  tipe: [
    { nama: 'Pilihan Ganda', kelas: 'tipe-green', desc: 'Pilih satu jawaban yang paling tepat dari beberapa opsi.' },
    { nama: 'Isian', kelas: 'tipe-red', desc: 'Tulis jawabanmu dengan kalimat sendiri, jelaskan langkah dan alasannya.' },
  ],
}

const memuatAwal = ref(true)
const galatAwal = ref('')
const aktif = ref(0)
const jawaban = reactive({})
const ragu = reactive({})
const mengirim = ref(false)

const bolehMengerjakan = computed(() => simulasi.status === STATUS_SIMULASI.MENGERJAKAN)
const soal = computed(() => simulasi.soal)
const total = computed(() => soal.value.length)
const ringkasan = computed(() => hitungRingkasan(soal.value, jawaban, ragu))
const tombolSelesai = computed(() => aktif.value === total.value - 1)

const subjudul = computed(() => {
  if (!total.value) return 'Menjawab soal dengan batas waktu server.'
  return `Soal ${aktif.value + 1} dari ${total.value} · jawaban terkunci setelah dikumpulkan`
})

function seedJawaban() {
  for (const k of Object.keys(jawaban)) delete jawaban[k]
  for (const k of Object.keys(ragu)) delete ragu[k]
  soal.value.forEach((s, i) => {
    jawaban[i] = s.jawaban ?? null
  })
  aktif.value = 0
}

/* ---------- Navigasi ---------- */
function pindah(i) {
  if (i < 0 || i >= total.value) return
  aktif.value = i
}
function toggleRagu() {
  if (!bolehMengerjakan.value) return
  ragu[aktif.value] = !ragu[aktif.value]
}

/* ---------- Timer dari server ---------- */
// Batas waktu dihitung dari `batas_pada`, bukan dari hitungan lokal, sehingga
// reload di tengah ujian melanjutkan dari sisa waktu yang sama di server.
const sisaDetik = ref(0)

let timerJam = null
function hitungSisa() {
  if (!simulasi.batasPada) return
  const batas = Date.parse(simulasi.batasPada)
  if (Number.isNaN(batas)) return
  sisaDetik.value = Math.max(0, Math.round((batas - Date.now()) / 1000))
  // Waktu habis -> kumpulkan otomatis (server juga menolak simpan jawaban
  // lewat kode WAKTU_HABIS).
  if (sisaDetik.value === 0 && bolehMengerjakan.value) kumpulkan({ paksa: true })
}

/* ---------- Autosave (debounce 800 ms per soal) ---------- */
const timerSimpan = {}
function jadwalSimpan(i) {
  clearTimeout(timerSimpan[i])
  timerSimpan[i] = setTimeout(async () => {
    const target = soal.value[i]
    if (!target || !bolehMengerjakan.value) return
    const nilai = jawaban[i]
    const status = await simulasi.simpanJawaban({
      soalId: target.id,
      jawaban: nilai === '' ? null : (nilai ?? null),
    })
    // Server sudah menutup percobaan: langsung kumpulkan, jangan diamkan.
    if (status === WAKTU_HABIS) kumpulkan({ paksa: true })
  }, 800)
}
function pilihGanda(kode) {
  if (!bolehMengerjakan.value) return
  jawaban[aktif.value] = kode
  jadwalSimpan(aktif.value)
}
function isiUraian(v) {
  if (!bolehMengerjakan.value) return
  jawaban[aktif.value] = v
  jadwalSimpan(aktif.value)
}

/* ---------- Kumpulkan ---------- */
let timerPolling = null
function stopPolling() {
  clearInterval(timerPolling)
  timerPolling = null
}
function mulaiPolling() {
  stopPolling()
  timerPolling = setInterval(async () => {
    const hasil = await simulasi.cekHasil()
    if (!hasil) return
    stopPolling()
    await setelahSelesai()
  }, 5000)
}

// Tahap bisa berubah jadi LULUS / PUTARAN_HABIS setelah hasil keluar, dan
// tingkat berikutnya ikut terbuka — jadi kedua sumber data disegarkan.
async function setelahSelesai() {
  await progress.fetchDashboard({ force: true }).catch(() => {})
  await pretest.muatTingkat({ force: true }).catch(() => {})
  router.replace({ name: 'siswa.simulasi.hasil', params: { hasilId: simulasi.hasilId } })
}

// `paksa` = siswa menekan "Kumpulkan yang Kosong" di modal, atau waktu habis.
async function kumpulkan({ paksa } = {}) {
  if (mengirim.value || !bolehMengerjakan.value) return
  if (!paksa && ringkasan.value.belum > 0) return
  mengirim.value = true
  try {
    const hasil = await simulasi.kumpulkan()
    if (!hasil) {
      // 503 HASIL_SEDANG_DIPROSES: jawaban terkunci, tunggu nilai.
      mulaiPolling()
      return
    }
    await setelahSelesai()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal mengumpulkan', detail: pesanError(err), life: 4000 })
  } finally {
    mengirim.value = false
  }
}

function kembaliKeLobi() {
  router.push({ name: 'siswa.simulasi' })
}

onMounted(async () => {
  const simulasiId = Number(route.params.simulasiId)
  if (!Number.isFinite(simulasiId) || simulasiId <= 0) {
    galatAwal.value = 'ID simulasi tidak valid. Buka simulasi dari daftar.'
    memuatAwal.value = false
    return
  }
  try {
    // Idempoten: 201 untuk percobaan baru, 200 untuk yang sedang berjalan,
    // jadi reload di tengah ujian menyambung percobaan yang sama.
    const status = await simulasi.mulai({ simulasiId })
    if (status === STATUS_SIMULASI.SELESAI) {
      router.replace({ name: 'siswa.simulasi.hasil', params: { hasilId: simulasi.hasilId } })
      return
    }
    if (status === STATUS_SIMULASI.MENILAI) {
      mulaiPolling()
      memuatAwal.value = false
      return
    }
    seedJawaban()
    hitungSisa()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal membuka simulasi', detail: pesanError(err), life: 4000 })
    galatAwal.value = pesanError(err, 'Tidak dapat membuka simulasi. Coba lagi.')
    memuatAwal.value = false
    return
  }
  memuatAwal.value = false
  timerJam = setInterval(hitungSisa, 1000)
})

onBeforeUnmount(() => {
  clearInterval(timerJam)
  stopPolling()
  for (const k of Object.keys(timerSimpan)) clearTimeout(timerSimpan[k])
})
</script>

<template>
  <div class="halaman">
    <!-- STATUS AWAL -->
    <template v-if="memuatAwal || galatAwal">
      <header class="sederhana-header">
        <div>
          <h1>Simulasi Seleksi</h1>
          <p>{{ subjudul }}</p>
        </div>
        <UserMenu />
      </header>

      <section v-if="memuatAwal" class="kartu-kosong mx-auto max-w-xl">
        Menyiapkan simulasi…
      </section>

      <section v-else class="kartu-kosong mx-auto max-w-xl">
        <p>{{ galatAwal }}</p>
        <button type="button" class="btn btn-emas" @click="kembaliKeLobi">
          Kembali ke Daftar Simulasi
        </button>
      </section>
    </template>

    <!-- SEDANG DINILAI -->
    <template v-else-if="simulasi.status === STATUS_SIMULASI.MENILAI">
      <header class="sederhana-header">
        <div>
          <h1>Simulasi Seleksi</h1>
          <p>{{ subjudul }}</p>
        </div>
        <UserMenu />
      </header>

      <section class="kartu-kosong mx-auto max-w-xl">
        Jawabanmu sudah dikumpulkan dan sedang dinilai. Halaman ini akan menampilkan hasil otomatis
        begitu selesai.
      </section>
    </template>

    <!-- PENGERJAAN -->
    <PengerjaanSoal
      v-else-if="bolehMengerjakan"
      judul="Simulasi Seleksi"
      :subjudul="subjudul"
      :soal="soal"
      :jawaban="jawaban"
      :ragu="ragu"
      :aktif="aktif"
      :sisa-detik="sisaDetik"
      :simpan-error="simulasi.simpanError"
      :tombol-selesai="tombolSelesai"
      :petunjuk="petunjuk"
      :mengirim="mengirim"
      modal-judul="Yakin ingin mengumpulkan simulasi?"
      modal-peringatan="Setelah simulasi dikumpulkan, jawaban tidak dapat diubah kembali."
      @pilih-ganda="pilihGanda"
      @isi-jawaban="isiUraian"
      @toggle-ragu="toggleRagu"
      @pindah="pindah"
      @kumpulkan="kumpulkan"
    />
  </div>
</template>

<style scoped>
.halaman {
  min-height: 100%;
  background: #f5f8fd;
  color: #0f172a;
  display: flex;
  flex-direction: column;
}
/* Header untuk state fallback (memuat / galat / menilai): PengerjaanSoal belum
   dirender di sana, dan tanpa UserMenu user tidak bisa logout. */
.sederhana-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  background: #fff;
  border-bottom: 1px solid #e6ebf3;
  padding: 18px 28px;
}
.sederhana-header h1 { margin: 0; font-size: 20px; font-weight: 600; color: #0b1220; }
.sederhana-header p { margin: 4px 0 0; font-size: 14px; color: #8a94a6; }

.kartu-kosong {
  margin: 26px auto;
  max-width: 36rem;
  background: #fff;
  border: 1px solid #e8ecf3;
  border-radius: 22px;
  padding: 24px 28px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 14px;
  font-size: 14px;
  line-height: 1.6;
  color: #4b5563;
}
.btn {
  border: 0;
  border-radius: 10px;
  padding: 11px 18px;
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s ease;
}
.btn-emas { background: #fbb024; color: #1a1a1a; }
.btn-emas:hover { background: #f9bc40; }
</style>
