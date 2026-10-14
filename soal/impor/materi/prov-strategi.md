---
id_sumber: prov-strategi
tingkat: provinsi
kompetensi: Strategi Pemecahan Masalah
urutan: 7
judul: Strategi Pemecahan Masalah
---
## Pendahuluan

Dalam pemrograman kemampuan merancang algoritma yang efisien adalah kunci utama. Sering kali, sebuah permasalahan tidak dapat diselesaikan hanya dengan cara coba-coba yang lambat, melainkan memerlukan strategi optimasi yang sistematis seperti Brute-force, Greedy, dan terutamanya Dynamic Programming (DP).

## 1. Pendekatan Brute-force & Kompleksitas Dasar

- **Pengertian:** Brute-force adalah strategi pemecahan masalah yang dilakukan secara langsung dengan mencoba seluruh kemungkinan solusi yang ada tanpa pandang bulu.
- **Karakteristik:** Sangat mudah dirancang dan diimplementasikan secara logika, namun memiliki kelemahan fatal berupa kompleksitas waktu yang sangat tinggi (biasanya eksponensial $O\left({2}^{n}\right)$ atau faktorial $O\left(n!\right)$).
- **Batasan Penggunaan:** Hanya dapat digunakan untuk menyelesaikan masalah dengan ukuran masukan ($N$) yang sangat kecil. Jika masukan besar, algoritma ini akan mengalami Time Limit Exceeded (TLE).

## 2. Pengantar Dynamic Programming (DP)

Istilah Dynamic Programming dicetuskan oleh Richard Bellman pada tahun 1950-an. Kata "Dynamic" dipilih agar terdengar menarik, sementara "Programming" merujuk pada perencanaan atau pembuatan tabel keputusan (bukan coding komputer).

### A. Inti Konsep Dynamic Programming

Dynamic Programming adalah strategi pemecahan masalah dengan cara memecah masalah besar menjadi sub-masalah yang lebih kecil, lalu mengingat (menyimpan) hasil dari sub-masalah tersebut agar tidak perlu dihitung ulang dari awal.

### B. Dua Syarat Utama Penggunaan DP

Sebuah permasalahan hanya dapat diselesaikan menggunakan DP jika memiliki dua sifat khusus berikut:

1. Overlapping Subproblems (Sub-Masalah yang Tumpang Tindih): Saat masalah besar dipecah, kita akan menemukan sub-masalah kecil yang sama persis dan muncul berulang-ulang. DP menghitung sub-masalah tersebut sekali saja, lalu menyimpannya.
1. Optimal Substructure (Substruktur Optimal): Solusi optimal untuk masalah besar dapat dibangun dari gabungan solusi optimal sub-masalah kecil bagianannya.

## 3. Dua Pendekatan Utama dalam Dynamic Programming

Terdapat dua cara utama dalam mengimplementasikan algoritma Dynamic Programming:

### A. Top-Down (Memoization / "Metode Catatan")

- **Cara Kerja:** Kita tetap menggunakan fungsi rekursif seperti biasa (mulai dari masalah besar turun ke bawah / base case). Namun, sebelum menghitung suatu nilai, fungsi akan mengecek sebuah tabel catatan (memo) terlebih dahulu.
- **Mekanisme:**
   - Jika nilai sudah ada di catatan, langsung kembalikan nilainya secara instan ($O\left(1\right)$).
   - Jika belum ada, hitung nilainya, lalu simpan ke dalam catatan sebelum dikembalikan.
- **Analisis Waktu:** Waktu pengerjaan dihitung melalui rumus $\text{Work per subproblem}×\text{Number of subproblems}$.

### B. Bottom-Up (Tabulation / "Metode Tabel")

- **Cara Kerja:** Menggunakan pendekatan iteratif (perulangan/looping) tanpa rekursi. Kita mulai menyelesaikan dari sub-masalah terkecil (bawah), lalu menyusun dan memperluasnya naik secara bertahap hingga mencapai masalah utama.
- **Kelebihan:** Sangat cepat, tidak memiliki risiko kehabisan memori tumpukan (stack overflow), dan dapat dioptimalkan penggunaan memorinya (misalnya mereduksi memori dari $O\left(n\right)$ menjadi $O\left(1\right)$ jika hanya butuh beberapa data sebelumnya).

## 4. Studi Kasus Klasik: Barisan Fibonacci

Sebagai perbandingan nyata efisiensi strategi:

- **Rekursif Naif (Brute-force):** Memiliki kompleksitas waktu $O\left({2}^{n}\right)$ (eksponensial). Untuk mencari $f\left(40\right)$, program harus melakukan miliaran operasi karena menghitung ulang sub-masalah yang sama berkali-kali.
- **Dengan Dynamic Programming (Top-Down/Bottom-Up):** Kompleksitas waktu dipangkas secara drastis menjadi $O\left(n\right)$ (linear) karena setiap nilai $f\left(i\right)$ hanya dihitung tepat satu kali.

## 5. Daftar Masalah Klasik Dynamic Programming

Untuk menguasai DP, berikut adalah deretan masalah klasik yang menjadi fondasi utama dalam kompetisi pemrograman:

1. **0-1 Knapsack:** Memilih subset barang dengan bobot tertentu untuk memaksimalkan total nilai tanpa melanggar kapasitas maksimal berat tas.
1. **Subset Sum:** Menentukan apakah ada subset dari sekumpulan bilangan bulat yang jumlahnya tepat sama dengan target $T$.
1. **Longest Increasing Subsequence (LIS):** Mencari panjang barisan subsequence terpanjang di mana setiap elemen nilainya lebih besar dari elemen sebelumnya.
1. **Counting Paths in a 2D Array:** Menghitung total jalur unik dari sudut kiri atas ke sudut kanan bawah pada matriks grid.
1. **Longest Common Subsequence (LCS):** Menemukan panjang string terpanjang yang merupakan subsequence dari dua buah string berbeda.
1. **Edit Distance:** Menghitung jumlah operasi minimal (tambah, hapus, ganti) untuk mengubah satu string menjadi string lainnya.
1. **Rod Cutting, Longest Palindromic Subsequence, & DAG Path.**

## 6. Studi Kasus: Barisan Fibonacci dengan DP Top-Down (Memoization)

- **Konsep:** Menggunakan fungsi rekursif yang dikombinasikan dengan tabel catatan (memo) agar setiap sub-masalah hanya dihitung satu kali, memangkas waktu dari eksponensial $O\left({2}^{n}\right)$ menjadi linear $O\left(n\right)$.
- **Implementasi C++:**

```cpp
#include <bits/stdc++.h>
using namespace std;

// Siapkan array memoisasi, isi dengan -1 (artinya belum dihitung)
long long memo[100];

long long fib_memo(int n) {
    // 1. Cek catatan terlebih dahulu
    if (memo[n] != -1) {
        return memo[n];
    }

    // Base case
    if (n <= 1) {
        return n;
    }

    // 2. Hitung dan simpan ke dalam catatan sebelum dikembalikan
    return memo[n] = fib_memo(n - 1) + fib_memo(n - 2);
}

int main() {
    memset(memo, -1, sizeof(memo));
    int n = 45;

    cout << "Hasil Fibonacci ke-" << n << " (Top-Down Memoization): " << fib_memo(n) << endl;
    return 0;
}
```

## 7. Studi Kasus: Barisan Fibonacci dengan DP Bottom-Up (Tabulation)

- **Konsep:** Menyelesaikan masalah secara iteratif dari sub-masalah terkecil (bawah) lalu membangun nilainya naik ke atas menggunakan tabel array, tanpa memerlukan fungsi rekursif.
- **Implementasi C++:**

```cpp
#include <iostream>
#include <vector>
using namespace std;

long long fib_tab(int n) {
    if (n <= 1) return n;

    // 1. Buat tabel (array) untuk menyimpan hasil
    vector<long long> tabel(n + 1);

    // 2. Isi nilai dasar
    tabel[0] = 0;
    tabel[1] = 1;

    // 3. Bangun sisanya dari bawah ke atas menggunakan perulangan
    for (int i = 2; i <= n; i++) {
        tabel[i] = tabel[i - 1] + tabel[i - 2];
    }

    // 4. Jawaban berada di ujung tabel
    return tabel[n];
}

int main() {
    int n = 45;
    cout << "Hasil Fibonacci ke-" << n << " (Bottom-Up Tabulation): " << fib_tab(n) << endl;
    return 0;
}
```
