<script setup>
const highlights = [
  'Pre-test & pemetaan kompetensi',
  'Materi sesuai kebutuhanmu',
  'Simulasi seleksi standar TOKI',
]
</script>

<template>
  <section
    id="keunggulan"
    class="flex items-center justify-center px-6 pt-12 pb-[38px] min-[720px]:justify-end min-[720px]:py-16 min-[720px]:pl-6 min-[720px]:pr-7 min-[950px]:pr-[clamp(32px,6vw,88px)] bg-[radial-gradient(ellipse_at_15%_90%,#e6f1fb_0,transparent_42%),#fff]"
  >
    <div class="w-full max-w-[480px] anim-a">
      <h1
        class="mb-5 text-[#10294a] text-[42px] min-[720px]:text-[52px] font-extrabold leading-[1.03] font-['Space_Grotesk']"
      >
        Bantuan Pemulihan Akun
      </h1>

      <p
        class="font-['Inter'] max-w-[460px] mb-5 min-[720px]:mb-[30px] text-[#154b86] text-sm min-[720px]:text-base leading-[1.65]"
      >
        Persiapan OSN Informatika yang terarah: dari pemetaan kompetensi sampai simulasi sesuai
        standar TOKI.
      </p>

      <ul class="grid gap-2 min-[720px]:gap-[11px] m-0 p-0 list-none">
        <li
          v-for="highlight in highlights"
          :key="highlight"
          class="font-['Inter'] flex min-h-[42px] min-[720px]:min-h-12 items-center gap-3 px-3.5 rounded-[7px] bg-[linear-gradient(to_right,rgba(230,243,255,0.65),rgba(230,243,255,0.08))] shadow-[0_6px_20px_rgba(30,75,143,0.10)] text-[#182f50] text-[13px] font-bold"
        >
          <span
            class="flex w-[22px] h-[22px] flex-none items-center justify-center rounded-[5px] bg-[#E6F3FF]"
          >
            <img src="/check.png" alt="" class="w-[14px] h-[14px] object-contain" />
          </span>

          <span>{{ highlight }}</span>
        </li>
      </ul>
    </div>
  </section>
</template>