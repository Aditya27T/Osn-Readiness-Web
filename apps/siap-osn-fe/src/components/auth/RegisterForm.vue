<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth.js'
import { pesanError, pesanField } from '@/lib/errors.js'

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()

const nama = ref('')
const email = ref('')
const password = ref('')
const konfirmasi = ref('')
const setuju = ref(false)
const namaError = ref('')
const emailError = ref('')
const passwordError = ref('')
const konfirmasiError = ref('')
const setujuError = ref('')
const loading = ref(false)
const tampilkanPassword = ref(false)
const tampilkanKonfirmasi = ref(false)
const registrasiBerhasil = ref(false)

function validasi() {
  namaError.value = ''
  emailError.value = ''
  passwordError.value = ''
  konfirmasiError.value = ''
  setujuError.value = ''
  nama.value = nama.value.trim()
  email.value = email.value.trim()
  if (nama.value.trim().length < 3) namaError.value = 'Nama minimal 3 karakter'
  if (!email.value) emailError.value = 'Email wajib diisi'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value))
    emailError.value = 'Format email tidak valid'
  if (password.value.length < 8) passwordError.value = 'Password minimal 8 karakter'
  else if (!/[a-zA-Z]/.test(password.value) || !/[0-9]/.test(password.value))
    passwordError.value = 'Password harus mengandung huruf dan angka'
  if (konfirmasi.value !== password.value) konfirmasiError.value = 'Konfirmasi password tidak sama'
  if (!setuju.value) setujuError.value = 'Persetujuan wajib dicentang'
  return (
    !namaError.value &&
    !emailError.value &&
    !passwordError.value &&
    !konfirmasiError.value &&
    !setujuError.value
  )
}

async function daftar() {
  if (!validasi()) return

  loading.value = true
  registrasiBerhasil.value = false

  try {
    await auth.register({
      nama: nama.value,
      email: email.value,
      password: password.value,
      konfirmasi: konfirmasi.value,
    })

    registrasiBerhasil.value = true

    toast.add({
      severity: 'success',
      summary: 'Registrasi berhasil',
      detail: 'Akun berhasil dibuat. Anda akan dialihkan ke halaman masuk.',
      life: 4000,
    })

    setTimeout(() => {
      router.push('/login')
    }, 1200)
  } catch (err) {
    const emailGanda = pesanField(err, 'email')

    if (emailGanda) {
      emailError.value = emailGanda
    } else {
      toast.add({
        severity: 'error',
        summary: 'Registrasi gagal',
        detail: pesanError(err),
        life: 4000,
      })
    }

    loading.value = false
  }
}
</script>

<template>
  <section
    id="register-form"
    class="flex items-center justify-center px-[18px] pt-4 pb-[42px] min-[720px]:justify-start min-[720px]:px-6 min-[720px]:py-8 min-[950px]:px-[clamp(28px,5.4vw,78px)] min-[950px]:py-[42px] bg-[#f0f5fb]"
  >
    <div
      class="w-full max-w-[440px] mx-auto p-6 px-5 min-[720px]:px-6 min-[950px]:p-[30px] border border-[#dce4ed] rounded-[9px] bg-white shadow-[0_12px_35px_rgb(21_48_80/7%)] anim-b"
    >
      <div class="mb-[10px]">
        <h2 class="text-[#172033] font-['Space_Grotesk'] text-[22px] font-extrabold">
          Daftar Akun Baru
        </h2>
      </div>

      <form class="grid gap-[13px]" @submit.prevent="daftar">
        <div class="grid gap-1.5">
          <label for="nama" class="text-[#182238] font-['Inter'] text-xs font-[750]"
            >Nama Lengkap <span class="text-[#c54c45]">*</span></label
          >
          <input
            id="nama"
            v-model="nama"
            autocomplete="name"
            type="text"
            placeholder="Masukkan nama lengkap"
            :aria-invalid="Boolean(namaError)"
            :aria-describedby="namaError ? 'nama-error' : undefined"
            class="w-full min-h-[42px] px-3 border border-[#d6e0eb] rounded-md outline-none bg-white text-[#17233b] font-inherit text-[13px] placeholder:text-[#95a5aa] focus:border-[#0759a5] focus:shadow-[0_0_0_3px_rgb(11_104_189/11%)] aria-invalid:border-[#c54c45]"
          />
          <small v-if="namaError" id="nama-error" class="text-[11px] text-[#b33a35]">{{
            namaError
          }}</small>
        </div>

        <div class="grid gap-1.5">
          <label for="email" class="text-[#182238] font-['Inter'] text-xs font-[750]"
            >Email Aktif <span class="text-[#c54c45]">*</span></label
          >
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
          <small v-if="emailError" id="email-error" class="text-[11px] text-[#b33a35]">{{
            emailError
          }}</small>
        </div>

        <div class="grid gap-1.5">
          <label for="password" class="text-[#182238] font-['Inter'] text-xs font-[750]">
            Kata Sandi <span class="text-[#c54c45]">*</span>
          </label>

          <div class="relative">
            <input
              id="password"
              v-model="password"
              autocomplete="new-password"
              :type="tampilkanPassword ? 'text' : 'password'"
              placeholder="Buat kata sandi"
              :aria-invalid="Boolean(passwordError)"
              :aria-describedby="passwordError ? 'password-error' : 'password-hint'"
              class="w-full min-h-[42px] px-3 pr-11 border border-[#d6e0eb] rounded-md outline-none bg-white text-[#17233b] font-inherit text-[13px] placeholder:text-[#95a5aa] focus:border-[#0759a5] focus:shadow-[0_0_0_3px_rgb(11_104_189/11%)] aria-invalid:border-[#c54c45]"
            />

            <!-- Tombol mata -->
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

          <small v-else id="password-hint" class="text-[11px] text-[#68758a]">
            Minimal 8 karakter, kombinasi huruf dan angka
          </small>
        </div>

        <div class="grid gap-1.5">
          <label for="konfirmasi" class="text-[#182238] font-['Inter'] text-xs font-[750]">
            Konfirmasi Kata Sandi
            <span class="text-[#c54c45]">*</span>
          </label>

          <div class="relative">
            <input
              id="konfirmasi"
              v-model="konfirmasi"
              autocomplete="new-password"
              :type="tampilkanKonfirmasi ? 'text' : 'password'"
              placeholder="Ulangi kata sandi Anda"
              :aria-invalid="Boolean(konfirmasiError)"
              :aria-describedby="konfirmasiError ? 'konfirmasi-error' : undefined"
              class="w-full min-h-[42px] px-3 pr-11 border border-[#d6e0eb] rounded-md outline-none bg-white text-[#17233b] font-inherit text-[13px] placeholder:text-[#95a5aa] focus:border-[#0759a5] focus:shadow-[0_0_0_3px_rgb(11_104_189/11%)] aria-invalid:border-[#c54c45]"
            />

            <!-- Tombol lihat/sembunyikan password -->
            <button
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center justify-center p-0 border-0 bg-transparent text-[#8993a4] hover:text-[#0759a5] cursor-pointer"
              :aria-label="tampilkanKonfirmasi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
              @click="tampilkanKonfirmasi = !tampilkanKonfirmasi"
            >
              <!-- Mata terbuka -->
              <svg
                v-if="tampilkanKonfirmasi"
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

          <small v-if="konfirmasiError" id="konfirmasi-error" class="text-[11px] text-[#b33a35]">
            {{ konfirmasiError }}
          </small>
        </div>

        <div class="agreement flex items-start gap-[9px] mt-[3px]">
          <input
            id="agreement"
            v-model="setuju"
            type="checkbox"
            :aria-invalid="Boolean(setujuError)"
            :aria-describedby="setujuError ? 'agreement-error' : undefined"
          />
          <label for="agreement" class="text-[#68758a] font-['Inter'] text-[11px] leading-[1.5]"
            >Saya menyetujui
            <a href="#agreement" class="text-[#064d91] font-bold no-underline">Ketentuan Layanan</a>
            dan
            <a href="#agreement" class="text-[#064d91] font-bold no-underline">Kebijakan Privasi</a>
            SIAP OSN</label
          >
        </div>
        <small
          v-if="setujuError"
          id="agreement-error"
          class="text-[11px] text-[#b33a35] -mt-[9px]"
          >{{ setujuError }}</small
        >

        <button
          class="flex min-h-11 items-center justify-center gap-[9px] mt-0.5 rounded-md text-white text-[13px] font-[750] cursor-pointer transition-colors disabled:cursor-wait"
          :class="
            registrasiBerhasil
              ? 'bg-[#238636]'
              : 'bg-[#0759a5] hover:bg-[#064d91] disabled:opacity-70'
          "
          type="submit"
          :disabled="loading"
        >
          <!-- Saat sedang memproses -->
          <template v-if="loading && !registrasiBerhasil">
            <span
              class="w-[15px] h-[15px] border-2 border-white/40 border-t-white rounded-full animate-spin"
              aria-hidden="true"
            ></span>
            <span>Memproses</span>
          </template>

          <!-- Saat berhasil -->
          <template v-else-if="registrasiBerhasil">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="w-[17px] h-[17px]"
              aria-hidden="true"
            >
              <path d="M20 6L9 17l-5-5" />
            </svg>

            <span>Berhasil Mengalihkan...</span>
          </template>

          <!-- Kondisi normal -->
          <template v-else>
            <span>Daftar Sekarang</span>
            <span aria-hidden="true">→</span>
          </template>
        </button>
      </form>

      <p class="mt-[19px] text-[#718087] font-['Inter'] text-xs text-center">
        Sudah punya akun?
        <RouterLink to="/login" class="text-[#064d91] font-[750] no-underline hover:text-[#0759a5]"
          >Masuk di sini</RouterLink
        >
      </p>
    </div>
  </section>
</template>

<style scoped>
/* Checkbox kustom (pseudo-element tak bisa via utility). */
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
</style>
