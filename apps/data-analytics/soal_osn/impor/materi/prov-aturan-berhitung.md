---
id_sumber: prov-aturan-berhitung
tingkat: provinsi
kompetensi: Aturan Berhitung
urutan: 4
judul: Aturan Berhitung
---
## 1. Aturan Dasar Pencacahan

- Aturan Penjumlahan (Rule of Sum): Jika suatu tugas dapat diselesaikan melalui $k$ himpunan bagian yang saling asing (${A}_{1},{A}_{2},…,{A}_{k}$), maka total cara penyelesaiannya adalah penjumlahan kardinalitas masing-masing himpunan:

$$|{A}_{1}∪{A}_{2}∪…∪{A}_{k}|=\sum_{i=1}^{k} |{A}_{i}|$$

- Aturan Perkalian (Rule of Product): Jika suatu pekerjaan melibatkan $k$ langkah berurutan yang saling bebas, di mana langkah ke-$i$ dapat dikerjakan dalam ${n}_{i}$ cara, maka keseluruhan pekerjaan dapat dilaksanakan dalam ${n}_{1}×{n}_{2}×…×{n}_{k}$ cara.

## 2. Relasi dan Fungsi dalam Kombinatorika

Dalam pemodelan diskrit, hubungan antara dua himpunan $A$ dan $B$ dinyatakan melalui relasi dan fungsi:

- Fungsi Injektif (Satu-satu): Setiap anggota di daerah hasil (range) menjadi kawan tunggal dari domain, dan tidak ada dua elemen domain berbeda yang dipetakan ke elemen kodomain yang sama.
- Fungsi Surjektif (Onto / Pada): Fungsi yang memiliki daerah hasil sama dengan kodomainnya (${R}_{f}=B$).
- Fungsi Bijektif: Fungsi yang bersifat injektif sekaligus surjektif, yang menjadi dasar penting dalam pembuktian korespondensi satu-satu pada pencacahan objek.

## 3. Permutasi, Kombinasi, dan Perluasannya

- Permutasi ($P\left(n,r\right)$): Susunan terurut dari $r$ elemen yang dipilih dari $n$ elemen, di mana urutan sangat diperhatikan ($ab≠ba$):

$$P\left(n,r\right)=\frac{n!}{\left(n-r\right)!}$$

- Permutasi Melingkar (Circular Permutation): Susunan objek secara melingkar di mana posisi rotasi dianggap identik, sehingga banyaknya cara menyusun $n$ objek adalah $\left(n-1\right)!$.
- Kombinasi ($C\left(n,r\right)$ / $\left(\frac{n}{r}\right)$): Pemilihan $r$ elemen dari $n$ elemen tanpa memperhatikan urutan ({a, b} dianggap sama dengan {b, a}):

$$\left(\frac{n}{r}\right)=\frac{n!}{r!\left(n-r\right)!}$$

- Permutasi dengan Unsur yang Sama: Jika terdapat $n$ objek dengan kelompok unsur yang sama sebanyak ${n}_{1},{n}_{2},…,{n}_{k}$, jumlah susunannya adalah:

$$\frac{n!}{{n}_{1}!{n}_{2}!⋯{n}_{k}!}$$

## 4. Koefisien Binomial dan Multinomial

- Teorema Binomial: Menyatakan penjabaran perpangkatan suku ${\left(a+b\right)}^{n}$ menggunakan koefisien kombinasi:

$${\left(a+b\right)}^{n}=\sum_{k=0}^{n} \left(\frac{n}{k}\right){a}^{n-k}{b}^{k}$$

- Teorema Multinomial: Perluasan penjabaran untuk ekspresi dengan banyak suku ${\left({x}_{1}+{x}_{2}+…+{x}_{t}\right)}^{n}$:

$$\sum \left(\frac{n}{{n}_{1},{n}_{2},…,{n}_{t}}\right){x}_{1}^{{n}_{1}}{x}_{2}^{{n}_{2}}⋯{x}_{t}^{{n}_{t}} \text{dengan }\sum {n}_{i}=n$$

## 5. Pemilihan dengan Pengulangan & Persamaan Diophantine

- Kombinasi dengan Pengulangan: Banyaknya cara memilih $r$ objek dari $n$ jenis objek di mana objek boleh dipilih berulang kali adalah:

$$\left(\frac{n+r-1}{r}\right)$$

- Metode Bintang dan Batang (Stars and Bars): Digunakan untuk mencari banyaknya penyelesaian bilangan bulat tak negatif dari persamaan ${x}_{1}+{x}_{2}+…+{x}_{n}=r$, yang rumusnya ekivalen dengan $\left(\frac{n+r-1}{r}\right)$.

## 6. Prinsip Inklusi-Eksklusi (PIE)

Prinsip ini digunakan untuk menghitung ukuran gabungan himpunan dengan mengeliminasi elemen yang terhitung ganda pada bagian irisannya.

- Untuk dua himpunan:

$$|A∪B|=|A|+|B|-|A∩B|$$

- Untuk $n$ buah himpunan secara umum:

$$\left|\bigcup_{i=1}^{n} {A}_{i}\right|=\sum_{i} |{A}_{i}|-\sum_{i<j} |{A}_{i}∩{A}_{j}|+\sum_{i<j<k} |{A}_{i}∩{A}_{j}∩{A}_{k}|-…+{\left(-1\right)}^{n-1}|{A}_{1}∩…∩{A}_{n}|$$

## 7. Prinsip Sarang Merpati (Pigeonhole Principle / PHP)

- Bentuk Dasar: Jika terdapat $k+1$ atau lebih objek yang dimasukkan ke dalam $k$ buah kotak, maka dipastikan ada sekurang-kurangnya satu kotak yang memuat dua atau lebih objek.
- Bentuk Umum (Generalized PHP): Jika $N$ objek ditempatkan ke dalam $k$ kotak, maka setidaknya ada satu kotak yang memuat paling sedikit $\left\lceil\frac{N}{k}\right\rceil$ objek. Prinsip ini sangat esensial dalam membuktikan eksistensi suatu pola, keterbagian, atau jarak minimum tertentu tanpa harus mencari objeknya secara langsung.

## 8. Relasi Rekurensi (Recurrence Relations)

Relasi rekurensi menyatakan suku ke-$n$ dari suatu barisan secara rekursif berdasarkan suku-suku sebelumnya, disertai kondisi basis (base case). Contoh klasik yang sering dijumpai adalah Menara Hanoi (${m}_{k}=2{m}_{k-1}+1$ dengan solusi eksplisit ${m}_{k}={2}^{k}-1$) dan Barisan Fibonacci (${f}_{n}={f}_{n-1}+{f}_{n-2}$).

## 9. Studi Kasus: Aturan Perkalian & Kombinasi (Pembuatan Kode Akses Produk)

- Deskripsi Masalah: Sebuah pabrik makanan kaleng memberikan kode produk yang terdiri dari 3 huruf diikuti oleh 4 angka (contoh: ABD2531). Jika huruf maupun angka boleh berulang, berapa banyak kombinasi kode yang dapat dibuat?
- Penyelesaian / Implementasi C++:

```cpp
#include <iostream>
#include <cmath>
using namespace std;

int main() {
    // 3 huruf (dari 26 abjad) yang boleh berulang -> 26^3
    long long pilihan_huruf = pow(26, 3);

    // 4 angka (dari 10 digit 0-9) yang boleh berulang -> 10^4
    long long pilihan_angka = pow(10, 4);

    // Berdasarkan aturan perkalian
    long long total_kode = pilihan_huruf * pilihan_angka;

    cout << "Total kode yang dapat dibuat: " << total_kode << endl;
    return 0;
}
```

## 10. Studi Kasus: Prinsip Inklusi-Eksklusi (PIE)

- Deskripsi Masalah: Tentukan berapa banyak bilangan bulat positif yang tidak lebih besar dari 1000 yang habis dibagi oleh 7 atau 11.
- Penyelesaian / Implementasi C++:

```cpp
#include <iostream>
using namespace std;

int main() {
    int batas = 1000;

    // Menggunakan rumus prinsip inklusi-eksklusi: |A gabung B| = |A| + |B| - |A iris B|
    int habis_7 = batas / 7;       // 1000 / 7 = 142
    int habis_11 = batas / 11;     // 1000 / 11 = 90
    int habis_7_11 = batas / (7 * 11); // 1000 / 77 = 12

    int total = habis_7 + habis_11 - habis_7_11;

    cout << "Jumlah bilangan yang habis dibagi 7 atau 11 adalah: " << total << endl;
    return 0;
}
```

## 11. Studi Kasus: Persamaan Diophantine & Kombinasi dengan Pengulangan (Bintang dan Batang)

- Deskripsi Masalah: Berapa banyak penyelesaian bilangan bulat tak negatif dari persamaan ${x}_{1}+{x}_{2}+{x}_{3}=20$? Masalah ini dapat diselesaikan menggunakan rumus kombinasi dengan pengulangan $\left(\frac{n+r-1}{r}\right)$.
- Penyelesaian / Implementasi C++:

```cpp
#include <iostream>
using namespace std;

// Fungsi untuk menghitung faktorial (atau kombinasi)
long long kombinasi(int n, int r) {
    if (r > n - r) r = n - r;
    long long res = 1;
    for (int i = 1; i <= r; i++) {
        res = res * (n - i + 1) / i;
    }
    return res;
}

int main() {
    int n = 3; // Jumlah variabel (x1, x2, x3)
    int r = 20; // Nilai total penjumlahan

    // Rumus: C(n + r - 1, r)
    long long total_penyelesaian = kombinasi(n + r - 1, r);

    cout << "Banyaknya penyelesaian bulat tak negatif adalah: " << total_penyelesaian << endl;
    return 0;
}
```
