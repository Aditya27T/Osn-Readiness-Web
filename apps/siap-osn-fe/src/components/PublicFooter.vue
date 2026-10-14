<script setup>
const footerColumns = [
  {
    title: 'Produk',
    links: [
      { label: 'Fitur', href: '#fitur' },
      { label: 'Cara Kerja', href: '#cara-kerja' },
      { label: 'Tingkat Seleksi', href: '#tingkat' },
    ],
  },
  {
    title: 'Tentang',
    links: [
      { label: 'Tim Kami', href: '#tim' },
      { label: 'Kenapa SIAP OSN', href: '#kenapa' },
    ],
  },
  {
    title: 'Mulai',
    links: [
      { label: 'Daftar Gratis', href: '/register' },
      { label: 'Masuk', href: '/login' },
    ],
  },
]
</script>

<template>
<footer id="tim" class="footer">
      <div class="container footer__grid">
        <div class="footer__about">
          <a href="#" class="brand" aria-label="SIAP OSN">
            <img class="brand__logo" src="/logo-icon.png" alt="" aria-hidden="true" />
            <span>SIAP OSN</span>
          </a>
          <p>
            Sistem Interaktif Adaptif Persiapan OSN — membantu siswa mempersiapkan diri menghadapi
            seleksi OSN Bidang Informatika secara adaptif.
          </p>
        </div>
        <div v-for="col in footerColumns" :key="col.title" class="footer__col">
          <strong>{{ col.title }}</strong>
          <a v-for="l in col.links" :key="l.label" :href="l.href">{{ l.label }}</a>
        </div>
      </div>
      <div class="container footer__bottom">
        <span>© 2026 SIAP OSN. Seluruh hak cipta dilindungi.</span>
        <span>Malang, Indonesia</span>
      </div>
    </footer>
</template>

<style scoped src="./landing/shared.css"></style>

<style scoped>
.footer {
  background: var(--navy);
  color: #c8d2ea;
  padding: 56px 0 28px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.footer .brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #fff;
  font-weight: 700;
  font-size: 0.95rem;
}

.footer .brand__logo {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  object-fit: cover;
}

.footer__grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr 1fr 1fr;
  gap: 32px;
}

.footer .brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #fff;
  font-weight: 700;
  font-size: 0.95rem;
}

.footer .brand__logo {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  object-fit: cover;
}

.footer__about p {
  font-size: 0.78rem;
  margin-top: 12px;
  max-width: 34ch;
}

.footer__col {
  display: grid;
  gap: 10px;
  align-content: start;
  font-size: 0.8rem;
}

.footer__col strong {
  color: #fff;
  font-size: 0.82rem;
}

.footer__col a:hover {
  color: #fff;
}

.footer__col strong {
  color: #fff;
  font-size: 0.82rem;
}

.footer__col a:hover {
  color: #fff;
}

.footer__bottom {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-top: 48px;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 0.74rem;
  color: #8fa0c6;
}

@media (max-width: 900px) {
  .footer__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 520px) {
  .footer__bottom {
    flex-direction: column;
  }
}
</style>
