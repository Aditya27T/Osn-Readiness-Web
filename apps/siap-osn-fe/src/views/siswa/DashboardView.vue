<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { useProgressStore } from '@/stores/progress.js'
import UserMenu from '@/components/UserMenu.vue'
import PretestPopup from './PretestPopup.vue'

const auth = useAuthStore()
const progress = useProgressStore()
const router = useRouter()

/* ---------- Ambil data dashboard ---------- */

onMounted(async () => {
  await progress.fetchDashboard()
})

const d = computed(() => progress.data)

/* ---------- Status Pre-Test ---------- */

const nama = computed(() => auth.nama || 'Siswa')

const sudahPreTest = computed(() => d.value.preTestSelesai)

/* ---------- Mulai Pre-Test ---------- */

function mulaiPretest() {
  router.push({ name: 'siswa.pretest' })
}

/* ---------- Sapaan ---------- */

const subjudul = computed(() => {
  if (!sudahPreTest.value) {
    return 'Semangat memulai persiapanmu!'
  }

  return d.value.tingkat
    ? `Semangat melanjutkan persiapan Tingkat ${d.value.tingkat}-mu`
    : 'Semangat melanjutkan persiapanmu!'
})

/* ---------- Banner ---------- */

// PUTARAN_HABIS: kuota simulasi habis tanpa lulus. Backend mengizinkan
// pre-test baru, jadi banner menawarkan putaran baru, bukan sekadar
// "lanjutkan belajar".
const banner = computed(() => {
  if (d.value.perluPretestUlang) {
    return {
      judul: 'Kuota Simulasi Putaran Ini Habis',
      isi: 'Ambil pre-test ulang untuk memulai putaran baru dan mendapatkan peta materi yang diperbarui.',
    }
  }

  return sudahPreTest.value
    ? {
        judul: 'Lanjutkan Perjalanan OSN Informatika-mu!',
        isi: 'Teruskan belajar sesuai rekomendasi materi, lalu ikuti simulasi untuk mengukur kesiapanmu.',
      }
    : {
        judul: 'Mulai Perjalanan OSN Informatika-mu!',
        isi: 'Kamu belum mengikuti tes pemetaan awal. Silahkan ambil pre-test terlebih dahulu agar sistem dapat mendeteksi kekuatanmu dan membuka kurikulum belajar yang tepat sasaran.',
      }
})

/* ---------- Kartu Statistik ---------- */

const stats = computed(() => {
  const {
    materiSelesai,
    materiTotal,
    simulasiDiikuti,
    rataRataNilai,
  } = d.value

  const pct = progress.progressPersen

  return [
    {
      value: `${pct}%`,
      label: 'Progress Belajar',
      note:
        pct > 0
          ? `${materiSelesai} dari ${materiTotal} materi selesai`
          : 'Belum ada progres',
      ada: pct > 0,
    },
    {
      value: String(rataRataNilai),
      label: 'Rata-rata Nilai Simulasi',
      note:
        simulasiDiikuti > 0
          ? `Dari ${simulasiDiikuti} simulasi`
          : 'Belum ada Nilai',
      ada: simulasiDiikuti > 0,
    },
    {
      value: `${materiSelesai}/${materiTotal}`,
      label: 'Materi Diselesaikan',
      note:
        materiTotal === 0
          ? 'Belum ada materi yang diambil'
          : materiSelesai === materiTotal
            ? 'Semua materi selesai'
            : `${materiTotal - materiSelesai} materi belum selesai`,
      ada: materiTotal > 0,
    },
    {
      value: String(simulasiDiikuti),
      label: 'Simulasi Diikuti',
      note:
        simulasiDiikuti > 0
          ? 'Terus asah kemampuanmu'
          : 'Belum mengikuti simulasi',
      ada: simulasiDiikuti > 0,
    },
  ]
})

/* ---------- Placeholder ---------- */

const kompetensiPlaceholder = [
  'Struktur Data',
  'Algoritma Greedy',
  'Graf & Pohon',
  'Dynamic Programming',
  'Matematika Diskrit',
]

const rekomendasiPlaceholder = [
  {
    judul: 'Dynamic Programming Dasar',
    sub: 'Belum dikerjakan',
    badge: 'Direkomendasikan',
  },
  {
    judul: 'Graf & Pohon',
    sub: 'Perlu latihan',
    badge: 'Latihan',
  },
  {
    judul: 'Algoritma Greedy Lanjutan',
    sub: 'Perlu latihan',
    badge: 'Latihan',
  },
]

const riwayatPlaceholder = [
  'Simulasi — Tingkat Provinsi',
  'Latihan — Struktur Data',
  'Simulasi — Tingkat Provinsi',
  'Pre-Test — Tingkat Provinsi',
]

/* ---------- Hasil Latihan Terakhir ---------- */

function formatTanggal(iso) {
  if (!iso) return ''

  const t = new Date(iso)

  if (Number.isNaN(t.getTime())) {
    return ''
  }

  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(t)
}

const hasil = computed(() => {
  const h = d.value.hasilTerakhir

  if (!h) {
    return null
  }

  return {
    ...h,
    persen: h.nilai ?? 0,
    tanggalLabel: formatTanggal(h.tanggal),
    nilaiLabel: h.nilai == null ? '-' : String(h.nilai),
    statusLabel: h.lulus ? 'Lulus' : 'Selesai',
  }
})

/* ---------- Donut ---------- */

const RADIUS = 34
const KELILING = 2 * Math.PI * RADIUS

const dash = computed(() => {
  return `${((hasil.value?.persen ?? 0) / 100) * KELILING} ${KELILING}`
})
</script>

<template>
  <div class="min-h-full bg-[#f5f8fc] font-sans text-[#0f1b33]">

    <!-- ========================================= -->
    <!-- TOPBAR -->
    <!-- ========================================= -->

    <header
      class="flex items-center justify-between border-b border-[#e6ebf2] bg-white px-7 py-4"
    >
      <div>
        <h1 class="text-[17px] font-bold leading-tight">
          Halo {{ nama }}! 👋
        </h1>

        <p class="mt-0.5 text-[13px] text-[#6b778c]">
          {{ subjudul }}
        </p>
      </div>

      <UserMenu />
    </header>

    <main class="space-y-5 px-6 py-6">

      <!-- ========================================= -->
      <!-- STATUS PENGAMBILAN DATA -->
      <!-- ========================================= -->

      <p
        v-if="progress.loading && !progress.loaded"
        class="rounded-2xl border border-[#e6ebf2] bg-white px-5 py-3 text-[13px] text-[#6b778c]"
      >
        Memuat data dashboard…
      </p>

      <p
        v-else-if="progress.error"
        class="rounded-2xl border border-[#f3c2c2] bg-[#fdf0f0] px-5 py-3 text-[13px] text-[#a33333]"
      >
        Tidak dapat memuat data terbaru. Periksa koneksi lalu muat ulang halaman.
      </p>

      <!-- ========================================= -->
      <!-- BANNER -->
      <!-- ========================================= -->

      <section
        class="rounded-3xl bg-gradient-to-r from-[#0b2150] to-[#154a8f] px-8 py-7 text-white"
      >
        <h2 class="text-2xl font-bold">
          {{ banner.judul }}
        </h2>

        <p
          class="mt-2 max-w-3xl text-sm leading-relaxed text-white/85"
        >
          {{ banner.isi }}
        </p>

        <button
          v-if="d.perluPretestUlang"
          type="button"
          class="mt-4 rounded-full bg-white px-6 py-2.5 text-[13px] font-bold text-[#0b2150]"
          @click="mulaiPretest"
        >
          Ambil Pre-Test Ulang →
        </button>
      </section>

      <!-- ========================================= -->
      <!-- KARTU STATISTIK -->
      <!-- ========================================= -->

      <section
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
      >
        <article
          v-for="s in stats"
          :key="s.label"
          class="rounded-2xl border border-[#e6ebf2] bg-white px-5 py-5"
        >
          <p class="text-2xl font-bold leading-none">
            {{ s.value }}
          </p>

          <p class="mt-2 text-[13px] text-[#6b778c]">
            {{ s.label }}
          </p>

          <p
            class="mt-1 text-xs font-semibold"
            :class="
              s.ada
                ? 'text-[#1f8a5b]'
                : 'text-[#d93a3a]'
            "
          >
            {{ s.note }}
          </p>
        </article>
      </section>

      <!-- ========================================= -->
      <!-- DUA KOLOM -->
      <!-- ========================================= -->

      <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">

        <!-- ======================================= -->
        <!-- KOLOM KIRI -->
        <!-- ======================================= -->

        <div class="flex flex-col gap-5">

          <!-- PEMETAAN KOMPETENSI -->

          <article
            class="rounded-3xl border border-[#e6ebf2] bg-white p-6"
          >
            <div class="flex items-center justify-between">
              <h3 class="text-base font-bold">
                Pemetaan Kompetensi
              </h3>

              <span
                v-if="!sudahPreTest"
                class="rounded-full bg-[#fdf1c7] px-3 py-1 text-[11px] font-semibold text-[#7a5b00]"
              >
                Perlu Pre-Test
              </span>
            </div>

            <!-- TERISI -->

            <div
              v-if="d.kompetensi.length"
              class="mt-5"
            >
              <ul class="space-y-4">
                <li
                  v-for="k in d.kompetensi"
                  :key="k.nama"
                  class="text-sm"
                >
                  <div class="flex items-center justify-between">
                    <span>
                      {{ k.nama }}
                    </span>

                    <span class="text-xs font-semibold">
                      {{ k.skor }}%
                    </span>
                  </div>

                  <div
                    class="relative mt-2 h-2 rounded-full bg-[#eef2f8]"
                  >
                    <div
                      class="h-full rounded-full"
                      :class="
                        k.target != null &&
                        k.skor < k.target
                          ? 'bg-[#f0614f]'
                          : 'bg-[#2e9e6b]'
                      "
                      :style="{
                        width: k.skor + '%'
                      }"
                    ></div>

                    <span
                      v-if="k.target != null"
                      class="absolute -top-1 h-4 w-0.5 rounded bg-[#0f2a5c]"
                      :style="{
                        left: k.target + '%'
                      }"
                      :title="
                        `Standar penguasaan ${k.target}%`
                      "
                    ></span>
                  </div>
                </li>
              </ul>

              <p class="mt-4 text-xs text-[#6b778c]">
                Garis biru tua menandai standar penguasaan materi.
              </p>
            </div>

            <!-- KOSONG -->

            <div
              v-else
              class="relative mt-5"
            >
              <ul
                class="pointer-events-none select-none space-y-5 opacity-50 blur-[3px]"
                aria-hidden="true"
              >
                <li
                  v-for="k in kompetensiPlaceholder"
                  :key="k"
                  class="flex items-center gap-3 text-sm"
                >
                  <span
                    class="h-6 w-6 shrink-0 rounded-md bg-[#f1b8b8]"
                  ></span>

                  <span class="flex-1">
                    {{ k }}
                  </span>

                  <span class="text-xs">
                    0%
                  </span>
                </li>
              </ul>

              <div
                class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center"
              >
                <p class="text-base font-bold">
                  Data Pemetaan Belum Tersedia
                </p>

                <p
                  class="mt-1.5 max-w-sm text-xs leading-relaxed text-[#4a5568]"
                >
                  Ikuti pre-test untuk melihat perbandingan skor kemampuanmu
                  terhadap standar penguasaan materi.
                </p>
              </div>
            </div>
          </article>

          <!-- RIWAYAT TERBARU -->

          <article
            class="rounded-3xl border border-[#e6ebf2] bg-white p-6"
          >
            <h3 class="text-base font-bold">
              Riwayat Terbaru
            </h3>

            <!-- ADA RIWAYAT -->

            <ul
              v-if="d.riwayat.length"
              class="mt-5 space-y-4"
            >
              <li
                v-for="(r, i) in d.riwayat"
                :key="i"
                class="flex items-center gap-3 text-sm"
              >
                <span
                  class="h-8 w-8 shrink-0 rounded-lg bg-[#dfe7f5]"
                ></span>

                <span class="flex-1">
                  {{ r.judul }}

                  <span
                    class="block text-xs text-[#6b778c]"
                  >
                    {{ formatTanggal(r.tanggal) }}
                  </span>
                </span>

                <span
                  v-if="r.nilai != null"
                  class="text-sm font-bold"
                >
                  {{ r.nilai }}
                </span>
              </li>
            </ul>

            <!-- BELUM ADA RIWAYAT -->

            <div
              v-else
              class="relative mt-5"
            >
              <ul
                class="pointer-events-none select-none space-y-5 opacity-50 blur-[3px]"
                aria-hidden="true"
              >
                <li
                  v-for="(r, i) in riwayatPlaceholder"
                  :key="i"
                  class="flex items-center gap-3 text-sm"
                >
                  <span
                    class="h-8 w-8 shrink-0 rounded-lg bg-[#dfe7f5]"
                  ></span>

                  <span class="flex-1">
                    {{ r }}

                    <span class="block text-xs text-[#6b778c]">
                      2026
                    </span>
                  </span>

                  <span class="text-xs">
                    0
                  </span>
                </li>
              </ul>

              <div
                class="absolute inset-0 flex items-center justify-center"
              >
                <p class="text-base font-bold">
                  Belum Ada Riwayat
                </p>
              </div>
            </div>
          </article>
        </div>

        <!-- ======================================= -->
        <!-- KOLOM KANAN -->
        <!-- ======================================= -->

        <div class="flex flex-col gap-5">

          <!-- REKOMENDASI -->

          <article
            class="flex-1 rounded-3xl border border-[#e6ebf2] bg-white p-6"
          >
            <div class="flex items-center justify-between">
              <h3 class="text-base font-bold">
                Rekomendasi Buat Kamu
              </h3>

              <span
                v-if="!sudahPreTest"
                class="rounded-full bg-[#fdf1c7] px-3 py-1 text-[11px] font-semibold text-[#7a5b00]"
              >
                Perlu Pre-Test
              </span>
            </div>

            <!-- ADA REKOMENDASI -->

            <ul
              v-if="d.rekomendasi.length"
              class="mt-5 space-y-4"
            >
              <li
                v-for="r in d.rekomendasi"
                :key="r.judul"
                class="flex items-center gap-3 text-sm"
              >
                <span
                  class="h-8 w-8 shrink-0 rounded-lg bg-[#f1b8b8]"
                ></span>

                <span class="flex-1">
                  {{ r.judul }}

                  <span class="block text-xs text-[#6b778c]">
                    {{ r.sub }}
                  </span>
                </span>

                <span
                  v-if="r.badge"
                  class="rounded-full bg-[#fdf1c7] px-2.5 py-0.5 text-[11px]"
                >
                  {{ r.badge }}
                </span>
              </li>
            </ul>

            <!-- BELUM ADA REKOMENDASI -->

            <div
              v-else
              class="relative mt-5"
            >
              <ul
                class="pointer-events-none select-none space-y-5 opacity-50 blur-[3px]"
                aria-hidden="true"
              >
                <li
                  v-for="r in rekomendasiPlaceholder"
                  :key="r.judul"
                  class="flex items-center gap-3 text-sm"
                >
                  <span
                    class="h-8 w-8 shrink-0 rounded-lg bg-[#f1b8b8]"
                  ></span>

                  <span class="flex-1">
                    {{ r.judul }}

                    <span class="block text-xs text-[#6b778c]">
                      {{ r.sub }}
                    </span>
                  </span>

                  <span
                    class="rounded-full bg-[#fdf1c7] px-2.5 py-0.5 text-[11px]"
                  >
                    {{ r.badge }}
                  </span>
                </li>
              </ul>

              <div
                class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center"
              >
                <p class="text-base font-bold">
                  Rekomendasi Belum Tersedia
                </p>

                <p
                  class="mt-1.5 max-w-xs text-xs leading-relaxed text-[#4a5568]"
                >
                  Ikuti pre-test untuk melihat rekomendasi materi berdasarkan
                  hasil skor pre-test.
                </p>
              </div>
            </div>
          </article>

          <!-- STATUS KENAIKAN TINGKAT -->

          <article
            class="rounded-3xl border border-[#e6ebf2] bg-white p-6"
          >
            <h3 class="text-base font-bold">
              Status Kenaikan Tingkat
            </h3>

            <div
              class="relative mx-auto mt-5 flex max-w-xs items-start justify-between"
            >
              <!-- Garis -->

              <span
                class="absolute left-[15%] right-[15%] top-5 h-0.5 bg-[#dbe4f0]"
              ></span>

              <!-- Tingkatan -->

              <div
                v-for="t in d.tingkatan"
                :key="t.nama"
                class="relative z-10 flex w-24 flex-col items-center gap-2"
              >
                <span
                  class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-[#dbe4f0] bg-white"
                  :class="
                    t.terbuka
                      ? 'border-[#f0b323]'
                      : ''
                  "
                >
                  <!-- Terbuka -->

                  <svg
                    v-if="t.terbuka"
                    class="h-4 w-4 text-[#2e9e6b]"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z"
                      clip-rule="evenodd"
                    />
                  </svg>

                  <!-- Terkunci -->

                  <svg
                    v-else
                    class="h-4 w-4 text-[#c9a227]"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </span>

                <span class="text-sm font-bold">
                  {{ t.nama }}
                </span>
              </div>
            </div>
          </article>
        </div>
      </section>

      <!-- ========================================= -->
      <!-- HASIL LATIHAN TERAKHIR -->
      <!-- ========================================= -->

      <section
        class="rounded-3xl border border-[#e6ebf2] bg-white p-6"
      >
        <h3 class="text-base font-bold">
          Hasil Latihan Terakhir
        </h3>

        <!-- BELUM ADA HASIL -->

        <div
          v-if="!hasil"
          class="mt-5 py-6 text-center"
        >
          <p class="text-base font-bold">
            Belum Ada Hasil Latihan
          </p>

          <p
            class="mt-1.5 text-xs leading-relaxed text-[#4a5568]"
          >
            Kerjakan latihan atau simulasi untuk melihat hasilnya di sini.
          </p>
        </div>

        <!-- ADA HASIL -->

        <div
          v-else
          class="mt-5 flex flex-col gap-6 lg:flex-row lg:items-center"
        >
          <!-- Donut + informasi -->

          <div
            class="flex items-center gap-4 lg:w-1/2"
          >
            <div
              class="relative h-20 w-20 shrink-0"
            >
              <svg
                viewBox="0 0 80 80"
                class="h-full w-full -rotate-90"
              >
                <circle
                  cx="40"
                  cy="40"
                  :r="RADIUS"
                  fill="none"
                  stroke="#eef2f8"
                  stroke-width="7"
                />

                <circle
                  cx="40"
                  cy="40"
                  :r="RADIUS"
                  fill="none"
                  stroke="#f0b323"
                  stroke-width="7"
                  stroke-linecap="round"
                  :stroke-dasharray="dash"
                />
              </svg>

              <span
                class="absolute inset-0 flex items-center justify-center text-sm font-bold"
              >
                {{ hasil.persen }}%
              </span>
            </div>

            <div>
              <p class="text-[15px] font-bold">
                {{ hasil.judul }}
              </p>

              <p
                class="mt-1 text-[13px] text-[#6b778c]"
              >
                {{ hasil.tanggalLabel }} ·
                {{ hasil.statusLabel }}
              </p>
            </div>
          </div>

          <!-- Statistik hasil -->

          <div
            class="grid flex-1 grid-cols-2 gap-3"
          >
            <div
              class="rounded-xl bg-[#f3f6fb] py-3 text-center"
            >
              <p class="text-lg font-bold">
                {{ hasil.nilaiLabel }}
              </p>

              <p class="text-xs text-[#6b778c]">
                Nilai
              </p>
            </div>

            <div
              class="rounded-xl bg-[#f3f6fb] py-3 text-center"
            >
              <p class="text-lg font-bold">
                {{ hasil.statusLabel }}
              </p>

              <p class="text-xs text-[#6b778c]">
                Status
              </p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- ========================================= -->
    <!-- POPUP PRETEST / LOCK DASHBOARD -->
    <!-- ========================================= -->

    <PretestPopup
      v-if="!sudahPreTest"
      @mulai="mulaiPretest"
    />

  </div>
</template>
