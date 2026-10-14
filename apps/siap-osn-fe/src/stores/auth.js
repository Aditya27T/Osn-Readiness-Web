import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { authService } from '@/services/auth.js'
import { useLatihanStore } from '@/stores/latihan.js'
import { useMateriStore } from '@/stores/materi.js'
import { usePretestStore } from '@/stores/pretest.js'
import { useProgressStore } from '@/stores/progress.js'
import { useRiwayatStore } from '@/stores/riwayat.js'
import { useSimulasiStore } from '@/stores/simulasi.js'

// Store tidak tahu HTTP/backend: token persisten + request lewat
// authService, bentuk user dari services/mappers/user.js.

// Tidak cukup hanya progress — pretest/latihan/materi/riwayat/simulasi
// menyimpan soal, jawaban, dan id percobaan milik akun sebelumnya.
function resetDataAkun() {
  useProgressStore().$reset()
  usePretestStore().$reset()
  useLatihanStore().$reset()
  useMateriStore().$reset()
  useRiwayatStore().$reset()
  useSimulasiStore().$reset()
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const initialized = ref(false)
  const token = ref(authService.tokenTersimpan())

  const isAuthenticated = computed(() => user.value !== null)
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin')
  const isSiswa = computed(() => user.value?.role === 'siswa')

  // Nama yang dipakai di sapaan & avatar. Kosong ('') kalau siswa belum mengisi nama.
  // Beberapa nama field dicoba supaya tetap jalan kalau backend memakai penamaan lain.
  const nama = computed(() => {
    const u = user.value ?? {}
    return String(u.nama ?? u.nama_lengkap ?? u.name ?? u.full_name ?? '').trim()
  })

  function saveToken(t) {
    token.value = t
    authService.simpanToken(t)
  }

  let meInflight = null // navigasi beruntun saat boot tidak memanggil /auth/me dua kali

  // Dipanggil sekali oleh router guard saat aplikasi dibuka.
  function fetchMe() {
    meInflight ??= muatUser().finally(() => {
      meInflight = null
    })
    return meInflight
  }

  async function muatUser() {
    if (!token.value) {
      user.value = null
      initialized.value = true
      return
    }
    try {
      user.value = await authService.me()
    } catch {
      user.value = null
      saveToken(null)
    } finally {
      initialized.value = true
    }
  }

  async function login(payload) {
    const res = await authService.login(payload)
    resetDataAkun() // pastikan tidak ada sisa data akun sebelumnya
    saveToken(res.token)
    user.value = res.user
    return user.value
  }

  // Tidak otomatis login — pemanggil redirect ke /login.
  function register(payload) {
    return authService.register(payload)
  }

  // Simpan perubahan profil (nama, email) dari halaman Profil.
  // sekolah/kelas hanya disimpan lokal (belum ada kolomnya di backend).
  // Karena sapaan & avatar membaca user.value, nama baru langsung tampil di mana-mana.
  async function updateProfile(payload) {
    const terbaru = await authService.updateProfile({
      nama: payload.nama ?? payload.name,
      email: payload.email,
    })
    user.value = { ...user.value, ...payload, ...terbaru }
    return user.value
  }

  async function logout() {
    try {
      await authService.logout()
    } catch {
      // Abaikan: sesi lokal tetap dibersihkan agar user kembali ke /login.
    } finally {
      user.value = null
      saveToken(null)
      resetDataAkun()
    }
  }

  function $reset() {
    user.value = null
    initialized.value = false
    saveToken(null)
    resetDataAkun()
  }

  return {
    user,
    token,
    initialized,
    isAuthenticated,
    isSuperAdmin,
    isSiswa,
    nama,
    fetchMe,
    login,
    register,
    updateProfile,
    logout,
    $reset,
  }
})
