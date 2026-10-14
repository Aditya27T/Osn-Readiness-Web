// Rendering konten kaya untuk teks dari backend: Markdown + TeX, lalu disanitasi.
//
// Dipakai untuk `soal.pertanyaan`, `pembahasan.isi_pembahasan`, dan
// `materi.isi_materi` yang sebelumnya dirender mentah lewat v-html. Isi bank
// soal memang berisi Markdown (`##`, `**tebal**`, list `-`, blok kode) dan
// LaTeX (`$1 \times 1$`, `$N-1$`), jadi tanpa langkah ini siswa membaca
// karakter `##` dan `**` apa adanya.
//
// Urutan langkahnya penting dan tidak boleh diacak:
//
//   1. Cabut blok matematika lebih dulu, ganti placeholder.
//   2. Render Markdown dengan html:false.
//   3. Sanitasi hasil Markdown.
//   4. Tanam ulang HTML KaTeX di dalam placeholder.
//
// Matematika dicabut sebelum Markdown karena karakter `_` dan `*` di dalam
// rumus akan dimakan jadi penanda emph/block bila dibiarkan. Sanitasi
// dijalankan SEBELUM KaTeX disisipkan supaya style dan class bawaan KaTeX
// tidak perlu diizinkan di DOMPurify -- HTML itu dihasilkan pustaka, bukan
// pengguna, sehingga tidak bisa dipakai sebagai celah.

import MarkdownIt from 'markdown-it'
import katex from 'katex'
import DOMPurify from 'dompurify'

// Teks sebelum/atasi placeholder dibungkus begini supaya tidak pernah bertabrakan
// dengan isi soal. Huruf semua, tanpa tanda baca Markdown, jadi utuh saat
// disanitasi dan tidak dirottle jadi elemen lain.
const AWAL_TOKEN = 'zqMathBarzq'
const AKHIR_TOKEN = 'zqEndzq'

// Pola pembuka: `$` diikuti karakter yang bukan spasi. Tanpa syarat ini,
// "$ 10" (mata uang) ikut dikira awal rumus.
// Penutup: `$` didahului karakter yang bukan spasi.
const POLA_MATH_BLOCK = /\$\$([^\n]+?)\$\$/g
const POLA_MATH_INLINE = /\$(?![\s$])(\S(?:[^\n$]*?\S)?)\$/g

function cabutMatematika(teks) {
  // Amankan literal yang sengaja ditulis soal memakai pola token di atas.
  // Harus SEBELUM placeholder masuk, kalau sesudahnya placeholder asli ikut
  // terganti dan semua rumus hilang.
  let hasil = teks
    .replaceAll(AWAL_TOKEN, 'zqMathEsczq')
    .replaceAll(AKHIR_TOKEN, 'zqEndEsczq')

  const simpan = []
  const ganti = (display) => (_match, isi) => {
    simpan.push({ html: katexRender(isi, display), display })
    return `${AWAL_TOKEN}${simpan.length - 1}${AKHIR_TOKEN}`
  }

  hasil = hasil
    .replace(POLA_MATH_BLOCK, ganti(true))
    .replace(POLA_MATH_INLINE, ganti(false))

  return { teks: hasil, simpan }
}

function tanamMatematika(html, simpan) {
  if (!simpan.length) return html
  return html.replace(
    new RegExp(`${AWAL_TOKEN}(\\d+)${AKHIR_TOKEN}`, 'g'),
    (_, index) => simpan[Number(index)]?.html ?? '',
  )
}

// throwOnError:false -> TeX rusak tampil merah apa adanya, bukan melempar
// error yang membuat halaman kosong. trust dibiarkan false (bawaan) supaya
// \href dan \url tidak bisa menyuntik tautan javascript: dari isi soal.
function katexRender(rumus, display) {
  try {
    return katex.renderToString(rumus, {
      displayMode: Boolean(display),
      throwOnError: false,
      output: 'htmlAndMathml',
    })
  } catch {
    // katex seharusnya tidak melempar dengan throwOnError:false, tapi kalau
    // suatu saat iya, lebih baik tampil teks mentah daripada halaman mati.
    return rumus
  }
}

// html:false -> HTML yang ada di isi soal diperlakukan sebagai TEKS, bukan
// markup. Ini yang dimaksud: bank soal punya pseudo-tag seperti <X,Y> dan
// <iostream> yang memang harus tampil sebagai karakter, bukan diperlakukan
// sebagai tag.
function buatMarkdownIt() {
  return new MarkdownIt({
    html: false,
    linkify: true,
    breaks: true,
  })
}

let md = null
function markdownIt() {
  if (!md) md = buatMarkdownIt()
  return md
}

// Konfigurasi DOMPurify disimpan sebagai konstanta supaya bisa diuji langsung.
export const KONFIG_SANITASI = {
  USE_PROFILES: { html: true, mathMl: true, svg: true },
  ADD_ATTR: ['target', 'rel'],
}

function sanitasi(html) {
  return DOMPurify.sanitize(html, KONFIG_SANITASI)
}

// Render blok: untuk isi materi dan pembahasan (bisa berisi heading, list,
// blok kode). Hasilnya boleh membungkus <p>, jadi jangan taruh di dalam <p>
// atau <h3>.
export function renderKonten(teks) {
  if (teks === null || teks === undefined) return ''
  const asal = String(teks)
  if (!asal.trim()) return ''

  const { teks: tanpaMath, simpan } = cabutMatematika(asal)
  const html = markdownIt().render(tanpaMath)

  return tanamMatematika(sanitasi(html), simpan)
}

// Render sebaris: untuk `soal.pertanyaan` yang dipasang di dalam <h3>.
// Versi blok akan menghasilkan <p> yang tidak valid di situ.
export function renderKontenSatuan(teks) {
  if (teks === null || teks === undefined) return ''
  const asal = String(teks)
  if (!asal.trim()) return ''

  const { teks: tanpaMath, simpan } = cabutMatematika(asal)
  const html = markdownIt().renderInline(tanpaMath)

  return tanamMatematika(sanitasi(html), simpan)
}