---
id_sumber: prov-dasar
tingkat: provinsi
kompetensi: Dasar Pemrograman
urutan: 1
judul: Dasar Pemrograman
---
## 1. Konsep Algoritma dan Pemodelan Masalah

- **Definisi Algoritma:** Urutan langkah-langkah logis, efisien, dan terstruktur untuk menyelesaikan suatu permasalahan komputasional.
- **Bentuk Penulisan Algoritma:**
   - Kalimat Deskriptif: Menggunakan narasi terstruktur yang terdiri dari judul, kamus/deklarasi, dan deskripsi proses.
   - Pseudocode: Notasi tiruan (pseudo-code) yang menyerupai bahasa pemrograman tingkat tinggi untuk menjembatani logika pemikiran ke dalam kode sumber.
   - Flowchart: Diagram alir visual menggunakan simbol standar untuk merepresentasikan alur proses (Input, Process, Output, Decision).

## 2. Struktur Dasar Bahasa C++ & Lingkungan Pengembangan (IDE)

- **Struktur Program Utama C++:**

```cpp
#include <iostream> // Preprocessor directive untuk I/O
using namespace std;

int main() {
    // Blok tubuh program utama
    return 0;
}
```

- **Siklus Kompilasi & Eksekusi:** Meliputi penulisan di Jendela Editor, proses Compile (penerjemahan ke bahasa mesin), Linking (penggabungan pustaka), hingga Run (menghasilkan file .exe).

## 3. Tipe Data, Variabel, dan Konstanta

- **Tipe Data Primitif:**
   - Integer (int, short, long): Bilangan bulat.
   - Floating Point / Double (float, double): Bilangan pecahan/desimal.
   - Character (char): Karakter tunggal diapit tanda petik tunggal ('a').
   - Boolean (bool): Nilai kebenaran benar (1 / true) atau salah (0 / false).
- **Aturan Variabel & Konstanta:**
   - Nama variabel harus diawali huruf atau garis bawah (_), tidak boleh mengandung spasi atau operator aritmatika.
   - Konstanta bersifat tetap dan nilainya tidak boleh berubah selama program berjalan.

## 4. Operasi Masukan dan Keluaran (I/O)

- **Standar C (stdio.h):** Menggunakan printf() dengan penentu format (%d untuk integer, %f untuk float, %s untuk string) dan scanf() untuk membaca masukan.
- **Standar C++ (iostream):** Menggunakan objek cout dengan operator penggeser << untuk keluaran, serta cin dengan operator >> untuk masukan.

## Kasus 1: Aritmetika Sederhana & Pembagian Bilangan Bulat (Modul Ekspresi & I/O)

**Deskripsi Masalah:**

Pak Dengklek memiliki sejumlah permen yang akan dibagikan secara merata kepada $N$ orang keponakannya. Sisa permen yang tidak habis dibagi akan disimpan kembali oleh Pak Dengklek. Buatlah program untuk menghitung berapa banyak permen yang didapat masing-masing keponakan dan berapa sisa permen Pak Dengklek.

- **Format Masukan:** Dua buah bilangan bulat $M$ (jumlah total permen) dan $N$ (jumlah keponakan).
- **Format Keluaran:** Dua baris: baris pertama jumlah permen per anak, baris kedua sisa permen.

**Penyelesaian (C++):**

```cpp
#include <iostream>
using namespace std;

int main() {
    int m, n;
    cin >> m >> n;

    int dapat = m / n; // Pembagian bulat
    int sisa = m % n;  // Sisa bagi (modulo)

    cout << "Masing-masing mendapat: " << dapat << endl;
    cout << "Sisa permen: " << sisa << endl;
    return 0;
}
```

**Kasus 2: Penggunaan Format Desimal & Manipulator (Modul** iomanip **/** printf**)**

**Deskripsi Masalah:**

Sebuah toko online ingin menampilkan harga diskon produk dengan presisi tepat 2 angka di belakang koma agar rapi di layar monitor kasir. Jika harga asli barang adalah $H$ dan persentase diskon adalah $D\%$, hitunglah harga setelah diskon.

- **Contoh Masukan:** Harga = 150000, Diskon = 15 (%)
- **Perhitungan:** Besaran diskon = $150000×15/100=22500$. Harga akhir = $150000-22500=127500.00$.

**Penyelesaian (C++ menggunakan** printf **seperti dalam modul Nusa Mandiri):**

```cpp
#include <stdio.h>
#include <conio.h>

int main() {
    float harga_asli, diskon_persen, harga_akhir;

    // Membaca input
    printf("Masukkan Harga Asli: ");
    scanf("%f", &harga_asli);
    printf("Masukkan Persentase Diskon (%%): ");
    scanf("%f", &diskon_persen);

    // Proses hitung
    float potongan = harga_asli * (diskon_persen / 100.0);
    harga_akhir = harga_asli - potongan;

    // Menampilkan hasil dengan penentu lebar field/desimal %.2f
    printf("\n--- RINCIAN PEMBAYARAN ---\n");
    printf("Potongan Harga : Rp %.2f\n", potongan);
    printf("Harga Akhir    : Rp %.2f\n", harga_akhir);

    getch();
    return 0;
}
```
