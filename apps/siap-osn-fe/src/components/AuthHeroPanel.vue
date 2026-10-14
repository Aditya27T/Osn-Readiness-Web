<script setup>
// Panel kiri halaman auth (login & register): judul + deskripsi + checklist.
// Satu komponen agar kedua halaman selalu konsisten.
defineProps({
  titleLines: { type: Array, default: () => [] }, // tiap baris jadi <br />
  description: { type: String, default: '' },
  items: { type: Array, default: () => [] },
})
</script>

<template>
  <section class="relative overflow-hidden bg-white flex items-center justify-center">
    <div class="absolute rounded-full pointer-events-none blur-[60px] w-[420px] h-[420px] top-[12%] right-[8%] bg-[radial-gradient(circle,rgba(254,240,200,0.7),rgba(254,240,200,0)_70%)]"></div>
    <div class="absolute rounded-full pointer-events-none blur-[60px] w-[380px] h-[380px] -bottom-[120px] -right-[40px] bg-[radial-gradient(circle,rgba(203,213,225,0.55),rgba(203,213,225,0)_70%)]"></div>

    <div class="relative w-full max-w-[480px] mx-auto px-6 pt-10 pb-4 min-[900px]:py-12">
      <h1 class="font-['Space_Grotesk',sans-serif] text-[34px] min-[900px]:text-[44px] leading-[1.25] font-bold mb-5">
        <template v-for="(line, i) in titleLines" :key="i">
          {{ line }}<br v-if="i < titleLines.length - 1" />
        </template>
      </h1>
      <p class="text-[15px] leading-[1.65] text-[#1e3a6b] mb-7 max-w-[380px]">
        {{ description }}
      </p>

      <ul class="list-none m-0 p-0 flex flex-col gap-2.5 max-w-[335px]">
        <li v-for="item in items" :key="item" class="flex items-center gap-3 px-3.5 py-[11px] bg-[#eef2f7] rounded-[10px] text-[13px] font-semibold">
          <span class="flex-none w-5 h-5 rounded-full bg-[#0f1b3d] grid place-items-center">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 6 9 17l-5-5" />
            </svg>
          </span>
          {{ item }}
        </li>
      </ul>
    </div>
  </section>
</template>
