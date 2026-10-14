---
id_sumber: prov-struktur-data
tingkat: provinsi
kompetensi: Struktur Data
urutan: 8
judul: Struktur Data
---
## Pendahuluan

Dalam pemrograman kompetitif, penyimpanan data dalam jumlah banyak secara terstruktur sangat krusial agar proses manipulasi, pencarian, dan pemrosesan berjalan cepat dan efisien.

## 1. Konsep Dasar Array (Larik)

Array adalah struktur data yang digunakan untuk menyimpan sekumpulan data dengan tipe data yang sama di dalam satu tempat dengan alokasi memori yang berurutan.

- **Indeks Array:** Setiap elemen di dalam array diakses melalui nomor indeks, di mana indeks array selalu dimulai dari angka nol (0).
- **Deklarasi Array:**
   - Array kosong dengan ukuran tertentu: int nama_array[10];
   - Array langsung diisi nilai awal: int nama_arr[3] = {0, 3, 2};
- **Panjang Array:** Bahasa pemrograman seperti C/C++ tidak menyediakan fungsi bawaan khusus untuk mendeteksi panjang array secara langsung, namun dapat dihitung menggunakan rumus ukuran memori:

```
int length = sizeof(nilai) / sizeof(*nilai);
```

## 2. Penggunaan Perulangan pada Array

Untuk memproses data array yang berukuran besar (misalnya ribuan elemen), penggunaan perintah cetak atau input secara manual sangat tidak efisien. Kita wajib memadukan array dengan struktur perulangan (looping) seperti for atau while untuk melakukan iterasi pengisian maupun pencetakan elemen secara otomatis berdasarkan indeksnya.

## 3. Array Multidimensi (Array 2 Dimensi & Matriks)

Array satu dimensi hanya menyimpan data dalam bentuk baris tunggal. Namun, untuk merepresentasikan tabel, koordinat grid, atau matriks matematika, kita membutuhkan Array Multidimensi (dua dimensi atau lebih).

- **Deklarasi Array 2 Dimensi:**

```cpp
int matriks[3][3] = {
    {1, 3, 5},
    {5, 3, 1},
    {6, 2, 3}
};
```

- **Cara Mengakses Data:** Menggunakan dua indeks, yaitu indeks baris dan indeks kolom: matriks[baris][kolom]. Sebagai contoh, matriks[1][0] mengakses baris indeks ke-1 dan kolom indeks ke-0.

## 4. String dan Operasinya dalam Pemrograman

- **Pengertian String:** Secara mendasar di dalam bahasa C, string merupakan **array satu dimensi dari tipe data karakter (**char**)** yang diakhiri dengan karakter khusus penanda akhir string, yaitu null terminator (\0).
- **Deklarasi String:**
   - Menggunakan array karakter: char namaKontak[32] = "Ayu";
   - Menggunakan pustaka standar string (<string.h> atau <string> di C++) untuk operasi lanjutan seperti penggabungan (concatenation), penyalinan (copy), dan pengecekan panjang string (strlen()).

## 5. Studi Kasus: Menghitung Nilai Rata-Rata Tinggi Badan Menggunakan Array 1 Dimensi

- **Deskripsi Masalah:** Diberikan data tinggi badan dari 10 orang siswa. Program harus menyimpan data tersebut ke dalam array, menjumlahkan seluruh nilainya, lalu menghitung rata-rata tinggi badan secara otomatis menggunakan perulangan.
- **Implementasi C++:**

```cpp
#include <iostream>
using namespace std;

int main() {
    // Membuat array tinggi badan dengan 10 elemen
    int tinggi_badan[10] = {175, 165, 166, 157, 184, 156, 163, 176, 171, 169};

    // Menghitung panjang array secara dinamis
    int length = sizeof(tinggi_badan) / sizeof(*tinggi_badan);
    int sum = 0;

    // Menggunakan perulangan untuk menjumlahkan seluruh elemen array
    for(int i = 0; i < length; i++){
        sum += tinggi_badan[i];
    }

    // Menghitung rata-rata
    float rata_rata = (float)sum / (float)length;

    cout << "Rata-rata tinggi badan: " << rata_rata << endl;
    return 0;
}
```

## 6. Studi Kasus: Pengaksesan Elemen pada Array 2 Dimensi (Matriks)

- **Deskripsi Masalah:** Menyimpan sekumpulan data numerik ke dalam bentuk matriks 2 dimensi berukuran 3x3 dan mengakses elemen tertentu berdasarkan koordinat baris dan kolomnya.
- **Implementasi C++:**

```cpp
#include <iostream>
using namespace std;

int main() {
    // Inisialisasi array dua dimensi (Matriks 3x3)
    int matriks[3][3] = {
        {1, 3, 5},
        {5, 3, 1},
        {6, 2, 3}
    };

    // Mengakses data pada baris indeks ke-1 dan kolom indeks ke-0
    cout << "Isi Data pada indeks ke-(1,0): " << matriks[1][0] << endl;

    return 0;
}
```
