<script setup>
import { RouterLink } from 'vue-router'

// Satu-satunya navbar untuk halaman publik (landing, login, register).
// `links`: tautan jangkar SEHALAMAN (mis. section di landing). Kosongkan di
// halaman yang tidak punya section — menu otomatis disembunyikan agar tidak
// ada dead link. `current`: halaman aktif, CTA-nya disembunyikan agar tidak
// ada tautan ke diri sendiri.
defineProps({
  links: {
    type: Array,
    default: () => [
      { label: 'Kenapa SIAP OSN', href: '/#kenapa' },
      { label: 'Cara Kerja', href: '/#cara-kerja' },
      { label: 'Fitur', href: '/#fitur' },
      { label: 'Tingkat Seleksi', href: '/#tingkat' },
      { label: 'Tim Kami', href: '/#tim' },
    ],
  },
  current: { type: String, default: 'landing' },
  theme: { type: String, default: 'light' },
})
</script>

<template>
  <header class="pubnav" :class="`pubnav--${theme}`">
    <div class="pubnav__inner">
      <RouterLink to="/" class="pubnav__brand" aria-label="SIAP OSN — ke beranda">
        <img class="pubnav__logo" src="/logo-icon.png" alt="" aria-hidden="true" />
        <span class="pubnav__name">SIAP OSN</span>
      </RouterLink>

      <nav v-if="links.length" class="pubnav__links" aria-label="Navigasi utama">
        <a v-for="l in links" :key="l.href" :href="l.href">{{ l.label }}</a>
      </nav>

      <div class="pubnav__actions">
        <RouterLink v-if="current !== 'login'" to="/login" class="pubnav__btn pubnav__btn--primary">Masuk</RouterLink>
        <RouterLink v-if="current !== 'register'" to="/register" class="pubnav__btn pubnav__btn--primary">Daftar Gratis</RouterLink>
      </div>
    </div>
  </header>
</template>

<style scoped>
.pubnav {
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 40;
  font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
}
.pubnav__inner {
  max-width: 1160px;
  margin: 0 auto;
  min-height: 62px;
  padding: 8px 24px;
  display: grid;
  grid-template-columns: 180px 1fr 180px;
  align-items: center;
  gap: 20px;
}

.pubnav__brand {
  display: inline-flex;
  align-items: center;
  justify-self: start;
  gap: 10px;
  white-space: nowrap;
}

.pubnav__logo {
  width: 43px;
  height: 38px;
  border-radius: 22px;
  object-fit: cover;
  flex: none;
}
.pubnav__name {
  font-weight: 700;
  font-size: 14px;
  white-space: nowrap;
}
.pubnav__links {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24px;
  margin: 0;
}
.pubnav__links a {
  font-size: 13px;
  color: #334155;
  text-decoration: none;
  white-space: nowrap;
}
.pubnav__links a:hover {
  color: #1e4b8f;
}
.pubnav__actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  margin: 0;
}
.pubnav__btn {
  padding: 9px 18px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  border: 1px solid transparent;
  white-space: nowrap;
}
.pubnav__btn--outline {
  background: #fff;
  color: #0f1b3d;
  border-color: #e2e8f0;
}
.pubnav__btn--outline:hover {
  border-color: #1e4b8f;
  color: #1e4b8f;
}
.pubnav__btn--primary {
  background: #1e4b8f;
  color: #fff;
}
.pubnav__btn--primary:hover {
  background: #183d75;
}
.pubnav__brand:focus-visible,
.pubnav__links a:focus-visible,
.pubnav__btn:focus-visible {
  outline: 2px solid #1e4b8f;
  outline-offset: 2px;
  border-radius: 4px;
}

/* Varian gelap: dipakai landing di atas hero navy (sesuai Figma) */
.pubnav--dark {
  background: #1a2d4d;
  border-bottom-color: rgba(255, 255, 255, 0.06);
}
.pubnav--dark .pubnav__brand,
.pubnav--dark .pubnav__name {
  color: #fff;
}
.pubnav--dark .pubnav__links a {
  color: #c8d2ea;
}
.pubnav--dark .pubnav__links a:hover {
  color: #fff;
}
.pubnav--dark .pubnav__btn--outline {
  background: transparent;
  color: #fff;
  border-color: rgba(255, 255, 255, 0.45);
}
.pubnav--dark .pubnav__btn--outline:hover {
  border-color: #fff;
  color: #fff;
  background: rgba(255, 255, 255, 0.08);
}
.pubnav--dark .pubnav__btn--primary {
  background: #fff;
  color: #1a2d4d;
}
.pubnav--dark .pubnav__btn--primary:hover {
  background: #e8edf8;
}
.pubnav--dark .pubnav__brand:focus-visible,
.pubnav--dark .pubnav__links a:focus-visible,
.pubnav--dark .pubnav__btn:focus-visible {
  outline-color: #f5c96a;
}

@media (max-width: 900px) {
  .pubnav__links {
    display: none;
  }
  .pubnav__inner {
    justify-content: space-between;
  }
}
</style>
