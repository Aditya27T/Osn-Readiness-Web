<script setup>
import { ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth.js'
import { dashboardFor } from '@/router/index.js'
import { pesanError } from '@/lib/errors.js'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const toast = useToast()

const email = ref('')
const password = ref('')
const remember = ref(false)
const tampilkanPassword = ref(false)

const emailError = ref('')
const passwordError = ref('')
const loading = ref(false)
const berhasil = ref(false)

function validasi() {
  emailError.value = ''
  passwordError.value = ''

  email.value = email.value.trim()

  if (!email.value) {
    emailError.value = 'Email wajib diisi'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    emailError.value = 'Format email tidak valid'
  }

  if (!password.value) {
    passwordError.value = 'Password wajib diisi'
  }

  return !emailError.value && !passwordError.value
}

async function masuk() {
  if (!validasi()) return

  loading.value = true
  berhasil.value = false

  try {
    const user = await auth.login({
      email: email.value,
      password: password.value,
      remember: remember.value,
    })

    // Login berhasil
    loading.value = false
    berhasil.value = true
    toast.add({
      severity: 'success',
      summary: 'Login berhasil',
      detail: 'Selamat datang kembali! Anda berhasil masuk.',
      life: 3000,
    })

    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null

    // Beri waktu agar status berhasil terlihat
    setTimeout(() => {
      router.push(redirect ?? dashboardFor(user.role))
    }, 1000)
  } catch (err) {
    loading.value = false
    berhasil.value = false
    toast.add({
      severity: 'error',
      summary: 'Login gagal',
      detail: pesanError(err),
      life: 4000,
    })
  }
}

function masukGoogle() {
  toast.add({
    severity: 'info',
    summary: 'Segera hadir',
    detail: 'Login dengan Google belum tersedia.',
    life: 3000,
  })
}
</script>

<template>
  <section
    id="login-form"
    class="flex items-center justify-center px-[18px] pt-4 pb-[42px] min-[720px]:justify-start min-[720px]:px-6 min-[720px]:py-8 min-[950px]:px-[clamp(28px,5.4vw,78px)] min-[950px]:py-[42px] bg-[#f0f5fb]"
  >
    <div
      class="w-full max-w-[440px] mx-auto p-6 px-5 min-[720px]:px-6 min-[950px]:p-[30px] border border-[#dce4ed] rounded-[9px] bg-white shadow-[0_12px_35px_rgb(21_48_80/7%)] anim-b"
    >
      <!-- Judul -->
      <div class="mb-[10px]">
        <h2 class="text-[#172033] font-['Space_Grotesk'] text-[22px] font-extrabold">
          Masuk ke Akun
        </h2>

        <p class="mt-1 text-[#68758a] font-['Inter'] text-xs leading-[1.5]">
          Lanjutkan latihan soal, simulasi, dan pelajari materi kompetisi Informatika.
        </p>
      </div>

      <form class="grid gap-[13px]" @submit.prevent="masuk">
        <!-- Email -->
        <div class="grid gap-1.5">
          <label for="email" class="text-[#182238] font-['Inter'] text-xs font-[750]">
            Email Aktif
            <span class="text-[#c54c45]">*</span>
          </label>

          <input
            id="email"
            v-model="email"
            autocomplete="email"
            type="email"
            placeholder="nama@sekolah.sch.id"
            :aria-invalid="Boolean(emailError)"
            :aria-describedby="emailError ? 'email-error' : undefined"
            class="w-full min-h-[42px] px-3 border border-[#d6e0eb] rounded-md outline-none bg-white text-[#17233b] font-inherit text-[13px] placeholder:text-[#95a5aa] focus:border-[#0759a5] focus:shadow-[0_0_0_3px_rgb(11_104_189/11%)] aria-invalid:border-[#c54c45]"
          />

          <small v-if="emailError" id="email-error" class="text-[11px] text-[#b33a35]">
            {{ emailError }}
          </small>
        </div>

        <!-- Password -->
        <div class="grid gap-1.5">
          <div class="flex items-center justify-between">
            <label for="password" class="text-[#182238] font-['Inter'] text-xs font-[750]">
              Kata Sandi
              <span class="text-[#c54c45]">*</span>
            </label>

            <RouterLink
              to="/lupa-kata-sandi"
              class="text-sm font-semibold text-[#0759a5] hover:underline"
            >
              Lupa Kata Sandi?
            </RouterLink>
          </div>

          <div class="relative">
            <input
              id="password"
              v-model="password"
              autocomplete="current-password"
              :type="tampilkanPassword ? 'text' : 'password'"
              placeholder="Masukkan kata sandi"
              :aria-invalid="Boolean(passwordError)"
              :aria-describedby="passwordError ? 'password-error' : undefined"
              class="w-full min-h-[42px] px-3 pr-11 border border-[#d6e0eb] rounded-md outline-none bg-white text-[#17233b] font-inherit text-[13px] placeholder:text-[#95a5aa] focus:border-[#0759a5] focus:shadow-[0_0_0_3px_rgb(11_104_189/11%)] aria-invalid:border-[#c54c45]"
            />

            <button
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center justify-center p-0 border-0 bg-transparent text-[#8993a4] hover:text-[#0759a5] cursor-pointer"
              :aria-label="tampilkanPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
              @click="tampilkanPassword = !tampilkanPassword"
            >
              <!-- Mata terbuka -->
              <svg
                v-if="tampilkanPassword"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="w-[18px] h-[18px]"
              >
                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                <circle cx="12" cy="12" r="2.5" />
              </svg>

              <!-- Mata tertutup -->
              <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="w-[18px] h-[18px]"
              >
                <path d="M3 3l18 18" />
                <path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6 0 9.5 7 9.5 7a17 17 0 0 1-3.1 4.1" />
                <path d="M6.2 6.2C3.8 8 2.5 12 2.5 12s3.5 7 9.5 7a9.6 9.6 0 0 0 4.1-.9" />
              </svg>
            </button>
          </div>

          <small v-if="passwordError" id="password-error" class="text-[11px] text-[#b33a35]">
            {{ passwordError }}
          </small>
        </div>

        <!-- Ingat saya -->
        <div class="agreement flex items-start gap-[9px] mt-[3px]">
          <input id="remember" v-model="remember" type="checkbox" />

          <label
            for="remember"
            class="text-[#68758a] font-['Inter'] text-[11px] leading-[1.5] cursor-pointer"
          >
            Ingat saya di perangkat ini
          </label>
        </div>

        <!-- Tombol Login -->
        <button
          type="submit"
          :disabled="loading || berhasil"
          class="flex min-h-11 items-center justify-center gap-[9px] mt-0.5 rounded-md text-white text-[13px] font-[750] cursor-pointer disabled:cursor-wait"
          :class="berhasil ? 'bg-[#16a34a]' : 'bg-[#0759a5] hover:bg-[#064d91] disabled:opacity-70'"
        >
          <!-- Berhasil -->
          <template v-if="berhasil">
            <span
              class="flex items-center justify-center w-[17px] h-[17px] rounded-full bg-white/20"
              aria-hidden="true"
            >
              ✓
            </span>
            <span>Berhasil, Masuk! Mengalihkan...</span>
          </template>
          <!-- Loading -->
          <template v-else-if="loading">
            <span
              class="w-[15px] h-[15px] border-2 border-white/40 border-t-white rounded-full animate-spin"
              aria-hidden="true"
            ></span>
            <span>Memproses</span>
          </template>
          <!-- Normal -->
          <template v-else>
            <span>Masuk ke Akun</span>
            <span aria-hidden="true">→</span>
          </template>
        </button>
      </form>

      <!-- Google -->
      <div class="flex items-center gap-2.5 my-5 text-[11px] text-[#94a3b8]">
        <span class="flex-1 h-px bg-[#e2e8f0]"></span>

        <span>atau masuk dengan</span>

        <span class="flex-1 h-px bg-[#e2e8f0]"></span>
      </div>

      <button
        type="button"
        class="flex w-full min-h-11 items-center justify-center gap-2 rounded-md bg-[#f1f4f8] border border-[#e2e8f0] text-[#182238] text-[13px] font-[750] cursor-pointer hover:bg-[#e8edf3]"
        @click="masukGoogle"
      >
        <svg width="16" height="16" viewBox="0 0 48 48">
          <path
            fill="#EA4335"
            d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"
          />

          <path
            fill="#4285F4"
            d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.6 5.9c4.4-4.1 7-10.1 7-17.6z"
          />

          <path
            fill="#FBBC05"
            d="M10.5 28.7A14.5 14.5 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7l-7.9-6.1A24 24 0 0 0 0 24c0 3.9.9 7.5 2.6 10.8l7.9-6.1z"
          />

          <path
            fill="#34A853"
            d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.6-5.9c-2.1 1.4-4.9 2.3-8.3 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"
          />
        </svg>

        <span>Masuk dengan Google</span>
      </button>

      <!-- Link Register -->
      <p class="mt-[19px] text-[#718087] font-['Inter'] text-xs text-center">
        Belum memiliki akun?
        <RouterLink
          to="/register"
          class="text-[#064d91] font-[750] no-underline hover:text-[#0759a5]"
        >
          Daftar akun siswa
        </RouterLink>
      </p>
    </div>
  </section>
</template>

<style scoped>
.agreement input {
  position: relative;
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  margin: 1px 0 0;
  appearance: none;
  border: 1px solid #aebbd0;
  border-radius: 3px;
  background: #fff;
  cursor: pointer;
}

.agreement input:checked {
  border-color: #0759a5;
  background: #0759a5;
}

.agreement input:checked::after {
  position: absolute;
  top: 2px;
  left: 5px;
  width: 4px;
  height: 7px;
  border-right: 2px solid #fff;
  border-bottom: 2px solid #fff;
  content: '';
  transform: rotate(45deg);
}

.agreement input:focus-visible {
  outline: 3px solid rgb(11 104 189 / 20%);
  outline-offset: 2px;
}

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
