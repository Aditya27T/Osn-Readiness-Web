<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useLatihanStore, STATUS_LATIHAN } from '@/stores/latihan.js'
import { useMateriStore } from '@/stores/materi.js'
import PengerjaanSoal from '@/components/soal/PengerjaanSoal.vue'
import UserMenu from '@/components/UserMenu.vue'
import { pesanError } from '@/lib/errors.js'
import { hitungRingkasan } from '@/lib/soal.js'

// Halaman latihan. Kerangka soal, panel nomor, dan modal kumpulkan memakai
// PengerjaanSoal yang sama dengan pre-test & simulasi; yang khas di sini
// latihan selesai tanpa batas waktu dan langsung menampilkan hasil.

const route = useRoute()
const router = useRouter()
const toast = useToast()
const latihan = useLatihanStore()
const materi = useMateriStore()

const petunjuk = {
  judul: 'Petunjuk Pengerjaan',
  isi: 'Latihan tidak dibatasi waktu. Jawaban tersimpan otomatis setiap kamu mengisi, dan jawaban hanya dikunci setelah kamu menekan Kumpulkan.',
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

const bolehMengerjakan = computed(() => latihan.status === STATUS_LATIHAN.MENGERJAKAN)
const soal = computed(() => latihan.soal)
const total = computed(() => soal.value.length)
const hasil = computed(() => latihan.hasil)
const ringkasan = computed(() => hitungRingkasan(soal.value, jawaban, ragu))
const tombolSelesai = computed(() => aktif.value === total.value - 1)

const subjudul = computed(() => {
  if (!total.value) return latihan.materiJudul || 'Latihan soal.'
  return `Soal ${aktif.value + 1} dari ${total.value} · belum dibatasi waktu`
})

function seedJawaban() {
  for (const k of Object.keys(jawaban)) delete jawaban[k]
  for (const k of Object.keys(ragu)) delete ragu[k]
  soal.value.forEach((s, i) => {
    jawaban[i] = s.jawaban ?? null
  })
  aktif.value = 0
}

/* ---------- Polling nilai (status assessing) ---------- */
let timerPolling = null
function stopPolling() {
  clearInterval(timerPolling)
  timerPolling = null
}
// Nilai terbaik di halaman materi harus ikut baru.
async function hasilMuncul() {
  stopPolling()
  if (!materi.tingkatId) return
  await materi.fetchDaftar({ tingkatId: materi.tingkatId, force: true }).catch(() => {})
}

onMounted(async () => {
  const quizId = Number(route.params.quizId)
  if (!Number.isFinite(quizId) || quizId <= 0) {
    galatAwal.value = 'ID quiz tidak valid. Buka latihan dari halaman materi.'
    memuatAwal.value = false
    return
  }
  // Store hangat hanya dipakai bila memang quiz yang sama.
  const hangat =
    latihan.quizId === quizId &&
    latihan.status === STATUS_LATIHAN.MENGERJAKAN &&
    latihan.soal.length
  if (!hangat) {
    try {
      await latihan.mulai({ quizId })
    } catch (err) {
      galatAwal.value = pesanError(err, 'Tidak dapat membuka latihan. Coba lagi.')
      memuatAwal.value = false
      return
    }
  }
  if (latihan.status === STATUS_LATIHAN.SELESAI) {
    await hasilMuncul()
    memuatAwal.value = false
    return
  }
  if (latihan.status === STATUS_LATIHAN.MENILAI) {
    mulaiPolling()
    memuatAwal.value = false
    return
  }
  seedJawaban()
  memuatAwal.value = false
})

/* ---------- Autosave (debounce 800 ms per soal) ---------- */
const timerSimpan = {}
function jadwalSimpan(i) {
  clearTimeout(timerSimpan[i])
  timerSimpan[i] = setTimeout(() => {
    const target = soal.value[i]
    if (!target || latihan.status !== STATUS_LATIHAN.MENGERJAKAN) return
    const nilai = jawaban[i]
    latihan.simpanJawaban({ soalId: target.id, jawaban: nilai === '' ? null : (nilai ?? null) })
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

function toggleRagu() {
  if (!bolehMengerjakan.value) return
  ragu[aktif.value] = !ragu[aktif.value]
}

function pindah(i) {
  if (i < 0 || i >= total.value) return
  aktif.value = i
}

/* ---------- Kumpulkan ---------- */
async function kumpulkan({ paksa } = {}) {
  if (mengirim.value || !bolehMengerjakan.value) return
  if (!paksa && ringkasan.value.belum > 0) return
  mengirim.value = true
  try {
    const hasil = await latihan.kumpulkan()
    if (!hasil) {
      // 503 HASIL_SEDANG_DIPROSES: jawaban terkunci, tunggu nilai.
      mulaiPolling()
      return
    }
    await hasilMuncul()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal mengumpulkan', detail: pesanError(err), life: 4000 })
  } finally {
    mengirim.value = false
  }
}

function mulaiPolling() {
  stopPolling()
  timerPolling = setInterval(async () => {
    const hasil = await latihan.cekHasil()
    if (!hasil) return
    await hasilMuncul()
  }, 5000)
}

function kembaliKeMateri() {
  router.push({ name: 'siswa.materi' })
}

onBeforeUnmount(() => {
  stopPolling()
  for (const k of Object.keys(timerSimpan)) clearTimeout(timerSimpan[k])
})

function formatNilai(nilai) {
  return nilai == null ? '—' : String(Math.round(nilai))
}
</script>

<template>
  <div class="halaman">
    <!-- STATUS AWAL -->
    <template v-if="memuatAwal || galatAwal">
      <header class="sederhana-header">
        <div>
          <h1>{{ latihan.materiJudul || 'Latihan' }}</h1>
          <p>{{ subjudul }}</p>
        </div>
        <UserMenu />
      </header>

      <section v-if="memuatAwal" class="kartu-kosong mx-auto max-w-xl">
        Menyiapkan latihan…
      </section>

      <section v-else class="kartu-kosong mx-auto max-w-xl">
        <p>{{ galatAwal }}</p>
        <button type="button" class="btn btn-emas" @click="kembaliKeMateri">Kembali ke Materi</button>
      </section>
    </template>

    <!-- HASIL -->
    <section v-else-if="latihan.status === 'selesai' && hasil" class="mx-auto max-w-3xl px-6 py-8">
      <div class="kartu hasil-kartu">
        <h2>Hasil Latihan</h2>
        <p class="hasil-nilai">{{ formatNilai(hasil.nilai) }}</p>
        <ul class="hasil-daftar">
          <li
            v-for="j in hasil.jawaban"
            :key="j.soalId"
            :class="j.benar ? 'ok' : 'salah'"
          >
            <span class="hasil-nomor">Soal {{ String(j.urutan).padStart(2, '0') }}</span>
            <span class="hasil-jawaban">{{ j.jawabanUser || 'Kosong' }}</span>
            <span class="hasil-status">{{ j.benar ? 'Benar' : 'Salah' }}</span>
          </li>
        </ul>
        <div class="hasil-kaki">
          <button type="button" class="btn btn-emas w-auto" @click="kembaliKeMateri">
            Kembali ke Materi
          </button>
        </div>
      </div>
    </section>

    <!-- SEDANG DINILAI -->
    <template v-else-if="latihan.status === STATUS_LATIHAN.MENILAI">
      <header class="sederhana-header">
        <div>
          <h1>{{ latihan.materiJudul || 'Latihan' }}</h1>
          <p>{{ subjudul }}</p>
        </div>
        <UserMenu />
      </header>

      <section class="kartu-kosong mx-auto max-w-xl">
        Jawabanmu sudah dikumpulkan dan sedang dinilai. Halaman ini akan menampilkan nilai otomatis
        begitu selesai.
      </section>
    </template>

    <!-- PENGERJAAN -->
    <PengerjaanSoal
      v-else-if="bolehMengerjakan"
      :judul="latihan.materiJudul || 'Latihan'"
      :subjudul="subjudul"
      :soal="soal"
      :jawaban="jawaban"
      :ragu="ragu"
      :aktif="aktif"
      :tombol-selesai="tombolSelesai"
      :petunjuk="petunjuk"
      :sisa-detik="null"
      :mengirim="mengirim"
      modal-judul="Yakin ingin mengumpulkan latihan?"
      modal-peringatan="Setelah latihan dikumpulkan, jawaban tidak dapat diubah kembali."
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
.kartu {
  background: #fff;
  border: 1px solid #e8ecf3;
  border-radius: 22px;
  padding: 28px;
  box-shadow: 0 2px 10px rgba(30, 50, 100, 0.04);
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

.hasil-kartu h2 { margin: 0; font-size: 18px; font-weight: 600; }
.hasil-nilai { margin: 6px 0 0; font-size: 40px; font-weight: 700; line-height: 1.1; }
.hasil-daftar { list-style: none; margin: 20px 0 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.hasil-daftar li {
  display: flex; align-items: center; gap: 12px;
  border: 1px solid #e8ecf3; border-radius: 12px;
  padding: 12px 16px; font-size: 13px;
}
.hasil-daftar li.ok { background: #f3fcf6; border-color: #a7e6bf; }
.hasil-daftar li.salah { background: #fff5f5; border-color: #f7c2c2; }
.hasil-nomor { font-weight: 600; }
.hasil-jawaban { flex: 1; min-width: 0; color: #4b5563; overflow-wrap: anywhere; }
.hasil-status { font-weight: 600; }
.hasil-daftar li.ok .hasil-status { color: #15803d; }
.hasil-daftar li.salah .hasil-status { color: #b91c1c; }
.hasil-kaki { margin-top: 22px; }
</style>
