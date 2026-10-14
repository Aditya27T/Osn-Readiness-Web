<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const toast = useToast()

const email = ref('')
const emailError = ref('')
const loading = ref(false)
const sudahDikirim = ref(false)

function validasi() {
  emailError.value = ''

  if (!email.value.trim()) {
    emailError.value = 'Alamat email wajib diisi.'
    return false
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  if (!emailPattern.test(email.value.trim())) {
    emailError.value = 'Masukkan alamat email yang valid.'
    return false
  }

  return true
}

async function kirimTautan() {
  if (!validasi()) return

  loading.value = true

  try {
    // Sementara untuk slicing UI.
    // Nanti diganti dengan auth.forgotPassword(...)

    await new Promise((resolve) => setTimeout(resolve, 500))

    sudahDikirim.value = true

    toast.add({
      severity: 'success',
      summary: 'Tautan berhasil dikirim',
      detail: 'Tautan pengaturan ulang kata sandi telah dikirim ke email Anda.',
      life: 4000,
    })

    router.push({
      name: 'reset-password',
      query: {
        email: email.value.trim(),
        token: 'preview-token',
      },
    })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section
    class="flex items-center justify-center px-[18px] py-8 min-[720px]:justify-start min-[720px]:px-6 min-[720px]:py-10 min-[950px]:px-[clamp(28px,5.4vw,78px)] min-[950px]:py-[42px] bg-[#eef4fb]"
  >
    <div
      class="w-full max-w-[440px] mx-auto p-6 px-5 min-[720px]:px-6 min-[950px]:p-[30px] border border-[#dce4ed] rounded-[16px] bg-white shadow-[0_12px_35px_rgb(21_48_80/7%)] anim-b"
    >
      <RouterLink
        to="/login"
        class="inline-flex items-center gap-1.5 mb-5 text-[13px] font-semibold text-[#0759a5] hover:underline"
      >
        <span aria-hidden="true">←</span>
        <span>Kembali ke Halaman Masuk</span>
      </RouterLink>

      <div class="mb-3">
        <h2
          class="mb-2 text-[#172033] text-[21px] min-[720px]:text-[22px] font-bold leading-[1.25] font-['Inter']"
        >
          Lupa Kata sandi?
        </h2>

        <p
          class="text-[#66758c] text-[13px] min-[720px]:text-sm leading-[1.55] font-['Inter']"
        >
          Masukkan alamat email yang terdaftar pada akun SIAP OSN Anda. Kami akan mengirimkan
          tautan verifikasi untuk mengatur ulang kata sandi.
        </p>
      </div>

      <!-- Catatan -->
      <div
        class="flex gap-2.5 mb-6 p-3 rounded-[9px] border border-[#d7def5] bg-[#f1f3ff]"
      >
        <span
          class="flex w-[18px] h-[18px] flex-none items-center justify-center mt-0.5 rounded-full border-[1.5px] border-[#0759a5] text-[#0759a5] text-[11px] font-bold"
        >
          i
        </span>

        <p class="m-0 text-[#46516a] text-[12px] leading-[1.5] font-['Inter']">
          <strong>Catatan:</strong> Pastikan email yang Anda masukkan aktif dan sesuai dengan
          akun yang terdaftar.
        </p>
      </div>

      <form @submit.prevent="kirimTautan" novalidate>
        <div class="mb-4">
          <label
            for="email"
            class="block mb-1.5 text-[#172033] text-[13px] font-semibold font-['Inter']"
          >
            Alamat Email Terdaftar
            <span class="text-[#d52f2f]">*</span>
          </label>

          <input
            id="email"
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="Masukkan email Anda"
            class="w-full min-h-[42px] px-3.5 border rounded-[8px] outline-none text-[#344054] text-sm font-['Inter'] transition-colors"
            :class="
              emailError
                ? 'border-[#d92d20] focus:border-[#d92d20]'
                : 'border-[#d6e0eb] focus:border-[#0759a5]'
            "
            @input="emailError = ''"
          />

          <p v-if="emailError" class="mt-1.5 text-xs text-[#d92d20]">
            {{ emailError }}
          </p>
        </div>

        <p class="mb-4 text-[#8993a4] text-[11px] font-['Inter']">
          Tidak menerima email? Periksa folder spam
        </p>

        <button
        type="submit"
        :disabled="loading"
        class="w-full min-h-[48px] flex items-center justify-center gap-2 rounded-[8px] bg-[#0759a5] hover:bg-[#064d8f] disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-bold font-['Inter'] transition-colors"
        >
        <span>
            {{
            loading
                ? 'Mengirim...'
                : sudahDikirim
                ? 'Kirim Ulang Tautan'
                : 'Kirim Tautan Atur Ulang Sandi'
            }}
        </span>

        <span v-if="!loading" aria-hidden="true">▷</span>
        </button>
      </form>

      <!-- Bantuan -->
      <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px bg-[#dce3eb]"></div>
        <span class="text-[#667085] text-[12px] font-['Inter']">bantuan akun</span>
        <div class="flex-1 h-px bg-[#dce3eb]"></div>
      </div>

      <p class="m-0 text-center text-[#4b5565] text-[11px] font-['Inter']">
        Butuh bantuan lain?
        <a
          href="#"
          class="font-semibold text-[#0759a5] hover:underline"
          @click.prevent="
            toast.add({
              severity: 'info',
              summary: 'Bantuan akun',
              detail: 'Fitur kontak bantuan akan tersedia setelah integrasi backend.',
              life: 3000,
            })
          "
        >
          Hubungi Tim Bantuan SIAP OSN
        </a>
        <span class="text-[#0759a5]">↗</span>
      </p>
    </div>
  </section>
</template>

<style scoped>
.anim-b {
  animation: fade-up 0.65s ease-out 0.12s both;
}

@keyframes fade-up {
  from {
    opacity: 0;
    transform: translateY(18px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .anim-b {
    animation: none;
  }
}
</style>