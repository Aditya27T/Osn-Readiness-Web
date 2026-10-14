<script setup>
// Paginasi untuk daftar admin. Semua daftar berpaginasi membaca
// { halaman, perHalaman, halamanTerakhir, total } dari store.
import Button from 'primevue/button'

defineProps({
  meta: { type: Object, required: true },
  memuat: { type: Boolean, default: false },
})
const emit = defineEmits(['ganti'])
</script>

<template>
  <div
    v-if="meta.halamanTerakhir > 1"
    class="mt-4 flex items-center justify-between gap-3 border-t border-[#eef1f6] pt-3"
  >
    <span class="text-xs text-[#6b778c]">
      Halaman {{ meta.halaman }} dari {{ meta.halamanTerakhir }} · {{ meta.total }} data
    </span>
    <div class="flex gap-2">
      <Button
        size="small"
        severity="secondary"
        outlined
        label="Sebelumnya"
        :disabled="meta.halaman <= 1 || memuat"
        @click="emit('ganti', meta.halaman - 1)"
      />
      <Button
        size="small"
        severity="secondary"
        outlined
        label="Berikutnya"
        :disabled="meta.halaman >= meta.halamanTerakhir || memuat"
        @click="emit('ganti', meta.halaman + 1)"
      />
    </div>
  </div>
</template>
