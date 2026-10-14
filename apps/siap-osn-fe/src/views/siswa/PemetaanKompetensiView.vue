<script setup>
import { computed, onMounted, ref, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UserMenu from '@/components/UserMenu.vue'
import { riwayatService } from '@/services/riwayat.js'
import { useMateriStore } from '@/stores/materi.js'
import { usePretestStore } from '@/stores/pretest.js'
import { STATUS } from '@/stores/pretest.js'

// Hasil dibaca dari store pre-test. Route memakai id opsional: setelah
// submit sukses id tersimpan dihapus, jadi tanpa id halaman ini harus
// menemukan sendiri pre-test terakhir lewat riwayat — kalau tidak,
// refresh akan selalu kosong.
const route = useRoute()
const router = useRouter()
const pretest = usePretestStore()
const materi = useMateriStore()

const memuat = ref(true)

onMounted(async () => {
  const idDariUrl = Number(route.params.id)

  // Store masih hangat (pindah halaman tanpa reload).
  if (!idDariUrl && pretest.hasil) {
    memuat.value = false
    return
  }

  try {
    if (Number.isFinite(idDariUrl) && idDariUrl > 0) {
      await pretest.lanjutkan({ id: idDariUrl })
      // Hasil belum keluar (pretest masih dikerjakan): kembalikan ke ujian.
      if (pretest.status === STATUS.MENGERJAKAN) {
        router.replace({ name: 'siswa.pretest' })
        return
      }
    } else {
      // Tanpa id: pakai pre-test terakhir dari riwayat.
      const { items } = await riwayatService.daftar({ jenis: 'pretest', perPage: 1 })
      const terakhir = items[0]?.referensiId
      if (terakhir) {
        router.replace({ name: 'siswa.pemetaan', params: { id: terakhir } })
        return
      }
      // Tidak ada riwayat sama sekali -> kondisi kosong di bawah.
    }
  } catch {
    // Gagal (404/403): tampilkan kondisi kosong di bawah.
  }
  memuat.value = false
})

const hasil = computed(() => pretest.hasil)

// Nama materi asli dari GET /api/materi (bukan lagi "Materi {id}").
const namaMateriById = computed(
  () => new Map(materi.daftar.map((m) => [m.id, m.judul])),
)
const prioritasWajib = computed(
  () => new Map((hasil.value?.materiWajib ?? []).map((m) => [m.materiId, m.prioritas])),
)

async function muatJudulMateri() {
  const tingkatId = hasil.value?.tingkatId
  if (!tingkatId || materi.tingkatId === tingkatId) return
  await materi.fetchDaftar({ tingkatId }).catch(() => {})
}

// Judul materi dibutuhkan untuk merender tabel, jadi dipanggil begitu
// hasil tersedia (onMounted mungkin selesai sebelum hasil ada).
watchEffect(() => {
  if (hasil.value) muatJudulMateri()
})

const skor = computed(() => (hasil.value?.nilai == null ? 0 : Math.round(hasil.value.nilai)))
const R = 44
const keliling = 2 * Math.PI * R
const garis = computed(() => (keliling * skor.value) / 100)

const namaTingkat = computed(
  () => pretest.tingkatList.find((t) => t.id === hasil.value?.tingkatId)?.nama ?? '',
)
const totalSoal = computed(() =>
  (hasil.value?.pemetaan ?? []).reduce((a, p) => a + p.jumlahSoal, 0),
)
const totalBenar = computed(() =>
  (hasil.value?.pemetaan ?? []).reduce((a, p) => a + p.jumlahBenar, 0),
)

// Fallback "Materi {id}" hanya sampai judulnya termuat.
const namaMateri = (materiId) => namaMateriById.value.get(materiId) ?? `Materi ${materiId}`

const ringkasanTopik = computed(() =>
  (hasil.value?.pemetaan ?? []).map((p) => ({
    materiId: p.materiId,
    nama: namaMateri(p.materiId),
    wajib: prioritasWajib.value.get(p.materiId) ?? null,
    benar: `${p.jumlahBenar}/${p.jumlahSoal}`,
    status: statusNilai(p.persentase),
  })),
)

const topik = computed(() =>
  (hasil.value?.pemetaan ?? []).map((p) => ({
    materiId: p.materiId,
    nama: namaMateri(p.materiId),
    wajib: prioritasWajib.value.get(p.materiId) ?? null,
    nilai: p.persentase == null ? 0 : Math.round(p.persentase),
  })),
)
const statusNilai = (n) => (n >= 70 ? 'ok' : n >= 40 ? 'mid' : 'low')
const labelStatus = { ok: 'Dikuasai', mid: 'Perlu latihan', low: 'Belum dikuasai' }

const terkuat = computed(() =>
  topik.value.length ? [...topik.value].sort((a, b) => b.nilai - a.nilai)[0] : null,
)
const terlemah = computed(() =>
  topik.value.length ? [...topik.value].sort((a, b) => a.nilai - b.nilai)[0] : null,
)
const jumlahWajib = computed(() => hasil.value?.materiWajib?.length ?? 0)

function formatTanggal(iso) {
  if (!iso) return ''
  const t = new Date(iso)
  if (Number.isNaN(t.getTime())) return ''
  return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(t)
}
const tanggalSubmit = computed(() => formatTanggal(hasil.value?.disubmitPada) || '—')

// P2: diisi dari GET /api/riwayat.
const riwayat = computed(() => [])
const teksTren = { naik: '↑ Naik', turun: '↓ Turun', stabil: '→ Stabil' }
</script>

<template>
  <div class="min-h-full bg-[#f5f8fc] text-[#0f1b33]">
    <header class="flex items-center justify-between border-b border-[#e6ebf2] bg-white px-7 py-4">
      <h1 class="text-[17px] font-bold leading-tight">{{ route.meta.title }}</h1>
      <UserMenu />
    </header>

    <main class="px-6 py-6">
      <div class="pk">

        <div v-if="memuat" class="card pad kosong">
          <h3>Memuat hasil pre-test…</h3>
        </div>
        <div v-else-if="!hasil" class="card pad kosong">
          <h3>Belum ada hasil pre-test</h3>
          <p class="desc">Ikuti pre-test terlebih dahulu untuk melihat pemetaan kompetensimu.</p>
          <button type="button" class="btn-amber" @click="router.push({ name: 'siswa.pretest' })">Ikuti Pre-Test →</button>
        </div>
        <template v-else>

        <!-- 01 -->
        <section class="section">
          <p class="eyebrow">01 · HASIL PRE-TEST</p>
          <h2>Hasil Pre-Test Terakhir{{ namaTingkat ? ` ${namaTingkat}` : '' }}</h2>
          <p class="desc">Ringkasan skor dan ketepatan jawabanmu pada pre-test paling baru.</p>

          <div class="grid-2">
            <div class="card pad">
              <h3>Skor Keseluruhan</h3>
              <div class="skor-wrap">
                <div class="donut">
                  <svg viewBox="0 0 100 100" aria-hidden="true">
                    <circle cx="50" cy="50" :r="R" class="d-track" />
                    <circle
                      cx="50" cy="50" :r="R" class="d-fill"
                      :stroke-dasharray="`${garis} ${keliling}`"
                      transform="rotate(-90 50 50)"
                    />
                  </svg>
                  <div class="donut-text"><strong>{{ skor }}%</strong><span>Skor</span></div>
                </div>
                <div>
                  <p class="pt-title">Pre-Test Tingkat {{ namaTingkat || '—' }}</p>
                  <p class="pt-meta">Dikerjakan {{ tanggalSubmit }} · {{ totalSoal }} soal</p>
                  <div class="chips">
                    <span v-if="namaTingkat" class="chip blue">Tingkat {{ namaTingkat }}</span>
                    <span class="chip amber">{{ totalBenar }} dari {{ totalSoal }} benar</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="card pad">
              <h3>Ringkasan Per Topik</h3>
              <ul class="ringkas">
                <li v-for="t in ringkasanTopik" :key="t.materiId">
                  <span>
                    {{ t.nama }}
                    <em v-if="t.wajib" class="wajib">Wajib · prioritas {{ t.wajib }}</em>
                  </span>
                  <span><b :class="t.status">{{ t.benar }}</b> benar</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- 02 -->
        <section class="section">
          <p class="eyebrow">02 · GRAFIK PER TOPIK</p>
          <h2>Peta Kompetensi Saat Ini</h2>
          <p class="desc">Persentase penguasaan tiap topik, dihitung dari gabungan pre-test, latihan, dan simulasi.</p>

          <div class="card pad">
            <div class="bar-head">
              <h3>Semua Topik</h3>
              <div class="legend">
                <span><i class="dot ok"></i>Dikuasai (≥70%)</span>
                <span><i class="dot mid"></i>Perlu latihan (40–69%)</span>
                <span><i class="dot low"></i>Belum dikuasai (&lt;40%)</span>
              </div>
              <button type="button" class="link-btn">Unduh laporan</button>
            </div>

            <div v-for="t in topik" :key="t.materiId" class="bar-row">
              <span class="bar-name">
                {{ t.nama }}
                <em v-if="t.wajib" class="wajib">Wajib · prioritas {{ t.wajib }}</em>
              </span>
              <div class="track"><div class="fill" :class="statusNilai(t.nilai)" :style="{ width: t.nilai + '%' }"></div></div>
              <span class="bar-val">{{ t.nilai }}%</span>
              <span class="bar-label" :class="statusNilai(t.nilai)">{{ labelStatus[statusNilai(t.nilai)] }}</span>
            </div>
          </div>

          <div class="card pad analisis">
            <h3 class="big">Analisis Hasil Kompetensi</h3>
            <p class="desc tight">Ringkasan berdasarkan hasil pemetaan kompetensimu saat ini.</p>

            <div class="point">
              <i class="dot ok"></i>
              <div>
                <p class="point-tag ok">KEKUATAN UTAMA</p>
                <p v-if="terkuat">{{ terkuat.nama }} menjadi topik terkuatmu dengan penguasaan {{ terkuat.nilai }}%.</p>
                <p v-else>Belum ada data topik.</p>
              </div>
            </div>
            <div class="point">
              <i class="dot low"></i>
              <div>
                <p class="point-tag low">PERLU DIPRIORITASKAN</p>
                <p v-if="terlemah">{{ terlemah.nama }} memiliki penguasaan terendah sebesar {{ terlemah.nilai }}%.</p>
                <p v-else>Belum ada data topik.</p>
              </div>
            </div>
            <div class="point">
              <i class="dot info"></i>
              <div>
                <p class="point-tag info">SARAN BELAJAR</p>
                <p v-if="terlemah">Fokuskan latihan berikutnya pada {{ terlemah.nama }}{{ jumlahWajib ? ` — ada ${jumlahWajib} materi wajib menunggumu` : '' }} sebelum melanjutkan ke topik lain.</p>
                <p v-else>Ikuti materi yang direkomendasikan untuk meningkatkan penguasaanmu.</p>
              </div>
            </div>

            <div class="cta">
              <div>
                <p class="cta-title">Lanjutkan belajarmu</p>
                <p class="cta-desc">Lihat materi yang direkomendasikan berdasarkan topik yang perlu kamu tingkatkan.</p>
              </div>
              <button type="button" class="btn-amber" @click="router.push({ name: 'siswa.materi' })">Lihat Rekomendasi Materi →</button>
            </div>
          </div>
        </section>

        <!-- 03 -->
        <section class="section">
          <p class="eyebrow">03 · RIWAYAT PEMETAAN</p>
          <h2>Perkembangan Pemetaan dari Waktu ke Waktu</h2>
          <p class="desc">Bandingkan skor pemetaan kompetensimu di setiap percobaan sebelumnya.</p>

          <div class="card pad">
            <h3>Riwayat Pemetaan Kompetensi</h3>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Tanggal</th><th>Tingkat</th><th>Skor Keseluruhan</th>
                    <th>Topik Terlemah</th><th>Tren</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!riwayat.length">
                    <td colspan="5" class="riwayat-kosong">Riwayat tampil setelah integrasi tahap berikutnya.</td>
                  </tr>
                  <tr v-for="r in riwayat" :key="r.tgl">
                    <td>{{ r.tgl }}</td>
                    <td>{{ r.tingkat }}</td>
                    <td><b>{{ r.skor }}</b></td>
                    <td>{{ r.lemah }}</td>
                    <td><span class="badge" :class="r.tren">{{ teksTren[r.tren] }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        </template>
      </div>
    </main>
  </div>
</template>

<style scoped>
.pk {
  --ok: #12b27a;
  --mid: #3b82f6;
  --low: #ef4444;
  --amber: #f59e0b;
  --gold-dark: #c8921a;
  --muted: #6b7686;
  --line: #e6ebf2;

  font-size: 13px;
}

.section { padding: 22px 0 18px; }
.section:first-child { padding-top: 0; }
.eyebrow { margin: 0 0 4px; font-size: 10.5px; font-weight: 700; letter-spacing: 0.04em; color: var(--gold-dark); }
.section h2 { margin: 0; font-size: 17px; font-weight: 600; }
.desc { margin: 3px 0 14px; color: var(--muted); font-size: 12px; }
.desc.tight { margin-bottom: 8px; }

.card { background: #fff; border: 1px solid var(--line); border-radius: 24px; box-shadow: 0 2px 12px rgba(15, 30, 60, 0.07); }
.pad { padding: 20px 24px; }
.card h3 { margin: 0 0 14px; font-size: 13px; font-weight: 600; }
.card h3.big { font-size: 15px; margin-bottom: 4px; }
.kosong { text-align: center; padding: 40px 24px; }
.kosong h3 { font-size: 15px; }
.riwayat-kosong { text-align: center; color: var(--muted); }
.analisis { margin-top: 16px; }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* Donut */
.skor-wrap { display: flex; align-items: center; gap: 18px; margin-top: 18px; }
.donut { position: relative; width: 94px; height: 94px; flex: none; }
.donut svg { width: 100%; height: 100%; }
.d-track { fill: none; stroke: #eef0f4; stroke-width: 10; }
.d-fill { fill: none; stroke: var(--amber); stroke-width: 10; stroke-linecap: round; }
.donut-text { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; }
.donut-text strong { font-size: 20px; line-height: 1; }
.donut-text span { font-size: 10px; color: var(--muted); }
.pt-title { margin: 0; font-size: 14px; font-weight: 600; }
.pt-meta { margin: 4px 0 10px; font-size: 11px; color: var(--muted); }
.chips { display: flex; gap: 6px; flex-wrap: wrap; }
.chip { padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 600; border: 1px solid transparent; }
.chip.blue { background: #e3edff; color: #1d3f8f; }
.chip.amber { background: #fff4d6; color: #9a6b00; border-color: #f7dc8e; }

/* Ringkasan topik */
.ringkas { list-style: none; margin: 0; padding: 0; }
.ringkas li { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; font-size: 11.5px; color: #3a4558; }
.wajib { font-style: normal; font-size: 10.5px; font-weight: 600; color: var(--amber); }
.ringkas b { font-weight: 600; }
.ringkas b.ok, .bar-label.ok { color: var(--ok); }
.ringkas b.mid, .bar-label.mid { color: var(--mid); }
.ringkas b.low, .bar-label.low { color: var(--low); }

/* Grafik */
.bar-head { display: flex; align-items: center; gap: 16px; margin-bottom: 18px; }
.bar-head h3 { margin: 0; }
.legend { display: flex; gap: 12px; flex: 1; font-size: 10px; color: var(--muted); flex-wrap: wrap; }
.legend span { display: inline-flex; align-items: center; gap: 5px; }
.dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; flex: none; }
.dot.ok { background: var(--ok); }
.dot.mid { background: var(--mid); }
.dot.low { background: var(--amber); }
.dot.info { background: var(--mid); }
.link-btn { border: 0; background: none; font-size: 11px; font-weight: 600; cursor: pointer; color: inherit; }

.bar-row { display: grid; grid-template-columns: 90px 1fr 40px 100px; align-items: center; gap: 14px; padding: 6px 0; font-size: 11.5px; }
.track { height: 8px; border-radius: 999px; background: #eef0f4; overflow: hidden; }
.fill { height: 100%; border-radius: 999px; }
.fill.ok { background: var(--ok); }
.fill.mid { background: var(--mid); }
.fill.low { background: linear-gradient(90deg, var(--low), var(--amber)); }
.bar-val { text-align: right; color: #3a4558; }
.bar-label { text-align: right; font-weight: 500; }

/* Analisis */
.point { display: flex; gap: 12px; padding: 12px 0 2px; }
.point .dot { margin-top: 5px; }
.point p { margin: 0; line-height: 1.5; }
.point-tag { font-size: 10px; font-weight: 700; letter-spacing: 0.04em; margin-bottom: 2px !important; }
.point-tag.ok { color: var(--ok); }
.point-tag.low { color: var(--low); }
.point-tag.info { color: var(--mid); }
.point .dot.low { background: var(--low); }

.cta { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--line); }
.cta-title { margin: 0; font-size: 13.5px; font-weight: 600; }
.cta-desc { margin: 3px 0 0; font-size: 11.5px; color: var(--muted); }
.btn-amber { border: 0; border-radius: 8px; padding: 10px 18px; background: var(--amber); color: #1a1100; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap; }
.btn-amber:hover { filter: brightness(0.95); }

/* Tabel */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { padding: 12px 0; text-align: left; font-size: 11px; font-weight: 500; color: var(--muted); border-bottom: 1px solid var(--line); }
td { padding: 14px 0; border-bottom: 1px solid #f0f2f6; white-space: nowrap; font-size: 12px; }
tbody tr:last-child td { border-bottom: 0; }
.badge { display: inline-block; padding: 2px 9px; border-radius: 6px; font-size: 10px; font-weight: 600; }
.badge.naik { background: #dff5ea; color: #13804f; }
.badge.turun { background: #fde4e4; color: #c7383d; }
.badge.stabil { background: #eef1f6; color: #5a6578; }

@media (max-width: 860px) {
  .grid-2 { grid-template-columns: 1fr; }
  .bar-head { flex-direction: column; align-items: flex-start; gap: 8px; }
  .bar-row { grid-template-columns: 70px 1fr 36px; }
  .bar-label { display: none; }
  .cta { flex-direction: column; align-items: flex-start; }
}
</style>