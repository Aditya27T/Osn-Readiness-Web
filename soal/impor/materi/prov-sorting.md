---
id_sumber: prov-sorting
tingkat: provinsi
kompetensi: Pencarian & Pengurutan
urutan: 6
judul: Pencarian & Pengurutan
---
## Pendahuluan

Dalam dunia kompetisi pemrograman (competitive programming), pemilihan algoritma pencarian dan pengurutan yang tepat sangat menentukan apakah program kita akan berjalan dengan cepat (lolos batas waktu / Time Limit Exceeded) atau justru gagal karena terlalu lambat.

## 1. Algoritma Pencarian (Searching)

Pencarian adalah proses menemukan keberadaan suatu elemen atau nilai tertentu di dalam sekumpulan data.

### A. Linear Search (Pencarian Sekuensial)

- **Cara Kerja:** Memeriksa setiap elemen data satu per satu secara berurutan mulai dari elemen pertama hingga elemen terakhir.
- **Karakteristik:** Sangat sederhana dan tidak memerlukan syarat data harus terurut. Namun, metode ini sangat lambat jika data berukuran sangat besar.
- **Kompleksitas Waktu:** $O\left(N\right)$ dalam kasus terburuk.

### B. Binary Search (Pencarian Biner Konvensional)

- **Cara Kerja:** Digunakan khusus untuk data yang sudah terurut. Algoritma ini membandingkan nilai yang dicari dengan elemen di tengah array. Jika nilai yang dicari lebih kecil, pencarian dilanjutkan ke paruh kiri; jika lebih besar, dilanjutkan ke paruh kanan. Proses ini memotong rentang pencarian menjadi separuh di setiap langkahnya.
- **Kompleksitas Waktu:** $O\left(log N\right)$, jauh lebih cepat daripada Linear Search.

### C. Tingkat Lanjut: Binary Search on Answer (Pencarian Biner pada Jawaban)

Terkadang, soal tidak meminta kita mencari indeks di dalam array, melainkan mencari nilai jawaban akhir (seperti angka minimum atau maksimum yang memenuhi syarat tertentu).

- **Konsep Utama:** Kita mendefinisikan ruang pencarian (search space) dari batas bawah (low) hingga batas atas (high) dari kemungkinan jawaban.
- **Sifat Monotonik:** Metode ini bisa digunakan jika permasalahan memiliki sifat monotonik—artinya, jika suatu nilai jawaban memenuhi syarat, maka nilai-nilai setelah atau sebelumnya memiliki pola keteraturan yang jelas (misalnya bernilai benar secara konsisten hingga batas tertentu, lalu salah seterusnya).
- **Langkah Pengerjaan:**
   1. Tentukan batas nilai terkecil (low) dan terbesar (hi) dari jawaban.
   1. Ambil nilai tengah (mid = (lo + hi) / 2).
   1. Uji apakah nilai mid valid memenuhi kondisi soal.
   1. Jika valid, simpan sebagai jawaban sementara lalu sempitkan rentang pencarian untuk mencari nilai yang lebih optimal.

## 2. Algoritma Pengurutan (Sorting)

Mengurutkan data (sorting) adalah proses menyusun kembali elemen-elemen data berdasarkan urutan tertentu (biasanya dari kecil ke besar / ascending, atau sebaliknya). Pengurutan adalah langkah awal (preprocessing) yang sangat penting agar algoritma lain seperti Binary Search dapat digunakan.

### A. Algoritma Kuadratik (O\left({N}^{2}\right))

- Bubble Sort & Insertion Sort: Algoritma dasar yang bekerja dengan membandingkan dan menukar elemen-elemen yang berdampingan. Mudah dipahami dan diimplementasikan, tetapi sangat lambat dan tidak efisien jika jumlah data melebihi puluhan ribu elemen.

### B. Algoritma Logaritmik (O\left(Nlog N\right))

- Merge Sort: Menggunakan paradigma Divide and Conquer (membagi data menjadi dua bagian, mengurutkannya secara rekursif, lalu menggabungkannya kembali / merge). Algoritma ini sangat stabil dan efisien.
- Quick Sort: Menggunakan elemen pemilih (pivot) untuk mempartisi array menjadi bagian yang lebih kecil dan lebih besar.
- Fungsi Bawaan C++ (std::sort): Dalam pemrograman kompetitif C++, kita hampir selalu menggunakan fungsi pustaka bawaan std::sort() karena dioptimalkan untuk berjalan dalam waktu $O\left(Nlog N\right)$.

## 3. Studi Kasus: Binary Search on Answer (Mencari Kecepatan Makan Buah Minimum)

- **Deskripsi Masalah:** Diberikan $N$ kotak buah dengan jumlah buah di setiap kotak disimpan dalam array arr, serta batas waktu total $H$ jam. Dalam satu jam, kita dapat memilih satu jenis buah dan memakan paling banyak $X$ buah dari kotak tersebut. Tentukan nilai minimum dari kecepatan $X$ agar seluruh buah dapat habis dalam waktu maksimum $H$ jam.
- **Implementasi C++:**

```cpp
#include <bits/stdc++.h>
using namespace std;

int minXvalue(vector<int>& arr, int H) {
    // Batas bawah (lo) dan batas atas (hi) untuk ruang pencarian jawaban
    int lo = 1;
    int hi = *max_element(arr.begin(), arr.end());
    int ans = hi;

    while (lo <= hi) {
        int x = lo + (hi - lo) / 2;
        long long req_time = 0;

        // Hitung waktu yang dibutuhkan dengan kecepatan x
        for (int val : arr) {
            req_time += (val + x - 1) / x; // Pembulatan ke atas (ceil division)
        }

        // Jika waktu memenuhi syarat (<= H), simpan jawaban dan cari yang lebih kecil di kiri
        if (req_time <= H) {
            ans = x;
            hi = x - 1;
        } else {
            // Jika waktu kurang, tingkatkan kecepatan (geser ke kanan)
            lo = x + 1;
        }
    }
    return ans;
}

int main() {
    vector<int> arr = {8, 11, 18, 20};
    int h = 10;

    int result = minXvalue(arr, h);
    cout << "Minimum nilai X (kecepatan makan) adalah: " << result << endl;
    return 0;
}
```

## 4. Studi Kasus: Pengurutan Cepat Menggunakan std::sort (O\left(Nlog N\right))

- **Deskripsi Masalah:** Diberikan sekumpulan data skor pemain dalam sebuah papan peringkat (leaderboard), urutkan skor tersebut dari yang terkecil ke terbesar sebelum melakukan pencarian biner atau pemrosesan lebih lanjut.
- **Implementasi C++:**

```cpp
#include <iostream>
#include <vector>
#include <algorithm>
using namespace std;

int main() {
    vector<int> skor = {45, 12, 89, 33, 71, 5};

    // Mengurutkan vektor secara ascending (kecil ke besar)
    sort(skor.begin(), skor.end());

    cout << "Skor setelah diurutkan: ";
    for (int s : skor) {
        cout << s << " ";
    }
    cout << endl;

    return 0;
}
```
