<script setup>
const features = [
  {
    icon: '📝',
    title: 'Pre-test Adaptif',
    text: 'Mengukur kemampuan awal sesuai tingkat seleksi yang kamu pilih, sebelum masuk ke materi.',
  },
  {
    icon: '🧭',
    title: 'Pemetaan Kompetensi Otomatis',
    text: 'Sistem menganalisis hasil pre-test dan menunjukkan kompetensi mana yang sudah kuat dan yang masih perlu ditingkatkan.',
    dark: true,
  },
  {
    icon: '🎯',
    title: 'Rekomendasi Materi Personalisasi',
    text: 'Belajar difokuskan pada materi yang paling kamu butuhkan, bukan menghabiskan waktu untuk topik yang sudah dikuasai.',
  },
  {
    icon: '🏁',
    title: 'Simulasi Seleksi Bertingkat',
    text: 'Rasakan simulasi ujian yang merepresentasikan kondisi seleksi sesungguhnya di tiap tingkat.',
  },
  {
    icon: '💡',
    title: 'Pembahasan & Evaluasi Tiap Soal',
    text: 'Setiap soal dilengkapi pembahasan agar kamu tahu persis letak kesalahan dan cara memperbaikinya.',
    dark: true,
  },
  {
    icon: '📈',
    title: 'Dashboard Progress Belajar',
    text: 'Pantau perkembangan belajar, riwayat pre-test, latihan, dan simulasi dalam satu tempat.',
  },
]
</script>

<template>
<section id="fitur" class="section section--white">
      <div class="container">
        <h2 class="marked--block">Semua yang kamu butuhkan untuk <span class="hl">satu jalur persiapan</span></h2>
        <div class="features">
          <article v-for="f in features" :key="f.title" class="feature" :class="{ 'feature--dark': f.dark }">
            <span class="feature__icon" aria-hidden="true">{{ f.icon }}</span>
            <h3>{{ f.title }}</h3>
            <p>{{ f.text }}</p>
          </article>
        </div>
      </div>
    </section>
</template>

<style scoped src="./shared.css"></style>

<style scoped>
.features {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.feature {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: var(--radius);
  padding: 22px;
}

.feature h3 {
  font-size: 0.92rem;
  margin-bottom: 8px;
}

.feature p {
  font-size: 0.8rem;
  color: var(--muted);
}

.feature__icon {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background: var(--soft);
  margin-bottom: 26px;
  font-size: 1.2rem;
}

.feature h3 {
  font-size: 0.92rem;
  margin-bottom: 8px;
}

.feature p {
  font-size: 0.8rem;
  color: var(--muted);
}

.feature--dark {
  background: var(--navy);
  border-color: var(--navy);
  color: #fff;
}

.feature--dark .feature__icon {
  background: rgba(255, 255, 255, 0.1);
}

.feature--dark p {
  color: #b7c4e2;
}

.feature--dark .feature__icon {
  background: rgba(255, 255, 255, 0.1);
}

.feature--dark p {
  color: #b7c4e2;
}

@media (max-width: 900px) {
  .features {
    grid-template-columns: 1fr;
  }
}
</style>
