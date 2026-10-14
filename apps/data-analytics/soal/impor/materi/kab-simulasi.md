---
id_sumber: kab-simulasi
tingkat: kabupaten
kompetensi: Simulasi & Optimisasi
urutan: 4
judul: Simulasi & Optimisasi
---
## 1. Konsep Dasar Pernyataan Logika

Pemahaman dasar mengenai pernyataan dan hubungannya sangat penting sebelum menarik kesimpulan logis.

- **Pernyataan:** Pernyataan atau kalimat deklaratif adalah kalimat yang mempunyai nilai benar saja atau salah saja, tetapi tidak sekaligus bernilai benar dan salah. Nilai ini disesuaikan dengan keadaan sesungguhnya, misalnya "Matahari terbit dari arah timur" bernilai benar.
- **Ingkaran atau Negasi:** Pernyataan baru yang dibentuk oleh pernyataan semula, di mana ia bernilai salah apabila pernyataan semula bernilai benar, dan bernilai benar apabila pernyataan semula bernilai salah. Jika pernyataan $p$ bernilai benar (B), maka ingkarannya $∼p$ bernilai salah (S).
- **Disjungsi:** Dua pernyataan $p$ dan $q$ yang digabung dengan menggunakan kata hubung "atau", dituliskan dengan $p∨q$. Disjungsi bernilai benar jika salah satu atau kedua pernyataan bernilai benar. Ingkaran dari $\left(p∨q\right)$ ekuivalen dengan $∼p∧∼q$.
- **Konjungsi:** Dua pernyataan $p$ dan $q$ yang digabungkan dengan kata hubung "dan", dinamakan konjungsi dan dituliskan $p∧q$. Bernilai benar hanya jika pernyataan $p$ dan $q$ benar semuanya. Ingkaran dari $\left(p∧q\right)$ ekuivalen dengan $∼p∨∼q$.
- **Implikasi:** Pernyataan majemuk yang dibentuk dengan kata hubung "jika... maka ...", dinyatakan dengan $p⇒q$. Pernyataan $p$ disebut anteseden (sebab) dan $q$ disebut konsekuen (akibat). Implikasi bernilai salah hanya jika $p$ bernilai benar dan $q$ bernilai salah. Terdapat pernyataan turunan dari implikasi:
   - Konvers: $q⇒p$.
   - Invers: $∼p⇒∼q$.
   - Kontraposisi: $∼q⇒∼p$.
- **Biimplikasi:** Pernyataan majemuk dengan kata hubung "... jika dan hanya jika...", ditulis $p⇔q$ atau $\left(p⇒q\right)∧\left(q⇒p\right)$. Bernilai benar jika $p$ dan $q$ mempunyai nilai kebenaran yang sama (benar semua atau salah semua).
- **Kuantor Universal & Eksistensial:**
   - Kuantor universal (umum) ditandai dengan kata "semua", "untuk setiap", atau "tiap-tiap". Ini berarti setiap anggota A adalah anggota B.
   - Kuantor eksistensial ditandai dengan kata "ada", "terdapat", atau "beberapa", yang berarti sekurang-kurangnya satu atau ada anggota A yang menjadi anggota B.

## 2. Penarikan Kesimpulan Deduktif

Penalaran deduktif adalah kemampuan untuk bernalar secara logis dengan menggunakan premis-premis. Terdapat tiga bentuk utama dalam penarikan kesimpulan:

### A. Modus Ponens

- **Aturan:** Jika $p⇒q$ benar dan $p$ benar maka $q$ benar.
- **Premis 1:** $p⇒q$.
- **Premis 2:** $p$.
- **Kesimpulan:** $q$.

### B. Modus Tollen

- **Aturan:** Jika $p⇒q$ benar dan $∼q$ benar maka $∼p$ benar.
- **Premis 1:** $p⇒q$.
- **Premis 2:** $∼q$.
- **Kesimpulan:** $∼p$.

### C. Silogisme

- **Aturan:** Jika $p⇒q$ benar dan $q⇒r$ benar maka $p⇒r$ benar. Silogisme terdiri dari premis mayor ($p⇒q$) dan premis minor ($q⇒r$).
- **Premis 1:** $p⇒q$.
- **Premis 2:** $q⇒r$.
- **Kesimpulan:** $p⇒r$.

## 3. Penalaran Induktif

Penalaran induktif adalah cara berpikir logis dalam pembuatan pernyataan umum. Bentuk penalaran ini dapat berupa:

- **Generalisasi:** Membuat penalaran induktif berdasarkan kumpulan fakta.
- **Analogi:** Mencari persamaan hubungan antar kata.
- **Sebab Akibat:** Menganalisis hubungan antara dua pernyataan. Hubungan tersebut dapat berupa:
   - Pernyataan 1 adalah sebab dan pernyataan 2 adalah akibat (atau sebaliknya).
   - Pernyataan 1 dan 2 adalah sebab, namun tidak saling berkaitan.
   - Pernyataan 1 dan 2 adalah akibat dari dua penyebab yang tidak saling berkaitan, atau akibat dari penyebab yang sama.
   - Petunjuk umum dalam soal: (A) pernyataan benar, alasan benar, dan keduanya menunjukkan hubungan sebab akibat; (B) pernyataan benar, alasan benar, tetapi tidak menunjukkan hubungan; (C) pernyataan benar dan alasan salah; (D) pernyataan salah dan alasan benar; (E) pernyataan dan alasan keduanya salah.

**Contoh Pada Topik Logika Penalaran dan Penarikan Kesimpulan**

## 1. Konsep Dasar Pernyataan Logika (Contoh)

Pemahaman dasar logika membutuhkan analisis terhadap nilai kebenaran dari sebuah kalimat atau pernyataan.

- **Pernyataan:** Kalimat harus bisa dinilai kebenarannya secara pasti.
   - Contoh pernyataan benar: Matahari terbit dari arah timur.
   - Contoh pernyataan salah: Bilangan prima terkecil adalah 1.
- **Ingkaran (Negasi):** Membalik nilai kebenaran suatu pernyataan.
   - Kasus: Jika $p$ adalah "Bumi berbentuk bulat" yang bernilai benar, maka negasinya ($∼p$) adalah "Bumi tidak berbentuk bulat" yang bernilai salah.
   - Kasus lain: Jika $p$ adalah "1 bukan merupakan bilangan asli" yang bernilai salah, maka negasinya ($∼p$) adalah "1 merupakan bilangan asli" yang bernilai benar.
- **Disjungsi (**$p∨q$**):** Penggabungan dua pernyataan menggunakan kata hubung "atau".
   - Contoh: $p$ = Silvi pandai bermain piano, $q$ = Silvi pandai bermain biola. Disjungsinya adalah "Silvi pandai bermain piano atau biola".
   - Negasi Disjungsi: "Silvi tidak pandai bermain piano dan biola".
- **Konjungsi (**$p∧q$**):** Penggabungan dua pernyataan menggunakan kata hubung "dan".
   - Contoh: $p$ = Giska adalah anak yang cantik, $q$ = Giska adalah anak yang ramah. Konjungsinya adalah "Giska adalah anak yang cantik dan ramah".
   - Negasi Konjungsi: "Giska anak yang tidak cantik atau tidak ramah".
- **Implikasi (**$p⇒q$**) dan Biimplikasi (**$p⇔q$**):** Hubungan sebab-akibat bersyarat.
   - Contoh Implikasi: "Jika cuaca buruk maka penerbangan pesawat ditiadakan".
   - Contoh Konvers Implikasi: "Jika penerbangan pesawat ditiadakan maka cuaca buruk".
   - Contoh Biimplikasi: "Cuaca buruk jika dan hanya jika penerbangan pesawat ditiadakan".
- **Kuantor Universal dan Eksistensial:** Pengukuran jumlah yang menunjukkan keberadaan atau cakupan.
   - Kuantor Universal (Semua/Setiap): "Setiap bilangan prima lebih besar daripada 1" atau "Semua makhluk hidup bernapas".
   - Kuantor Eksistensial (Ada/Beberapa): "Ada kendaraan yang tidak berbahan bakar bensin" atau "Beberapa rumah tidak berpenghuni".

## 2. Penarikan Kesimpulan Deduktif (Contoh)

Penarikan kesimpulan ini menguji alur logika dari premis-premis yang disediakan menjadi sebuah konklusi mutlak.

**A. Modus Ponens** Menegaskan akibat jika sebabnya terjadi.

- Kasus 1:
   - **Premis 1:** Jika Fulan orang baik maka ia suka menolong.
   - **Premis 2:** Fulan orang baik.
   - **Kesimpulan:** Fulan suka menolong.
- Kasus 2:
   - **Premis 1:** Hari ini langit mendung maka hari ini akan hujan.
   - **Premis 2:** Hari ini langit mendung.
   - **Kesimpulan:** Hari ini akan hujan.

**B. Modus Tollens** Mengingkari sebab jika akibatnya tidak terjadi.

- Kasus 1:
   - **Premis 1:** Jika Anda tidak pulang maka Anda dimarahi.
   - **Premis 2:** Anda tidak dimarahi.
   - **Kesimpulan:** Anda pulang.
- Kasus 2:
   - **Premis 1:** Hari ini langit mendung maka hari ini akan hujan.
   - **Premis 2:** Hari ini tidak akan hujan.
   - **Kesimpulan:** Hari ini langit tidak mendung.

**C. Silogisme** Menarik kesimpulan berantai dari dua implikasi.

- Kasus 1:
   - **Premis 1:** Jika tanaman tidak disiram maka tanaman akan mati.
   - **Premis 2:** Jika tanaman mati maka tidak ada lagi hijaunya dedaunan.
   - **Kesimpulan:** Jika tanaman tidak disiram maka tidak ada lagi hijaunya dedaunan.
- Kasus 2:
   - **Premis 1:** Jika hari ini langit mendung maka akan turun hujan.
   - **Premis 2:** Jika hari ini hujan maka toni akan membawa payung.
   - **Kesimpulan:** Jika hari ini langit mendung maka toni membawa payung.

## 3. Penalaran Induktif (Contoh)

Penalaran induktif diaplikasikan untuk merumuskan pernyataan umum dari fakta-fakta spesifik atau mencari pola.

- **Generalisasi:** Membuat kesimpulan induktif berdasarkan pengamatan kumpulan fakta.
   - Fakta yang diamati: Manusia membutuhkan makanan; Hewan membutuhkan makanan; Tanaman membutuhkan makanan.
   - Kesimpulan induktif: Semua makhluk hidup membutuhkan makanan.
- **Analogi:** Mencari persamaan hubungan antar kata secara logis.
   - Kasus 1: Belajar : Pandai. Hubungan kata ini adalah proses dan hasil (dengan belajar menjadi pandai), yang ekuivalen dengan Berfikir : arif.
   - Kasus 2: Air : Es $∼$ Uap : .... Analogi yang tepat untuk mengisi kekosongan tersebut adalah Air.
- **Sebab Akibat:** Menelaah apakah dua pernyataan memiliki korelasi langsung.
   - Dalam pengerjaan soal sebab-akibat, peserta harus menentukan posisi pernyataan apakah bertindak sebagai sebab, akibat, atau sama sekali tidak berkaitan.
   - Panduan Jawaban: (A) pernyataan benar, alasan benar, dan keduanya menunjukkan hubungan sebab akibat; (B) pernyataan benar, alasan benar, tetapi keduanya tidak menunjukkan hubungan; (C) pernyataan benar dan alasan salah; (D) pernyataan salah dan alasan benar; (E) pernyataan dan alasan keduanya salah.
