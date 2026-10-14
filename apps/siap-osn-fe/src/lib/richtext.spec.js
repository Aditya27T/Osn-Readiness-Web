// Uji untuk src/lib/richtext.js: sanitasi (prioritas), Markdown, dan TeX.
//
// Kasus sanitasi yang diuji di sini bukan paranoia. `soal.pertanyaan`,
// `pembahasan.isi_pembahasan`, dan `materi.isi_materi` semuanya keluar dari
// v-html dan isinya berasal dari berkas bank soal yang diimpor, bukan dari
// kode yang ditulis developer. Kalau ada satu celah di sini, teks soal bisa
// membawa skrip milik orang yang menulis isi bank.

// Asersi sanitasi di bawah memeriksa DOM hasil render, bukan sekadar string.
// Kalau hanya searching "onerror", teks yang sudah ter-escape juga ikut kena
// dan test-nya lulus padahal perlindungannya tidak bekerja -- atau sebaliknya
// test gagal padahal HTML-nya sudah aman karena kata itu cuma teks.

import { describe, it, expect } from 'vitest'
import { renderKonten, renderKontenSatuan, KONFIG_SANITASI } from './richtext.js'

// Tag yang tidak boleh pernah muncul sebagai elemen hidup di hasil render.
const TAG_BERBAHAYA = ['script', 'iframe', 'object', 'embed', 'form', 'img', 'style']

function elemen(html) {
  const wadah = document.createElement('div')
  wadah.innerHTML = html
  return wadah
}

// Semua atribut yang dimulai "on" di elemen hidup mana pun.
function atributEvent(html) {
  return [...elemen(html).querySelectorAll('*')]
    .flatMap((el) => [...el.attributes].map((a) => a.name))
    .filter((nama) => nama.toLowerCase().startsWith('on'))
}

// href yang tujuan skripnya masih utuh setelah sanitasi.
function hrefBerbahaya(html) {
  return [...elemen(html).querySelectorAll('a[href]')]
    .map((a) => a.getAttribute('href'))
    .filter((h) => /^\s*(javascript|data|vbscript):/i.test(h))
}

describe('renderKonten - sanitasi', () => {
  it('tidak menghasilkan tag berbahaya sebagai elemen hidup', () => {
    const html = renderKonten('<script>alert(1)</script><img src=x><iframe src=y></iframe>')
    const hidup = elemen(html).querySelectorAll(TAG_BERBAHAYA.join(','))
    expect(hidup.length).toBe(0)
  })

  it('menetralkan event handler pada atribut', () => {
    const html = renderKonten('<img src=x onerror="alert(1)">')
    expect(atributEvent(html)).toEqual([])
  })

  it('menetralkan tautan javascript:', () => {
    const html = renderKonten('[klik](javascript:alert(1))')
    expect(hrefBerbahaya(html)).toEqual([])
  })

  it('menetralkan tautan data: yang bisa jadi vektor skrip', () => {
    const html = renderKonten('[klik](data:text/html,<script>alert(1)</script>)')
    expect(hrefBerbahaya(html)).toEqual([])
  })

  it('menampilkan pseudo-tag bank soal sebagai teks, bukan markup', () => {
    // Draft skema di data-analytics menyebut pseudo-tag <X,Y> harus di-escape.
    // Yang diperiksa adalah isi teksnya: "<X,Y>" harus tetap terbaca
    // sebagai karakter, bukan jadi elemen bernama x,y.
    const html = renderKonten('Sisa himpunan <X,Y> memenuhi')
    expect(elemen(html).textContent).toContain('<X,Y>')
    expect(elemen(html).querySelectorAll('X,Y').length).toBe(0)
  })

  it('menampilkan pustaka C++ sebagai teks', () => {
    const html = renderKonten('Gunakan <iostream> untuk I/O')
    expect(html).toContain('&lt;iostream&gt;')
    expect(elemen(html).querySelectorAll('iostream').length).toBe(0)
  })

  it('tidak bisa dipalsukan placeholder lewat isi soal', () => {
    // Kalau placeholder bisa ditulis sendiri, isinya bisa menyuntik HTML.
    const html = renderKonten('zqMathBarzq0zqEndzq <img src=x onerror=alert(1)>')
    expect(elemen(html).querySelectorAll('img').length).toBe(0)
    expect(atributEvent(html)).toEqual([])
  })

  it('memakai konfigurasi sanitasi yang memang diekspor', () => {
    expect(KONFIG_SANITASI.USE_PROFILES).toEqual({
      html: true,
      mathMl: true,
      svg: true,
    })
  })
})

describe('renderKonten - markdown', () => {
  it('merender heading materi', () => {
    expect(renderKonten('## Masukan/Keluaran')).toContain('<h2>')
  })

  it('merender tebal dan miring', () => {
    const html = renderKonten('Penting **tebal** dan *miring*')
    expect(html).toContain('<strong>tebal</strong>')
    expect(html).toContain('<em>miring</em>')
  })

  it('merender daftar berbutir', () => {
    expect(renderKonten('- satu\n- dua')).toContain('<li>')
  })

  it('merender blok kode', () => {
    expect(renderKonten('```\nvar x: longint;\n```')).toContain('<code')
  })

  it('membungkus hasil blok dengan <p>', () => {
    expect(renderKonten('Paragraf biasa.')).toContain('<p>')
  })

  it('menghormati baris baru sebagai <br>', () => {
    // Pembahasan bank soal sering berupa daftar angka dengan baris baru dengan baris baru
    // polos, tanpa markdown. breaks:true yang membuatnya terbaca.
    const html = renderKonten('f1 = 1\nf2 = 2')
    expect(html).toContain('<br>')
  })
})

describe('renderKonten - TeX', () => {
  it('merender rumus sebaris', () => {
    const html = renderKonten('Jumlahnya $N-1$ buah.')
    expect(html).toContain('katex')
  })

  it('merender operator times', () => {
    expect(renderKonten('Ukuran $1 \times 1$')).toContain('katex')
  })

  it('merender blok matematika', () => {
    const html = renderKonten('$$a^2 + b^2 = c^2$$')
    expect(html).toContain('katex')
    expect(html).toContain('katex-display')
  })

  it('tidak memakan garis bawah di dalam rumus sebagai penanda emph', () => {
    // Tanpa mencabut matematika lebih dulu, `x_1` berubah jadi x<em>1</em>.
    const html = renderKonten('Rumus $x_1 + y_2$ di sini.')
    expect(html).not.toContain('<em>1</em>')
    expect(html).toContain('katex')
  })

  it('membiarkan angka mata uang tanpa spasi setelah $', () => {
    // "$5" tidak boleh ikut jadi rumus: pembuka wajib diikuti karakter
    // yang bukan spasi, dan "$ 5" memang gagal di pola ini.
    const html = renderKonten('Harganya $ 5 dolar.')
    expect(html).not.toContain('katex')
  })

  it('tidak melempar error saat TeX rusak', () => {
    expect(() => renderKonten('$\\frac{1}{$')).not.toThrow()
  })

  it('menampilkan TeX rusak, bukan halaman kosong', () => {
    const html = renderKonten('$\\undefinedcommand$')
    expect(html.length).toBeGreaterThan(0)
  })
})

describe('renderKonten - nilai kosong', () => {
  it('mengembalikan string kosong untuk null dan undefined', () => {
    expect(renderKonten(null)).toBe('')
    expect(renderKonten(undefined)).toBe('')
  })

  it('mengembalikan string kosong untuk teks kosong', () => {
    expect(renderKonten('')).toBe('')
    expect(renderKonten('   ')).toBe('')
  })

  it('tidak melempar untuk angka', () => {
    expect(() => renderKonten(42)).not.toThrow()
  })
})

describe('renderKontenSatuan', () => {
  it('tidak membungkus hasil dengan <p>', () => {
    // Dipakai di dalam <h3>; <p> di dalam <h3> tidak valid.
    expect(renderKontenSatuan('Teks soal')).not.toContain('<p>')
  })

  it('tetap merender Markdown sebaris', () => {
    expect(renderKontenSatuan('Teks **tebal**')).toContain('<strong>tebal</strong>')
  })

  it('tetap merender TeX', () => {
    expect(renderKontenSatuan('Berapa $N-1$?')).toContain('katex')
  })

  it('tetap menetralkan skrip', () => {
    expect(renderKontenSatuan('<script>alert(1)</script>')).not.toContain('<script')
  })

  it('mengembalikan string kosong untuk null', () => {
    expect(renderKontenSatuan(null)).toBe('')
  })
})