<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UserMenu from '@/components/UserMenu.vue'
import { useSimulasiStore } from '@/stores/simulasi.js'
import { kodeError, pesanError } from '@/lib/errors.js'
import { renderKonten, renderKontenSatuan } from '@/lib/richtext.js'

// Pembahasan per soal. Ini satu-satunya layar yang mengirim kunci jawaban dan
// pembahasan, jadi tersedia setelah percobaan selesai dinilai.

const route = useRoute()
const router = useRouter()
const simulasi = useSimulasiStore()

const memuat = ref(true)
const galat = ref('')
const belumDinilai = ref(false)

const soal = computed(() => simulasi.review?.soal ?? [])

// Pertanyaan dipasang di <h3>, jadi pakai render sebaris. Pembahasan boleh
// blok penuh karena isinya boleh berparagraf dan ber-heading.
function soalHtml(teks, blok = false) {
  return blok ? renderKonten(teks) : renderKontenSatuan(teks)
}

function keHasil() {
  router.push({ name: 'siswa.simulasi.hasil', params: { hasilId: simulasi.hasilId } })
}

onMounted(async () => {
  const hasilId = Number(route.params.hasilId)
  if (!Number.isFinite(hasilId) || hasilId <= 0) {
    galat.value = 'ID hasil tidak valid. Buka pembahasan dari halaman hasil.'
    memuat.value = false
    return
  }
  try {
    await simulasi.muatReview({ id: hasilId })
  } catch (err) {
    // 409 SIMULASI_BELUM_DINILAI = normal saat submit gagal menilai, bukan galat.
    if (kodeError(err) === 'SIMULASI_BELUM_DINILAI') belumDinilai.value = true
    else galat.value = pesanError(err, 'Tidak dapat membuka pembahasan. Coba lagi.')
  }
  memuat.value = false
})
</script>

<template>
  <div class="min-h-full bg-[#f5f8fc] text-[#0f1b33]">
    <header class="flex items-center justify-between border-b border-[#e6ebf2] bg-white px-7 py-4">
      <div>
        <h1 class="text-[17px] font-bold leading-tight">Pembahasan Simulasi</h1>
        <p class="mt-0.5 text-[13px] text-[#6b778c]">
          Nilai {{ simulasi.review?.nilai ?? '—' }} · kunci jawaban &amp; pembahasan per soal
        </p>
      </div>
      <UserMenu />
    </header>

    <main class="space-y-5 px-6 py-6">
      <section
        v-if="memuat"
        class="rounded-2xl border border-[#e6ebf2] bg-white px-5 py-3 text-[13px] text-[#6b778c]"
      >
        Memuat pembahasan…
      </section>

      <section
        v-else-if="galat"
        class="space-y-3 rounded-2xl border border-[#f3c2c2] bg-[#fdf0f0] px-5 py-3 text-[13px] text-[#a33333]"
      >
        <p>{{ galat }}</p>
        <button
          type="button"
          class="rounded-full border border-[#a33333] px-4 py-1.5 font-semibold"
          @click="keHasil"
        >
          Kembali ke Hasil
        </button>
      </section>

      <section
        v-else-if="belumDinilai"
        class="space-y-3 rounded-2xl border border-[#f3d9a8] bg-[#fffaef] px-5 py-3 text-[13px] text-[#8a5a12]"
      >
        <p>Percobaan ini belum selesai dinilai, jadi pembahasan belum tersedia.</p>
        <button
          type="button"
          class="rounded-full border border-[#8a5a12] px-4 py-1.5 font-semibold"
          @click="keHasil"
        >
          Kembali ke Hasil
        </button>
      </section>

      <template v-else>
        <article
          v-for="s in soal"
          :key="s.id"
          class="rounded-3xl border border-[#e6ebf2] bg-white p-6"
          :class="s.benar ? '' : 'border-[#f3c2c2]'"
        >
          <div class="flex items-center justify-between">
            <span class="rounded-md bg-[#e8f0fe] px-2.5 py-1 text-xs font-semibold text-[#1d4ed8]">
              Soal {{ s.urutan }}
            </span>
            <span
              class="rounded-md px-2.5 py-1 text-xs font-semibold"
              :class="s.benar ? 'bg-[#f3fcf6] text-[#1f8a5b]' : 'bg-[#fdf0f0] text-[#a33333]'"
            >
              {{ s.benar ? 'Benar' : 'Salah' }}
            </span>
          </div>

          <div v-if="s.konteks" class="mt-4 rounded-xl bg-[#f6f9ff] p-4 text-sm">
            <strong class="block">{{ s.konteks.judul }}</strong>
            <p class="mt-1 text-[#374151]">{{ s.konteks.isi }}</p>
          </div>
          <img
            v-if="s.gambar"
            :src="s.gambar"
            alt="Gambar soal"
            class="mt-4 max-w-full rounded-xl"
          />
          <h3 class="mt-4 text-[15px] font-semibold" v-html="soalHtml(s.pertanyaan)"></h3>

          <div v-if="s.tipe === 'ganda'" class="mt-3 flex flex-col gap-2">
            <div
              v-for="o in s.opsi"
              :key="o.kode"
              class="flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm"
              :class="o.kode === s.kunci
                ? 'border-[#8fe0ae] bg-[#f3fcf6]'
                : o.kode === s.jawaban
                  ? 'border-[#ef6b6b] bg-[#fdf0f0]'
                  : 'border-[#e3e8f1]'"
            >
              <b class="text-[#1e3a8a]">{{ o.kode }}.</b>
              <span class="flex-1">{{ o.teks }}</span>
              <span v-if="o.kode === s.kunci" class="text-xs font-bold text-[#15803d]">Kunci</span>
            </div>
          </div>
                    <!-- Ringkasan jawaban vs kunci. Untuk pilihan ganda keduanya sudah
               ditandai di daftar opsi, jadi baris ini khusus soal isian. -->
          <div v-if="s.tipe !== 'ganda'" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <p class="rounded-xl bg-[#f6f9ff] px-4 py-2.5 text-[13px]">
              <span class="text-[#6b778c]">Jawabanmu:</span>
              <b>{{ s.jawaban ?? '—' }}</b>
            </p>
            <p class="rounded-xl bg-[#f3fcf6] px-4 py-2.5 text-[13px]">
              <span class="text-[#6b778c]">Kunci:</span>
              <b>{{ s.kunci ?? '—' }}</b>
            </p>
          </div>
          <p v-else class="mt-4 rounded-xl bg-[#f3fcf6] px-4 py-2.5 text-[13px]">
            <span class="text-[#6b778c]">Jawabanmu:</span> <b>{{ s.jawaban ?? '—' }}</b>
            <span class="ml-3 text-[#6b778c]">Kunci:</span> <b>{{ s.kunci ?? '—' }}</b>
          </p>

          <div
            v-if="s.pembahasan"
            class="mt-3 rounded-xl border border-[#eaeef5] bg-[#fbfcff] px-4 py-3 text-[13px] leading-relaxed"
          >
            <strong class="block text-[#2a3a52]">Pembahasan</strong>
            <div class="konten-richtext mt-1 text-[#374151]" v-html="soalHtml(s.pembahasan, true)"></div>
          </div>
        </article>

        <div class="flex gap-2">
          <button
            type="button"
            class="rounded-full bg-[#0f2a5c] px-5 py-2 text-[13px] font-semibold text-white"
            @click="keHasil"
          >
            ← Kembali ke Hasil
          </button>
        </div>
      </template>
    </main>
  </div>
</template>
