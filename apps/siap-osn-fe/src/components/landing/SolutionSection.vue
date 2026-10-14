<script setup>
const priorities = [
  { icon: '📊', judul: 'Dynamic Programming Dasar', sub: 'Prioritas tinggi · Belum dikuasai', tone: 'yellow' },
  { icon: '🌳', judul: 'Graf & Pohon Lanjutan', sub: 'Prioritas sedang · Perlu latihan', tone: 'blue' },
  { icon: '✅', judul: 'Struktur Data Dasar', sub: 'Dikuasai · Simulasi tersedia', tone: 'green' },
]
</script>

<template>
<section id="solusi" class="section section--navy">
      <div class="container split split--reverse">
        <div class="priority-card">
          <h3 class="priority-card__title">Rekomendasi Materi Kamu</h3>
          <span class="tag tag--green">Diperbarui otomatis</span>
          <ul>
            <li v-for="p in priorities" :key="p.judul">
              <span class="rec-icon" :class="`dot--${p.tone}`" aria-hidden="true">{{ p.icon }}</span>
              <span>
                <strong>{{ p.judul }}</strong>
                <small>{{ p.sub }}</small>
              </span>
            </li>
          </ul>
        </div>
        <div class="split__text split__text--light">
          <small class="kicker">Solusinya</small>
          <h2>Bagaimana SIAP OSN <span class="hl">menjawab masalah itu?</span></h2>
          <p>
            SIAP OSN memetakan kompetensi siswa secara otomatis lewat pre-test, memberi rekomendasi
            materi yang personal, dan menyediakan simulasi seleksi bertingkat lengkap dengan
            pembahasan — sehingga belajar jadi lebih terarah dan siap menghadapi ujian
            sesungguhnya.
          </p>
          <a href="/register" class="btn btn--light">Coba Pre-Test Sekarang</a>
        </div>
      </div>
    </section>
</template>

<style scoped src="./shared.css"></style>

<style scoped>
.priority-card {
  background: #fff;
  color: var(--ink);
  border-radius: 14px;
  padding: 20px 24px 8px;
}

.priority-card ul {
  list-style: none;
  margin: 12px 0 0;
  padding: 0;
}

.priority-card li {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 0;
  border-top: 1px solid var(--line);
  font-size: 0.8rem;
  color: var(--muted);
}

.priority-card li strong {
  display: block;
  color: var(--ink);
  font-size: 0.85rem;
}

.priority-card li small {
  display: block;
  font-size: 0.72rem;
}

.priority-card__title {
  font-size: 0.95rem;
  margin: 0 0 4px;
}

.priority-card ul {
  list-style: none;
  margin: 12px 0 0;
  padding: 0;
}

.priority-card li {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 0;
  border-top: 1px solid var(--line);
  font-size: 0.8rem;
  color: var(--muted);
}

.priority-card li strong {
  display: block;
  color: var(--ink);
  font-size: 0.85rem;
}

.priority-card li small {
  display: block;
  font-size: 0.72rem;
}

.priority-card li strong {
  display: block;
  color: var(--ink);
  font-size: 0.85rem;
}

.priority-card li small {
  display: block;
  font-size: 0.72rem;
}

.tag {
  display: block;
  width: fit-content;
  margin-left: auto;
  padding: 4px 10px;
  border-radius: 99px;
  font-size: 0.68rem;
  font-weight: 600;
}

.tag--green {
  background: #e3f5ec;
  color: #1f8a5b;
}

.rec-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  flex: none;
  display: grid;
  place-items: center;
  font-size: 1rem;
}

.dot--yellow {
  background: #fdf0d5;
}

.dot--blue {
  background: #e3ecfd;
}

.dot--green {
  background: #dff3e8;
}

@media (max-width: 900px) {
  .split {
    grid-template-columns: 1fr;
  }
  .split--reverse .priority-card {
    order: 2;
  }
}
</style>
