<script setup>
import { JUMLAH_SOAL_PRETEST, DURASI_PRETEST_MENIT, TIPE_SOAL_PRETEST } from '@/lib/pretest.js'

const emit = defineEmits(['mulai'])

function mulaiPretest() {
  emit('mulai')
}
</script>

<template>
  <!-- Overlay -->
  <div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-[#071a35]/50 px-4 backdrop-blur-[3px]"
  >
    <!-- Popup -->
    <div
      class="relative w-[436px] rounded-[21px] bg-white px-[29px] pb-[33px] pt-[22px] shadow-[0_20px_50px_rgba(0,0,0,0.18)]"
    >
      <!-- Label -->
      <div class="flex items-center">
        <span
          class="inline-flex h-[26px] items-center rounded-full border border-[#f5d77a] bg-[#fffaf0] px-[13px] text-[11px] font-semibold text-[#e79a00]"
        >
          Langkah pertama
        </span>
      </div>

      <!-- Judul & deskripsi -->
      <div class="mt-[25px] text-center">
        <h2
          class="text-[22px] font-bold leading-[1.2] text-[#18243b]"
        >
          Kerjakan pre-test dulu
        </h2>

        <p
          class="mx-auto mt-[16px] max-w-[350px] text-[12.5px] leading-[1.65] text-[#71809a]"
        >
          Pre-test memetakan kompetensimu di setiap sub-bab informatika,
          supaya materi yang kami rekomendasikan pas untukmu.
        </p>
      </div>

      <!-- Informasi pretest -->
      <div class="mt-[14px] grid grid-cols-3 gap-[10px]">
        <!-- Soal -->
        <div
          class="flex h-[63px] flex-col items-center justify-center rounded-[11px] bg-[#f1f5fa]"
        >
          <span
            class="text-[18px] font-bold leading-none text-[#18243b]"
          >
            {{ JUMLAH_SOAL_PRETEST }}
          </span>

          <span
            class="mt-[5px] text-[11px] leading-none text-[#71809a]"
          >
            soal
          </span>
        </div>

        <!-- Waktu -->
        <div
          class="flex h-[63px] flex-col items-center justify-center rounded-[11px] bg-[#f1f5fa]"
        >
          <span
            class="text-[18px] font-bold leading-none text-[#18243b]"
          >
            {{ DURASI_PRETEST_MENIT }}
          </span>

          <span
            class="mt-[5px] text-[11px] leading-none text-[#71809a]"
          >
            menit
          </span>
        </div>

        <!-- Tipe soal -->
        <div
          class="flex h-[63px] flex-col items-center justify-center rounded-[11px] bg-[#f1f5fa]"
        >
          <span
            class="text-[18px] font-bold leading-none text-[#18243b]"
          >
            {{ TIPE_SOAL_PRETEST }}
          </span>

          <span
            class="mt-[5px] text-[11px] leading-none text-[#71809a]"
          >
            tipe soal
          </span>
        </div>
      </div>

      <!-- Tombol mulai -->
      <button
        type="button"
        class="mt-[12px] flex h-[41px] w-full items-center justify-center rounded-[10px] bg-[#0759a6] text-[14px] font-semibold text-white transition-colors duration-150 hover:bg-[#064d91] active:scale-[0.995]"
        @click="mulaiPretest"
      >
        <span>Mulai Pretest</span>

        <span class="ml-[5px] text-[18px] leading-none">
          →
        </span>
      </button>
    </div>
  </div>
</template>
