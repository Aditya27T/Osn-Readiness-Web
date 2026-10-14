---
id_sumber: prov-geometri-dasar
tingkat: provinsi
kompetensi: Geometri Dasar
urutan: 10
judul: Geometri Dasar
---
## 1. Sistem Koordinat Kartesius 2D

Sistem koordinat Kartesius 2D adalah fondasi utama untuk memetakan titik, garis, dan bangun datar secara presisi. Sistem ini dibangun oleh dua garis lurus yang berpotongan tegak lurus di titik asal atau **origin** $\left(0,0\right)$.

- **Sumbu X (Absis):** Garis horizontal. Posisi di sebelah kanan origin bernilai positif, sedangkan di sebelah kiri bernilai negatif.
- **Sumbu Y (Ordinat):** Garis vertikal. Posisi di atas origin bernilai positif, sedangkan di bawah bernilai negatif.
- **Pembagian Kuadran:** Perpotongan kedua sumbu membagi ruang menjadi empat wilayah kerja:
   - **Kuadran I:** Kanan atas ($x>0,y>0$).
   - **Kuadran II:** Kiri atas ($x<0,y>0$).
   - **Kuadran III:** Kiri bawah ($x<0,y<0$).
   - **Kuadran IV:** Kanan bawah ($x>0,y<0$).

## 2. Jarak Euclidean

Jarak Euclidean adalah metode untuk mengukur jarak garis lurus terpendek antara dua titik dalam ruang Kartesius. Konsep ini diturunkan langsung dari Teorema Pythagoras.

- **Persamaan Dasar:** Jika terdapat Titik A $\left({x}_{1},{y}_{1}\right)$ dan Titik B $\left({x}_{2},{y}_{2}\right)$, jarak $d$ antara kedua titik tersebut dihitung menggunakan rumus:

$$d=\sqrt{{\left({x}_{2}-{x}_{1}\right)}^{2}+{\left({y}_{2}-{y}_{1}\right)}^{2}}$$

- **Fungsi Utama:** Rumus ini krusial untuk menghitung panjang sisi dari poligon (seperti segitiga atau persegi) jika hanya diketahui titik koordinat sudut-sudutnya, serta mengukur radius pasti dari pusat lingkaran ke tepinya.

## 3. Geometri Dasar (Segitiga, Persegi, Lingkaran)

Geometri dasar ini mendefinisikan bentuk, luas, dan batas ruang suatu objek 2D.

**Segitiga**

Bangun tertutup dengan tiga sisi di mana jumlah total sudut dalamnya pasti bernilai ${180}^{∘}$.

- **Luas dan Keliling:** Luas = $\frac{1}{2}×\text{alas}×\text{tinggi}$. Keliling diperoleh dengan menjumlahkan ketiga sisi luarnya.
- **Hubungan Siku-siku:** Khusus pada segitiga siku-siku (salah satu sudut ${90}^{∘}$), berlaku **Teorema Pythagoras** untuk mencari panjang hipotenusa (sisi miring): ${c}^{2}={a}^{2}+{b}^{2}$.

**Persegi dan Persegi Panjang**

Bangun bersisi empat dengan semua sudut berukuran ${90}^{∘}$.

- **Persegi:** Keempat sisi identik. Luas = ${s}^{2}$, Keliling = $4s$ ($s$ = panjang sisi).
- **Persegi Panjang:** Memiliki pasang sisi sejajar (panjang $p$ dan lebar $l$). Luas = $p×l$, Keliling = $2\left(p+l\right)$.

**Lingkaran**

Himpunan seluruh titik yang memiliki jarak tetap (jari-jari atau $r$) dari satu titik pusat.

- **Ukuran Fisik:** Luas = $π{r}^{2}$ dan Keliling = $2πr$.
- **Korelasi dengan Koordinat:** Dalam sistem Kartesius, jika sebuah lingkaran memiliki pusat di $\left({x}_{p},{y}_{p}\right)$ dan jari-jari $r$, persamaannya adalah:

$${\left(x-{x}_{p}\right)}^{2}+{\left(y-{y}_{p}\right)}^{2}={r}^{2}$$

Persamaan ini sejatinya adalah modifikasi dari rumus Jarak Euclidean.

## 4. Pemetaan dan Jarak Layanan (Aplikasi Jasa Antar / Navigasi)

Saat Anda menggunakan aplikasi transportasi online atau pesan antar makanan, sistem secara konstan menghitung jarak dari lokasi Anda ke restoran atau pengemudi. Perhitungan garis lurus antar dua lokasi ini menggunakan prinsip Jarak Euclidean di dalam sistem koordinat 2D.

**Contoh Soal:**

Sebuah gudang pengiriman berada di koordinat $A\left(2,3\right)$. Sebuah paket harus dikirimkan ke rumah pelanggan yang berada di koordinat $B\left(14,8\right)$. Setiap 1 unit pada peta setara dengan 1 kilometer. Berapa jarak garis lurus dari gudang ke rumah pelanggan?

**Langkah Penyelesaian:**

Titik awal: ${x}_{1}=2,{y}_{1}=3$

Titik tujuan: ${x}_{2}=14,{y}_{2}=8$

$$d=\sqrt{{\left(14-2\right)}^{2}+{\left(8-3\right)}^{2}}$$

$$d=\sqrt{{\left(12\right)}^{2}+{\left(5\right)}^{2}}$$

$$d=\sqrt{144+25}$$

$$d=\sqrt{169}$$

$$d=13$$

**Kesimpulan:** Jarak garis lurus dari gudang ke rumah pelanggan adalah 13 kilometer.

## 5. Pengecekan Presisi Geometri (Desain Grafis & CAD)

Dalam perangkat lunak desain atau arsitektur, komputer perlu memverifikasi apakah bentuk yang dibuat pengguna benar-benar akurat. Misalnya, memastikan sebuah bidang yang digambar benar-benar berbentuk segitiga siku-siku agar struktur bangunan stabil.

**Contoh Soal:**

Seorang arsitek meletakkan tiga pilar pancang pada koordinat $P\left(0,0\right)$, $Q\left(4,0\right)$, dan $R\left(4,3\right)$. Apakah susunan ketiga pilar tersebut membentuk segitiga siku-siku yang sempurna?

**Langkah Penyelesaian:**

Kita perlu mencari panjang masing-masing sisi menggunakan Jarak Euclidean, lalu membuktikannya dengan Teorema Pythagoras (${a}^{2}+{b}^{2}={c}^{2}$).

- Sisi alas $PQ$: Jarak dari $\left(0,0\right)$ ke $\left(4,0\right)$ adalah $4$.
- Sisi tegak $QR$: Jarak dari $\left(4,0\right)$ ke $\left(4,3\right)$ adalah $3$.
- Sisi miring $PR$:

$$PR=\sqrt{{\left(4-0\right)}^{2}+{\left(3-0\right)}^{2}}=\sqrt{16+9}=\sqrt{25}=5$$

**Pembuktian Pythagoras:**

$$P{Q}^{2}+Q{R}^{2}=P{R}^{2}$$

$${4}^{2}+{3}^{2}={5}^{2}$$

$$16+9=25$$

**Kesimpulan:** Karena hasil perhitungan terbukti seimbang ($25=25$), maka susunan pilar tersebut terbukti membentuk segitiga siku-siku sempurna.

## 6. Analisis Zona Jangkauan (Telekomunikasi / Radar)

Konsep persamaan Lingkaran sangat vital untuk memetakan radius jangkauan sinyal, menara BTS, atau zona deteksi radar. Kita bisa mengevaluasi apakah suatu lokasi masuk dalam jangkauan sinyal atau di luar batas (blank spot).

**Contoh Soal:**

Sebuah menara seluler (BTS) didirikan pada koordinat pusat $\left(1,-2\right)$ dan memancarkan sinyal radio dengan radius maksimal $10$ kilometer. Seorang pengguna HP sedang berada di koordinat $\left(7,6\right)$. Apakah pengguna tersebut masih mendapatkan sinyal dari menara BTS?

**Langkah Penyelesaian:**

Batas jangkauan sinyal menara dirumuskan dalam persamaan lingkaran:

$${\left(x-{x}_{p}\right)}^{2}+{\left(y-{y}_{p}\right)}^{2}≤{r}^{2}$$

Keterangan: Pusat di $\left(1,-2\right)$, jari-jari $r=10$. Kita substitusikan koordinat pengguna $\left(7,6\right)$ ke dalam $x$ dan $y$.

Jarak kuadrat pengguna ke menara:

$${d}^{2}={\left(7-1\right)}^{2}+{\left(6-\left(-2\right)\right)}^{2}$$

$${d}^{2}={\left(6\right)}^{2}+{\left(6+2\right)}^{2}$$

$${d}^{2}=36+{\left(8\right)}^{2}$$

$${d}^{2}=36+64$$

$${d}^{2}=100$$

$$d=\sqrt{100}=10$$

**Kesimpulan:** Jarak pengguna ke menara adalah tepat 10 kilometer. Karena jaraknya sama dengan radius maksimal jangkauan menara ($10$ km), pengguna tersebut berada persis di batas terluar area layanan dan masih bisa mendapatkan sinyal.
