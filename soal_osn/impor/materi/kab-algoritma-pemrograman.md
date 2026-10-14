---
id_sumber: kab-algoritma-pemrograman
tingkat: kabupaten
kompetensi: Membaca Algoritma & Pemrograman
urutan: 5
judul: Membaca Algoritma & Pemrograman
---
## 1. Masukan/Keluaran (I/O) & Membaca Algoritma

- **Konsep Dasar I/O:** Program komputer membutuhkan interaksi untuk membaca data (input) dari pengguna atau file, memprosesnya, lalu mencetak hasil (output) ke layar. Dalam C++, standar I/O menggunakan pustaka <iostream> (cin untuk input dan cout untuk output) atau pustaka C <stdio.h> (scanf dan printf).
- **Membaca Algoritma:** Kemampuan menelusuri alur logika (tracing) dari suatu program atau pseudocode tanpa harus selalu menjalankannya di komputer.
- **Bentuk Algoritma:**
   - Kalimat Deskriptif: Menggunakan narasi terstruktur yang terdiri dari judul, kamus/deklarasi variabel, dan deskripsi proses.
   - Pseudocode: Notasi yang menyerupai bahasa pemrograman tingkat tinggi untuk menjembatani rancangan logika.
   - Flowchart: Diagram alir yang menggunakan simbol standar (seperti jajar genjang untuk I/O, persegi panjang untuk proses, dan belah ketupat untuk kondisi/decision).

## 2. Percabangan (Branching / Selection)

- Digunakan ketika program harus mengambil keputusan atau memilih jalur eksekusi berbeda berdasarkan kondisi tertentu.
- **Struktur Percabangan di C++:**
   - if: Menjalankan blok perintah hanya jika kondisi bernilai benar (true).
   - if-else: Menyediakan jalur alternatif jika kondisi bernilai salah (false).
   - nested if & if-else majemuk: Percabangan bertingkat untuk menangani banyak kondisi sekaligus.
   - switch-case: Alternatif percabangan untuk memeriksa nilai diskrit dari suatu variabel bertipe integer atau karakter secara lebih rapi.

## 3. Perulangan (Looping)

- Digunakan untuk mengeksekusi blok kode yang sama secara berulang kali sampai suatu kondisi berhenti tercapai.
- **Jenis-jenis Perulangan:**
   - for: Digunakan ketika jumlah iterasi atau perulangan sudah diketahui sejak awal.
   - while: Perulangan yang dikendalikan oleh kondisi di awal; blok kode akan terus diulang selama syarat bernilai benar.
   - do-while: Mirip dengan while, namun pengecekan kondisi dilakukan di akhir, sehingga blok kode dipastikan minimal berjalan sekali.
- **Kontrol Perulangan:** Perintah break untuk menghentikan paksa perulangan, dan continue untuk melewati iterasi saat ini dan langsung lanjut ke iterasi berikutnya.

## 4. Array (Larik)

- Struktur data linear yang menampung sekumpulan elemen dengan tipe data yang sama di dalam alokasi memori yang berurutan.
- **Indeks Array:** Pengaksesan elemen di dalam array selalu dimulai dari indeks ke-0 hingga $N-1$ (di mana $N$ adalah ukuran array).
- **Dimensi Array:**
   - Array 1 Dimensi: Digunakan untuk menyimpan daftar nilai atau barisan data.
   - Array 2 Dimensi: Digunakan untuk merepresentasikan matriks, koordinat grid, atau tabel data berbaris dan berkolom.

## 5. Subprogram (Fungsi dan Prosedur)

- Teknik memecah program utama yang besar menjadi modul-modul kecil agar kode lebih terstruktur, mudah dibaca, dan dapat digunakan kembali (reusable).
- **Prosedur (**void**):** Blok kode yang menjalankan tugas tertentu tanpa mengembalikan nilai balik.
- **Fungsi:** Blok kode yang melakukan perhitungan dan mengembalikan suatu nilai ke program pemanggil menggunakan kata kunci return.

## 6. Rekursi (Recursion)

- Sebuah fungsi atau subprogram yang memanggil dirinya sendiri secara berulang untuk menyelesaikan masalah besar dengan memecahnya menjadi sub-masalah yang lebih kecil.
- **Komponen Wajib Rekursi:**
   - Basis (Base Case): Kondisi berhenti di mana fungsi tidak lagi memanggil dirinya sendiri (mencegah infinite loop / stack overflow).
   - Langkah Rekurens (Recursive Step): Bagian di mana fungsi memanggil dirinya sendiri dengan parameter yang mendekati kondisi basis.

## Studi Kasus: Validasi Nilai Ujian dan Kategori Kelulusan

- **Deskripsi:** Program membaca nama dan nilai akhir seorang peserta. Jika nilai $≥75$, status lulus; jika di bawah itu, tidak lulus.
- **Implementasi C++:**

```
#include <iostream>
#include <string>
using namespace std;

int main() {
    string nama;
    int nilai;

    // Input data
    cin >> nama >> nilai;

    // Percabangan
    if (nilai >= 75) {
        cout << nama << " dinyatakan LULUS." << endl;
    } else {
        cout << nama << " dinyatakan TIDAK LULUS." << endl;
    }
    return 0;
```

}

## Studi Kasus: Pencarian Nilai Terbesar (Maximum Element) dalam Array

- **Deskripsi:** Diberikan daftar nilai dari $N$ siswa menggunakan array 1 dimensi. Program harus mencari nilai tertinggi di antara semuanya.
- **Implementasi C++:**

```
#include <iostream>
using namespace std;
int main() {
    int n;
    cin >> n;
    int nilai[100]; // Array 1 dimensi[cite: 14]

    for (int i = 0; i < n; i++) {
        cin >> nilai[i];
    }

    int maks = nilai[0];
    for (int i = 1; i < n; i++) {
        if (nilai[i] > maks) {
            maks = nilai[i];
        }
    }

    cout << "Nilai tertinggi adalah: " << maks << endl;
    return 0;
```

## Studi Kasus: Menghitung Faktorial secara Rekursif

- **Deskripsi:** Menghitung nilai $N!$ (Faktorial) menggunakan konsep fungsi rekursi (memecah masalah menjadi kasus basis dan langkah rekurens).
- **Implementasi C++:**

```
#include <iostream>
using namespace std;

// Fungsi Rekursi Faktorial
int faktorial(int n) {
    // Basis (Base Case)
    if (n == 0 || n == 1) {
        return 1;
    }
    // Langkah Rekurens
    return n * faktorial(n - 1);
}

int main() {
    int n;
    cin >> n;
    cout << "Faktorial dari " << n << " adalah: " << faktorial(n) << endl;
    return 0;
}
```
