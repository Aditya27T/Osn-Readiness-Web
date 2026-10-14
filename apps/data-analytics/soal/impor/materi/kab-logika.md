---
id_sumber: kab-logika
tingkat: kabupaten
kompetensi: Aljabar Boolean & Himpunan
urutan: 1
judul: Aljabar Boolean & Himpunan
---
## 1. Logika Proposisional & Aljabar Boolean

Aljabar Boolean adalah cabang aljabar di mana nilai variabelnya adalah kebenaran (biasanya dilambangkan dengan $1$ untuk True / Benar dan $0$ untuk False / Salah).

### A. Operator Logika Dasar

- **Konjungsi (AND -** $∧$**):** Pernyataan bernilai benar hanya jika semua komponen proposisi penyusunnya bernilai benar.
- **Disjungsi (OR -** $∨$**):** Pernyataan bernilai benar jika setidaknya salah satu komponen proposisi penyusunnya bernilai benar.
- **Negasi (NOT -** $∼$**):** Operator unear yang membalikkan nilai kebenaran suatu proposisi (benar menjadi salah, salah menjadi benar).
- **Implikasi (**$→$**):** Bentuk bersyarat "Jika $P$, maka $Q$". Nilainya hanya akan salah jika $P$ benar dan $Q$ salah.
- **Biimplikasi (**$↔$**):** Bentuk "P jika dan hanya jika Q". Bernilai benar jika $P$ dan $Q$ memiliki nilai kebenaran yang sama.

### B. Hukum-Hukum Aljabar Boolean

- **Hukum De Morgan:**
   - $∼\left(P∧Q\right)≡∼P∨∼Q$
   - $∼\left(P∨Q\right)≡∼P∧∼Q$
- **Hukum Distributif:** $P∨\left(Q∧R\right)≡\left(P∨Q\right)∧\left(P∨R\right)$
- **Hukum Identitas:** $P∨0≡P$ dan $P∧1≡P$

## 2. Teori Himpunan Dasar

Himpunan adalah kumpulan objek atau elemen yang terdefinisi dengan jelas. Dalam kaitannya dengan logika, himpunan dan aljabar Boolean memiliki kesetaksetaraan hukum (dualisme).

### A. Operasi Himpunan Utama

- **Gabungan (Union -** $A∪B$**):** Himpunan yang seluruh anggotanya berasal dari himpunan $A$, himpunan $B$, atau keduanya.
- **Irisan (Intersection -** $A∩B$**):** Himpunan yang anggotanya hanya elemen-elemen yang terdapat pada kedua himpunan $A$ dan $B$ secara bersamaan.
- **Komplemen (**${A}^{c}$ **atau** $\overline{A}$**):** Seluruh elemen di dalam ruang semesta ($S$) yang tidak menjadi anggota himpunan $A$.
- **Selisih (**$A-B$**):** Elemen yang ada di $A$ tetapi tidak ada di $B$.

## 3. Prinsip Inklusi-Eksklusi (PIE)

Dalam teori kombinatorika dan himpunan, ketika kita ingin menghitung ukuran dari gabungan beberapa himpunan, penjumlahan kardinalitas masing-masing himpunan sering kali menyebabkan perhitungan ganda (overcounting). Prinsip Inklusi-Eksklusi digunakan untuk mengoreksi hal tersebut.

### A. Rumus untuk 2 Himpunan

$$|A∪B|=|A|+|B|-|A∩B|$$

Penjelasan: Jumlah elemen gabungan $A$ dan $B$ adalah jumlah elemen $A$ ditambah jumlah elemen $B$, dikurangi elemen yang terhitung dua kali pada bagian irisannya.

### B. Rumus untuk 3 Himpunan

$$|A∪B∪C|=|A|+|B|+|C|-\left(|A∩B|+|A∩C|+|B∩C|\right)+|A∩B∩C|$$

Penjelasan: Kita menjumlahkan ukuran masing-masing himpunan, mengurangkan semua irisan berpasangan ganda, lalu menambahkan kembali irisan ketiga himpunan yang sempat terkurang habis di tahap sebelumnya.

## Use Case 1: Prinsip Inklusi-Eksklusi (PIE) dalam Analisis Data E-Commerce

**Deskripsi Masalah:**

Sebuah platform e-commerce menganalisis perilaku 1.000 pengguna aktif dalam sebulan terakhir untuk melihat preferensi kategori belanja mereka. Tim data mencatat tiga kategori produk utama yang sering dikunjungi:

- Himpunan **A**: Pengguna yang melihat kategori Elektronik ($|A|=450$)
- Himpunan **B**: Pengguna yang melihat kategori Fashion ($|B|=350$)
- Himpunan **C**: Pengguna yang melihat kategori Kecantikan ($|C|=250$)

Data irisan antar kategori menunjukkan:

- Melihat Elektronik dan Fashion: $|A∩B|=120$
- Melihat Elektronik dan Kecantikan: $|A∩C|=80$
- Melihat Fashion dan Kecantikan: $|B∩C|=60$
- Melihat ketiga kategori sekaligus: $|A∩B∩C|=30$

**Pertanyaan:**

1. Berapa total pengguna unik yang melihat **setidaknya satu** dari ketiga kategori tersebut?
1. Berapa banyak pengguna yang **tidak sama sekali** melihat ketiga kategori di atas (artinya mereka melihat kategori lain di luar $A,B,$ dan $C$)?

**Penyelesaian:**

1. **Menghitung Total Pengguna yang Melihat Setidaknya Satu Kategori (**$|A∪B∪C|$**)**

Menggunakan rumus PIE 3 Himpunan:

$$|A∪B∪C|=|A|+|B|+|C|-\left(|A∩B|+|A∩C|+|B∩C|\right)+|A∩B∩C|$$

Masukkan nilai yang diketahui:

$$|A∪B∪C|=450+350+250-\left(120+80+60\right)+30$$

$$|A∪B∪C|=1050-260+30=820$$

Hasil: Terdapat **820 pengguna unik** yang melihat setidaknya satu dari ketiga kategori tersebut.

1. **Menghitung Pengguna di Luar Ketiga Kategori (Komplemen)**

Total keseluruhan pengguna aktif adalah 1.000 orang.

$$\text{Pengguna Lain}=1000-820=180$$

Hasil: Terdapat **180 pengguna** yang tidak melihat ketiga kategori utama tersebut.

## Use Case 2: Aljabar Boolean & Logika dalam Sistem Kontrol Akses (Smart Door Lock)

**Deskripsi Masalah:**

Sebuah sistem keamanan pintu pintar di ruang server perusahaan memerlukan validasi akses masuk berdasarkan kondisi Boolean agar pintu dapat terbuka ($P=1$). Variabel yang menentukan adalah:

- $K$: Kartu akses valid ($1$ = Valid, $0$ = Tidak)
- $S$: Pemindaian sidik jari cocok ($1$ = Cocok, $0$ = Tidak)
- $M$: Mode pemeliharaan darurat aktif ($1$ = Aktif, $0$ = Tidak Aktif)

Aturan keamanan sistem dirumuskan dalam fungsi Boolean berikut:

$$P=\left(K∨S\right)∧∼M$$

**Pertanyaan:**

Tentukan kondisi pintu terbuka ($P=1$) atau tertutup ($P=0$) pada skenario berikut:

1. Skenario A: Kartu akses valid ($K=1$), sidik jari tidak cocok ($S=0$), mode pemeliharaan tidak aktif ($M=0$).
1. Skenario B: Kartu akses tidak valid ($K=0$), sidik jari cocok ($S=1$), mode pemeliharaan aktif ($M=1$).

**Penyelesaian:**

1. **Analisis Skenario A:**
   - Masukkan nilai: $K=1,S=0,M=0$
   - Hitung bagian dalam kurung OR: $\left(1∨0\right)=1$
   - Hitung negasi NOT: $∼M=∼0=1$
   - Hasil akhir AND: $1∧1=1$ (Pintu Terbuka / True)
1. **Analisis Skenario B:**
   - Masukkan nilai: $K=0,S=1,M=1$
   - Hitung bagian dalam kurung OR: $\left(0∨1\right)=1$
   - Hitung negasi NOT: $∼M=∼1=0$
   - Hasil akhir AND: $1∧0=0$ (Pintu Tertutup / False, karena mode pemeliharaan sedang aktif sehingga akses diblokir).
