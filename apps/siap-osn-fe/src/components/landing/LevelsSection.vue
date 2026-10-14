<script setup>
const levels = [
  { title: 'Kabupaten', text: 'Titik awal persiapan — membangun fondasi kompetensi dasar Informatika.' },
  { title: 'Provinsi', text: 'Materi dan simulasi meningkat mengikuti standar seleksi tingkat provinsi.' },
]
</script>

<template>
<section id="tingkat" class="section section--soft">
      <div class="container levels">
        <small class="kicker kicker--center">Jenjang seleksi</small>
        <h2 class="marked--center">Naik tingkat sesuai <span class="hl">pencapaianmu</span></h2>
        <p class="levels__lead">
          Tingkat berikutnya terbuka otomatis setelah kamu memenuhi ketentuan pada tingkat
          sebelumnya.
        </p>
        <div class="levels__row">
          <template v-for="l in levels" :key="l.title">
            <article class="level">
              <h3>{{ l.title }}</h3>
              <p>{{ l.text }}</p>
            </article>
            <span class="levels__arrow" aria-hidden="true">→</span>
          </template>
        </div>
      </div>
    </section>
</template>

<style scoped src="./shared.css"></style>

<style scoped>
.lp .levels__lead {
  text-align: center;
  color: var(--muted);
  font-size: 0.92rem;
  max-width: 46ch;
  margin: 16px auto 64px;
}

.levels__row {
  display: grid;
  grid-template-columns: 1fr auto 1fr auto;
  align-items: center;
  gap: 12px;
}

.level {
  background: #fff;
  border-radius: var(--radius);
  padding: 24px;
  height: 100%;
}

.level h3 {
  font-size: 1rem;
  margin-bottom: 8px;
}

.level p {
  font-size: 0.8rem;
  color: var(--muted);
}

.level h3 {
  font-size: 1rem;
  margin-bottom: 8px;
}

.level p {
  font-size: 0.8rem;
  color: var(--muted);
}

.levels__arrow {
  color: #98a3bf;
  font-size: 1.4rem;
}

.levels .marked--center {
  margin-top: 14px;
}

@media (max-width: 900px) {
  .levels__row {
    grid-template-columns: 1fr;
  }
  .levels__arrow {
    display: none;
  }
}
</style>
