<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { useProgressStore } from '@/stores/progress.js'

const route = useRoute()
const auth = useAuthStore()
const progress = useProgressStore()

// Ikon: path SVG (viewBox 24x24, stroke). Tidak perlu library tambahan.
const ICON = {
  dashboard: 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
  pemetaan: 'M3 3v18h18M7 16v-5M12 16V8M17 16v-3',
  materi: 'M2 4h6a4 4 0 014 4v13a3 3 0 00-3-3H2zM22 4h-6a4 4 0 00-4 4v13a3 3 0 013-3h7z',
  progress: 'M3 17l6-6 4 4 8-8M15 7h6v6',
  simulasi: 'M4 4h16a1 1 0 011 1v11a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1zM8 21h8M12 17v4M7 9l3 2-3 2M12 13h4',
  riwayat: 'M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8M3 3v5h5M12 7v5l4 2',
  siswa: 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 7a4 4 0 100 8 4 4 0 000-8M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',
  aturan: 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z',
}

// Nama route harus sama dengan yang ada di router/index.js
const menuSiswa = [
  { label: 'Dashboard', to: { name: 'siswa.dashboard' }, icon: ICON.dashboard },
  { label: 'Pemetaan Kompetensi', to: { name: 'siswa.pemetaan' }, icon: ICON.pemetaan },
  { label: 'Materi', to: { name: 'siswa.materi' }, icon: ICON.materi },
  { label: 'Progress Belajar', to: { name: 'siswa.progress' }, icon: ICON.progress },
  { label: 'Simulasi Seleksi', to: { name: 'siswa.simulasi' }, icon: ICON.simulasi },
  { label: 'Riwayat Hasil', to: { name: 'siswa.riwayat' }, icon: ICON.riwayat },
]
// Menu admin mengikuti urutan PR Fase 6 di plan: dashboard dulu, lalu
// siswa, konten, soal, ujian, aturan pemetaan.
const menuAdmin = [
  { label: 'Dashboard', to: { name: 'admin.dashboard' }, icon: ICON.dashboard },
  { label: 'Kelola Siswa', to: { name: 'admin.siswa' }, icon: ICON.siswa },
  { label: 'Kompetensi & Materi', to: { name: 'admin.konten' }, icon: ICON.materi },
  { label: 'Soal & Pembahasan', to: { name: 'admin.soal' }, icon: ICON.dashboard },
  { label: 'Latihan & Simulasi', to: { name: 'admin.ujian' }, icon: ICON.simulasi },
  { label: 'Tingkat & Aturan', to: { name: 'admin.aturan' }, icon: ICON.aturan },
]

const isAdmin = computed(() => auth.user?.role === 'super_admin')
const menu = computed(() => (isAdmin.value ? menuAdmin : menuSiswa))

// Sidebar ada di semua halaman siswa, jadi data progress dimuat dari sini.
onMounted(() => {
  if (!isAdmin.value) progress.fetchDashboard()
})
</script>

<template>
  <div class="flex min-h-screen bg-[#f5f8fc]">
    <!-- Sidebar -->
    <aside v-if="route.name !== 'siswa.pretest'" class="sticky top-0 flex h-screen w-[250px] shrink-0 flex-col bg-[#0a1f47] px-4 py-6">
      <div class="mb-6 flex items-center gap-3 px-2">
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#0a1f47]">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path :d="ICON.materi" />
          </svg>
        </span>
        <span class="text-[15px] font-bold tracking-tight text-white">SIAP OSN</span>
      </div>

      <nav class="flex flex-col gap-1.5" aria-label="Menu utama">
        <RouterLink
          v-for="item in menu"
          :key="item.label"
          :to="item.to"
          exact-active-class="is-active"
          class="nav-link flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm text-white/60 transition-colors hover:bg-white/5 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#f0b323]"
        >
          <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path :d="item.icon" />
          </svg>
          {{ item.label }}
        </RouterLink>
      </nav>

      <!-- Kartu streak disembunyikan: backend belum punya endpoint streak,
           jadi angkanya selalu 0 dan selalu tampil "Belum ada streak".
           Lihat keputusan #4 di PLAN-INTEGRASI-LANJUTAN.md §7.2. -->
    </aside>

    <!-- Konten halaman -->
    <main class="min-w-0 flex-1">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.nav-link.is-active {
  background: rgb(240 179 35 / 0.14);
  color: #f0b323;
  font-weight: 600;
}
</style>
