<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UserMenu from '@/components/UserMenu.vue'
import { STATUS_SIMULASI, useSimulasiStore } from '@/stores/simulasi.js'
import { pesanError } from '@/lib/errors.js'

// Hasil satu percobaan simulasi. Data diambil dari store bila hangat (baru
// sajasubmit dari halaman ujian); kalau tidak, `lanjutkan` membuka percobaan
// dari `referensi_id` di riwayat.

const route = useRoute()
const router = useRouter()
const simulasi = useSimulasiStore()

const memuat = ref(true)
const galat = ref('')
const belumDinilai = ref(false)

const hasil = computed(() => simulasi.hasil)

function formatNilai(nilai) {
  return nilai == null ? '—' : String(Math.round(nilai))
}
function formatTanggal(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
}

// Kisi nomor: benar/salah per soal, urut.
const kisi = computed(() => (hasil.value?.jawaban ?? []).slice().sort((a, b) => a.urutan - b.urutan))

function keLobi() {
  router.push({ name: 'siswa.simulasi' })
}
function kePembahasan() {
  router.push({ name: 'siswa.simulasi.review', params: { hasilId: simulasi.hasilId } })
}

async function ambilData() {
  const hasilId = Number(route.params.hasilId)
  if (!Number.isFinite(hasilId) || hasilId <= 0) {
    galat.value = 'ID hasil tidak valid. Buka hasil dari riwayat.'
    memuat.value = false
    return
  }
  // Store hangat: hasil milik percobaan yang sama, tidak perlu request lagi.
  const hangat = simulasi.status === STATUS_SIMULASI.SELESAI && simulasi.hasilId === hasilId
  if (!hangat) {
    try {
      const status = await simulasi.lanjutkan({ id: hasilId })
      if (status === STATUS_SIMULASI.MENILAI) belumDinilai.value = true
    } catch (err) {
      galat.value = pesanError(err, 'Tidak dapat membuka hasil simulasi. Coba lagi.')
      memuat.value = false
      return
    }
  }
  if (!hasil.value) belumDinilai.value = true
  memuat.value = false
}

let timerPolling = null
onMounted(async () => {
  await ambilData()
  if (!belumDinilai.value) return
  // Percobaan yang submit-nya gagal dinilai: cek ulang berkala.
  timerPolling = setInterval(async () => {
    const baru = await simulasi.cekHasil()
    if (!baru) return
    clearInterval(timerPolling)
    timerPolling = null
    belumDinilai.value = false
  }, 5000)
})

onBeforeUnmount(() => {
  clearInterval(timerPolling)
})
</script>

<template>
  <div class="min-h-full bg-[#f5f8fc] text-[#0f1b33]">
    <header class="flex items-center justify-between border-b border-[#e6ebf2] bg-white px-7 py-4">
      <div>
        <h1 class="text-[17px] font-bold leading-tight">Hasil Simulasi</h1>
        <p class="mt-0.5 text-[13px] text-[#6b778c]">Rincian nilai percobaanmu</p>
      </div>
      <UserMenu />
    </header>

    <main class="space-y-5 px-6 py-6">
      <section
        v-if="memuat"
        class="rounded-2xl border border-[#e6ebf2] bg-white px-5 py-3 text-[13px] text-[#6b778c]"
      >
        Memuat hasil…
      </section>

      <section
        v-else-if="galat"
        class="space-y-3 rounded-2xl border border-[#f3c2c2] bg-[#fdf0f0] px-5 py-3 text-[13px] text-[#a33333]"
      >
        <p>{{ galat }}</p>
        <button
          type="button"
          class="rounded-full border border-[#a33333] px-4 py-1.5 font-semibold"
          @click="keLobi"
        >
          Kembali ke Daftar Simulasi
        </button>
      </section>

      <section
        v-else-if="belumDinilai || !hasil"
        class="rounded-2xl border border-[#f3d9a8] bg-[#fffaef] px-5 py-3 text-[13px] text-[#8a5a12]"
      >
        Percobaan ini sedang dinilai. Halaman ini akan menampilkan nilai otomatis begitu selesai.
      </section>

      <template v-else>
        <!-- RINGKASAN -->
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div class="rounded-3xl border border-[#e6ebf2] bg-white p-6">
            <p class="text-[13px] text-[#6b778c]">Nilai</p>
            <p class="mt-1 text-4xl font-bold leading-none">{{ formatNilai(hasil.nilai) }}</p>
          </div>
          <div class="rounded-3xl border border-[#e6ebf2] bg-white p-6">
            <p class="text-[13px] text-[#6b778c]">Benar / Salah</p>
            <p class="mt-1 text-2xl font-bold">
              <span class="text-[#1f8a5b]">{{ hasil.jumlahBenar ?? '—' }}</span>
              <span class="text-[#b45309]"> / {{ hasil.jumlahSalah ?? '—' }}</span>
            </p>
          </div>
          <div
            class="rounded-3xl border p-6"
            :class="hasil.lulus ? 'border-[#a7e6bf] bg-[#f3fcf6]' : 'border-[#f3c2c2] bg-[#fdf0f0]'"
          >
            <p class="text-[13px] text-[#6b778c]">Status</p>
            <p
              class="mt-1 text-2xl font-bold"
              :class="hasil.lulus ? 'text-[#1f8a5b]' : 'text-[#a33333]'"
            >
              {{ hasil.lulus ? 'Lulus' : 'Belum Lulus' }}
            </p>
            <p class="mt-2 text-xs text-[#6b778c]">Selesai {{ formatTanggal(hasil.selesaiPada) }}</p>
          </div>
        </section>

        <!-- PETUNJUK BERIKUTNYA -->
        <section
          v-if="hasil.lulus"
          class="rounded-2xl border border-[#a7e6bf] bg-[#f3fcf6] px-5 py-3 text-[13px] text-[#15803d]"
        >
          Selamat, kamu lulus pada tingkat ini. Tingkat berikutnya sudah terbuka.
        </section>
        <section
          v-else
          class="rounded-2xl border border-[#f3d9a8] bg-[#fffaef] px-5 py-3 text-[13px] text-[#8a5a12]"
        >
          Belum lulus. Perbaiki materi yang sama lalu coba lagi, atau ambil pre-test ulang bila
          kuota simulasi sudah habis.
        </section>

        <!-- KISI NOMOR -->
        <section v-if="kisi.length" class="rounded-3xl border border-[#e6ebf2] bg-white p-6">
          <h3 class="text-sm font-bold text-[#2a3a52]">Kisi Jawaban</h3>
          <div class="mt-3 grid grid-cols-5 gap-2">
            <div
              v-for="j in kisi"
              :key="j.soalId"
              class="rounded-lg border py-2 text-center text-[13px] font-semibold"
              :class="j.benar
                ? 'border-[#8fe0ae] bg-[#c9f5d9] text-[#15803d]'
                : 'border-[#f0a957] bg-[#fde8c8] text-[#b45309]'"
              :title="j.benar ? 'Benar' : 'Salah'"
            >
              {{ String(j.urutan).padStart(2, '0') }}
            </div>
          </div>
          <div class="mt-4 flex gap-4 text-[11.5px] text-[#6b778c]">
            <span class="flex items-center gap-2">
              <i class="inline-block h-3 w-3 rounded-full border border-[#8fe0ae] bg-[#c9f5d9]"></i>
              Benar
            </span>
            <span class="flex items-center gap-2">
              <i class="inline-block h-3 w-3 rounded-full border border-[#f0a957] bg-[#fde8c8]"></i>
              Salah
            </span>
          </div>
        </section>

        <div class="flex gap-2">
          <button
            type="button"
            class="rounded-full border border-[#0f2a5c] px-5 py-2 text-[13px] font-semibold text-[#0f2a5c]"
            @click="keLobi"
          >
            Kembali ke Daftar Simulasi
          </button>
          <button
            type="button"
            class="rounded-full bg-[#0f2a5c] px-5 py-2 text-[13px] font-semibold text-white"
            @click="kePembahasan"
          >
            Lihat Pembahasan →
          </button>
        </div>
      </template>
    </main>
  </div>
</template>
