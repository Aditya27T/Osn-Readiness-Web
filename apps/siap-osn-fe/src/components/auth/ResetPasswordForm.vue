<script setup>
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const password = ref('')
const konfirmasiPassword = ref('')
const passwordError = ref('')
const konfirmasiError = ref('')
const loading = ref(false)
const resetBerhasil = ref(false)

const email = computed(() => {
  return typeof route.query.email === 'string' ? route.query.email : ''
})

function validasi() {
  passwordError.value = ''
  konfirmasiError.value = ''

  if (!password.value) {
    passwordError.value = 'Kata sandi baru wajib diisi.'
  } else if (password.value.length < 8) {
    passwordError.value = 'Kata sandi minimal 8 karakter.'
  }

  if (!konfirmasiPassword.value) {
    konfirmasiError.value = 'Konfirmasi kata sandi wajib diisi.'
  } else if (konfirmasiPassword.value !== password.value) {
    konfirmasiError.value = 'Konfirmasi kata sandi tidak sama.'
  }

  return !passwordError.value && !konfirmasiError.value
}

async function selesai() {
  if (!validasi()) return

  loading.value = true
  resetBerhasil.value = false

  try {
    /*
     * Sementara untuk slicing UI.
     * Nanti diganti dengan auth.resetPassword(...)
     * setelah endpoint backend tersedia.
     */

    await new Promise((resolve) => setTimeout(resolve, 500))

    resetBerhasil.value = true

    toast.add({
      severity: 'success',
      summary: 'Kata sandi berhasil diubah',
      detail: 'Anda akan dialihkan ke halaman masuk.',
      life: 4000,
    })

    setTimeout(() => {
      router.push('/login')
    }, 1200)
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Gagal mengubah kata sandi',
      detail: 'Terjadi kesalahan. Silakan coba lagi.',
      life: 4000,
    })

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
          Buat Kata Sandi Baru
        </h2>

        <p class="text-[#66758c] text-[13px] min-[720px]:text-sm leading-[1.55] font-['Inter']">
          Silakan masukkan kata sandi baru Anda yang aman untuk akun SIAP OSN
          <span v-if="email">({{ email }})</span>.
        </p>
      </div>

      <!-- Catatan -->
      <div class="flex gap-2.5 mb-6 p-3 rounded-[9px] border border-[#d7def5] bg-[#f1f3ff]">
        <span
          class="flex w-[18px] h-[18px] flex-none items-center justify-center mt-0.5 rounded-full border-[1.5px] border-[#0759a5] text-[#0759a5] text-[11px] font-bold"
        >
          i
        </span>

        <p class="m-0 text-[#46516a] text-[12px] leading-[1.5] font-['Inter']">
          <strong>Catatan:</strong> Pastikan sandi yang Anda masukkan kuat dan mudah diingat.
        </p>
      </div>

      <form @submit.prevent="selesai" novalidate>
        <!-- Password baru -->
        <div class="mb-3">
          <label
            for="password"
            class="block mb-1.5 text-[#172033] text-[13px] font-semibold font-['Inter']"
          >
            Kata Sandi Baru
            <span class="text-[#d52f2f]">*</span>
          </label>

          <div class="relative">
            <input
              id="password"
              v-model="password"
              type="password"
              autocomplete="new-password"
              placeholder="Masukkan kata sandi baru"
              class="w-full min-h-[42px] px-3.5 pr-11 border rounded-[8px] outline-none text-[#344054] text-sm font-['Inter'] transition-colors"
              :class="
                passwordError ? 'border-[#d92d20]' : 'border-[#d6e0eb] focus:border-[#0759a5]'
              "
              @input="passwordError = ''"
            />

            <span
              class="absolute right-3 top-1/2 -translate-y-1/2 text-[#a8b5c7]"
              aria-hidden="true"
            >
              ◉
            </span>
          </div>

          <p class="mt-1.5 text-[#8993a4] text-[11px] font-['Inter']">
            Minimal 8 karakter, kombinasi huruf dan angka
          </p>

          <p v-if="passwordError" class="mt-1.5 text-xs text-[#d92d20]">
            {{ passwordError }}
          </p>
        </div>

        <!-- Konfirmasi password -->
        <div class="mb-6">
          <label
            for="konfirmasi-password"
            class="block mb-1.5 text-[#172033] text-[13px] font-semibold font-['Inter']"
          >
            Konfirmasi Kata Sandi Baru
            <span class="text-[#d52f2f]">*</span>
          </label>

          <div class="relative">
            <input
              id="konfirmasi-password"
              v-model="konfirmasiPassword"
              type="password"
              autocomplete="new-password"
              placeholder="Masukkan kembali kata sandi"
              class="w-full min-h-[42px] px-3.5 pr-11 border rounded-[8px] outline-none text-[#344054] text-sm font-['Inter'] transition-colors"
              :class="
                konfirmasiError ? 'border-[#d92d20]' : 'border-[#d6e0eb] focus:border-[#0759a5]'
              "
              @input="konfirmasiError = ''"
            />

            <span
              class="absolute right-3 top-1/2 -translate-y-1/2 text-[#a8b5c7]"
              aria-hidden="true"
            >
              ◉
            </span>
          </div>

          <p v-if="konfirmasiError" class="mt-1.5 text-xs text-[#d92d20]">
            {{ konfirmasiError }}
          </p>
        </div>

        <button
          type="submit"
          :disabled="loading"
          :class="
            resetBerhasil ? 'bg-[#238636]' : 'bg-[#0759a5] hover:bg-[#064d8f] disabled:opacity-60'
          "
          class="w-full min-h-[48px] flex items-center justify-center gap-2 rounded-[8px] disabled:cursor-not-allowed text-white text-sm font-bold font-['Inter'] transition-colors"
        >
          <template v-if="loading && !resetBerhasil">
            <span
              class="w-[15px] h-[15px] border-2 border-white/40 border-t-white rounded-full animate-spin"
              aria-hidden="true"
            ></span>
            <span>Menyimpan</span>
          </template>

          <template v-else-if="resetBerhasil">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="w-[17px] h-[17px]"
              aria-hidden="true"
            >
              <path d="M20 6L9 17l-5-5" />
            </svg>
            <span>Berhasil Mengalihkan...</span>
          </template>

          <template v-else>
            <span>Selesai</span>
          </template>
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
