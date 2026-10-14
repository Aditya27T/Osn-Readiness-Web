<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UserMenu from '@/components/UserMenu.vue'
import { JENIS_SEMUA, useRiwayatStore } from '@/stores/riwayat.js'
import { usePretestStore } from '@/stores/pretest.js'
import { pesanError } from '@/lib/errors.js'
import { useToast } from 'primevue/usetoast'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const riwayat = useRiwayatStore()
const pretest = usePretestStore()

const FILTER_JENIS = [
  { nilai: JENIS_SEMUA, label: 'Semua' },
  { nilai: 'pretest', label: 'Pre-Test' },
  { nilai: 'latihan', label: 'Latihan' },
  { nilai: 'simulasi', label: 'Simulasi' },
]

onMounted(async () => {
  try {
    await pretest.muatTingkat().catch(() => {})
    await riwayat.fetchDaftar()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal memuat riwayat', detail: pesanError(err), life: 4000 })
  }
})

function formatTanggal(iso) {
  if (!iso) return '—'
  const t = new Date(iso)
  if (Number.isNaN(t.getTime())) return '—'
  return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(t)
}

function formatNilai(nilai) {
  return nilai == null ? '—' : String(Math.round(nilai))
}

// Baris hasil bisa dibuka lagi: pre-test ke pemetaan, simulasi ke halaman
// hasil. Latihan tidak punya halaman hasil tersendiri, jadi tetap teks biasa.
function ruteBaris(r) {
  if (!r.referensiId) return null
  if (r.jenis === 'simulasi') {
    return { name: 'siswa.simulasi.hasil', params: { hasilId: r.referensiId } }
  }
  if (r.jenis === 'pretest') {
    return { name: 'siswa.pemetaan', params: { id: r.referensiId } }
  }
  return null
}
</script>

<template>
  <div class="min-h-full bg-[#f5f8fc] text-[#0f1b33]">
    <header class="flex items-center justify-between border-b border-[#e6ebf2] bg-white px-7 py-4">
      <h1 class="text-[17px] font-bold leading-tight">{{ route.meta.title || 'Riwayat Hasil' }}</h1>
      <UserMenu />
    </header>

    <main class="space-y-5 px-6 py-6">
      <!-- FILTER -->
      <section class="flex flex-wrap items-center gap-2">
        <button
          v-for="f in FILTER_JENIS"
          :key="f.nilai"
          type="button"
          class="rounded-full border px-4 py-1.5 text-[13px] font-semibold"
          :class="riwayat.jenis === f.nilai
            ? 'border-[#0f2a5c] bg-[#0f2a5c] text-white'
            : 'border-[#e6ebf2] bg-white text-[#4a5568]'"
          @click="riwayat.aturJenis(f.nilai)"
        >
          {{ f.label }}
        </button>
        <select
          class="ml-auto rounded-full border border-[#e6ebf2] bg-white px-4 py-1.5 text-[13px] font-semibold text-[#4a5568]"
          :value="riwayat.tingkatId ?? ''"
          @change="riwayat.aturTingkat($event.target.value === '' ? null : Number($event.target.value))"
        >
          <option value="">Semua tingkat</option>
          <option v-for="t in pretest.tingkatList" :key="t.id" :value="t.id">{{ t.nama }}</option>
        </select>
      </section>

      <!-- STATUS -->
      <p
        v-if="riwayat.loading && !riwayat.items.length"
        class="rounded-2xl border border-[#e6ebf2] bg-white px-5 py-3 text-[13px] text-[#6b778c]"
      >
        Memuat riwayat…
      </p>
      <p
        v-else-if="riwayat.error && !riwayat.items.length"
        class="rounded-2xl border border-[#f3c2c2] bg-[#fdf0f0] px-5 py-3 text-[13px] text-[#a33333]"
      >
        Tidak dapat memuat riwayat. Periksa koneksi lalu muat ulang halaman.
      </p>

      <!-- DAFTAR -->
      <section v-else class="rounded-3xl border border-[#e6ebf2] bg-white p-6">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-bold">Hasil Pengerjaan</h3>
          <span class="text-xs text-[#6b778c]">{{ riwayat.total }} data</span>
        </div>

        <ul v-if="riwayat.items.length" class="mt-5 space-y-4">
          <li
            v-for="r in riwayat.items"
            :key="`${r.jenis}-${r.referensiId}`"
            class="flex items-center gap-3 text-sm"
          >
            <span class="h-8 w-8 shrink-0 rounded-lg bg-[#dfe7f5]"></span>
            <component
              :is="ruteBaris(r) ? 'button' : 'span'"
              :type="ruteBaris(r) ? 'button' : undefined"
              class="flex-1 text-left"
              :class="ruteBaris(r) ? 'hover:underline' : ''"
              @click="ruteBaris(r) && router.push(ruteBaris(r))"
            >
              {{ r.judul }}
              <span class="block text-xs text-[#6b778c]">{{ formatTanggal(r.tanggal) }}</span>
            </component>
            <span class="text-sm font-bold">{{ formatNilai(r.nilai) }}</span>
          </li>
        </ul>
        <p v-else class="mt-5 py-6 text-center text-sm font-bold">Belum ada riwayat untuk filter ini.</p>

        <!-- PAGINATION -->
        <div v-if="riwayat.halamanTerakhir > 1" class="mt-6 flex items-center justify-center gap-3">
          <button
            type="button"
            class="rounded-full border border-[#e6ebf2] bg-white px-4 py-1.5 text-[13px] font-semibold disabled:opacity-40"
            :disabled="riwayat.halaman <= 1 || riwayat.loading"
            @click="riwayat.keHalaman(riwayat.halaman - 1)"
          >
            ← Sebelumnya
          </button>
          <span class="text-xs text-[#6b778c]">Halaman {{ riwayat.halaman }} dari {{ riwayat.halamanTerakhir }}</span>
          <button
            type="button"
            class="rounded-full border border-[#e6ebf2] bg-white px-4 py-1.5 text-[13px] font-semibold disabled:opacity-40"
            :disabled="riwayat.halaman >= riwayat.halamanTerakhir || riwayat.loading"
            @click="riwayat.keHalaman(riwayat.halaman + 1)"
          >
            Berikutnya →
          </button>
        </div>
      </section>
    </main>
  </div>
</template>
