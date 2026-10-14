---
id_sumber: prov-aritmetika
tingkat: provinsi
kompetensi: Aritmetika
urutan: 3
judul: Aritmetika
---
## 1. Teori Bilangan: Keterbagian (Divisibilitas)

Keterbagian adalah landasan utama dalam menyelesaikan berbagai persoalan sisa bagi dan optimasi bilangan bulat.

- **Definisi Keterbagian:** Untuk dua buah bilangan bulat $a$ dan $b$ dengan $a≠0$, kita katakan $a$ **membagi** $b$ (notasi: $a∣b$) jika terdapat suatu bilangan bulat $c$ sedemikian rupa sehingga $b=ac$.
- Sifat-Sifat Penting Keterbagian:
   - Jika $a∣b$ dan $b≠0$, maka $|a|≤|b|$.
   - Jika $a∣b$ dan $a∣c$, maka $a∣\left(αb+βc\right)$ untuk sembarang bilangan bulat $α$ dan $β$ (Sifat Linieritas).
   - Jika $a∣b$ dan $b∣c$, maka $a∣c$ (Sifat Transitif).
   - Jika $a∣b$ dan $b∣a$, maka $|a|=|b|$.
- **Algoritma Pembagian (Division Algorithm):** Untuk sembarang bilangan bulat $a$ dan $b$ dengan $a≠0$, selalu terdapat pasangan bilangan bulat unik $\left(q,r\right)$ yang memenuhi:

$$b=aq+r \text{dengan }0≤r<|a|$$

di mana $q$ adalah hasil bagi (quotient) dan $r$ adalah sisa pembagian (remainder).

## 2. Bilangan Prima & Faktorisasi Kanonikal

Bilangan prima memiliki peran krusial dalam kriptografi, pencarian faktor, dan optimasi struktur data.

- **Definisi Bilangan Prima:** Bilangan bulat $p>1$ dikatakan prima jika tidak ada bilangan bulat lain $d>1$ yang membaginya selain $1$ dan $p$ itu sendiri. Bilangan bulat $n>1$ yang bukan prima disebut bilangan komposit.
- **Teorema Pembagi Prima Minimum:** Jika $n$ adalah bilangan komposit, maka $n$ pasti memiliki sebuah pembagi prima $p$ yang nilainya tidak melebihi $\sqrt{n}$ ($p≤\sqrt{n}$).
- **Teorema Faktorisasi Prima (Tunggal):** Setiap bilangan bulat $n>1$ mempunyai representasi yang unik sebagai produk dari bilangan-bilangan prima, yang disebut sebagai **faktorisasi kanonikal**:

$$n={p}_{1}^{{α}_{1}}{p}_{2}^{{α}_{2}}⋯{p}_{k}^{{α}_{k}}$$

- **Teorema Euclid:** Jumlah bilangan prima adalah tak terhingga.

## 3. FPB (GCD), KPK (LCM), dan Algoritma Euclidean

- **Faktor Persekutuan Terbesar (FPB / GCD):** Elemen maksimal dari irisan himpunan pembagi positif antara dua bilangan $m$ dan $n$ (notasi: $\text{fpb}\left(m,n\right)$). Dua bilangan disebut **relatif prima** jika $\text{fpb}\left(m,n\right)=1$.
- **Algoritma Euclidean:** Metode sangat efisien untuk mencari FPB menggunakan penerapan berulang dari Algoritma Pembagian:

$$m=n{q}_{1}+{r}_{1} \left(0≤{r}_{1}<n\right)$$

$$n={r}_{1}{q}_{2}+{r}_{2} \left(0≤{r}_{2}<{r}_{1}\right)$$

Sisa pembagian terakhir yang bernilai tidak nol (${r}_{k}$) adalah nilai $\text{fpb}\left(m,n\right)$.

- **Hubungan FPB dan KPK:** Untuk setiap dua bilangan bulat positif $m$ dan $n$, berlaku relasi dasar:

$$m×n=\text{fpb}\left(m,n\right)×\text{kpk}\left(m,n\right)$$

## 4. Aritmetika Modular & Kongruensi

Aritmetika modular mempelajari sisa dari suatu operasi aritmetika tanpa harus memproses nilai angka aslinya yang sangat besar.

- **Definisi Kongruensi:** Bilangan bulat $a$ dan $b$ dikatakan kongruen modulo $n$ (ditulis $a≡b \left(mod n\right)$) jika $n∣\left(a-b\right)$.
- Sifat Aljabar Kongruensi:
   1. Jika $a≡b \left(mod n\right)$ dan $c≡d \left(mod n\right)$, maka $\left(a+c\right)≡\left(b+d\right) \left(mod n\right)$.
   1. Jika $a≡b \left(mod n\right)$ dan $c≡d \left(mod n\right)$, maka $\left(a-c\right)≡\left(b-d\right) \left(mod n\right)$.
   1. Jika $a≡b \left(mod n\right)$ dan $c≡d \left(mod n\right)$, maka $\left(ac\right)≡\left(bd\right) \left(mod n\right)$.
- **Pola Pangkat Berulang:** Dalam komputasi, untuk menghitung ${a}^{b} \left(mod m\right)$, nilai sisa perpangkatan akan selalu masuk ke dalam siklus pola yang berulang setelah sejumlah langkah tertentu, sehingga kita dapat mereduksi eksponen $b$ menggunakan modulo periode siklus tersebut.

## 5. Analisis Digit Terakhir & Paritas

- **Digit Terakhir:** Digit paling belakang dari suatu bilangan desimal ekuivalen dengan sisa pembagian bilangan tersebut dengan $10$ ($N \left(mod 10\right)$).
- **Siklus Pola Pangkat Digit Terakhir:**
   - Basis yang berakhiran $2,3,7,8$ memiliki pola siklus digit terakhir yang berulang setiap **4 satuan pangkat** ($n \left(mod 4\right)$).
   - Basis yang berakhiran $4$ dan $9$ berulang setiap **2 satuan pangkat** ($n \left(mod 2\right)$).
   - Basis berakhiran $0,1,5,6$ menghasilkan digit terakhir yang konstan / tetap.
- **Paritas (Genap & Ganjil):** Merupakan bentuk khusus dari modulo 2. Operasi penjumlahan dan perkalian paritas mengikuti hukum aljabar biner (misal: $\text{ganjil}×\text{ganjil}=\text{ganjil}$).

## 6. Prinsip Kombinatorika Dasar & Lanjutan dalam Aritmetika

Persoalan diskrit sering kali melibatkan teknik pencacahan dan batasan struktur:

- **Aturan Penjumlahan & Perkalian:** Jika suatu tugas terdiri dari langkah-langkah yang saling lepas, total cara adalah penjumlahan; jika berupa urutan langkah beruntun, total cara adalah perkalian.
- **Permutasi & Kombinasi:**
   - Permutasi memperhatikan urutan: $P\left(n,r\right)=\frac{n!}{\left(n-r\right)!}$.
   - Kombinasi mengabaikan urutan: $\left(\frac{n}{r}\right)=\frac{n!}{r!\left(n-r\right)!}$.
- **Prinsip Sarang Merpati (Pigeonhole Principle / Dirichlet):** Jika sejumlah $n+1$ atau lebih objek dimasukkan ke dalam $n$ buah kotak, maka minimal terdapat satu kotak yang memuat lebih dari satu objek. Prinsip ini sangat ampuh dalam membuktikan eksistensi suatu pola atau sisa bagi tertentu dalam kompetisi.
- **Prinsip Inklusi-Eksklusi:** Digunakan untuk menghitung kardinalitas gabungan beberapa himpunan yang saling beririsan (misalnya mencari jumlah angka yang habis dibagi oleh bilangan tertentu dalam rentang tertentu).

## 7. Studi Kasus: Pencarian FPB (GCD) Menggunakan Algoritma Euclidean

- **Deskripsi Masalah:** Diberikan dua buah bilangan bulat positif $m$ dan $n$. Carilah Faktor Persekutuan Terbesar (FPB) dari kedua bilangan tersebut seefisien mungkin.
- **Implementasi C++:**

```cpp
#include <iostream>
using namespace std;

// Fungsi rekursif untuk Algoritma Euclidean
long long fpb(long long a, long long b) {
    if (b == 0) {
        return a;
    }
    return fpb(b, a % b);
}

int main() {
    long long m, n;
    cin >> m >> n;

    cout << "FPB dari " << m << " dan " << n << " adalah: " << fpb(m, n) << endl;
    return 0;
}
```

## 8. Studi Kasus: Menentukan Digit Terakhir Bilangan Berpangkat Besar

- **Deskripsi Masalah:** Berdasarkan materi teori bilangan, digit terakhir dari suatu bilangan desimal dapat ditentukan dengan operasi modulo 10. Untuk bilangan berpangkat besar seperti ${7}^{2026}$, kita menggunakan siklus pola sisa pangkat yang berulang setiap 4 satuan pangkat.
- **Implementasi C++:**

```cpp
#include <iostream>
using namespace std;

int main() {
    // Mencari digit terakhir dari 7^2026
    // Pola digit terakhir 7^n modulo 10 berulang setiap 4 pola:
    // n mod 4 = 1 -> 7
    // n mod 4 = 2 -> 9
    // n mod 4 = 3 -> 3
    // n mod 4 = 0 -> 1

    long long basis = 7;
    long long pangkat = 2026;

    int sisa_pangkat = pangkat % 4;
    int digit_terakhir;

    if (sisa_pangkat == 1) digit_terakhir = 7;
    else if (sisa_pangkat == 2) digit_terakhir = 9;
    else if (sisa_pangkat == 3) digit_terakhir = 3;
    else digit_terakhir = 1;

    cout << "Digit terakhir dari 7^2026 adalah: " << digit_terakhir << endl;
    return 0;
}
```

## 9. Studi Kasus: Prinsip Inklusi-Eksklusi untuk Keterbagian

- **Deskripsi Masalah:** Berapa banyak bilangan bulat positif dari 1 sampai 100 yang habis dibagi oleh 6 atau 9? Kita dapat menyelesaikannya menggunakan rumus Prinsip Inklusi-Eksklusi.
- **Implementasi C++:**

```cpp
#include <iostream>
using namespace std;

int main() {
    int batas = 100;
    int habis_6 = batas / 6;       // 100 / 6 = 16
    int habis_9 = batas / 9;       // 100 / 9 = 11
    // KPK dari 6 dan 9 adalah 18
    int habis_18 = batas / 18;     // 100 / 18 = 5

    // Rumus Inklusi-Eksklusi: |A gabung B| = |A| + |B| - |A iris B|
    int total = habis_6 + habis_9 - habis_18;
    cout << "Jumlah bilangan antara 1 dan 100 yang habis dibagi 6 atau 9 adalah: " << total << endl;
    return 0;
}
```
