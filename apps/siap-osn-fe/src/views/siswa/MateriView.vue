<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import UserMenu from '@/components/UserMenu.vue'
import { useMateriStore } from '@/stores/materi.js'
import { usePretestStore } from '@/stores/pretest.js'
import { useProgressStore } from '@/stores/progress.js'
import { pesanError } from '@/lib/errors.js'
import { renderKonten } from '@/lib/richtext.js'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const materi = useMateriStore()
const pretest = usePretestStore()
const progress = useProgressStore()

const tingkatDipilih = ref(null)
const dibukaId = ref(null)
const menandai = ref(false)

const detail = computed(() => materi.detail)
// Isi materi arrive sebagai Markdown (heading, list, blok kode) dan LaTeX.
// Versi blok dipakai karena materi memang boleh punya heading dan paragraf.
const isiHtml = computed(() => renderKonten(detail.value?.isi))

onMounted(async () => {
  try {
    await pretest.muatTingkat().catch(() => {})
    await progress.fetchDashboard().catch(() => {})
    const aktifId = progress.data.tingkatAktifId
    const daftar = pretest.tingkatList
    tingkatDipilih.value =
      daftar.find((t) => t.id === aktifId)?.id ?? daftar[0]?.id ?? null
    if (tingkatDipilih.value) await materi.fetchDaftar({ tingkatId: tingkatDipilih.value })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal memuat materi', detail: pesanError(err), life: 4000 })
  }
})

async function gantiTingkat(id) {
  tingkatDipilih.value = id
  dibukaId.value = null
  try {
    await materi.fetchDaftar({ tingkatId: id, force: true })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal memuat materi', detail: pesanError(err), life: 4000 })
  }
}

async function buka(id) {
  dibukaId.value = id
  try {
    await materi.fetchDetail({ id })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal membuka materi', detail: pesanError(err), life: 4000 })
    dibukaId.value = null
  }
}

function tutupDetail() {
  dibukaId.value = null
}

async function tandaiSelesai() {
  if (!dibukaId.value || menandai.value) return
  menandai.value = true
  try {
    await materi.tandaiSelesai({ id: dibukaId.value })
    await progress.fetchDashboard({ force: true }).catch(() => {})
    toast.add({ severity: 'success', summary: 'Materi selesai', detail: 'Progress tersimpan.', life: 4000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal menyimpan', detail: pesanError(err), life: 4000 })
  } finally {
    menandai.value = false
  }
}

function mulaiLatihan(m) {
  if (!m.quizId) return
  router.push({ name: 'siswa.latihan', params: { quizId: m.quizId } })
}

function statusLabel(m) {
  if (m.progress?.status === 'selesai') return 'Selesai'
  if (m.progress?.status === 'belajar') return 'Diproses'
  return 'Belum mulai'
}
</script>

<template>
  <div class="min-h-full bg-[#f5f8fc] text-[#0f1b33]">
    <header class="flex items-center justify-between border-b border-[#e6ebf2] bg-white px-7 py-4">
      <h1 class="text-[17px] font-bold leading-tight">{{ route.meta.title || 'Materi' }}</h1>
      <UserMenu />
    </header>

    <main class="space-y-5 px-6 py-6">
      <!-- TAB TINGKAT -->
      <section class="flex flex-wrap items-center gap-2">
        <button
          v-for="t in pretest.tingkatList"
          :key="t.id"
          type="button"
          class="rounded-full border px-4 py-1.5 text-[13px] font-semibold disabled:opacity-40"
          :class="tingkatDipilih === t.id
            ? 'border-[#0f2a5c] bg-[#0f2a5c] text-white'
            : 'border-[#e6ebf2] bg-white text-[#4a5568]'"
          :disabled="!t.terbuka"
          @click="gantiTingkat(t.id)"
        >
          {{ t.nama }}
        </button>
      </section>

      <p
        v-if="materi.loading && !materi.daftar.length && !dibukaId"
        class="rounded-2xl border border-[#e6ebf2] bg-white px-5 py-3 text-[13px] text-[#6b778c]"
      >
        Memuat materi…
      </p>
      <p
        v-else-if="materi.error && !materi.daftar.length"
        class="rounded-2xl border border-[#f3c2c2] bg-[#fdf0f0] px-5 py-3 text-[13px] text-[#a33333]"
      >
        Tidak dapat memuat materi. Periksa koneksi lalu muat ulang halaman.
      </p>

      <!-- DETAIL -->
      <section v-else-if="dibukaId" class="rounded-3xl border border-[#e6ebf2] bg-white p-6">
        <button
          type="button"
          class="text-[13px] font-semibold text-[#1d4ed8]"
          @click="tutupDetail"
        >
          ← Kembali ke daftar
        </button>
        <template v-if="detail">
          <div class="mt-3 flex flex-wrap items-center gap-2">
            <h3 class="text-base font-bold">{{ detail.judul }}</h3>
            <span v-if="detail.wajib" class="rounded-full bg-[#fdf1c7] px-2.5 py-0.5 text-[11px] font-semibold">
              Wajib{{ detail.prioritas ? ` · Prioritas ${detail.prioritas}` : '' }}
            </span>
            <span class="rounded-full bg-[#eef2f8] px-2.5 py-0.5 text-[11px] font-semibold">
              {{ statusLabel(detail) }}
            </span>
          </div>
          <p class="mt-1 text-[13px] text-[#6b778c]">{{ detail.deskripsi }}</p>
          <img v-if="detail.gambarUrl" :src="detail.gambarUrl" alt="Gambar materi" class="mt-4 max-w-full rounded-xl" />
          <div v-if="detail.isi" class="konten-richtext mt-4 text-sm leading-relaxed" v-html="isiHtml"></div>
          <div v-if="detail.fileUrl" class="mt-4">
            <a :href="detail.fileUrl" target="_blank" rel="noopener" class="text-[13px] font-semibold text-[#1d4ed8]">
              Unduh file materi →
            </a>
          </div>
          <div class="mt-6 flex flex-wrap gap-2">
            <button
              v-if="detail.progress?.status !== 'selesai'"
              type="button"
              class="rounded-full bg-[#0f2a5c] px-5 py-2 text-[13px] font-semibold text-white disabled:opacity-50"
              :disabled="menandai"
              @click="tandaiSelesai"
            >
              {{ menandai ? 'Menyimpan…' : 'Tandai Selesai' }}
            </button>
            <button
              v-if="detail.latihanTersedia && detail.quizId"
              type="button"
              class="rounded-full border border-[#0f2a5c] px-5 py-2 text-[13px] font-semibold text-[#0f2a5c]"
              @click="mulaiLatihan(detail)"
            >
              Mulai Latihan →
            </button>
          </div>
        </template>
      </section>

      <!-- DAFTAR -->
      <section v-else class="rounded-3xl border border-[#e6ebf2] bg-white p-6">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-bold">Daftar Materi</h3>
          <span class="text-xs text-[#6b778c]">{{ materi.daftar.length }} materi</span>
        </div>
        <ul v-if="materi.daftar.length" class="mt-5 space-y-4">
          <li
            v-for="m in materi.daftar"
            :key="m.id"
            class="flex items-center gap-3 text-sm"
          >
            <span class="h-8 w-8 shrink-0 rounded-lg bg-[#dfe7f5]"></span>
            <button type="button" class="flex-1 text-left" @click="buka(m.id)">
              <span class="font-semibold">{{ m.judul }}</span>
              <span class="block text-xs text-[#6b778c]">
                {{ statusLabel(m) }}
                <template v-if="m.nilaiTerbaik != null"> · Nilai latihan {{ Math.round(m.nilaiTerbaik) }}</template>
              </span>
            </button>
            <span v-if="m.wajib" class="rounded-full bg-[#fdf1c7] px-2.5 py-0.5 text-[11px] font-semibold">
              Wajib
            </span>
          </li>
        </ul>
        <p v-else class="mt-5 py-6 text-center text-sm font-bold">Belum ada materi di tingkat ini.</p>
      </section>
    </main>
  </div>
</template>
