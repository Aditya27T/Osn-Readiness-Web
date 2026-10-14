---
id_sumber: prov-logika-bitwise
tingkat: provinsi
kompetensi: Operasi Logika dan Bitwise
urutan: 2
judul: Operasi Logika dan Bitwise
---
## Pendahuluan

Operasi bitwise bekerja langsung pada representasi tingkat rendah (biner) dari suatu bilangan bulat (integer atau unsigned integer). Pendekatan ini menawarkan efisiensi waktu eksekusi dan memori yang sangat drastis dibandingkan operasi aritmetika konvensional, menjadikannya pilar utama dalam competitive programming (CP).

## 1. Operator Dasar Bitwise dan Tabel Kebenaran

Sebelum masuk ke algoritma tingkat lanjut, pemahaman mendalam mengenai perilaku setiap operator dasar pada level bit sangat diperlukan.

**A. Bitwise AND (**&**)**

- **Cara Kerja:** Membandingkan setiap bit dari dua angka. Hasil bit bernilai 1 hanya jika kedua bit yang dibandingkan bernilai 1; jika tidak, bernilai 0.
- **Contoh Implementasi & Ilustrasi (**$5\text{ & }3$**):**

```
Plaintext
0101 (Desimal: 5)
& 0011 (Desimal: 3)
----
0001 (Desimal: 1)
```

- **Aplikasi Praktis (Optimasi Cek Genap/Ganjil):**

Memeriksa apakah angka ganjil atau genap dapat dilakukan dengan ekspresi x & 1. Jika hasilnya 1, maka ganjil; jika 0, maka genap. Cara ini terbukti secara empiris jauh lebih cepat dibandingkan menggunakan modulo konvensional (x % 2).

**B. Bitwise OR (**|**)**

- **Cara Kerja:** Membandingkan setiap bit dari dua angka. Hasil bit bernilai 1 jika salah satu atau kedua bit yang dibandingkan bernilai 1.
- **Contoh Ilustrasi (5|3):**

```
Plaintext
0101 (Desimal: 5)
| 0011 (Desimal: 3)
----
0111 (Desimal: 7)
```

- **Aplikasi Praktis (Sistem Flags / Konfigurasi):**

Digunakan untuk menggabungkan beberapa opsi atau atribut status ke dalam satu variabel integer tunggal menggunakan konstanta berupa perpangkatan dua (misal: YES | NO | CANCEL).

**C. Bitwise XOR (**^ **/ Exclusive OR)**

- **Cara Kerja:** Membandingkan setiap bit. Hasil bit bernilai 1 hanya jika kedua bit yang dibandingkan berbeda.
- **Properti Aljabar XOR Penting:**
   1. $x⊕x=0$ (Suatu angka di-XOR dengan dirinya sendiri menghasilkan nol).
   1. $x⊕0=x$ (Di-XOR dengan nol menghasilkan angka itu sendiri).
   1. Bersifat Komutatif dan Asosiatif.
- **Contoh Ilustrasi (**${5 }^{3}$**):**

```
Plaintext
0101 (Desimal: 5)
^ 0011 (Desimal: 3)
----
0110 (Desimal: 6)
```
```

**D. Bitwise NOT (**~**)**

- **Cara Kerja:** Operator uner yang membalik seluruh bit dari sebuah angka ($0$ menjadi $1$, dan $1$ menjadi $0$).
- **Keterkaitan dengan Komplemen Dua:** Pada tipe data bertanda (signed integer 32-bit), operasi $∼a$ menghasilkan $-a-1$ akibat representasi sistem penyimpanan negatif.

## 2. Sistem Bilangan: Komplemen Dua (Two's Complement) & Shift Operators

### A. Sistem Komplemen Dua

Komputer menyimpan bilangan bulat negatif menggunakan sistem komplemen dua agar operasi aritmetika penjumlahan dan pengurangan biner berjalan seragam.

- Cara Mencari Nilai Negatif (Komplemen Dua): Balik seluruh bit angka tersebut (operasi NOT / ~), kemudian tambahkan 1 ke hasilnya (~x + 1).
- Bit paling kiri (Most Significant Bit / MSB) pada integer bertanda berfungsi sebagai penanda negatif ($1$) atau positif ($0$).

### B. Shift Operators (Pergeseran Bit)

- **Left Shift (**<<**):** Menggeser seluruh bit ke kiri sebanyak $b$ posisi. Bit kosong di kanan diisi dengan angka 0. Secara matematis setara dengan operasi perkalian $a×{2}^{b}$.
- **Right Shift (**>>**):** Menggeser seluruh bit ke kanan sebanyak $b$ posisi. Setara dengan operasi pembagian bulat $a/{2}^{b}$.
- **Unsigned Right Shift (**>>>**):** Menggeser bit ke kanan dengan memastikan bit kosong di sebelah kiri selalu diisi dengan 0 (khusus untuk angka positif atau unsigned).

## 3. Teknik dan Algoritma Klasik Berbasis Bitwise

### A. Brian Kernighan's Algorithm (Menghitung Bit '1')

Untuk menghitung jumlah bit 1 (population count) dalam representasi biner suatu angka secara efisien tanpa harus memeriksa seluruh 32/64 bit satu per satu:

```cpp
int count = 0;
while (n > 0) {
    n = n & (n - 1); // Mematikan bit '1' paling kanan
    count++;
}
```

- **Kompleksitas Waktu:** $O\left(k\right)$, di mana $k$ adalah jumlah bit 1 yang menyala. (Atau gunakan fungsi bawaan compiler C++ yang sangat optimal: __builtin_popcount(n)).

### B. Basis XOR (Linear Basis)

Konsep dari aljabar linear untuk mereduksi sekumpulan angka besar menjadi sekumpulan "vektor basis" independen.

- Digunakan untuk menyelesaikan masalah pencarian subset XOR-sum maksimum, menghitung total kombinasi nilai XOR unik (${2}^{\text{ukuran basis}}$), hingga mencari nilai XOR terkecil ke-$k$ ($k$-th smallest subset XOR) dengan mereduksi basis ke bentuk row echelon form.

### C. SOS DP (Sum Over Subsets)

Teknik pemograman dinamis tingkat lanjut untuk menghitung agregasi nilai atas semua submask dari suatu mask biner dalam kompleksitas waktu yang optimal sebesar $O\left(N⋅{2}^{N}\right)$, sangat berguna dalam soal-soal kontes pemrograman tingkat nasional/internasional yang melibatkan relasi bitmask.

## 4. Studi Kasus

**Optimasi Pemeriksaan Bilangan Genap/Ganjil menggunakan Bitwise AND (**&**)**

- **Deskripsi Masalah:** Dalam sebuah kompetisi, Anda diminta memproses jutaan angka acak secepat mungkin untuk menyaring mana yang ganjil dan mana yang genap. Alih-alih menggunakan operator modulo % 2 yang relatif lambat di tingkat hardware, kita menggunakan operasi bitwise & 1.
- **Implementasi C++:**

```cpp
#include <iostream>
using namespace std;

int main() {
    int n;
    cin >> n; // Membaca jumlah angka

    for (int i = 0; i < n; i++) {
        int x;
        cin >> x;

        // Mengecek ganjil/genap dengan bitwise AND
        if (x & 1) {
            cout << x << " adalah Ganjil" << endl;
        } else {
            cout << x << " adalah Genap" << endl;
        }
    }
    return 0;
}
```

**2. Studi Kasus: Sistem Flags Konfigurasi Menggunakan Bitwise OR (**|**) dan AND (**&**)**

- **Deskripsi Masalah:** Sebuah aplikasi jendela pop-up dapat menampilkan berbagai macam tombol pilihan secara fleksibel: YES (1), NO (2), OKAY (4), dan CANCEL (8). Kita bisa menggabungkan beberapa tombol sekaligus menggunakan operator | dan memeriksanya menggunakan operator &.
- **Implementasi C++:**

```cpp
#include <iostream>
using namespace std;

// Definisi konstanta berbasis perpangkatan 2 (bit flags)
const int YES    = 1; // 0001
const int NO     = 2; // 0010
const int OKAY   = 4; // 0100
const int CANCEL = 8; // 1000

void tampilkanTombol(int konfigurasi) {
    if (konfigurasi & YES) {
        cout << "[Tombol YES Aktif] ";
    }
    if (konfigurasi & NO) {
        cout << "[Tombol NO Aktif] ";
    }
    if (konfigurasi & CANCEL) {
        cout << "[Tombol CANCEL Aktif] ";
    }
    cout << endl;
}

int main() {
    // Menggabungkan tombol YES, NO, dan CANCEL sekaligus menggunakan Bitwise OR
    int pilihanUser = YES | NO | CANCEL;

    tampilkanTombol(pilihanUser);
    return 0;
}
```

## 3. Studi Kasus: Menghitung Jumlah Bit '1' Menggunakan Brian Kernighan's Algorithm

- **Deskripsi Masalah:** Diberikan sebuah angka integer, tentukan berapa banyak bit bernilai 1 (popcount) dalam representasi binernya secara efisien.
- **Implementasi C++:**

```cpp
#include <iostream>
using namespace std;

int hitungBitSatu(int n) {
    int count = 0;
    while (n > 0) {
        n = n & (n - 1); // Mematikan bit '1' paling kanan secara instan
        count++;
    }
    return count;
}

int main() {
    int angka = 5; // Representasi biner: 0101 (memiliki 2 buah bit '1')
    cout << "Jumlah bit 1 dari " << angka << " adalah: " << hitungBitSatu(angka) << endl;

    // Atau menggunakan fungsi bawaan compiler GCC yang berkinerja O(1):
    // cout << __builtin_popcount(angka);
    return 0;
}
```
