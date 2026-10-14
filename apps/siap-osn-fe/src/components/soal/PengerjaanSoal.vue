<script setup>
/*
 * Kerangka tampilan mengerjakan soal. Dipakai bersama oleh pre-test, latihan,
 * dan simulasi supaya ketiganya punya header, panel nomor, penanda ragu, dan
 * modal kumpulkan yang sama persis.
 *
 * Komponen ini tidak tahu store/backend: ia hanya menerima soal, jawaban,
 * ragu, dan index aktif, lalu meneruskan aksi lewat emit.
 */
import { computed, ref } from 'vue'
import UserMenu from '@/components/UserMenu.vue'
import {
  kelasNomer as kelasNomerSoal,
  hitungRingkasan,
  nomorSoal as formatNomorSoal,
  sudahDijawab,
} from '@/lib/soal.js'
import { renderKontenSatuan } from '@/lib/richtext.js'

const props = defineProps({
  judul: { type: String, default: '' },
  subjudul: { type: String, default: '' },
  soal: { type: Array, default: () => [] },
  jawaban: { type: Object, default: () => ({}) },
  ragu: { type: Object, default: () => ({}) },
  aktif: { type: Number, default: 0 },
  // Sisa waktu dalam detik; null = tanpa timer.
  sisaDetik: { type: Number, default: null },
  // "Sisa Waktu" saat mengerjakan, "Durasi" sebelum mulai (pre-test).
  timerLabel: { type: String, default: 'Sisa Waktu :' },
  timerAktif: { type: Boolean, default: false },
  simpanError: { type: Boolean, default: false },
  // Tampilkan tombol "Selesai" di soal terakhir (atau selalu lewat prop ini).
  tombolSelesai: { type: Boolean, default: false },
  // Judul/narasi yang menyesuaikan jenis ujian.
  modalJudul: { type: String, default: 'Yakin ingin mengumpulkan jawaban?' },
  modalPeringatan: { type: String, default: 'Setelah jawaban dikumpulkan, jawaban tidak dapat diubah kembali.' },
  // Boleh submit walau ada soal kosong (hanya lewat tombol paksa di modal).
  bolehKumpulkanKosong: { type: Boolean, default: false },
  mengirim: { type: Boolean, default: false },
  petunjuk: {
    type: Object,
    default: null,
    // { judul, isi, tipe: [{ nama, kelas, desc }] }
  },
})

const emit = defineEmits([
  'pilih-ganda',
  'isi-jawaban',
  'toggle-ragu',
  'pindah',
  'kumpulkan',
])

const petunjukTerbuka = ref(true)
const modalSelesai = ref(false)

const total = computed(() => props.soal.length)
const soalAktif = computed(() => props.soal[props.aktif] ?? null)
// Teks soal dari backend berisi Markdown dan LaTeX, jadi dirender lewat
// lib/richtext (bukan v-html mentah). Versi sebaris dipakai karena elemennya
// h3 -- render blok akan membungkus <p> yang tidak valid di situ.
const pertanyaanHtml = computed(() => renderKontenSatuan(soalAktif.value?.pertanyaan))
const ringkasan = computed(() => hitungRingkasan(props.soal, props.jawaban, props.ragu))

const waktuTampil = computed(() => {
  const totalDetik = props.sisaDetik ?? 0
  const m = Math.floor(totalDetik / 60)
  const s = totalDetik % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})
const mepet = computed(() => (props.sisaDetik ?? 0) <= 300)

const tipeSoal = computed(() => props.petunjuk?.tipe ?? [])

const labelTipe = computed(() =>
  soalAktif.value?.tipe === 'ganda' ? 'Pilihan Ganda · pilih 1' : 'Isian · tulis jawabanmu',
)
const petunjukTipe = computed(() =>
  soalAktif.value?.tipe === 'ganda'
    ? 'Pilih satu jawaban yang paling tepat.'
    : 'Tulis jawabanmu beserta langkah dan alasannya.',
)

function kelasNomer(i) {
  return kelasNomerSoal(i, { aktif: props.aktif, ragu: props.ragu, jawaban: props.jawaban })
}

function nomorSoal(i) {
  return formatNomorSoal(i)
}

function soalTerisi(i) {
  return sudahDijawab(props.jawaban, i)
}

function kirim(paksa) {
  emit('kumpulkan', { paksa })
}

// Dari modal: langsung ke soal kosong yang diklik.
function pergiKe(i) {
  modalSelesai.value = false
  emit('pindah', i)
}
</script>

<template>
  <div class="kerja">
    <header class="header">
      <div class="header-left">
        <h1>{{ judul }}</h1>
        <p>{{ subjudul }}</p>
      </div>

      <div class="header-right">
        <span class="timer-label">{{ timerLabel }}</span>
        <span class="timer" :class="{ 'timer-mepet': timerAktif && mepet }">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
            <circle cx="12" cy="13" r="8" /><path d="M12 9v4l2 2M9 2h6" />
          </svg>
          {{ waktuTampil }}
        </span>

        <span v-if="simpanError" class="belum-tersimpan">Gagal menyimpan, periksa koneksi</span>

        <UserMenu />
      </div>
    </header>

    <main class="main">
      <section class="col-left">
        <!-- PETUNJUK -->
        <div v-if="petunjuk && petunjukTerbuka" class="card petunjuk">
          <div class="petunjuk-head">
            <h2>{{ petunjuk.judul }}</h2>
            <button class="close" type="button" aria-label="Tutup petunjuk" @click="petunjukTerbuka = false">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                <path d="M5 5l14 14M19 5L5 19" />
              </svg>
            </button>
          </div>
          <p class="petunjuk-desc">{{ petunjuk.isi }}</p>
          <div class="tipe-grid">
            <div v-for="t in tipeSoal" :key="t.nama" class="tipe" :class="t.kelas">
              <span class="tipe-label">TIPE SOAL</span>
              <strong>{{ t.nama }}</strong>
              <p>{{ t.desc }}</p>
            </div>
          </div>
        </div>

        <!-- SOAL -->
        <div v-if="soalAktif" class="card soal">
          <div class="soal-meta">
            <div class="soal-meta-left">
              <span class="chip chip-blue">Soal {{ aktif + 1 }} dari {{ total }} soal</span>
              <span class="chip chip-gray">{{ labelTipe }}</span>
            </div>
            <div class="soal-meta-right">
              <span v-if="soalTerisi(aktif)" class="tersimpan">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 12l5 5L20 6" />
                </svg>
                Terisi
              </span>
              <span class="bobot">Bobot {{ soalAktif.bobot }} poin</span>
            </div>
          </div>

          <div v-if="soalAktif.konteks" class="konteks">
            <strong>{{ soalAktif.konteks.judul }}</strong>
            <p>{{ soalAktif.konteks.isi }}</p>
            <img v-if="soalAktif.konteks.gambar" :src="soalAktif.konteks.gambar" alt="Gambar konteks soal" />
          </div>
          <img v-if="soalAktif.gambar" :src="soalAktif.gambar" class="gambar-soal" alt="Gambar soal" />
          <h3 class="pertanyaan" v-html="pertanyaanHtml"></h3>
          <p class="hint">{{ petunjukTipe }}</p>

          <div v-if="soalAktif.tipe === 'ganda'" class="opsi-list">
            <button
              v-for="o in soalAktif.opsi"
              :key="o.kode"
              type="button"
              class="opsi"
              :class="{ aktif: jawaban[aktif] === o.kode }"
              @click="emit('pilih-ganda', o.kode)"
            >
              <span class="radio"><span v-if="jawaban[aktif] === o.kode" class="dot"></span></span>
              <b>{{ o.kode }}.</b>
              <span>{{ o.teks }}</span>
            </button>
          </div>

          <div v-else class="opsi-list">
            <textarea
              class="uraian"
              rows="7"
              placeholder="Tulis jawabanmu di sini..."
              :value="jawaban[aktif] || ''"
              @input="emit('isi-jawaban', $event.target.value)"
            ></textarea>
          </div>

          <div class="nav">
            <button class="btn btn-prev" type="button" :disabled="aktif === 0" @click="emit('pindah', aktif - 1)">
              <small>←</small> Sebelumnya
            </button>
            <button class="btn btn-ragu" type="button" @click="emit('toggle-ragu')">
              <span class="cb" :class="{ on: ragu[aktif] }">
                <svg v-if="ragu[aktif]" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 12l5 5L20 6" />
                </svg>
              </span>
              Ragu
            </button>
            <button v-if="tombolSelesai" class="btn btn-next" type="button" @click="modalSelesai = true">
              Selesai <small>→</small>
            </button>
            <button v-else class="btn btn-next" type="button" @click="emit('pindah', aktif + 1)">
              Berikutnya <small>→</small>
            </button>
          </div>
        </div>
      </section>

      <!-- RINGKASAN -->
      <aside class="col-right">
        <div class="card ringkasan">
          <div class="ringkasan-head">
            <h2>RINGKASAN STATUS SOAL</h2>
            <span class="badge">{{ total }} Butir</span>
          </div>

          <div class="stat-grid">
            <div class="stat stat-green"><b>{{ ringkasan.terjawab }}</b><span>Terisi</span></div>
            <div class="stat stat-orange"><b>{{ ringkasan.ragu }}</b><span>Ragu-ragu</span></div>
            <div class="stat stat-gray"><b>{{ ringkasan.belum }}</b><span>Kosong</span></div>
          </div>

          <p class="nomer-label">Nomer Soal :</p>
          <div class="nomer-grid">
            <button
              v-for="(s, i) in soal"
              :key="i"
              class="nomer"
              :class="kelasNomer(i)"
              type="button"
              @click="emit('pindah', i)"
            >
              {{ nomorSoal(i) }}
            </button>
          </div>

          <div class="legend">
            <span><i class="lg lg-green"></i>Terisi ({{ ringkasan.terjawab }})</span>
            <span><i class="lg lg-yellow"></i>Ragu-ragu ({{ ringkasan.ragu }})</span>
            <span><i class="lg lg-open"></i>Sedang dibuka</span>
            <span><i class="lg lg-empty"></i>Kosong ({{ ringkasan.belum }})</span>
          </div>
        </div>
      </aside>
    </main>

    <footer class="footer">
      <div class="footer-inner">
        <span>© 2026 SIAP OSN. Seluruh hak cipta dilindungi.</span>
        <span>Malang, Indonesia</span>
      </div>
    </footer>

    <!-- MODAL KUMPULKAN -->
    <div v-if="modalSelesai" class="overlay" @click.self="!mengirim && (modalSelesai = false)">
      <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
          <span class="modal-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9.5" /><path d="M9.2 9.3a2.9 2.9 0 1 1 4.3 2.5c-.9.5-1.5 1-1.5 2" /><circle cx="12" cy="16.9" r=".6" fill="currentColor" />
            </svg>
          </span>
          <div>
            <h3>{{ modalJudul }}</h3>
            <p>
              Kamu masih memiliki {{ ringkasan.belum }} soal kosong dan
              {{ ringkasan.ragu }} soal yang ditandai ragu.
            </p>
          </div>
        </div>

        <div class="modal-stats">
          <div class="mstat mstat-green"><b>{{ ringkasan.terjawab }}</b><span>Terisi</span></div>
          <div class="mstat mstat-orange"><b>{{ ringkasan.ragu }}</b><span>Ragu-ragu</span></div>
          <div class="mstat mstat-gray"><b>{{ ringkasan.belum }}</b><span>Kosong</span></div>
        </div>

        <div v-if="ringkasan.belum" class="modal-kosong">
          <span class="modal-kosong-label">Soal kosong:</span>
          <button
            v-for="i in ringkasan.kosong"
            :key="i"
            class="modal-kosong-nomor"
            type="button"
            @click="pergiKe(i)"
          >
            {{ nomorSoal(i) }}
          </button>
          <span v-if="!bolehKumpulkanKosong" class="modal-kosong-hint">
            Isi dulu, atau kumpulkan yang kosong bila mau.
          </span>
        </div>

        <div class="modal-warning">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="12" cy="12" r="9.5" /><path d="M12 11v5.5" /><circle cx="12" cy="7.8" r=".6" fill="currentColor" />
          </svg>
          <span>{{ modalPeringatan }}</span>
        </div>

        <div class="modal-actions">
          <button class="mbtn mbtn-yellow" type="button" :disabled="mengirim" @click="modalSelesai = false">
            Kembali Mengerjakan
          </button>
          <button
            v-if="ringkasan.belum"
            class="mbtn mbtn-ghost"
            type="button"
            :disabled="!bolehKumpulkanKosong || mengirim"
            @click="kirim(true)"
          >
            {{ mengirim ? 'Mengumpulkan…' : 'Kumpulkan yang Kosong' }}
          </button>
          <button v-else class="mbtn mbtn-navy" type="button" :disabled="mengirim" @click="kirim(false)">
            {{ mengirim ? 'Mengumpulkan…' : 'Kumpulkan Jawaban' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.kerja {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}
.header {
  background: #fff;
  border-bottom: 1px solid #e6ebf3;
  padding: 18px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}
.header h1 { margin: 0; font-size: 20px; font-weight: 600; color: #0b1220; }
.header p { margin: 4px 0 0; font-size: 14px; color: #8a94a6; }
.header-right { display: flex; align-items: center; gap: 10px; }
.timer-label { font-size: 14px; color: #8a94a6; }
.timer {
  display: inline-flex; align-items: center; gap: 7px;
  background: #fde9ec; color: #d91c4a;
  font-weight: 600; font-size: 13.5px;
  padding: 7px 14px; border-radius: 999px;
  transition: background .15s ease, color .15s ease;
}
.timer-mepet { background: #fde3b8; color: #b45309; }
.belum-tersimpan { color: #b45309; font-size: 12.5px; font-weight: 500; }

.main {
  flex: 1;
  width: 100%;
  max-width: 1440px;
  margin: 0 auto;
  padding: 26px 28px 34px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 374px;
  gap: 19px;
  align-items: start;
}
.col-left { display: flex; flex-direction: column; gap: 24px; }
.card {
  background: #fff;
  border: 1px solid #e8ecf3;
  border-radius: 22px;
  box-shadow: 0 2px 10px rgba(30, 50, 100, 0.04);
}

/* Petunjuk */
.petunjuk { border-color: #fbd9ae; background: #fffefc; padding: 36px 30px 35px; }
.petunjuk-head { display: flex; justify-content: space-between; align-items: center; }
.petunjuk h2 { margin: 0; font-size: 18px; font-weight: 600; color: #0b1220; }
.close { background: none; border: 0; color: #c8202f; padding: 2px; display: grid; place-items: center; }
.petunjuk-desc { margin: 12px 0 22px; font-size: 14.5px; line-height: 1.6; color: #4b5563; }
.tipe-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.tipe {
  background: #fff6ec;
  border: 1px solid #fbe3c6;
  border-radius: 14px;
  padding: 22px 16px 18px;
}
.tipe-label { display: block; font-size: 12.5px; letter-spacing: 0.9px; color: #4b5563; margin-bottom: 8px; }
.tipe strong { display: block; font-size: 16px; font-weight: 600; color: #0b1220; margin-bottom: 10px; }
.tipe p { margin: 0; font-size: 12.5px; line-height: 1.55; color: #6b7280; }

/* Soal */
.soal { padding: 36px 33px; }
.soal-meta {
  display: flex; justify-content: space-between; align-items: center;
  padding-bottom: 16px; border-bottom: 1px solid #eef1f6;
}
.soal-meta-left { display: flex; gap: 8px; }
.chip { font-size: 12.5px; padding: 5px 11px; border-radius: 7px; font-weight: 500; }
.chip-blue { background: #e8f0fe; color: #1d4ed8; font-weight: 600; }
.chip-gray { background: #f3f5f9; color: #374151; }
.soal-meta-right { display: flex; align-items: center; gap: 12px; font-size: 12.5px; }
.tersimpan { color: #0f9a55; display: inline-flex; align-items: center; gap: 5px; font-weight: 500; }
.bobot { color: #6b7280; }

.pertanyaan { margin: 28px 0 6px; font-size: 17px; line-height: 1.3; font-weight: 600; color: #0b1220; max-width: 790px; }
.hint { margin: 6px 0 14px; font-size: 12.5px; color: #8a94a6; }

.opsi-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }
.opsi {
  display: flex; align-items: center; gap: 10px;
  width: 100%; text-align: left;
  min-height: 50px; padding: 10px 14px;
  background: #fff; border: 1px solid #e3e8f1; border-radius: 10px;
  font-size: 15px; color: #1e293b;
  box-shadow: 0 1px 3px rgba(30, 50, 100, 0.06);
  transition: border-color 0.15s, background 0.15s;
}
.opsi:hover { border-color: #b8c9ee; }
.opsi b { font-weight: 600; color: #1e3a8a; }
.opsi.aktif { border-color: #3b6fe0; background: #f6f9ff; }
.radio {
  width: 18px; height: 18px; border-radius: 50%;
  border: 1.5px solid #cbd2de; background: #fff;
  display: grid; place-items: center; flex-shrink: 0;
}
.opsi.aktif .radio { border-color: #2563eb; background: #2563eb; }
.dot { width: 6px; height: 6px; border-radius: 50%; background: #fff; }
.uraian {
  width: 100%; border: 1px solid #e3e8f1; border-radius: 10px;
  padding: 14px; font: inherit; font-size: 15px; resize: vertical;
  outline: none; color: #1e293b;
}
.uraian:focus { border-color: #3b6fe0; background: #f6f9ff; }

/* Navigasi */
.nav {
  display: flex; justify-content: space-between; align-items: center;
  border-top: 1px solid #eef1f6; padding-top: 30px; margin-top: 4px;
}
.btn {
  height: 38px; padding: 0 15px; border-radius: 7px;
  font-size: 13px; font-weight: 500;
  display: inline-flex; align-items: center; gap: 5px;
  transition: filter 0.15s, transform 0.05s;
}
.btn small { font-size: 11px; }
.btn:hover:not(:disabled) { filter: brightness(0.97); }
.btn:active:not(:disabled) { transform: translateY(1px); }
.btn:disabled { opacity: 0.45; cursor: not-allowed; }
.btn-prev { background: #fde4e4; border: 1px solid #ef6b6b; color: #b91c1c; }
.btn-ragu { background: #fff3e0; border: 1px solid #f2ab57; color: #c2610c; padding: 0 24px; }
.btn-next { background: #fff; border: 1px solid #3b6fe0; color: #1d4ed8; }
.cb {
  width: 11px; height: 11px; border: 1.2px solid #c2610c; border-radius: 2px;
  background: #fff; display: grid; place-items: center;
}
.cb.on { background: #c2610c; }

/* Ringkasan */
.ringkasan { padding: 42px 35px 40px; }
.ringkasan-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; }
.ringkasan h2 { margin: 0; font-size: 16.5px; font-weight: 600; letter-spacing: 0.3px; color: #2a3a52; }
.badge { background: #fde7c4; color: #b45309; font-size: 12.5px; font-weight: 600; padding: 4px 10px; border-radius: 7px; }

.stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; }
.stat {
  height: 78px; border-radius: 14px; border: 1px solid;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
}
.stat b { font-size: 20px; font-weight: 700; line-height: 1; }
.stat span { font-size: 12px; }
.stat-green { background: #f3fcf6; border-color: #a7e6bf; color: #15803d; }
.stat-orange { background: #fff8ef; border-color: #f6c58a; color: #c2410c; }
.stat-gray { background: #fff; border-color: #e3e8f1; color: #1f2937; }

.nomer-label { margin: 18px 0 14px; font-size: 14px; color: #6b7280; }
.nomer-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 9px; }
.nomer {
  height: 54px; border-radius: 9px; border: 1px solid #e8ecf3;
  font-size: 13px; font-weight: 600; background: #fff; color: #b45309;
  transition: transform 0.08s, filter 0.15s;
}
.nomer:hover { filter: brightness(0.96); }
.nomer:active { transform: scale(0.96); }
.n-done { background: #c9f5d9; border-color: #8fe0ae; color: #15803d; }
.n-ragu { background: #fde8c8; border-color: #f0a957; color: #b45309; }
.n-open { background: #eaf1ff; border: 2px solid #2563eb; color: #1d4ed8; box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15); }
.n-empty { background: #fff; border-color: #eaeef5; color: #b45309; }

.legend {
  margin-top: 22px; padding-top: 22px; border-top: 1px solid #eef1f6;
  display: grid; grid-template-columns: 1fr 1fr; row-gap: 10px; column-gap: 12px;
  font-size: 11.5px; color: #4b5563;
}
.legend span { display: flex; align-items: center; gap: 7px; padding-left: 20px; }
.lg { width: 11px; height: 11px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.lg-green { background: #c9f5d9; }
.lg-yellow { background: #fde8c8; }
.lg-open { background: #fff; border: 2px solid #2563eb; }
.lg-empty { background: #fff; border: 1px solid #eaeef5; }

/* Konteks & gambar soal dari backend */
.konteks {
  margin: 20px 0 6px; padding: 16px 18px;
  background: #f6f9ff; border: 1px solid #dbe6fb; border-radius: 12px;
}
.konteks strong { display: block; font-size: 14px; color: #0b1220; margin-bottom: 6px; }
.konteks p { margin: 0; font-size: 14px; line-height: 1.6; color: #374151; white-space: pre-line; }
.konteks img { max-width: 100%; border-radius: 8px; margin-top: 10px; }
.gambar-soal { max-width: 100%; border-radius: 10px; margin-top: 16px; }

/* Warna tipe soal */
.tipe-green { background: #d9f7e6; border-color: #b4ebcc; }
.tipe-red { background: #fde2e6; border-color: #f8c4cc; }

/* Footer */
.footer { background: #0b1a3a; padding: 22px 28px; }
.footer-inner {
  max-width: 930px; margin: 0 auto;
  display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  font-size: 13px; color: #8b97b3;
}

/* Modal */
.overlay {
  position: fixed; inset: 0; z-index: 50; padding: 20px;
  background: rgba(30, 45, 90, 0.5);
  backdrop-filter: blur(3px);
  display: grid; place-items: center;
}
.modal {
  background: #fff; border-radius: 12px;
  width: 100%; max-width: 512px;
  padding: 32px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.3);
}
.modal-head { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 26px; }
.modal-icon {
  width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
  background: #fdebc8; color: #7a4a00;
  display: grid; place-items: center;
}
.modal h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0b1220; line-height: 1.4; }
.modal-head p { margin: 2px 0 0; font-size: 15px; line-height: 1.55; color: #4b5563; }
.modal-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 20px; }
.mstat {
  height: 78px; border-radius: 10px; border: 1px solid;
  background: #fff;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px;
}
.mstat b { font-size: 18px; font-weight: 700; line-height: 1; }
.mstat span { font-size: 12px; }
.mstat-green { border-color: #aeead0; color: #15803d; }
.mstat-orange { border-color: #f6c58a; color: #c2410c; }
.mstat-gray { border-color: #e3e8f1; color: #374151; }

.modal-kosong {
  display: flex; align-items: center; flex-wrap: wrap; gap: 7px;
  margin-bottom: 20px; font-size: 13px; color: #4b5563;
}
.modal-kosong-label { font-weight: 600; }
.modal-kosong-nomor {
  min-width: 34px; height: 30px; padding: 0 8px;
  border: 1px solid #f0a957; border-radius: 7px;
  background: #fde8c8; color: #b45309;
  font: inherit; font-size: 12.5px; font-weight: 600;
}
.modal-kosong-nomor:hover { background: #fbdcae; }
.modal-kosong-hint { color: #8a94a6; }

.modal-warning {
  display: flex; align-items: center; gap: 12px;
  background: #ffe3e5; border-radius: 8px;
  padding: 16px 20px; margin-bottom: 26px;
  font-size: 15px; line-height: 1.5; color: #3f3f46;
}
.modal-warning svg { color: #b45309; flex-shrink: 0; }
.modal-actions { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; }
.mbtn {
  height: 44px; padding: 0 24px; border: 0; border-radius: 10px;
  font-size: 15px; font-weight: 600; color: #fff;
  transition: filter 0.15s, transform 0.05s;
}
.mbtn:disabled { opacity: 0.45; cursor: not-allowed; }
.mbtn:hover:not(:disabled) { filter: brightness(0.95); }
.mbtn:active:not(:disabled) { transform: translateY(1px); }
.mbtn-yellow { background: #fbb024; }
.mbtn-navy { background: #1e3a8a; }
.mbtn-ghost {
  background: #fff; color: #b91c1c; border: 1px solid #ef6b6b;
}

/* Responsif */
@media (max-width: 1000px) {
  .main { grid-template-columns: 1fr; padding: 20px; }
  .tipe-grid { grid-template-columns: 1fr; }
  .timer-label { display: none; }
}

@media (max-width: 560px) {
  .header { padding: 14px 16px; flex-wrap: wrap; }
  .soal { padding: 22px 18px; }
  .ringkasan { padding: 26px 20px; }
  .nomer-grid { grid-template-columns: repeat(4, 1fr); }
  .nomer { height: 46px; }
  .modal { padding: 24px 18px; }
  .modal-actions { flex-direction: column; }
  .mbtn { width: 100%; }
}
</style>
