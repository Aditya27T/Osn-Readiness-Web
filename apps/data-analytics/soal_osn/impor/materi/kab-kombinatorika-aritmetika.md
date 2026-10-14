---
id_sumber: kab-kombinatorika-aritmetika
tingkat: kabupaten
kompetensi: Kombinatorika, Model Matematis & Deret
urutan: 3
judul: Kombinatorika, Model Matematis & Deret
---
## 1. Perluasan Permutasi dan Kombinasi

Kombinatorika tingkat olimpiade tidak lagi hanya menggunakan permutasi dan kombinasi dasar, melainkan variasinya dalam kondisi objek yang berulang, identik, atau membentuk siklus.

- **Permutasi Siklis:** Apabila susunan objek berbentuk lingkaran (siklis) dan rotasi susunan dianggap sama, banyak permutasi untuk $n$ objek adalah $\left(n-1\right)!$.
- **Permutasi Unsur Sama:** Jika terdapat $n$ objek yang terdiri dari $k$ kelompok objek identik sebanyak ${n}_{1},{n}_{2},…,{n}_{k}$, banyak susunan berbedanya adalah $\frac{n!}{{n}_{1}!{n}_{2}!…{n}_{k}!}$. Konsep ini ekuivalen dengan pembagian ke dalam kelompok atau koefisien multinomial.
- **Permutasi Berulang:** Jika $k$ objek diambil dari $n$ jenis objek yang ketersediaannya tidak terbatas (boleh berulang), banyak cara penyusunannya adalah ${n}^{k}$.
- **Kombinasi Berulang (Stars and Bars Theorem):** Untuk mencari banyaknya cara mendistribusikan $r$ objek identik ke dalam $n$ tempat berbeda, kita mencari solusi bilangan bulat dari persamaan ${x}_{1}+{x}_{2}+…+{x}_{n}=r$.
   - Jika himpunan penyelesaiannya adalah bilangan bulat non-negatif (${x}_{i}≥0$), banyak solusinya adalah $\left(\frac{n+r-1}{r}\right)$ atau $\left(\frac{n+r-1}{n-1}\right)$.
   - Jika himpunan penyelesaiannya adalah bilangan bulat positif (${x}_{i}≥1$), banyak solusinya adalah $\left(\frac{r-1}{n-1}\right)$.

## 2. Prinsip Inklusi-Eksklusi (PIE) dan Derangement

- **Prinsip Inklusi-Eksklusi:** Metode untuk menghitung jumlah elemen dari gabungan himpunan dengan menghindari perhitungan ganda (double counting). Untuk $n$ himpunan, prinsip ini diformulasikan dengan menjumlahkan kardinalitas setiap himpunan tunggal, mengurangkan irisan dua himpunan, menjumlahkan kembali irisan tiga himpunan, dan seterusnya secara bergantian: $|\bigcup_{i=1}^{n} {A}_{i}|=\sum |{A}_{i}|-\sum |{A}_{i}∩{A}_{j}|+\sum |{A}_{i}∩{A}_{j}∩{A}_{k}|-…+{\left(-1\right)}^{n-1}|\bigcap_{i=1}^{n} {A}_{i}|$.
- **Derangement (Kekacauan Total):** Derangement adalah permutasi dari $n$ objek sedemikian rupa sehingga tidak ada satu pun objek yang menempati posisi awalnya. Banyaknya susunan ini dihitung dengan formula $D\left(n\right)=n!\left[\frac{1}{0!}-\frac{1}{1!}+\frac{1}{2!}-\frac{1}{3!}+…+\frac{{\left(-1\right)}^{n}}{n!}\right]$.

## 3. Teorema Binomial dan Multinomial

- **Ekspansi Binomial:** Penjabaran ${\left(a+b\right)}^{n}$ diformulasikan sebagai $\sum_{i=0}^{n} \left(\frac{n}{i}\right){a}^{n-i}{b}^{i}$. Koefisien-koefisien ini juga merepresentasikan nilai pada Segitiga Pascal.
- **Ekspansi Multinomial:** Penjabaran dari ${\left({x}_{1}+{x}_{2}+…+{x}_{k}\right)}^{n}$. Koefisien untuk suku ${x}_{1}^{{e}_{1}}{x}_{2}^{{e}_{2}}…{x}_{k}^{{e}_{k}}$ (dengan syarat ${e}_{1}+{e}_{2}+…+{e}_{k}=n$) dihitung menggunakan nilai $\frac{n!}{{e}_{1}!{e}_{2}!…{e}_{k}!}$.

## 4. Prinsip Sarang Merpati (Pigeonhole Principle)

Prinsip ini digunakan untuk membuktikan eksistensi suatu kondisi dalam pengaturan objek, bukan untuk menghitung banyaknya kombinasi.

- **Bentuk I:** Jika $n+1$ objek (merpati) didistribusikan ke dalam $n$ kotak (sangkar), sedikitnya terdapat satu kotak yang berisi dua atau lebih objek.
- **Bentuk II / Fungsi:** Jika terdapat sebuah fungsi dari himpunan berhingga $X$ ke himpunan berhingga $Y$ dengan kardinalitas $|X|=n$ dan $|Y|=m$, maka sedikitnya terdapat $\left\lceil\frac{n}{m}\right\rceil$ elemen di $X$ yang dipetakan ke elemen yang sama di $Y$.
- **Rata-rata:** Jika $n\left(x-1\right)+1$ objek dimasukkan ke dalam $n$ kotak, pasti ada setidaknya satu kotak yang berisi $x$ objek atau lebih.

## 5. Model Matematis Berbasis Deret: Relasi Rekurensi & Fungsi Pembangkit

- **Relasi Rekurensi:** Penentuan suku ke-$n$ dari sebuah barisan tidak diberikan secara eksplisit, melainkan dirumuskan menggunakan satu atau beberapa suku sebelumnya. Penentuan rumus eksplisitnya sering kali diselesaikan menggunakan perhitungan mundur (backward) yang membutuhkan pemahaman rumus deret aritmetika atau geometri dasar.
- **Fungsi Pembangkit (Generating Function):** Sebuah teknik untuk merepresentasikan barisan bilangan tak hingga ke dalam bentuk koefisien ekspansi deret, $f\left(x\right)=\sum_{n=0}^{∞} {a}_{n}{x}^{n}$. Contoh penerapannya adalah ekspansi $\frac{x}{{\left(1-x\right)}^{2}}$ yang merepresentasikan barisan $x+2{x}^{2}+3{x}^{3}+…$ di mana koefisiennya membentuk barisan $n$.

## Studi Kasus 1: Kombinasi Berulang (Teorema Stars and Bars)

**Deskripsi Masalah:** Berapa banyak penyelesaian bilangan bulat tak negatif dari persamaan ${x}_{1}+{x}_{2}+{x}_{3}=20$? Dalam konteks nyata, persoalan ini ekuivalen dengan pertanyaan: "Ada berapa cara membagikan 20 buah apel yang identik kepada 3 orang anak, jika seorang anak boleh tidak mendapat apel sama sekali?"

**Pembahasan:**

Jika menggunakan cara penelusuran manual (brute force), kita harus mendata semua kemungkinan seperti (20,0,0), (19,1,0), (19,0,1), yang rentan terjadi kesalahan perhitungan.

Menggunakan Teorema Stars and Bars, kita memandang 20 apel identik sebagai rentetan bintang (stars) dan membutuhkan 2 garis pembatas (bars) untuk membaginya ke dalam 3 kelompok berbeda (merepresentasikan 3 anak). Secara matematis, banyaknya penyelesaian untuk himpunan bilangan bulat tak negatif dirumuskan dengan $\left(\frac{n+r-1}{r}\right)$, dengan $n=3$ (banyak anak/variabel) dan $r=20$ (banyak apel/objek).

Perhitungan kombinasinya adalah:

$$\left(\frac{3+20-1}{20}\right)=\left(\frac{22}{20}\right)=\frac{22!}{20!\left(22-20\right)!}=\frac{22×21}{2}=231$$

Terdapat tepat 231 cara pembagian yang valid.

## Studi Kasus 2: Pembuktian dengan Prinsip Sarang Merpati

**Deskripsi Masalah:** Tunjukkan bahwa dari 52 buah bilangan bulat yang dipilih secara acak, pasti terdapat 2 buah bilangan yang jumlah atau selisihnya habis dibagi 100.

**Pembahasan:** Soal ini tidak meminta kita mencari bilangannya, melainkan membuktikan eksistensinya. Kita akan membagi kemungkinan sisa pembagian (modulo) bilangan-bilangan tersebut saat dibagi 100 ke dalam himpunan-himpunan yang bertindak sebagai "sangkar". Sisa pembagian suatu bilangan oleh 100 selalu berkisar dari 0 hingga 99.

Kita kelompokkan pasangan sisa yang jika dijumlahkan menghasilkan 100, atau jika dikurangkan menghasilkan 0:

- Sangkar 0: $\left\{0\right\}$
- Sangkar 50: $\left\{50\right\}$
- Sangkar 1 hingga 49: $\left\{1,99\right\},\left\{2,98\right\},…,\left\{49,51\right\}$

Total sangkar (kategori sisa) yang terbentuk adalah $1+1+49=51$ sangkar. Karena kita memiliki 52 buah bilangan sebagai "merpati", sedangkan hanya tersedia 51 sangkar, menurut Prinsip Sarang Merpati, pasti ada sedikitnya 1 sangkar yang memuat minimal 2 bilangan.

- Jika 2 bilangan tersebut jatuh di sangkar $\left\{0\right\}$ atau $\left\{50\right\}$, maka selisih keduanya pasti habis dibagi 100.
- Jika 2 bilangan tersebut berada di sangkar berpasangan, misalnya $\left\{2,98\right\}$:
   - Jika kedua bilangan memiliki sisa yang sama (misal sama-sama bersisa 2), selisihnya habis dibagi 100.
   - Jika satu bilangan bersisa 2 dan yang lainnya bersisa 98, jumlah keduanya ($2+98=100$) pasti habis dibagi 100.

Ini membuktikan bahwa kondisi tersebut dijamin mutlak terjadi.

## Studi Kasus 3: Kekacauan Total (Derangement)

**Deskripsi Masalah:**

Empat orang tamu (A, B, C, D) menitipkan topinya ke meja resepsionis. Saat acara selesai, listrik padam sehingga petugas memberikan topi secara acak di dalam gelap. Berapa banyak kemungkinan **tidak ada satu pun** tamu yang menerima topi miliknya sendiri?

**Pembahasan:** Ini adalah masalah Derangement, yaitu permutasi khusus di mana tidak ada objek yang menempati posisi awalnya. Untuk objek dengan jumlah kecil ($n=4$), perhitungannya ditangani oleh formula Derangement:

$$D\left(n\right)=n!\left(\frac{1}{0!}-\frac{1}{1!}+\frac{1}{2!}-\frac{1}{3!}+…+\frac{{\left(-1\right)}^{n}}{n!}\right)$$

Dengan memasukkan $n=4$:

$$D\left(4\right)=4!\left(\frac{1}{1}-\frac{1}{1}+\frac{1}{2}-\frac{1}{6}+\frac{1}{24}\right)$$

$$D\left(4\right)=24\left(\frac{1}{2}-\frac{1}{6}+\frac{1}{24}\right)=12-4+1=9$$

Terdapat 9 kemungkinan penyerahan topi di mana setiap tamu mendapatkan topi yang salah.
