<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const auth = useAuthStore()
const router = useRouter()

const open = ref(false)
const root = ref(null)

const nama = computed(() => auth.nama || 'Pengguna')
const inisial = computed(() => nama.value.slice(0, 1).toUpperCase())
const isAdmin = computed(() => auth.user?.role === 'super_admin')

function tutupJikaDiLuar(e) {
  if (root.value && !root.value.contains(e.target)) open.value = false
}
onMounted(() => document.addEventListener('click', tutupJikaDiLuar))
onBeforeUnmount(() => document.removeEventListener('click', tutupJikaDiLuar))

function keProfil() {
  open.value = false
  router.push({ name: 'siswa.profil' })
}

async function keluar() {
  open.value = false
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      class="flex items-center gap-2.5 rounded-full border border-[#e6ebf2] bg-white py-1.5 pl-1.5 pr-3 text-sm font-medium text-[#0f1b33] hover:bg-[#f3f6fb]"
      aria-haspopup="menu"
      :aria-expanded="open"
      @click="open = !open"
    >
      <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0f2a5c] text-xs font-bold text-white">
        {{ inisial }}
      </span>
      {{ nama }}
      <svg class="h-3.5 w-3.5 text-[#6b778c] transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
      </svg>
    </button>

    <div
      v-if="open"
      role="menu"
      class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-xl border border-[#e6ebf2] bg-white py-1 shadow-lg"
    >
      <button v-if="!isAdmin" type="button" role="menuitem" class="block w-full px-4 py-2.5 text-left text-sm text-[#0f1b33] hover:bg-[#f3f6fb]" @click="keProfil">
        Profil Akun
      </button>
      <button type="button" role="menuitem" class="block w-full px-4 py-2.5 text-left text-sm text-[#d93a3a] hover:bg-[#f3f6fb]" @click="keluar">
        Keluar
      </button>
    </div>
  </div>
</template>
