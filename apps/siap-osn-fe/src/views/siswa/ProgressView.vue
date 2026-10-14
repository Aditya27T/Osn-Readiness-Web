<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UserMenu from '@/components/UserMenu.vue'
import { useMateriStore } from '@/stores/materi.js'
import { usePretestStore } from '@/stores/pretest.js'
import { useSimulasiStore } from '@/stores/simulasi.js'

// Ringkasan seluruh progres belajar siswa. Tidak memakai endpoint baru:
// daftar materi dari store materi (per tingkat), syarat simulasi dari
// store simulasi, tingkat dari store pre-test.

const route = useRoute()
const router = useRouter()
const pretest = usePretestStore()
const materi = useMateriStore()
const simulasi = useSimulasiStore()

const tingkatId = ref(null)
const memuat = ref(true)

/* ---------- Tingkat ---------- */
const tingkatTerbuka = computed(() => pretest.tingkatList.filter((t) => t.terbuka))
const tingkatDipilih = computed(() =>
  pretest.tingkatList.find((t) => t.id === tingkatId.value) ?? null,
)

/* ---------- Materi ---------- */
const daftar = computed(() => materi.daftar)
const selesai = computed(() => daftar.value.filter((m) => m.progress?.status === 'selesai').length)
const persen = computed(() =>
  daftar.value.length ? Math.round((selesai.value / daftar.value.length) * 100) : 0,
)
const wajib = computed(() => daftar.value.filter((m) => m.wajib))
const wajibBelum = computed(() => wajib.value.filter((m) => m.progress?.status !== 'selesai'))
const belumTerdorong = computed(() => daftar.value.filter((m) => m.progress?.status !== 'selesai'))
const wajibTanpaLatihan = computed(() =>
  wajib.value.filter((m) => m.latihanTersedia && !m.quizId),
)
const nilaiLatihan = computed(() =>
  daftar.value.filter((m) => m.nilaiTerbaik != null).length,
)

/* ---------- Syarat simulasi ---------- */
const syarat = computed(() => simulasi.syarat)

/* ---------- Tongkat progress ---------- */
function statusMateri(m) {
  const s = m.progress?.status
  if (s === 'selesai') return { label: 'Selesai', kelas: 'ok' }
  if (s === 'belajar') return { label: 'Belajar', kelas: 'mid' }
  return { label: 'Belum mulai', kelas: 'low' }
}
function nilaiMateri(m) {
  return m.nilaiTerbaik == null ? '—' : String(Math.round(m.nilaiTerbaik))
}

onMounted(async () => {
  try {
    await pretest.muatTingkat()
  } catch {
    // Dashboard tetap tampil; daftar tingkat yang kosong.
  }
  // Default: tingkat aktif siswa, atau tingkat terbuka pertama.
  const kandidat =
    pretest.tingkatList.find((t) => t.id === materi.tingkatId && t.terbuka) ??
    tingkatTerbuka.value[0] ??
    pretest.tingkatList[0] ??
    null
  if (kandidat) {
    tingkatId.value = kandidat.id
    await Promise.all([
      materi.fetchDaftar({ tingkatId: kandidat.id }),
      simulasi.muatRuang({ tingkatId: kandidat.id }).catch(() => {}),
    ])
  }
  memuat.value = false
})

async function gantiTingkat(id) {
  if (id === tingkatId.value) return
  tingkatId.value = id
  memuat.value = true
  await Promise.all([
    materi.fetchDaftar({ tingkatId: id }),
    simulasi.muatRuang({ tingkatId: id }).catch(() => {}),
  ])
  memuat.value = false
}
</script>

<template>
  <div class="min-h-full bg-[#f5f8fc] text-[#0f1b33]">
    <header class="flex items-center justify-between border-b border-[#e6ebf2] bg-white px-7 py-4">
      <h1 class="text-[17px] font-bold leading-tight">{{ route.meta.title || 'Progress Belajar' }}</h1>
      <UserMenu />
    </header>

    <main class="space-y-5 px-6 py-6">
      <!-- PILIH TINGKAT -->
      <section v-if="pretest.tingkatList.length" class="flex flex-wrap gap-2">
        <button
          v-for="t in pretest.tingkatList"
          :key="t.id"
          type="button"
          class="rounded-full border px-4 py-1.5 text-[13px] font-semibold disabled:opacity-40"
          :class="tingkatId === t.id
            ? 'border-[#0f2a5c] bg-[#0f2a5c] text-white'
            : 'border-[#e6ebf2] bg-white text-[#4a5568]'"
          :disabled="!t.terbuka"
          :title="t.terbuka ? '' : 'Tingkat ini belum terbuka'"
          @click="gantiTingkat(t.id)"
        >
          {{ t.nama }}{{ t.terbuka ? '' : ' 🔒' }}
        </button>
      </section>

      <section
        v-if="memuat"
        class="rounded-2xl border border-[#e6ebf2] bg-white px-5 py-3 text-[13px] text-[#6b778c]"
      >
        Memuat progress…
      </section>

      <section
        v-else-if="!tingkatDipilih"
        class="rounded-3xl border border-[#e6ebf2] bg-white p-6"
      >
        <p class="text-sm font-bold">Belum ada tingkat yang bisa dibuka.</p>
        <p class="mt-1 text-[13px] text-[#6b778c]">Selesaikan pre-test terlebih dahulu.</p>
        <button
          type="button"
          class="mt-4 rounded-full bg-[#0f2a5c] px-5 py-2 text-[13px] font-semibold text-white"
          @click="router.push({ name: 'siswa.pretest' })"
        >
          Ikuti Pre-Test →
        </button>
      </section>

      <template v-else>
        <!-- RINGKASAN -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <div class="rounded-3xl border border-[#e6ebf2] bg-white p-5">
            <p class="text-[12px] text-[#6b778c]">Progress Belajar</p>
            <p class="mt-1 text-2xl font-bold leading-none">{{ persen }}%</p>
            <p class="mt-1.5 text-xs text-[#6b778c]">{{ selesai }} dari {{ daftar.length }} materi</p>
          </div>
          <div class="rounded-3xl border border-[#e6ebf2] bg-white p-5">
            <p class="text-[12px] text-[#6b778c]">Materi Wajib</p>
            <p class="mt-1 text-2xl font-bold leading-none">
              {{ wajib.length - wajibBelum.length }}<span class="text-[#6b778c]">/{{ wajib.length }}</span>
            </p>
            <p class="mt-1.5 text-xs text-[#6b778c]">
              {{ wajibBelum.length ? `${wajibBelum.length} belum selesai` : 'Semua selesai' }}
            </p>
          </div>
          <div class="rounded-3xl border border-[#e6ebf2] bg-white p-5">
            <p class="text-[12px] text-[#6b778c]">Latihan Dikerjakan</p>
            <p class="mt-1 text-2xl font-bold leading-none">{{ nilaiLatihan }}</p>
            <p class="mt-1.5 text-xs text-[#6b778c]">dari {{ daftar.length }} materi</p>
          </div>
          <div class="rounded-3xl border border-[#e6ebf2] bg-white p-5">
            <p class="text-[12px] text-[#6b778c]">Syarat Simulasi</p>
            <p
              class="mt-1 text-2xl font-bold leading-none"
              :class="syarat?.terpenuhi ? 'text-[#1f8a5b]' : 'text-[#b45309]'"
            >
              {{ syarat?.terpenuhi ? 'Terpenuhi' : 'Belum' }}
            </p>
            <p class="mt-1.5 text-xs text-[#6b778c]">
              {{ syarat?.rincian?.length ?? 0 }} materi wajib diperiksa
            </p>
          </div>
        </section>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
          <!-- DAFTAR MATERI -->
          <section class="rounded-3xl border border-[#e6ebf2] bg-white p-6">
            <div class="flex items-center justify-between">
              <h3 class="text-base font-bold">Materi — {{ tingkatDipilih.nama }}</h3>
              <span class="text-xs text-[#6b778c]">{{ daftar.length }} materi</span>
            </div>

            <ul v-if="daftar.length" class="mt-4 space-y-2">
              <li
                v-for="m in daftar"
                :key="m.id"
                class="flex items-center gap-3 rounded-xl border border-[#eef1f6] px-4 py-3"
              >
                <span
                  class="h-2 w-2 shrink-0 rounded-full"
                  :class="{
                    'bg-[#1f8a5b]': statusMateri(m).kelas === 'ok',
                    'bg-[#3b82f6]': statusMateri(m).kelas === 'mid',
                    'bg-[#d1d5db]': statusMateri(m).kelas === 'low',
                  }"
                />
                <span class="min-w-0 flex-1">
                  <span class="block truncate text-sm font-medium">{{ m.judul }}</span>
                  <span class="mt-0.5 block text-xs text-[#6b778c]">
                    <span
                      v-if="m.wajib"
                      class="mr-1.5 rounded bg-[#fdf1d3] px-1.5 py-0.5 text-[10.5px] font-semibold text-[#92400e]"
                    >
                      Wajib · prioritas {{ m.prioritas ?? '—' }}
                    </span>
                    {{ statusMateri(m).label }} · nilai latihan {{ nilaiMateri(m) }}
                  </span>
                </span>
                <button
                  type="button"
                  class="shrink-0 rounded-full border border-[#0f2a5c] px-3 py-1 text-[12px] font-semibold text-[#0f2a5c]"
                  @click="router.push({ name: 'siswa.materi' })"
                >
                  Buka
                </button>
              </li>
            </ul>
            <p v-else class="mt-4 py-6 text-center text-sm font-bold">
              Belum ada materi di tingkat ini.
            </p>
          </section>

          <!-- SYARAT SIMULASI -->
          <section class="rounded-3xl border border-[#e6ebf2] bg-white p-6">
            <h3 class="text-base font-bold">Syarat Simulasi</h3>
            <p v-if="syarat?.alasan === 'belum_pretest'" class="mt-2 rounded-xl bg-[#fdf1d3] px-3 py-2 text-[13px] text-[#92400e]">
              Belum menyelesaikan pre-test di tingkat ini.
            </p>

            <ul v-if="syarat?.rincian?.length" class="mt-3 space-y-2">
              <li
                v-for="r in syarat.rincian"
                :key="r.materiId"
                class="rounded-xl border px-3 py-2 text-[13px]"
                :class="(r.selesai && (!r.latihanTersedia || (r.nilaiLatihan ?? 0) >= r.batas))
                  ? 'border-[#cdeedd] bg-[#f7fdfa]'
                  : 'border-[#f9c4cf] bg-[#fffafb]'"
              >
                <p class="font-medium">
                  {{ r.judul }}
                  <span class="text-[11px] text-[#6b778c]">· prioritas {{ r.prioritas || '—' }}</span>
                </p>
                <p class="mt-1 text-xs text-[#6b778c]">
                  <span :class="r.selesai ? 'text-[#1f8a5b]' : 'text-[#a33333]'">
                    {{ r.selesai ? '✓ Materi selesai' : '✗ Belum tandai selesai' }}
                  </span>
                  <template v-if="r.latihanTersedia">
                    ·
                    <span :class="(r.nilaiLatihan ?? 0) >= r.batas ? 'text-[#1f8a5b]' : 'text-[#a33333]'">
                      {{ (r.nilaiLatihan ?? 0) >= r.batas ? '✓' : '✗' }}
                      Nilai latihan {{ r.nilaiLatihan == null ? 'belum ada' : Math.round(r.nilaiLatihan) }}
                      ≥ {{ r.batas }}
                    </span>
                  </template>
                  <template v-else>
                    · <span class="text-[#a33333]">Latihan belum tersedia</span>
                  </template>
                </p>
              </li>
            </ul>
            <p v-else class="mt-3 text-[13px] text-[#6b778c]">Tidak ada materi wajib di tingkat ini.</p>

            <div class="mt-4 flex flex-col gap-2">
              <button
                type="button"
                class="rounded-full bg-[#0f2a5c] px-4 py-2 text-[13px] font-semibold text-white"
                @click="router.push({ name: 'siswa.materi' })"
              >
                Belajar Materi
              </button>
              <button
                type="button"
                class="rounded-full border border-[#0f2a5c] px-4 py-2 text-[13px] font-semibold text-[#0f2a5c]"
                @click="router.push({ name: 'siswa.simulasi' })"
              >
                Lihat Simulasi
              </button>
            </div>
          </section>
        </div>

        <!-- CATATAN -->
        <section
          v-if="wajibTanpaLatihan.length || belumTerdorong.length"
          class="rounded-2xl border border-[#f3d9a8] bg-[#fffaef] px-5 py-3 text-[13px] text-[#8a5a12]"
        >
          <p v-if="wajibTanpaLatihan.length">
            {{ wajibTanpaLatihan.length }} materi wajib belum punya latihan. Syarat simulasi tidak bisa
            terpenuhi sampai latihan tersedia.
          </p>
          <p v-else>
            Masih ada {{ belumTerdorong.length }} materi yang belum ditandai selesai.
          </p>
        </section>
      </template>
    </main>
  </div>
</template>
