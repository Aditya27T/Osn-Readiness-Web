<script setup>
// Modal konfirmasi untuk aksi yang merusak (hapus, nonaktifkan).
// Aksi dikirim sebagai fungsi lewat prop `aksi`, bukan lewat emit, supaya
// `await`-nya benar: emit() hanya memicu listener, tidak mengembalikan
// promise. Galat dari server ditampilkan apa adanya supaya kode 409
// (mis. MATERI_MASIH_DIGUNAKAN) terbaca.
import { ref, watch } from 'vue'
import Button from 'primevue/button'
import { kodeError, pesanError } from '@/lib/errors.js'

const props = defineProps({
  terbuka: { type: Boolean, default: false },
  judul: { type: String, required: true },
  pesan: { type: String, default: '' },
  labelKonfirmasi: { type: String, default: 'Hapus' },
  sibuk: { type: Boolean, default: false },
  aksi: { type: Function, required: true },
})
const emit = defineEmits(['tutup'])

const galat = ref('')
const kirim = async () => {
  galat.value = ''
  try {
    await props.aksi()
    emit('tutup')
  } catch (err) {
    galat.value = kodeError(err) ?? pesanError(err)
  }
}
watch(() => props.terbuka, (b) => {
  if (b) galat.value = ''
})
</script>

<template>
  <div
    v-if="terbuka"
    class="fixed inset-0 z-50 grid place-items-center bg-[#1e2d5a66] p-5"
    @click.self="!sibuk && emit('tutup')"
  >
    <div class="w-full max-w-md rounded-2xl bg-white p-6" role="dialog" aria-modal="true">
      <h2 class="text-base font-bold text-[#0f1b33]">{{ judul }}</h2>
      <p v-if="pesan" class="mt-2 text-sm text-[#4a5568]">{{ pesan }}</p>
      <p
        v-if="galat"
        class="mt-3 rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]"
      >
        {{ galat }}
      </p>
      <div class="mt-5 flex justify-end gap-2">
        <Button label="Batal" severity="secondary" outlined :disabled="sibuk" @click="emit('tutup')" />
        <Button :label="labelKonfirmasi" severity="danger" :loading="sibuk" @click="kirim" />
      </div>
    </div>
  </div>
</template>
