<script setup>
// Komponen bersama untuk halaman admin: judul + aksi utama + blok galat.
// Aturan FE: view hanya merender dan memberi toast lewat pesanError();
// pemanggilan API lewat store admin, bukan langsung ke service.
import Button from 'primevue/button'

defineProps({
  judul: { type: String, required: true },
  deskripsi: { type: String, default: '' },
  aksiTeks: { type: String, default: '' },
  aksiDisabled: { type: Boolean, default: false },
  memuat: { type: Boolean, default: false },
  galat: { type: String, default: '' },
})
const emit = defineEmits(['aksi', 'coba-lagi'])
</script>

<template>
  <div class="mb-4 flex items-start justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-[#0f1b33]">{{ judul }}</h1>
      <p v-if="deskripsi" class="mt-1 text-sm text-[#6b778c]">{{ deskripsi }}</p>
    </div>
    <div class="flex shrink-0 gap-2">
      <slot name="aksi-extra" />
      <Button
        v-if="aksiTeks"
        :label="aksiTeks"
        :disabled="aksiDisabled || memuat"
        @click="emit('aksi')"
      />
    </div>
  </div>

  <p
    v-if="galat"
    class="mb-4 rounded-2xl border border-[#f3c2c2] bg-[#fdf0f0] px-4 py-3 text-[13px] text-[#a33333]"
  >
    {{ galat }}
    <button type="button" class="ml-2 underline" @click="emit('coba-lagi')">Coba lagi</button>
  </p>

  <slot />
</template>
