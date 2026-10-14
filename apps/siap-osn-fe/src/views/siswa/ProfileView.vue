<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { pesanError } from '@/lib/errors.js'

const router = useRouter()
const auth = useAuthStore()

/* ---------- Data profil: diambil dari akun yang sedang login ---------- */
function formatBergabung(iso) {
  const t = iso ? new Date(iso) : null
  if (!t || Number.isNaN(t.getTime())) return '-'
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(t)
}

// Bentuk data profil dari user di store. Kolom yang belum diisi dibiarkan kosong.
function dariUser(u) {
  return {
    nama: auth.nama,
    email: u?.email ?? '',
    sekolah: u?.sekolah ?? '',
    kelas: u?.kelas ?? '',
    tingkat: u?.tingkat ?? '-',
    bergabung: formatBergabung(u?.created_at ?? u?.bergabung),
  }
}

const profile = reactive(dariUser(auth.user))

// Ikuti perubahan akun (mis. setelah fetchMe selesai atau setelah simpan berhasil).
watch(
  () => auth.user,
  (u) => Object.assign(profile, dariUser(u)),
)

const initials = computed(
  () =>
    profile.nama
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((n) => n[0].toUpperCase())
      .join('') || '?',
)

const saving = ref(false)
const notice = ref({ type: '', text: '' })

function resetProfile() {
  Object.assign(profile, dariUser(auth.user))
  notice.value = { type: '', text: '' }
}

async function saveProfile() {
  const nama = profile.nama.trim()
  if (!nama) {
    notice.value = { type: 'error', text: 'Nama lengkap tidak boleh kosong.' }
    return
  }

  saving.value = true
  notice.value = { type: '', text: '' }
  try {
    await auth.updateProfile({
      nama,
      email: profile.email.trim(),
      sekolah: profile.sekolah.trim(),
      kelas: profile.kelas.trim(),
    })
    notice.value = { type: 'success', text: 'Profil berhasil diperbarui.' }
  } catch (e) {
    notice.value = {
      type: 'error',
      text: pesanError(e, 'Profil belum tersimpan. Periksa koneksi lalu coba lagi.'),
    }
  } finally {
    saving.value = false
  }
}

/* ---------- Ubah kata sandi ---------- */
const password = reactive({ current: '', next: '', confirm: '' })

const strength = computed(() => {
  const p = password.next
  if (!p) return { score: 0, label: '-', percent: 0 }
  let score = 0
  if (p.length >= 8) score++
  if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++
  if (/\d/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  const labels = ['Lemah', 'Cukup', 'Baik', 'Kuat', 'Kuat']
  return { score, label: labels[score], percent: Math.max(score, 1) * 25 }
})

function resetPassword() {
  Object.assign(password, { current: '', next: '', confirm: '' })
}

function updatePassword() {
  if (password.next.length < 8) return alert('Kata sandi minimal 8 karakter')
  if (password.next !== password.confirm) return alert('Konfirmasi kata sandi tidak sama')
  // TODO: panggil API ubah kata sandi
  resetPassword()
}

/* ---------- Logout ---------- */
async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="profile-page">
    <!-- Topbar -->
    <header class="topbar">
      <div>
        <h1 class="topbar__title">Profil Akun</h1>
        <p class="topbar__sub">Kelola informasi akun dan keamanan login kamu</p>
      </div>
      <div class="avatar avatar--sm">{{ initials }}</div>
    </header>

    <div class="content">
      <!-- 01 Edit Profil -->
      <section class="section">
        <span class="eyebrow">01 · Edit Profil</span>
        <h2 class="section__title">Informasi Profil</h2>
        <p class="section__desc">Perbarui data diri yang tampil di akun SIAP OSN-mu.</p>

        <form class="card" @submit.prevent="saveProfile">
          <div class="user">
            <div class="avatar avatar--lg">{{ initials }}</div>
            <div>
              <div class="user__name">{{ profile.nama }}</div>
              <div class="user__email">{{ profile.email }}</div>
              <button type="button" class="link">Ganti foto profil</button>
            </div>
          </div>

          <div class="grid">
            <div class="field">
              <label for="nama">Nama Lengkap</label>
              <input id="nama" v-model="profile.nama" type="text" />
            </div>
            <div class="field">
              <label for="email">Email Aktif</label>
              <input id="email" v-model="profile.email" type="email" />
            </div>
            <div class="field">
              <label for="sekolah">Asal Sekolah</label>
              <input id="sekolah" v-model="profile.sekolah" type="text" />
            </div>
            <div class="field">
              <label for="kelas">Kelas</label>
              <input id="kelas" v-model="profile.kelas" type="text" />
            </div>
            <div class="field">
              <label for="tingkat">Tingkat Seleksi Saat Ini</label>
              <input id="tingkat" :value="profile.tingkat" type="text" disabled />
              <small>Berubah otomatis saat kamu naik tingkat</small>
            </div>
            <div class="field">
              <label for="bergabung">Bergabung Sejak</label>
              <input id="bergabung" :value="profile.bergabung" type="text" disabled />
            </div>
          </div>

          <p v-if="notice.text" class="notice" :class="`notice--${notice.type}`" role="status">
            {{ notice.text }}
          </p>

          <div class="actions">
            <button type="button" class="btn btn--ghost" @click="resetProfile">Batalkan</button>
            <button type="submit" class="btn btn--primary" :disabled="saving">
              {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </form>
      </section>

      <!-- 02 Keamanan -->
      <section class="section">
        <span class="eyebrow">02 · Keamanan</span>
        <h2 class="section__title">Ubah Kata Sandi</h2>
        <p class="section__desc">Gunakan kata sandi yang kuat dan belum pernah dipakai di akun lain.</p>

        <form class="card" @submit.prevent="updatePassword">
          <div class="field">
            <label for="pw-now">Kata Sandi Saat Ini</label>
            <input id="pw-now" v-model="password.current" type="password" autocomplete="current-password" />
          </div>

          <div class="field">
            <label for="pw-new">Kata Sandi Baru</label>
            <input
              id="pw-new"
              v-model="password.next"
              type="password"
              placeholder="Kombinasi minimal 8 karakter"
              autocomplete="new-password"
            />
            <div class="strength">
              <div
                class="strength__bar"
                :style="{ width: strength.percent + '%' }"
                :class="`strength__bar--${strength.score}`"
              ></div>
            </div>
            <small>Kekuatan kata sandi: {{ strength.label }}</small>
          </div>

          <div class="field">
            <label for="pw-confirm">Konfirmasi Kata Sandi Baru</label>
            <input
              id="pw-confirm"
              v-model="password.confirm"
              type="password"
              placeholder="Ulangi kata sandi baru"
              autocomplete="new-password"
            />
          </div>

          <div class="actions">
            <button type="button" class="btn btn--ghost" @click="resetPassword">Batalkan</button>
            <button type="submit" class="btn btn--primary">Perbarui Kata Sandi</button>
          </div>
        </form>
      </section>

      <!-- 03 Logout -->
      <section class="section">
        <span class="eyebrow">03 · Logout</span>
        <h2 class="section__title">Keluar dari Akun</h2>
        <p class="section__desc">Akhiri sesi login di perangkat ini.</p>

        <div class="card">
          <div class="logout">
            <p>
              Kamu akan keluar dari sesi ini di perangkat sekarang. Progress belajar dan pemetaan
              kompetensimu tetap tersimpan aman.
            </p>
            <button type="button" class="btn btn--danger" @click="logout">Keluar Sekarang →</button>
          </div>
          <div class="device">
            <span>Perangkat: <strong>Chrome · Windows</strong></span>
            <span>Login terakhir: <strong>Hari ini, 08:12</strong></span>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.profile-page {
  --navy: #0d2150;
  --navy-dark: #0a1a3f;
  --yellow: #f0b323;
  --red: #f0614f;
  --text: #111827;
  --muted: #6b7280;
  --border: #e5e7eb;
  --bg-disabled: #f3f4f6;

  min-height: 100%;
  background: #fff;
  color: var(--text);
  font-family: 'Inter', system-ui, sans-serif;
}

/* Topbar */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  border-bottom: 1px solid var(--border);
}
.topbar__title { margin: 0; font-size: 18px; font-weight: 600; }
.topbar__sub { margin: 2px 0 0; font-size: 12px; color: var(--muted); }

/* Content */
.content { max-width: 640px; padding: 28px 22px 48px; }
.section { padding-bottom: 28px; margin-bottom: 28px; border-bottom: 1px solid var(--border); }
.section:last-child { border-bottom: none; margin-bottom: 0; }
.eyebrow {
  font-size: 11px; font-weight: 700; letter-spacing: 0.04em;
  text-transform: uppercase; color: var(--yellow);
}
.section__title { margin: 4px 0 2px; font-size: 18px; font-weight: 600; }
.section__desc { margin: 0 0 14px; font-size: 12px; color: var(--muted); }

/* Card */
.card {
  background: #fff; border: 1px solid var(--border);
  border-radius: 16px; padding: 20px;
}

/* Avatar & user */
.avatar {
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%; background: var(--navy); color: #fff; font-weight: 700;
}
.avatar--lg { width: 62px; height: 62px; font-size: 22px; }
.avatar--sm { width: 36px; height: 36px; font-size: 12px; box-shadow: 0 0 0 3px #fff, 0 0 0 5px var(--navy); }
.user { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
.user__name { font-size: 16px; font-weight: 600; }
.user__email { font-size: 12px; color: var(--muted); margin: 2px 0 6px; }
.link {
  background: none; border: none; padding: 0; cursor: pointer;
  font-size: 12px; font-weight: 600; color: var(--navy);
}

/* Form */
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 14px; }
.field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 4px; }
.grid .field { margin-bottom: 0; }
.card > .field + .field { margin-top: 14px; }
.field label { font-size: 12px; font-weight: 600; }
.field input {
  height: 38px; padding: 0 12px; font-size: 13px; color: var(--text);
  border: 1px solid var(--border); border-radius: 8px; background: #fff; outline: none;
}
.field input::placeholder { color: #9ca3af; }
.field input:focus { border-color: var(--navy); box-shadow: 0 0 0 3px rgb(13 33 80 / 0.12); }
.field input:disabled { background: var(--bg-disabled); color: var(--muted); }
.field small { font-size: 11px; color: var(--muted); }

/* Strength bar */
.strength { height: 4px; border-radius: 99px; background: var(--border); overflow: hidden; margin-top: 2px; }
.strength__bar { height: 100%; border-radius: 99px; background: var(--yellow); transition: width 0.25s; }
.strength__bar--0, .strength__bar--1 { background: var(--red); }

/* Buttons */
.actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.btn {
  height: 36px; padding: 0 16px; font-size: 12px; font-weight: 700;
  border-radius: 8px; cursor: pointer; border: 1px solid transparent;
}
.btn--ghost { background: #fff; border-color: var(--border); color: var(--text); }
.btn--primary { background: var(--yellow); color: var(--navy-dark); }
.btn--danger { background: var(--red); color: #fff; white-space: nowrap; }
.btn:hover { filter: brightness(0.96); }
.btn:disabled { opacity: 0.6; cursor: not-allowed; filter: none; }

/* Notice */
.notice { margin: 16px 0 0; padding: 10px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; }
.notice--success { background: #e7f6ee; color: #1f6b47; }
.notice--error { background: #fdeceb; color: #b3372b; }

/* Logout */
.logout { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.logout p { margin: 0; font-size: 12px; line-height: 1.6; color: var(--muted); }
.device {
  display: flex; justify-content: space-between; gap: 12px; margin-top: 14px;
  padding: 10px 14px; border-radius: 8px; background: var(--bg-disabled);
  font-size: 11px; color: var(--muted);
}
.device strong { color: var(--text); }

@media (max-width: 640px) {
  .grid { grid-template-columns: 1fr; }
  .logout { flex-direction: column; align-items: flex-start; }
  .device { flex-direction: column; }
}
</style>
