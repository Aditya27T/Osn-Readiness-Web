---
id_sumber: prov-graf
tingkat: provinsi
kompetensi: Graf & Tree
urutan: 9
judul: Graf & Tree
---
## 1. Pendalaman Teoretis Struktur Graf dan Karakteristiknya

Secara matematis, sebuah graf didefinisikan sebagai pasangan himpunan $G=\left(V,E\right)$, di mana $V$ adalah himpunan simpul (vertices) dan $E$ adalah himpunan sisi (edges) yang menghubungkan pasangan simpul tersebut.

- **Graf Sederhana vs Tak-Sederhana:**
   - **Graf Sederhana (Simple Graph):** Tidak mengizinkan adanya sisi ganda (multiple edges) antara dua simpul yang sama dan tidak memiliki loop (sisi yang berawal dan berakhir pada simpul yang sama).
   - **Graf Tak-Sederhana:** Mencakup multigraph (ada lebih dari satu sisi di antara dua simpul yang sama) dan pseudograph (mengandung loop).
- **Graf Berarah (Digraph) vs Graf Tak-Berarah (Undirected Graph):**
   - **Graf Tak-Berarah:** Hubungan bersifat simetris. Jika simpul $A$ terhubung dengan $B$, maka secara otomatis $B$ terhubung dengan $A$.
   - **Graf Berarah:** Hubungan bersifat asimetris karena sisi dilengkapi dengan orientasi panah. Sisi berarah dari $A$ ke $B$ ditulis sebagai pasangan berurutan $\left(A,B\right)$, di mana $A$ adalah simpul asal (tail) dan $B$ adalah simpul tujuan (head).
- **Derajat Simpul (Degree) dan Lema Jabat Tangan:**
   - Pada graf tak-berarah, derajat suatu simpul $d\left(v\right)$ adalah jumlah sisi yang bersisian dengannya.
   - Lema jabat tangan menyatakan bahwa jumlah total derajat seluruh simpul dalam sebuah graf selalu bernilai genap, karena setiap sisi dihitung dua kali (pada dua simpul yang dihubunginya):

$$\sum_{v∈V} d\left(v\right)=2|E|$$

   - Pada graf berarah, derajat dipecah menjadi **derajat masuk (in-degree)**—jumlah sisi yang masuk ke suatu simpul—dan **derajat keluar (out-degree)**—jumlah sisi yang keluar dari suatu simpul.

## 2. Lintasan (Path), Sirkuit (Circuit), dan Keterhubungan

Dalam analisis graf, bagaimana cara kita berpindah dari satu simpul ke simpul lain sangat menentukan properti graf tersebut:

- **Walk (Jalan):** Barisan berselang-seling antara simpul dan sisi. Semua simpul dan sisi dalam sebuah walk boleh dikunjungi berulang kali.
- **Trail:** Sebuah walk dengan syarat **semua sisinya berbeda** (tidak ada sisi yang dilalui lebih dari sekali).
- **Path (Lintasan):** Sebuah walk di mana **semua simpul dan sisinya unik** (tidak ada simpul maupun sisi yang dikunjungi berulang kali).
- **Cycle (Siklus / Sikel):** Trail tertutup yang berawal dan berakhir pada simpul yang sama, dengan panjang minimal tiga simpul serta semua simpul perantara bersifat unik.
- **Keterhubungan (Connectedness):** Graf tak-berarah disebut terhubung (connected) jika terdapat path yang sah antara setiap pasang simpul di dalamnya. Jika tidak, graf tersebut terpecah menjadi beberapa komponen terhubung (connected components).

## 3. Hubungan Khusus: Konsep Pohon (Tree)

Pohon adalah bentuk khusus dari graf yang memiliki karakteristik struktural yang sangat ketat:

- **Definisi:** Pohon adalah graf tak-berarah yang **terhubung dan tidak mengandung sikel (acyclic connected graph)**.
- **Teorema Karakteristik Pohon:** Jika sebuah graf $G$ memiliki $n$ buah simpul, maka pernyataan berikut ekuivalen:
   1. $G$ adalah sebuah pohon.
   1. $G$ tidak memiliki sikel dan tepat memiliki $n-1$ buah sisi ($|E|=|V|-1$).
   1. $G$ terhubung dan setiap sisinya adalah jembatan (bridge—jika sisi dihapus, graf terpecah).
- **Pohon Merentang (Spanning Tree):** Subgraf dari graf terhubung $G$ yang mencakup seluruh simpul asli $G$ namun berbentuk pohon (tanpa sikel). Konsep ini sangat penting dalam perancangan jaringan komputer, jalur kabel minimum (Minimum Spanning Tree), dan optimasi rute.

## 4. Algoritma Penjelajahan Graf (Graph Traversal)

Penjelajahan graf adalah teknik sistematis untuk mengunjungi setiap simpul dan sisi di dalam graf setidaknya satu kali. Dua algoritma utama yang menjadi fondasi ilmu komputer adalah BFS dan DFS.

| **Parameter Perbandingan** | **Breadth-First Search (BFS)** | **Depth-First Search (DFS)** |
| --- | --- | --- |
| **Prinsip Utama** | Penjelajahan melebar (level-by-level), mengunjungi seluruh tetangga terdekat terlebih dahulu. | Penjelajahan mendalam (depth-first), menyusuri satu jalur sejauh mungkin sebelum mundur (backtracking). |
| **Struktur Data** | **Queue (Antrian)** menggunakan prinsip **FIFO** (First In, First Out). | **Stack (Tumpukan)** menggunakan prinsip rekursi atau struktur stack eksplisit. |
| **Kompleksitas Waktu** | $$O\left(V+E\right)$$ | $$O\left(V+E\right)$$ |
| **Kompleksitas Ruang** | $O\left(V\right)$ (menyimpan seluruh simpul pada level aktif di memori antrian). | $O\left(V+E\right)$ atau $O\left(V\right)$ pada kasus terburuk (menyimpan jejak rekursi stack). |

## 5. Representasi Graf di dalam Memori Komputer

Sebelum melakukan penjelajahan (BFS/DFS), sebuah graf harus direpresentasikan ke dalam struktur data pemrograman. Dua cara paling umum adalah:

1. **Matriks Ketetanggaan (Adjacency Matrix):**
   - Menggunakan matriks dua dimensi berukuran $V×V$.
   - Jika ada sisi dari simpul $i$ ke simpul $j$, maka nilai matriks A[i][j] = 1 (atau bernilai bobot jika graf berbobot); jika tidak ada, bernilai 0.
   - **Kelebihan:** Pengecekan apakah dua simpul terhubung sangat cepat ($O\left(1\right)$).
   - **Kekurangan:** Membutuhkan memori yang besar, yaitu $O\left({V}^{2}\right)$, yang tidak efisien untuk graf yang jarang (sparse graph).
1. **Daftar Ketetanggaan (Adjacency List):**
   - Menggunakan susunan array dari linked list (atau list dinamis), di mana setiap simpul memiliki daftar simpul-simpul lain yang terhubung langsung dengannya.
   - **Kelebihan:** Sangat hemat memori, yaitu $O\left(V+E\right)$, dan sangat efisien untuk graf yang jarang (sparse graph).
   - **Kekurangan:** Pengecekan koneksi langsung antara dua simpul tertentu sedikit lebih lambat dibanding matriks karena harus menelusuri daftar tetangga dari simpul tersebut.

## 6. Studi Kasus dan Cara Kerja Penjelajahan (BFS vs DFS)

Bayangkan kita memiliki sebuah graf tak-berarah dengan simpul dari A sampai F dan kita ingin memulai penjelajahan dari simpul A:

- **Ilustrasi BFS (Lebar / Melebar):**
   1. Mulai dari simpul A (masukkan ke queue, tandai sudah dikunjungi).
   1. Keluarkan A, kunjungi semua tetangga langsungnya (misalnya: B dan C). Masukkan B dan C ke queue.
   1. Ambil antrean berikutnya yaitu B, kunjungi tetangga B yang belum dikunjungi (misalnya: D dan E). Masukkan ke queue.
   1. Ambil antrean berikutnya yaitu C, kunjungi tetangganya (misalnya: F).
   1. Urutan kunjungan BFS secara umum: A $→$ B $→$ C $→$ D $→$ E $→$ F (berdasarkan kedekatan level).
- **Ilustrasi DFS (Mendalam / Rekursif Stack):**
   1. Mulai dari simpul A (tandai sudah dikunjungi).
   1. Pilih satu jalur tetangga secara mendalam, misal langsung ke B (tandai B).
   1. Dari B, pilih tetangga selanjutnya yang belum dikunjungi, misal langsung ke D (tandai D).
   1. Dari D, jika tidak ada tetangga lain yang belum dikunjungi, lakukan backtracking (mundur) ke B, lalu telusuri jalur lain dari B ke E.
   1. Urutan kunjungan DFS akan masuk lebih dulu ke ujung jalur terdalam sebelum berpindah ke cabang tetangga lainnya (misalnya: A $→$ B $→$ D $→$ E $→$ C $→$ F).

## 7. Ringkasan Pilihan Penggunaan Algoritma

- Gunakan BFS jika tujuan utama Anda adalah menemukan jalur terpendek (minimal langkah/sisi) pada graf yang tidak memiliki bobot sisi.
- Gunakan DFS jika Anda perlu mengeksplorasi seluruh kemungkinan cabang secara mendalam, mendeteksi adanya siklus dalam graf, atau melakukan pengurutan topologis (topological sort).

## 8. Studi Kasus BFS: Pencarian Rute Terpendek di Peta (GPS/Maps)

- **Penerapan:** Algoritma BFS sangat optimal digunakan untuk menentukan rute dengan jumlah persimpangan atau langkah minimal (shortest path) antara titik asal dan tujuan pada peta digital yang tidak mempertimbangkan bobot kemacetan.
- **Cara Kerja:** Peta direpresentasikan sebagai graf di mana persimpangan jalan adalah simpul (vertex) dan ruas jalan adalah sisi (edge). BFS mengeksplorasi wilayah secara melebar lapis demi lapis dari titik awal hingga menemukan tujuan dengan jarak langkah paling sedikit.

## 9. Studi Kasus DFS: Penyelesaian Labirin (Maze) dan Dependensi

- **Penerapan:** DFS banyak digunakan untuk mencari jalur keluar dalam sebuah labirin, mendeteksi siklus pada jaringan, atau melakukan pengurutan topologis (topological sorting) pada manajemen tugas yang memiliki prasyarat.
- **Cara Kerja:** Algoritma masuk sedalam mungkin ke satu arah koridor atau jalur cabang. Jika menemui jalan buntu, algoritma akan melakukan backtracking (mundur ke simpul sebelumnya) untuk mencoba jalur alternatif yang belum dikunjungi.

## 10. Studi Kasus Tree: Struktur Direktori Sistem Operasi

- **Penerapan:** Sistem berkas komputer (File System) seperti pada Windows, macOS, atau Linux menggunakan struktur data hierarki Tree (Pohon).
- **Cara Kerja:** Direktori utama (root folder) bercabang menjadi beberapa folder anak (subfolders), yang kemudian dapat memiliki subfolder lain atau berkas di dalamnya tanpa membentuk siklus tertutup.
