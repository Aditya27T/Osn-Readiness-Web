<script setup>
import { computed, ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import UserMenu from '@/components/UserMenu.vue'
import { usePretestStore } from '@/stores/pretest.js'
import { useProgressStore } from '@/stores/progress.js'
import { useSimulasiStore } from '@/stores/simulasi.js'
import { pesanError } from '@/lib/errors.js'
import { segeraDatang } from '@/lib/tingkat.js'

// Lobi simulasi. Tingkat, daftar simulasi, dan syarat semuanya dari backend;
// tidak ada data mock lagi. Modal aturan dipertahankan dari versi sebelumnya.

const route = useRoute()
const router = useRouter()
const toast = useToast()
const pretest = usePretestStore()
const progress = useProgressStore()
const simulasi = useSimulasiStore()

/* ---------- Tingkat ---------- */
const levels = computed(() => pretest.tingkatList)
const levelDipilihId = ref(null)
const levelDipilih = computed(() => levels.value.find((l) => l.id === levelDipilihId.value) ?? null)
const sudahLulus = computed(() => levelDipilih.value?.tahap === 'LULUS')
// Tingkat yang belum rilis: tampil "Segera" dan tidak bisa dipilih, apa pun
// status terbuka dari backend.
function terkunciSegera(l) {
  return segeraDatang(l)
}
function bolehPilih(l) {
  return l.terbuka && !terkunciSegera(l)
}
// Percobaan yang sedang berjalan: tombol menjadi "Lanjutkan".
const simulasiBerjalan = computed(() => levelDipilih.value?.tahap === 'SIMULASI_BERJALAN')

/* ---------- Simulasi di tingkat terpilih ---------- */
const daftarSimulasi = computed(() => simulasi.daftar)
const syarat = computed(() => simulasi.syarat)

function alasanMulaiNonaktif(s) {
  if (!s.aktif) return 'Simulasi ini sedang tidak aktif.'
  if (!syarat.value?.terpenuhi) return 'Syarat simulasi belum terpenuhi.'
  if (s.sisaKuota === 0) return 'Kuota simulasi untuk putaran ini sudah habis.'
  return ''
}
function bisaMulai(s) {
  return alasanMulaiNonaktif(s) === ''
}

function pilihLevel(l) {
  if (!l.terbuka || terkunciSegera(l)) return
  levelDipilihId.value = l.id
  simulasi.muatRuang({ tingkatId: l.id }).catch(() => {})
}

/* ---------- Syarat ---------- */
function syaratTerpenuhi(r) {
  return r.selesai && (!r.latihanTersedia || (r.nilaiLatihan ?? 0) >= r.batas)
}
function labelSyarat(r) {
  const parts = []
  parts.push(r.selesai ? 'Materi selesai' : 'Belum tandai selesai')
  if (r.latihanTersedia) {
    parts.push(
      (r.nilaiLatihan ?? 0) >= r.batas
        ? `Nilai latihan ${Math.round(r.nilaiLatihan)} ≥ ${r.batas}`
        : `Nilai latihan ${r.nilaiLatihan == null ? 'belum ada' : Math.round(r.nilaiLatihan)} < ${r.batas}`,
    )
  } else {
    parts.push('Latihan belum tersedia')
  }
  return parts
}

function kePretest() {
  router.push({ name: 'siswa.pretest' })
}
function keMateri() {
  router.push({ name: 'siswa.materi' })
}

/* ---------- Hasil terakhir ---------- */
const hasilTerakhir = computed(() => progress.data.hasilTerakhir)
const skor = computed(() => {
  const n = hasilTerakhir.value?.nilai
  return n == null ? 0 : Math.max(0, Math.min(100, n))
})
const R = 36
const keliling = 2 * Math.PI * R
const offset = computed(() => keliling * (1 - skor.value / 100))

function formatTanggal(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  return Number.isNaN(d.getTime())
    ? '—'
    : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

/* ---------- Modal aturan ---------- */
const modalTerbuka = ref(false)
const simulasiDipilih = ref(null)
const setuju = ref(false)
const modalEl = ref(null)

const aturan = computed(() => {
  const menit = simulasiDipilih.value?.durasiMenit ?? 0
  return [
    { judul: 'Timer Berjalan Otomatis', isi: 'Timer dihitung dari batas waktu server dan berjalan sejak simulasi dimulai. Reload tidak menambah waktu.' },
    { judul: 'Penyimpanan Jawaban Otomatis (Autosave)', isi: 'Setiap jawaban yang kamu isi otomatis tersimpan ke server. Killing internet tidak menghapus jawaban yang sudah terkirim.' },
    { judul: 'Navigasi Bebas Antarsoal', isi: 'Kamu bebas berpindah antarsoal kapan saja melalui panel nomor soal.' },
    { judul: 'Pengumpulan Otomatis oleh Sistem', isi: `Simulasi dikumpulkan otomatis tepat saat waktu ${menit} menit habis.` },
    { judul: 'Kestabilan Koneksi Jaringan', isi: 'Pastikan koneksi internet tetap aktif dan stabil selama pengerjaan.' },
  ]
})

function bukaModal(s) {
  simulasiDipilih.value = s
  setuju.value = false
  modalTerbuka.value = true
}
function tutupModal() {
  modalTerbuka.value = false
}
function mulaiUjian() {
  if (!setuju.value || !simulasiDipilih.value) return
  const s = simulasiDipilih.value
  modalTerbuka.value = false
  router.push({ name: 'siswa.ujian', params: { simulasiId: s.id } })
}

watch(modalTerbuka, (buka) => {
  document.body.style.overflow = buka ? 'hidden' : ''
  if (buka) nextTick(() => modalEl.value?.focus())
})
function onKeydown(e) {
  if (e.key === 'Escape' && modalTerbuka.value) tutupModal()
}

/* ---------- Navigasi ---------- */
function lihatRiwayat() {
  router.push({ name: 'siswa.riwayat' })
}
function lihatHasil(hasilId) {
  if (!hasilId) {
    lihatRiwayat()
    return
  }
  router.push({ name: 'siswa.simulasi.hasil', params: { hasilId } })
}
function lihatPembahasan(hasilId) {
  if (!hasilId) {
    lihatRiwayat()
    return
  }
  router.push({ name: 'siswa.simulasi.review', params: { hasilId } })
}

onMounted(async () => {
  await progress.fetchDashboard().catch(() => {})
  try {
    await pretest.muatTingkat({ force: true })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal memuat daftar tingkat', detail: pesanError(err), life: 4000 })
  }
  // Default: tingkat terbuka pertama yang belum lulus (yang belum segera datang).
  const kandidat =
    levels.value.find((l) => bolehPilih(l) && l.tahap !== 'LULUS') ?? levels.value.find(bolehPilih)
  if (kandidat) pilihLevel(kandidat)
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="halaman">
    <header class="topbar">
      <div>
        <h1>{{ route.meta.title || 'Simulasi Seleksi' }}</h1>
        <p>Rasakan kondisi ujian sesungguhnya, sesuai standar TOKI</p>
      </div>
      <UserMenu />
    </header>

    <div class="konten">
      <!-- 01 Pilih tingkat -->
      <section>
        <span class="eyebrow">01 · Pilih Tingkat</span>
        <h2>Pilih Tingkat Simulasi</h2>
        <p class="sub">Pilih tingkat untuk melihat simulasi dan syarat mengikutinya.</p>

        <div class="grid-level">
          <article
            v-for="l in levels"
            :key="l.id"
            class="kartu level"
            :class="[
              terkunciSegera(l) ? 'is-segera' : !l.terbuka ? 'is-terkunci' : l.tahap === 'LULUS' ? 'is-selesai' : 'is-berjalan',
              levelDipilihId === l.id ? 'is-dipilih' : '',
            ]"
          >
            <span
              class="badge"
              :class="terkunciSegera(l) ? 'badge-segera' : !l.terbuka ? 'badge-terkunci' : l.tahap === 'LULUS' ? 'badge-selesai' : 'badge-berjalan'"
            >
              <template v-if="terkunciSegera(l)">⏳ </template>
              <template v-else-if="!l.terbuka">🔒 </template>
              {{ terkunciSegera(l) ? 'Segera' : !l.terbuka ? 'Terkunci' : l.tahap === 'LULUS' ? 'Sudah Lulus' : l.tahap === 'SIMULASI_BERJALAN' ? 'Simulasi Berjalan' : 'Tersedia' }}
            </span>
            <h3>{{ l.nama }}</h3>
            <p class="desc">{{ l.deskripsi || 'Tahap seleksi OSN.' }}</p>
            <p v-if="terkunciSegera(l)" class="catatan-segera">
              Simulasi tingkat ini sedang dipersiapkan. Nanti bisa diakses di sini.
            </p>
            <div class="info"><span>{{ l.tahap ?? '—' }}</span></div>
            <button
              type="button"
              class="btn"
              :class="bolehPilih(l) ? 'btn-emas' : 'btn-mati'"
              :disabled="!bolehPilih(l)"
              @click="pilihLevel(l)"
            >
              {{ terkunciSegera(l) ? 'Segera Hadir' : l.terbuka ? (l.tahap === 'LULUS' ? 'Lihat Riwayat' : 'Pilih Tingkat') : 'Terkunci' }}
            </button>
          </article>
        </div>

        <p v-if="!levels.length" class="sub mt-3">Belum ada data tingkat. Coba muat ulang halaman.</p>
      </section>

      <hr />

      <!-- 02 Simulasi di tingkat terpilih -->
      <section v-if="levelDipilih">
        <span class="eyebrow">02 · Simulasi</span>
        <h2>Simulasi Tingkat {{ levelDipilih.nama }}</h2>
        <p class="sub">
          Kuota tersisa: {{ daftarSimulasi[0]?.sisaKuota ?? '—' }} percobaan.
        </p>

        <!-- SYARAT -->
        <div class="kartu syarat-kartu">
          <div class="syarat-kepala">
            <h3>
              Syarat Mengikuti Simulasi
              <span class="syarat-status" :class="syarat?.terpenuhi ? 'ok' : 'belum'">
                {{ syarat?.terpenuhi ? 'Terpenuhi' : 'Belum Terpenuhi' }}
              </span>
            </h3>
            <button type="button" class="btn-link" @click="keMateri">Buka halaman Materi →</button>
          </div>

          <p v-if="syarat?.alasan === 'belum_pretest'" class="syarat-alasan">
            Kamu belum menyelesaikan pre-test di tingkat ini.
            <button type="button" class="btn-link inline" @click="kePretest">Ambil pre-test →</button>
          </p>

          <ul v-if="syarat?.rincian?.length" class="syarat-daftar">
            <li
              v-for="r in syarat.rincian"
              :key="r.materiId"
              :class="syaratTerpenuhi(r) ? 'ok' : 'belum'"
            >
              <span class="syarat-nama">
                {{ r.judul }}
                <em v-if="r.prioritas">· prioritas {{ r.prioritas }}</em>
              </span>
              <span class="syarat-butir">
                <span v-for="(t, i) in labelSyarat(r)" :key="t">
                  {{ i > 0 ? ' · ' : '' }}{{ t }}
                </span>
              </span>
            </li>
          </ul>
          <p v-else-if="syarat && !syarat.rincian.length" class="sub">
            Tidak ada materi wajib di tingkat ini.
          </p>
        </div>

        <!-- DAFTAR SIMULASI -->
        <div v-if="daftarSimulasi.length" class="grid-simulasi">
          <article v-for="s in daftarSimulasi" :key="s.id" class="kartu simulasi-kartu">
            <div class="simulasi-atas">
              <h3>{{ s.nama }}</h3>
              <span class="badge" :class="s.aktif ? 'badge-berjalan' : 'badge-terkunci'">
                {{ s.aktif ? 'Aktif' : 'Tidak Aktif' }}
              </span>
            </div>
            <div class="info">
              <span>📝 {{ s.jumlahSoal }} soal</span>
              <span>⏱ {{ s.durasiMenit }} menit</span>
            </div>
            <p v-if="alasanMulaiNonaktif(s)" class="simulasi-alasan">{{ alasanMulaiNonaktif(s) }}</p>
            <button
              v-if="sudahLulus"
              type="button"
              class="btn btn-hijau"
              @click="lihatRiwayat"
            >
              Sudah Lulus — Lihat Riwayat
            </button>
            <button
              v-else
              type="button"
              class="btn"
              :class="bisaMulai(s) ? 'btn-emas' : 'btn-mati'"
              :disabled="!bisaMulai(s)"
              @click="bukaModal(s)"
            >
              {{ simulasiBerjalan ? 'Lanjutkan Simulasi →' : 'Mulai Simulasi →' }}
            </button>
          </article>
        </div>
        <p v-else-if="simulasi.loading" class="sub mt-3">Memuat daftar simulasi…</p>
        <p v-else class="sub mt-3">Belum ada simulasi untuk tingkat ini.</p>
      </section>

      <hr class="hr-lebar" />

      <!-- 03 Hasil terakhir -->
      <section>
        <span class="eyebrow">03 · Hasil Terakhir</span>
        <h2>Hasil Simulasi Terakhir</h2>
        <p class="sub">Ringkasan nilai dari simulasi terakhirmu.</p>

        <div v-if="hasilTerakhir" class="kartu hasil">
          <div class="hasil-atas">
            <div class="ring">
              <svg viewBox="0 0 88 88" width="80" height="80" aria-hidden="true">
                <circle cx="44" cy="44" :r="R" fill="none" stroke="#f1f3f7" stroke-width="8" />
                <circle cx="44" cy="44" :r="R" fill="none" stroke="#f0a30f" stroke-width="8" stroke-linecap="round"
                  :stroke-dasharray="keliling" :stroke-dashoffset="offset" transform="rotate(-90 44 44)" />
              </svg>
              <div class="ring-teks"><strong>{{ Math.round(skor) }}</strong><small>Skor</small></div>
            </div>
            <div>
              <h3>{{ hasilTerakhir.judul }}</h3>
              <p class="meta">{{ formatTanggal(hasilTerakhir.tanggal) }}</p>
              <p class="meta">
                {{ hasilTerakhir.lulus ? 'Lulus' : 'Belum lulus' }}
              </p>
            </div>
          </div>
          <div class="hasil-kaki">
            <button type="button" class="btn-auto btn-hijau" @click="lihatHasil(hasilTerakhir.id)">
              Lihat Hasil →
            </button>
            <button type="button" class="btn-auto btn-emas" @click="lihatPembahasan(hasilTerakhir.id)">
              Lihat Pembahasan Soal →
            </button>
          </div>
        </div>

        <div v-else class="kartu kosong">
          <p>Belum ada hasil simulasi. Selesaikan satu simulasi untuk melihat ringkasannya di sini.</p>
          <button type="button" class="btn-auto btn-emas" @click="lihatRiwayat">Buka Riwayat →</button>
        </div>
      </section>
    </div>

    <!-- Modal aturan simulasi -->
    <Teleport to="body">
      <Transition name="fade-modal">
        <div v-if="modalTerbuka && simulasiDipilih" class="overlay" @click.self="tutupModal">
          <div
            ref="modalEl"
            class="modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="judul-modal"
            tabindex="-1"
          >
            <div class="modal-kepala">
              <h3 id="judul-modal" class="modal-judul"><i class="titik-emas"></i>Detail Simulasi</h3>
              <span class="pil-status">Tersedia untuk Dikerjakan</span>
            </div>

            <div class="grid-info">
              <div class="kartu-info">
                <span class="label-info">Tingkat Seleksi</span>
                <b class="nilai-info">{{ levelDipilih?.nama ?? '—' }}</b>
              </div>
              <div class="kartu-info">
                <span class="label-info">Jumlah Soal</span>
                <span class="nilai-info"><b>{{ simulasiDipilih.jumlahSoal }}</b> Soal</span>
                <span class="sub-info">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" /></svg>
                  Bank Soal Acak
                </span>
              </div>
              <div class="kartu-info">
                <span class="label-info">Durasi Waktu</span>
                <span class="nilai-info"><b>{{ simulasiDipilih.durasiMenit }}</b> Menit</span>
                <span class="sub-info">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="8" /><path d="M12 9v4l2 2M9 2h6" /></svg>
                  Batas Waktu Server
                </span>
              </div>
              <div class="kartu-info">
                <span class="label-info">Sisa Kuota</span>
                <b class="nilai-info">{{ simulasiDipilih.sisaKuota ?? '—' }} percobaan</b>
              </div>
            </div>

            <hr class="garis-modal" />

            <h4 class="judul-aturan">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l8 3v6c0 5-3.5 9-8 11-4.5-2-8-6-8-11V5z" /><path d="M9 12l2 2 4-4" /></svg>
              Aturan dan Ketentuan Simulasi
            </h4>
            <ol class="aturan">
              <li v-for="(a, i) in aturan" :key="a.judul">
                <span class="nomor">{{ i + 1 }}</span>
                <div>
                  <p class="aturan-judul">{{ a.judul }}</p>
                  <p class="aturan-isi">{{ a.isi }}</p>
                </div>
              </li>
            </ol>

            <label class="setuju">
              <input v-model="setuju" type="checkbox" />
              <span>Saya telah membaca dan memahami aturan simulasi.</span>
            </label>

            <div class="modal-kaki">
              <button type="button" class="btn-modal btn-batal" @click="tutupModal">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7" /></svg>
                Batal
              </button>
              <button type="button" class="btn-modal btn-mulai" :disabled="!setuju" @click="mulaiUjian">
                Mulai Simulasi
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.halaman { min-height: 100%; background: #f7f9fc; }
.topbar {
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  background: #fff; border-bottom: 1px solid #e6ebf2; padding: 18px 28px;
}
.topbar h1 { font-size: 20px; font-weight: 700; line-height: 1.25; margin: 0; color: #0f1b33; }
.topbar p { font-size: 14px; color: #6b778c; margin: 4px 0 0; }

.konten {
  --emas: #f59e0b;
  --emas-tua: #d9780a;
  --hijau: #12805a;
  --merah: #e11d48;
  --ink: #0f1b33;
  --abu: #6b778c;
  --garis: #e6e9f0;
  color: var(--ink);
  padding: 32px 28px 56px;
}
.konten, .konten *, .konten *::before, .konten *::after { box-sizing: border-box; }

.eyebrow { display: block; font-size: 12px; font-weight: 700; letter-spacing: .02em; color: var(--emas-tua); text-transform: uppercase; }
h2 { font-size: 26px; font-weight: 700; line-height: 1.25; margin: 4px 0 2px; }
h3 { font-size: 16px; font-weight: 700; margin: 0; }
.sub { font-size: 14px; color: var(--abu); margin: 0 0 14px; }
hr { border: 0; border-top: 1px solid var(--garis); margin: 32px 0; }
.hr-lebar { margin: 56px 0 32px; }

.kartu { background: #fff; border: 1px solid var(--garis); border-radius: 16px; padding: 24px; }

/* Level */
.grid-level { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; align-items: stretch; }
.level { display: flex; flex-direction: column; gap: 10px; box-shadow: 0 1px 3px rgba(15, 27, 51, .05); }
.level.is-berjalan { border: 2px solid var(--emas); padding: 23px; box-shadow: 0 6px 20px rgba(245, 158, 11, .15); }
.level.is-dipilih { outline: 2px solid var(--emas); outline-offset: 1px; }
.level h3 { font-size: 20px; margin-top: 8px; }
.badge { align-self: flex-start; font-size: 12px; font-weight: 500; padding: 4px 10px; border-radius: 99px; white-space: nowrap; }
.badge-selesai { background: #e7f6ef; color: var(--hijau); }
.badge-berjalan { background: #fdf1d3; color: #92400e; }
.badge-terkunci { background: #eef1f6; color: var(--abu); }
.badge-segera { background: #fdf1d3; color: #92400e; }
.level.is-segera { border-style: dashed; background: #fffdf7; }
.catatan-segera { margin: 0; font-size: 13px; line-height: 1.5; color: #92400e; }
.desc { font-size: 13px; color: var(--abu); line-height: 1.5; margin: 0; max-width: 260px; flex: 1; }
.info { display: flex; flex-wrap: nowrap; gap: 16px; font-size: 13px; color: var(--ink); margin: 6px 0 8px; white-space: nowrap; }

/* Syarat */
.syarat-kartu { margin-bottom: 24px; }
.syarat-kepala { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.syarat-kepala h3 { display: flex; align-items: center; gap: 10px; }
.syarat-status { font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 99px; }
.syarat-status.ok { background: #e7f6ef; color: var(--hijau); }
.syarat-status.belum { background: #fde8ec; color: var(--merah); }
.syarat-alasan { margin: 12px 0 0; font-size: 13px; color: #92400e; background: #fdf1d3; padding: 10px 14px; border-radius: 10px; }
.syarat-daftar { list-style: none; margin: 14px 0 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.syarat-daftar li {
  display: flex; align-items: baseline; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  border: 1px solid var(--garis); border-radius: 10px; padding: 10px 14px; font-size: 13px;
}
.syarat-daftar li.ok { background: #f7fdfa; border-color: #cdeedd; }
.syarat-daftar li.belum { background: #fffafb; border-color: #f9c4cf; }
.syarat-nama { font-weight: 600; }
.syarat-nama em { font-style: normal; font-weight: 400; color: var(--abu); font-size: 12px; }
.syarat-butir { color: var(--abu); font-size: 12.5px; }
.btn-link { background: none; border: 0; padding: 0; font: inherit; font-size: 13px; font-weight: 600; color: var(--emas-tua); cursor: pointer; text-decoration: underline; }
.btn-link.inline { display: inline; }

/* Simulasi */
.grid-simulasi { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.simulasi-kartu { display: flex; flex-direction: column; gap: 10px; }
.simulasi-atas { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.simulasi-alasan { margin: 0; font-size: 12.5px; color: #92400e; }

/* Tombol */
.btn {
  width: 100%; border: 0; border-radius: 10px; padding: 11px 14px;
  font: inherit; font-size: 13px; font-weight: 600; line-height: 1.2; white-space: nowrap; cursor: pointer;
  transition: background .15s ease;
}
.btn-hijau { background: #e9f7f0; color: var(--hijau); }
.btn-hijau:hover { background: #dcf1e7; }
.btn-emas { background: var(--emas); color: #1a1a1a; }
.btn-emas:hover { background: #f7ae2e; }
.btn-mati { background: #eef1f6; color: var(--abu); cursor: not-allowed; }
.btn:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }
.btn-auto { width: auto; padding: 11px 20px; border-radius: 8px; border: 0; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-auto:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }

/* Hasil */
.hasil { padding: 28px; }
.hasil-atas { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
.hasil-kaki { display: flex; gap: 10px; flex-wrap: wrap; }
.ring { position: relative; width: 80px; height: 80px; flex: none; }
.ring-teks { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.ring-teks strong { font-size: 17px; line-height: 1.1; }
.ring-teks small { font-size: 11px; color: var(--abu); }
.hasil-atas h3 { font-size: 17px; }
.meta { font-size: 13px; color: var(--abu); margin: 4px 0 0; }
.kosong { display: flex; flex-direction: column; align-items: flex-start; gap: 14px; }
.kosong p { margin: 0; font-size: 14px; color: var(--abu); }

/* Modal detail simulasi (dirender di <body>, jadi punya variabel sendiri) */
.overlay {
  --emas: #f59e0b;
  --ink: #0f1b33;
  --garis: #e6e9f0;
  position: fixed; inset: 0; z-index: 1000;
  display: flex; padding: 24px 16px;
  background: rgba(15, 27, 51, .55);
  overflow-y: auto;
}
.overlay, .overlay *, .overlay *::before, .overlay *::after { box-sizing: border-box; }

.modal {
  width: 100%; max-width: 960px; margin: auto;
  background: #fff; color: var(--ink); border-radius: 14px;
  padding: 32px 44px 28px;
  box-shadow: 0 24px 60px rgba(15, 27, 51, .3);
  outline: none;
}

.modal svg {
  width: 14px; height: 14px; flex: none; fill: none; stroke: currentColor;
  stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
}

.modal-kepala { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.modal-judul { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; margin: 0; }
.titik-emas { width: 8px; height: 8px; border-radius: 50%; background: var(--emas); display: inline-block; }

.pil-status {
  display: inline-flex; align-items: center; gap: 6px;
  background: #fde3b8; color: #5c3200; font-size: 12.5px; font-weight: 500;
  padding: 6px 12px; border-radius: 99px;
}
.pil-status::before { content: ''; width: 4px; height: 4px; border-radius: 50%; background: currentColor; }

.grid-info { display: grid; grid-template-columns: 1.08fr 1fr 1fr .94fr; gap: 12px; }
.kartu-info {
  border: 1px solid var(--garis); border-radius: 12px; padding: 14px 20px;
  display: flex; flex-direction: column; gap: 7px;
}
.label-info { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
.nilai-info { font-size: 13.5px; }
.nilai-info b, b.nilai-info { font-weight: 700; }
.sub-info { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #3c4658; }

.garis-modal { border: 0; border-top: 1px solid var(--garis); margin: 22px 0; }

.judul-aturan { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; margin: 0 0 14px; }
.judul-aturan svg { color: #b7791f; }

.aturan { list-style: none; margin: 0; padding: 22px 26px; background: #f5f8fe; border-radius: 12px; display: flex; flex-direction: column; gap: 10px; }
.aturan li { display: flex; gap: 12px; align-items: flex-start; }
.nomor {
  flex: none; width: 24px; height: 24px; border-radius: 50%;
  display: grid; place-items: center; background: #e4ecf9; font-size: 12px; font-weight: 700;
}
.aturan p { margin: 0; font-size: 13px; line-height: 1.5; max-width: 640px; }
.aturan-judul { font-weight: 600; }
.aturan-isi { color: #2b3447; }

.setuju { display: flex; align-items: center; gap: 10px; margin: 20px 0 0 10px; font-size: 13px; font-weight: 500; cursor: pointer; width: fit-content; }
.setuju input { width: 16px; height: 16px; margin: 0; accent-color: var(--emas); cursor: pointer; }

.modal-kaki { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 28px; }

.btn-modal {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  border: 0; border-radius: 8px; padding: 9px 18px;
  font: inherit; font-size: 13px; font-weight: 600; line-height: 1.2; cursor: pointer;
  transition: background .15s ease, opacity .15s ease;
}
.btn-batal { background: #b91c1c; color: #fff; padding: 9px 16px; }
.btn-batal:hover { background: #a11818; }
.btn-mulai { background: var(--emas); color: #1a1a1a; }
.btn-mulai:hover:not(:disabled) { background: #f7ae2e; }
.btn-mulai:disabled { opacity: .45; cursor: not-allowed; }

.btn-modal:focus-visible, .setuju input:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }

.fade-modal-enter-active, .fade-modal-leave-active { transition: opacity .18s ease; }
.fade-modal-enter-active .modal, .fade-modal-leave-active .modal { transition: transform .18s ease; }
.fade-modal-enter-from, .fade-modal-leave-to { opacity: 0; }
.fade-modal-enter-from .modal, .fade-modal-leave-to .modal { transform: translateY(8px); }

@media (prefers-reduced-motion: reduce) {
  .fade-modal-enter-active, .fade-modal-leave-active,
  .fade-modal-enter-active .modal, .fade-modal-leave-active .modal { transition: none; }
}

/* Responsif */
@media (max-width: 1000px) {
  .modal { padding: 28px 24px 24px; }
  .grid-info { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
  .topbar { padding: 14px 16px; }
  .konten { padding: 22px 16px 40px; }
  h2 { font-size: 22px; }
  .grid-level { grid-template-columns: 1fr; gap: 16px; }
  .hasil { padding: 20px; }
  .hasil-kaki { flex-direction: column; align-items: stretch; }
  .btn-auto { width: 100%; }
}

@media (max-width: 560px) {
  .overlay { padding: 12px 8px; }
  .modal { padding: 20px 14px 16px; }
  .grid-info { grid-template-columns: 1fr; }
  .kartu-info { padding: 12px 16px; }
  .aturan { padding: 16px 14px; }
  .setuju { margin-left: 0; align-items: flex-start; }
  .modal-kaki { margin-top: 22px; }
}
</style>
