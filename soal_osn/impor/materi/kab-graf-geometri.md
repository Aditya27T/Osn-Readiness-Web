---
id_sumber: kab-graf-geometri
tingkat: kabupaten
kompetensi: Graf & Geometri
urutan: 2
judul: Graf & Geometri
---
## Bagian A: Teori Graf (Graph Theory)

1. **Pengertian Dasar Graf**
   - Graf adalah struktur diskrit yang dinotasikan sebagai $G=\left(V,E\right)$, di mana $V$ adalah himpunan simpul (vertex / titik) dan $E$ adalah himpunan sisi (edge / garis) yang menghubungkan pasangan simpul.
1. **Jenis-Jenis Graf**
   - **Graf Tak Berarah & Berarah:** Graf tak berarah tidak memiliki orientasi pada sisinya, sedangkan graf berarah (digraph) memiliki sisi berpanah yang menunjukkan arah hubungan.
   - **Graf Berbobot & Tak Berbobot:** Graf berbobot memiliki nilai/bobot pada setiap sisinya (misalnya jarak atau biaya), sedangkan graf tak berbobot tidak.
   - **Graf Sederhana, Multigraf, & Pseudograf:** Graf sederhana tidak memiliki sisi ganda atau loop. Multigraf memiliki sisi ganda di antara dua simpul yang sama. Pseudograf memperbolehkan adanya loop (sisi yang menghubungkan simpul ke dirinya sendiri).
   - **Graf Khusus:**
      - Graf Lengkap (${K}_{n}$): Graf sederhana di mana setiap pasang simpul terhubung oleh sebuah sisi unik.
      - Graf Bipartit: Graf yang himpunan simpulnya dapat dibagi menjadi dua bagian terpisah sedemikian rupa sehingga setiap sisi menghubungkan simpul dari bagian pertama ke bagian kedua.
1. **Representasi Graf**
   - **Adjacency Matrix (Matriks Ketetanggaan):** Matriks berukuran $n×n$ yang merepresentasikan apakah pasangan simpul terhubung oleh sisi atau tidak.
   - **Adjacency List (Daftar Ketetanggaan):** Daftar yang memuat tetangga dari masing-masing simpul dalam graf.
1. **Derajat Simpul dan Lemma Jabat Tangan**
   - Derajat suatu simpul (degree, dinotasikan $deg \left(v\right)$) adalah jumlah sisi yang bersisian dengannya.
   - **Lemma Jabat Tangan:** Jumlah total derajat semua simpul dalam suatu graf selalu bernilai genap, yakni sama dengan dua kali jumlah sisi ($\sum_{v∈V} deg \left(v\right)=2|E|$).
1. **Koneksitas dan Lintasan Khusus**
   - **Walk, Trail, & Path:** Walk adalah barisan selang-seling simpul dan sisi. Trail adalah walk dengan sisi-sisi yang berbeda. Path adalah trail dengan simpul-simpul yang semuanya berbeda.
   - **Cycle:** Path tertutup yang simpul awal dan akhirnya sama.
   - **Graf Euler:** Graf yang memuat sirkuit Euler (sirkuit yang melalui setiap sisi tepat sekali).
   - **Graf Hamilton:** Graf yang memuat sirkuit/lintasan Hamilton (lintasan yang melalui setiap simpul tepat sekali).

## Bagian B: Geometri Analitik (Analytical Geometry)

1. **Sistem Koordinat Kartesius dan Jarak**
   - Posisi titik pada bidang datar dinyatakan dalam koordinat Kartesius $\left(x,y\right)$ di ${R}^{2}$ atau $\left(x,y,z\right)$ di ruang tiga dimensi ${R}^{3}$.
   - Jarak antara dua titik ${P}_{1}\left({x}_{1},{y}_{1}\right)$ dan ${P}_{2}\left({x}_{2},{y}_{2}\right)$ dirumuskan menggunakan Teorema Pythagoras:

$$d=\sqrt{{\left({x}_{2}-{x}_{1}\right)}^{2}+{\left({y}_{2}-{y}_{1}\right)}^{2}}$$

1. **Persamaan Garis Lurus**
   - Bentuk implisit: $ax+by+c=0$.
   - Bentuk eksplisit: $y=mx+n$, di mana $m$ adalah gradien (kemiringan garis) dan $n$ adalah titik potong sumbu-$y$.
1. **Irisan Kerucut (Conic Sections)**
   - **Lingkaran:** Tempat kedudukan titik-titik yang berjarak sama (jari-jari $r$) dari titik pusat $\left(a,b\right)$.

$$\text{Persamaan: }{\left(x-a\right)}^{2}+{\left(y-b\right)}^{2}={r}^{2}$$

   - **Elips:** Tempat kedudukan titik-titik yang jumlah jaraknya dari dua titik fokus bernilai konstan.

$$\text{Persamaan: }\frac{{\left(x-p\right)}^{2}}{{a}^{2}}+\frac{{\left(y-q\right)}^{2}}{{b}^{2}}=1$$

   - **Parabola:** Tempat kedudukan titik-titik yang berjarak sama terhadap suatu titik tertentu (fokus) dan garis tertentu (direktris).

$$\text{Persamaan: }{\left(y-b\right)}^{2}=4p\left(x-a\right)$$

   - **Hiperbola:** Tempat kedudukan titik-titik yang selisih jaraknya dari dua titik fokus bernilai konstan.

$$\text{Persamaan: }\frac{{\left(x-p\right)}^{2}}{{a}^{2}}-\frac{{\left(y-q\right)}^{2}}{{b}^{2}}=1$$
