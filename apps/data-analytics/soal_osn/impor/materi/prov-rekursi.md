---
id_sumber: prov-rekursi
tingkat: provinsi
kompetensi: Rekursi
urutan: 5
judul: Rekursi
---
## 1. Pengertian dan Konsep Dasar Algoritma Rekursif

Algoritma rekursif adalah sebuah fungsi atau prosedur yang menyelesaikan suatu permasalahan dengan cara memanggil dirinya sendiri secara berulang hingga mencapai kondisi berhenti tertentu.

- **Ciri Utama Rekursi:**
   1. Self-Calling: Fungsi memanggil dirinya sendiri di dalam blok kodenya.
   1. Base Case (Basis): Kondisi penghenti proses rekursi untuk mencegah perulangan tak terbatas (infinite loop / stack overflow).
   1. Progress: Setiap pemanggilan harus mengarah atau mendekati base case dengan memperkecil ukuran masalah.
- **Tujuan Penggunaan:** Menyederhanakan masalah kompleks dengan memecahnya menjadi sub-masalah sejenis, mempermudah penulisan kode untuk struktur data hierarkis (seperti Tree dan Graph), serta mencerminkan konsep matematis secara alami.

## 2. Jenis-Jenis Algoritma Rekursif

1. **Rekursi Langsung (Direct Recursion):** Fungsi memanggil dirinya sendiri secara langsung di dalam tubuh fungsi tersebut.
1. **Rekursi Tidak Langsung (Indirect Recursion):** Fungsi A memanggil fungsi B, dan fungsi B kemudian memanggil kembali fungsi A.
1. **Rekursi Ekor (Tail Recursion):** Pemanggilan rekursif diletakkan di bagian paling akhir dari instruksi fungsi, sehingga tidak ada operasi tambahan setelah pemanggilan tersebut selesai.
1. **Rekursi Ganda (Multiple Recursion):** Fungsi memanggil dirinya sendiri lebih dari sekali dalam satu kali eksekusi (contoh klasik: deret Fibonacci).

## 3. Pendekatan Divide and Conquer

Divide and Conquer adalah paradigma desain algoritma yang memecah masalah besar menjadi beberapa sub-masalah yang lebih kecil, menyelesaikannya secara rekursif, kemudian menggabungkan kembali solusinya. Paradigma ini terdiri dari tiga langkah utama:

1. **Divide (Membagi):** Memecah masalah utama menjadi beberapa sub-masalah berukuran lebih kecil.
1. **Conquer (Menaklukkan):** Menyelesaikan sub-masalah secara rekursif. Jika ukuran sub-masalah sudah sangat kecil (mencapai base case), selesaikan secara langsung (brute force).
1. **Combine (Menggabungkan):** Menggabungkan solusi dari sub-sub-masalah menjadi solusi utuh bagi permasalahan awal.

Studi Kasus Klasik - Merge Sort: Menggunakan pendekatan ini dengan membagi array menjadi dua bagian, mengurutkannya secara rekursif, lalu menggabungkannya kembali melalui prosedur MERGE.

## 4. Analisis Kompleksitas dengan Relasi Rekurens (Recurrence Relation)

Ketika suatu algoritma menggunakan fungsi rekursif, waktu eksekusinya dapat dideskripsikan menggunakan fungsi rekuren (recurrence equation). Bentuk umum relasi rekurens dalam Divide and Conquer dinyatakan sebagai:

$$T\left(n\right)=\left\{\begin{matrix}Θ\left(1\right) & \text{jika }n≤c \\ aT\left(\frac{n}{b}\right)+D\left(n\right)+C\left(n\right) & \text{jika }n>c\end{matrix}\right.$$

Di mana:

- $a$ adalah jumlah sub-masalah yang dihasilkan.
- $\frac{n}{b}$ adalah ukuran masing-masing sub-masalah.
- $D\left(n\right)$ adalah waktu yang dibutuhkan untuk memecah masalah (Divide).
- $C\left(n\right)$ adalah waktu untuk menggabungkan kembali solusi (Combine).

Sebagai contoh, pada algoritma **Merge Sort**, relasi rekurensnya disederhanakan menjadi:

$$T\left(n\right)=\left\{\begin{matrix}Θ\left(1\right) & \text{jika }n=1 \\ 2T\left(\frac{n}{2}\right)+Θ\left(n\right) & \text{jika }n>1\end{matrix}\right.$$

yang menghasilkan kompleksitas waktu total sebesar $O\left(nlog n\right)$.

## 5. Studi Kasus: Fungsi Rekursif Ganda (Deret Fibonacci)

- **Deskripsi Masalah:** Menghitung nilai suku ke-$n$ dari barisan Fibonacci menggunakan teknik rekursi ganda (multiple recursion) di mana fungsi memanggil dirinya sendiri dua kali.
- **Implementasi C++:**

```cpp
#include <iostream>
using namespace std;

// Fungsi rekursif ganda untuk Fibonacci
long long fibonacci(int n) {
    // Base case
    if (n <= 1) {
        return n;
    }
    // Rekurens
    return fibonacci(n - 1) + fibonacci(n - 2);
}

int main() {
    int n = 6;
    cout << "Nilai Fibonacci ke-" << n << " adalah: " << fibonacci(n) << endl;
    return 0;
}
```

## 6. Studi Kasus: Pendekatan Divide and Conquer (Merge Sort)

- **Deskripsi Masalah:** Mengurutkan sekumpulan elemen data menggunakan paradigma Divide and Conquer melalui algoritma Merge Sort, dengan memecah array, mengurutkannya secara rekursif, lalu menggabungkannya kembali menggunakan fungsi MERGE.
- **Implementasi C++ (Kerangka Utama Merge Sort):**

```cpp
#include <iostream>
using namespace std;

// Fungsi penggabungan (Merge) dua subarray terurut[cite: 9]
void merge(int arr[], int p, int q, int r) {
    int n1 = q - p + 1;
    int n2 = r - q;

    int L[n1], R[n2];
    for (int i = 0; i < n1; i++) L[i] = arr[p + i];
    for (int j = 0; j < n2; j++) R[j] = arr[q + 1 + j];

    int i = 0, j = 0, k = p;
    while (i < n1 && j < n2) {
        if (L[i] <= R[j]) {
            arr[k++] = L[i++];
        } else {
            arr[k++] = R[j++];
        }
    }
    while (i < n1) arr[k++] = L[i++];
    while (j < n2) arr[k++] = R[j++];
}

// Fungsi utama Merge Sort dengan Divide and Conquer[cite: 9]
void mergeSort(int arr[], int p, int r) {
    if (p < r) {
        int q = p + (r - p) / 2; // Mencari titik tengah[cite: 9]
        mergeSort(arr, p, q);     // Conquer bagian kiri[cite: 9]
        mergeSort(arr, q + 1, r); // Conquer bagian kanan[cite: 9]
        merge(arr, p, q, r);      // Combine / Merge kembali[cite: 9]
    }
}

int main() {
    int arr[] = {12, 11, 13, 5, 6, 7};
    int n = sizeof(arr) / sizeof(arr[0]);

    mergeSort(arr, 0, n - 1);

    cout << "Array setelah diurutkan dengan Merge Sort: ";
    for (int i = 0; i < n; i++) cout << arr[i] << " ";
    cout << endl;
    return 0;
}
```
