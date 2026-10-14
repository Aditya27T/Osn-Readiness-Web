# Logic per fitur

Setiap fitur ditulis sebagai endpoint, validasi, langkah di Service, dan kode error. Controller hanya memanggil Service; semua aturan bisnis ada di Service, dan query ada di Repository.

## Struktur kode

Aturan bisnis terkumpul di lima service inti; service lain hanya menyusun data. Nama kelas di bawah adalah usulan dan mengikuti pola Service-Repository di ARCHITECTURE\_RULES.

| Service | Tanggung jawab | Dipakai oleh |
| --- | --- | --- |
| AturanService | Membaca aturan\_pemetaan satu tingkat menjadi objek bertipe (bobot, persen level, batas, kuota) | Hampir semua service |
| PutaranService | Menurunkan keadaan siswa di satu tingkat: tingkat terbuka, putaran aktif, kuota terpakai, sudah lulus | Pre-test, latihan, simulasi, dashboard |
| SoalPickerService | Mengambil soal acak sesuai peruntukan, level, dan materi. Tidak menulis ke database | Pre-test, latihan, simulasi |
| SyaratSimulasiService | Memeriksa materi wajib dan nilai latihan | Simulasi, dashboard |
| KelulusanService | Menerapkan passing grade, mencatat kenaikan, dan menjalankan penghapusan saat putaran habis | Simulasi |
| AuthService | Register, login, logout | Auth |
| PretestService, LatihanService, SimulasiService | Mulai, simpan jawaban, submit, dan menyimpan hasil hitungan | Controller siswa |
| MateriService | Daftar materi, isi, dan progress | Controller siswa |
| DashboardService, RiwayatService | Menyusun data baca | Controller siswa |
| BankSoalService | Menghitung kecukupan bank soal | Admin |
| Service CRUD admin | Satu per resource | Controller admin |

**Di luar service:**

- PerhitunganClientInterface dan PerhitunganClient untuk memanggil Python.
- NilaiUlangJob untuk mengulang penilaian yang gagal.
- Command simulasi:tutup-kedaluwarsa yang dijalankan scheduler tiap menit.
- PurgeAkunJob untuk akun yang melewati masa retensi.
- API Resource terpisah: SoalResource (tanpa kunci) dan SoalReviewResource (dengan kunci dan pembahasan).

## Auth (BE-01)

Auth memakai token Sanctum dengan masa berlaku, dan login selalu memeriksa is\_active sebelum memberi token.

| Endpoint | Validasi | Langkah | Gagal |
| --- | --- | --- | --- |
| POST /api/auth/register | name wajib; email unik di antara akun yang belum dihapus; password minimal 8 karakter dengan konfirmasi | Buat user dengan is\_active true, beri role siswa, buat token, balas 201 dengan user dan token | 422 |
| POST /api/auth/login | email dan password wajib | Throttle per email dan IP. Cari user yang belum dihapus, cek password dengan Hash::check, cek is\_active, buat token dengan masa berlaku | 401 dengan pesan yang sama untuk email tidak dikenal dan password salah; 403 untuk akun nonaktif; 429 |
| POST /api/auth/logout | Token sah | Hapus currentAccessToken() saja | 401 |
| GET /api/auth/me | Token sah | Balas user, role, dan tingkat\_aktif\_id | 401 |

- Auth::attempt tidak dipakai karena menyentuh session; pengecekan dilakukan manual.
- Soft delete dan penonaktifan akun mencabut semua token user itu.
- Middleware active dipasang di semua rute yang butuh token, supaya akun yang dinonaktifkan di tengah sesi langsung tertolak.
- Batas throttle login (misalnya 5 kali per menit) masih angka usulan.

## Akses tingkat dan status putaran (BE-02)

Keadaan siswa tidak disimpan sebagai kolom status; PutaranService menurunkannya dari data setiap kali dibutuhkan, sehingga tidak ada status yang bisa tertinggal dari kenyataan.

**Nilai yang diturunkan untuk satu siswa di satu tingkat:**

| Nilai | Cara menghitung |
| --- | --- |
| tingkat\_terbuka | Urutan tingkat = 1, atau ada kenaikan\_tingkat berstatus lulus dengan asal tingkat sebelumnya |
| sudah\_lulus | Ada kenaikan\_tingkat berstatus lulus dengan asal tingkat ini |
| pretest\_berjalan | Pre-test di tingkat ini dengan selesai\_pada kosong |
| putaran\_aktif | Pre-test terbaru di tingkat ini dengan selesai\_pada terisi |
| percobaan\_terpakai | Jumlah hasil\_simulasi dengan pretest\_id = putaran aktif |
| simulasi\_berjalan | hasil\_simulasi milik siswa dengan selesai\_pada kosong |
| putaran\_habis | percobaan\_terpakai mencapai simulasi\_maks\_percobaan, tidak ada simulasi\_berjalan, dan belum lulus |
| boleh\_pretest\_baru | tingkat\_terbuka, belum lulus, tidak ada pretest\_berjalan, dan (belum ada putaran aktif atau putaran\_habis) |

**Tahap siswa** yang dikirim ke frontend, dihitung dari nilai di atas:

| Tahap | Keadaan | Aksi yang dibolehkan |
| --- | --- | --- |
| BELUM\_PRETEST | Belum ada pre-test di tingkat ini | Mulai pre-test |
| PRETEST\_BERJALAN | Pre-test belum selesai | Menjawab dan submit pre-test |
| BELAJAR | Putaran aktif, syarat simulasi belum terpenuhi | Materi dan latihan |
| SIAP\_SIMULASI | Syarat terpenuhi dan kuota tersisa | Mulai simulasi; materi dan latihan tetap boleh |
| SIMULASI\_BERJALAN | Ada simulasi yang belum selesai atau belum dinilai | Menjawab dan submit simulasi |
| PUTARAN\_HABIS | Tiga percobaan terpakai, belum lulus | Mulai pre-test baru |
| LULUS | Sudah lulus tingkat ini | Membaca materi dan riwayat |

GET /api/tingkat mengembalikan kedua tingkat beserta tingkat\_terbuka dan tahap untuk siswa yang login. users.tingkat\_aktif\_id diisi saat siswa memulai pre-test di sebuah tingkat.

## Pengambilan soal acak (BE-03)

SoalPickerService menerima permintaan, mengembalikan daftar soal beserta bobot dan urutannya, atau gagal seluruhnya; ia tidak pernah mengembalikan susunan yang kurang dari yang diminta dan tidak menulis ke database.

**Masukan**

| Masukan | Pre-test | Latihan | Simulasi |
| --- | --- | --- | --- |
| Peruntukan | pretest | latihan | simulasi |
| Jumlah soal | pretest\_jumlah\_soal | quiz.jumlah\_soal | simulasi.jumlah\_soal |
| Pembagian level | 50, 30, 20 | Bebas | 30, 40, 30 |
| Batas materi | Minimal 2 soal per materi | Hanya materi latihan itu | Tidak ada |
| Soal dikecualikan (keras) | Soal dari semua pre-test siswa di tingkat itu | Tidak ada | Tidak ada |
| Soal dihindari (lunak) | Tidak ada | Tidak ada | Soal dari simulasi siswa di putaran yang sama |

**Langkah**

1. Hitung kuota per level: jumlah dikali persen, dibulatkan ke bawah; sisa soal diberikan ke level dengan pecahan terbesar. Untuk 30 soal pre-test hasilnya 15, 9, 6.
2. Ambil kandidat: soal dengan tingkat dan peruntukan yang sesuai, belum dihapus, dan tidak termasuk daftar dikecualikan.
3. Khusus pre-test, penuhi dulu batas materi: untuk tiap materi dalam urutan acak, ambil 2 soal acak dari level yang kuotanya masih paling banyak tersisa, lalu kurangi kuota level itu.
4. Isi sisa kuota tiap level dengan soal acak dari seluruh kandidat yang belum terambil.
5. Khusus simulasi, dahulukan kandidat yang tidak ada di daftar dihindari; soal yang dihindari baru dipakai bila kandidat lain habis.
6. Jika ada kuota level atau batas materi yang tidak bisa dipenuhi, lempar BankSoalTidakCukupException yang memuat tingkat, peruntukan, level atau materi, dan jumlah yang kurang.
7. Acak urutan akhir, beri nomor urutan, dan pasang bobot tiap soal dari AturanService.

**Contoh pre-test**

| Tingkat | Kuota level | Langkah 3 (batas materi) | Langkah 4 (sisa) |
| --- | --- | --- | --- |
| Kabupaten | 15, 9, 6 | 5 materi x 2 = 10 soal | 20 soal |
| Provinsi | 15, 9, 6 | 10 materi x 2 = 20 soal | 10 soal |

**Catatan implementasi**

- Pengambil acak disuntikkan (misalnya Randomizer dengan seed) supaya unit test bisa memastikan hasil yang sama.
- Pemanggil membungkus pembuatan baris pengerjaan dan baris jawaban dalam satu transaksi. Jika picker gagal, tidak ada baris yang dibuat.
- Exception ditangkap handler global dan dibalas 503 BANK\_SOAL\_TIDAK\_CUKUP, serta dicatat di log dengan rinciannya untuk admin.
- Soal bercerita diperlakukan seperti soal lain. Resource menyertakan isi cerita pada tiap soal yang punya konteks\_id.

## Pre-test dan pemetaan (BE-04, BE-05)

Pre-test berjalan dalam empat endpoint; submit dipecah menjadi dua transaksi dengan panggilan Python di antaranya, supaya jawaban sudah aman sebelum Python disentuh.

| Endpoint | Fungsi |
| --- | --- |
| POST /api/pretest | Mulai pre-test, atau kembalikan yang sedang berjalan |
| GET /api/pretest/{id} | Soal dan jawaban tersimpan (bila berjalan), atau hasil (bila selesai) |
| PUT /api/pretest/{id}/jawaban | Simpan satu jawaban |
| POST /api/pretest/{id}/submit | Kunci jawaban, nilai, petakan, simpan hasil |

**Mulai** (body: tingkat\_id)

1. Ambil status dari PutaranService. Tingkat terkunci dibalas 403 TINGKAT\_TERKUNCI; sudah lulus dibalas 409 SUDAH\_LULUS.
2. Jika ada pre-test berjalan, kembalikan pre-test itu dengan status 200.
3. Jika boleh\_pretest\_baru salah, balas 409 PUTARAN\_MASIH\_BERJALAN.
4. Kumpulkan soal\_id dari semua pretest\_jawaban siswa di tingkat itu sebagai daftar dikecualikan, lalu panggil SoalPickerService.
5. Dalam satu transaksi: buat pretest, buat baris pretest\_jawaban (soal\_id, urutan, bobot), dan isi users.tingkat\_aktif\_id.
6. Jika index pretest\_berjalan\_unique menolak karena permintaan ganda, ambil pre-test yang sudah ada dan kembalikan.
7. Balas 201 dengan soal lewat SoalResource.

**Simpan jawaban** (body: soal\_id, jawaban\_user)

- Pre-test dicari dengan where user\_id; bukan miliknya dibalas 404.
- Ditolak dengan 409 SUDAH\_DISUBMIT jika disubmit\_pada sudah terisi. Kode yang sama dipakai latihan dan simulasi.
- soal\_id harus ada di pretest\_jawaban pre-test itu; selain itu 422.

**Submit**

1. Transaksi 1: kunci baris pretest dengan lockForUpdate. Jika selesai\_pada sudah terisi, kembalikan hasil yang ada. Jika belum, isi disubmit\_pada (bila masih kosong).
2. Susun payload: tiap soal dengan materi\_id, tipe\_soal, bobot, jawaban\_user, dan kunci\_jawaban; daftar materi tingkat itu dengan urutannya; jumlah\_materi\_wajib dari AturanService.
3. Panggil PerhitunganClient::hitungPretest di luar transaksi.
4. Jika Python gagal, kirim NilaiUlangJob dan balas 503 HASIL\_SEDANG\_DIPROSES. Jawaban tetap terkunci.
5. Transaksi 2: isi status\_benar tiap jawaban, isi nilai dan selesai\_pada, simpan satu baris pemetaan\_materi per materi, dan simpan baris rekomendasi\_materi dari materi\_wajib.
6. Balas 200 dengan nilai, pemetaan, dan materi wajib.

Langkah 2 sampai 5 berada di satu method, misalnya PretestService::selesaikanPenilaian, yang juga dipanggil NilaiUlangJob. Method itu idempoten: bila selesai\_pada sudah terisi, ia berhenti tanpa menulis apa pun. Pemeriksaan itu diulang di dalam transaksi 2, di bawah kunci baris, supaya job dan submit ulang yang berjalan bersamaan tidak menilai dua kali.

Sebelum menyimpan, Laravel memeriksa balasan Python: jumlah jawaban harus sama dengan yang dikirim, setiap materi yang dikirim punya baris pemetaan, dan jumlah materi wajib sesuai aturan. Balasan yang tidak cocok diperlakukan sebagai kesalahan konfigurasi (502).

## Materi, latihan, dan syarat simulasi (BE-06, BE-07, BE-08)

Materi bisa dibaca kapan saja selama tingkatnya terbuka, tetapi menandai selesai dan mengerjakan latihan hanya dicatat saat ada putaran aktif. Tanpa aturan ini siswa bisa memenuhi syarat sebelum pre-test baru dipetakan.

| Endpoint | Fungsi |
| --- | --- |
| GET /api/materi?tingkat\_id= | Daftar materi dengan tanda wajib, prioritas, status progress, dan nilai latihan terbaik |
| GET /api/materi/{id} | Isi materi |
| PUT /api/materi/{id}/progress | Ubah status belajar atau selesai |
| POST /api/quiz/{id}/mulai | Mulai latihan, atau kembalikan pengerjaan yang belum disubmit |
| PUT /api/quiz-pengerjaan/{id}/jawaban | Simpan satu jawaban |
| POST /api/quiz-pengerjaan/{id}/submit | Nilai latihan |
| GET /api/simulasi/syarat?tingkat\_id= | Status syarat membuka simulasi |

**Materi dan progress**

- Semua endpoint memeriksa tingkat\_terbuka; tingkat terkunci dibalas 403 TINGKAT\_TERKUNCI.
- Tanda wajib dan prioritas diambil dari rekomendasi\_materi putaran aktif. Materi wajib ditampilkan lebih dulu.
- PUT progress membuat atau memperbarui satu baris per siswa per materi. Status selesai mengisi tanggal\_selesai dan persentase 100.
- Tanpa putaran aktif, PUT progress dibalas 409 BELUM\_PRETEST (usulan).

**Latihan**

1. Mulai: periksa tingkat terbuka dan putaran aktif. Jika ada pengerjaan yang belum disubmit untuk latihan itu, kembalikan pengerjaan itu. Index quiz\_pengerjaan\_berjalan\_unique (user\_id, quiz\_id, hanya baris yang belum disubmit) menolak permintaan ganda; permintaan yang kalah mengembalikan pengerjaan yang sudah ada.
2. Panggil SoalPickerService dengan materi latihan dan quiz.jumlah\_soal, lalu dalam satu transaksi buat quiz\_pengerjaan dan baris quiz\_jawaban.
3. Submit memakai pola yang sama dengan pre-test: transaksi 1 mengunci dan mengisi disubmit\_pada, lalu PerhitunganClient::hitungPenilaian, lalu transaksi 2 mengisi status\_benar, nilai, dan selesai\_pada.
4. Nilai latihan sebuah materi adalah nilai tertinggi dari semua pengerjaan yang selesai.

**Syarat simulasi** (SyaratSimulasiService)

1. Ambil putaran aktif. Jika tidak ada, syarat belum terpenuhi dengan alasan belum pre-test (kolom alasan berisi belum\_pretest; selain itu kosong).
2. Ambil materi wajib dari rekomendasi\_materi putaran itu.
3. Untuk tiap materi wajib, periksa dua hal: progress berstatus selesai, dan nilai latihan terbaik mencapai latihan\_min\_nilai.
4. Kembalikan terpenuhi (benar bila semua lolos) dan rincian per materi: judul, selesai atau belum, nilai latihan, dan batasnya.

Materi wajib yang belum punya latihan dihitung belum memenuhi, dan rinciannya memuat tanda latihan\_belum\_tersedia supaya frontend bisa menampilkan pesan yang benar dan admin tahu apa yang harus dilengkapi.

Pengecekan ini dijalankan dua kali: di endpoint status untuk tampilan, dan lagi di dalam mulai simulasi sebagai penjaga yang sebenarnya.

## Simulasi, kelulusan, dan putaran baru (BE-09, BE-10)

Mulai simulasi adalah titik paling rawan, karena di sana syarat, kuota, dan permintaan bersamaan bertemu; semuanya diperiksa di dalam satu transaksi yang mengunci baris putaran.

| Endpoint | Fungsi |
| --- | --- |
| GET /api/simulasi?tingkat\_id= | Daftar simulasi aktif dan sisa kuota |
| POST /api/simulasi/{id}/mulai | Mulai percobaan, atau lanjutkan yang sedang berjalan |
| GET /api/hasil-simulasi/{id} | Soal dan jawaban tersimpan (bila berjalan), atau hasil (bila selesai) |
| PUT /api/hasil-simulasi/{id}/jawaban | Simpan satu jawaban |
| POST /api/hasil-simulasi/{id}/submit | Nilai dan terapkan aturan kelulusan |
| GET /api/hasil-simulasi/{id}/review | Jawaban, kunci, dan pembahasan setelah selesai |

**Mulai**

1. Simulasi harus is\_aktif dan tingkatnya terbuka untuk siswa.
2. Buka transaksi dan kunci baris pretest putaran aktif dengan lockForUpdate. Tanpa putaran aktif, balas 409 SYARAT\_SIMULASI\_BELUM\_TERPENUHI.
3. Sudah lulus tingkat itu: 409 SUDAH\_LULUS.
4. Ada simulasi berjalan: bila belum disubmit dan belum melewati batas\_pada, kembalikan percobaan itu; bila sudah disubmit tetapi belum bernilai, balas 409 SIMULASI\_BELUM\_DINILAI; bila batas waktunya sudah lewat tetapi belum ditutup scheduler, tutup dulu lewat alur submit.
5. percobaan\_terpakai sudah mencapai simulasi\_maks\_percobaan: 409 KUOTA\_SIMULASI\_HABIS.
6. SyaratSimulasiService belum terpenuhi: 409 SYARAT\_SIMULASI\_BELUM\_TERPENUHI beserta rinciannya.
7. Kumpulkan soal dari percobaan lain di putaran ini sebagai daftar dihindari, lalu panggil SoalPickerService.
8. Buat hasil\_simulasi (pretest\_id, mulai\_pada, batas\_pada) dan baris jawabannya, lalu tutup transaksi.
9. Balas 201 dengan soal lewat SoalResource dan batas\_pada.

**Simpan jawaban**

- Ditolak dengan 409 WAKTU\_HABIS bila waktu server melewati batas\_pada ditambah toleransi (30 detik, usulan).
- Ditolak dengan 409 SUDAH\_DISUBMIT bila disubmit\_pada sudah terisi.

**Submit**

1. Transaksi 1: kunci baris hasil\_simulasi. Jika selesai\_pada terisi, kembalikan hasil yang ada. Jika belum, isi disubmit\_pada.
2. Panggil PerhitunganClient::hitungPenilaian di luar transaksi. Bila gagal, kirim NilaiUlangJob dan balas 503.
3. Transaksi 2: isi status\_benar, nilai, jumlah\_benar, jumlah\_salah, dan selesai\_pada, lalu panggil KelulusanService di transaksi yang sama.

**KelulusanService**

| Kondisi | Tindakan |
| --- | --- |
| Nilai mencapai passing\_grade | Isi lulus = benar. Tulis kenaikan\_tingkat berstatus lulus, dengan tingkat\_tujuan\_id tingkat berikutnya atau kosong untuk Provinsi |
| Nilai belum mencapai, percobaan selesai kurang dari maksimum | Isi lulus = salah |
| Nilai belum mencapai, percobaan selesai sama dengan maksimum | Isi lulus = salah. Hapus progress\_belajar siswa untuk materi tingkat itu. Hapus quiz\_pengerjaan siswa untuk latihan tingkat itu (jawabannya ikut terhapus). Tulis kenaikan\_tingkat berstatus tidak\_lulus dengan nilai terbaik dan passing grade di keterangan |

- Passing grade dibaca dari AturanService saat penilaian, lalu hasilnya disimpan di kolom lulus. Perubahan angka oleh admin tidak mengubah hasil lama.
- Index kenaikan\_lulus\_unique mencegah baris lulus ganda bila penilaian terpicu dua kali.
- Pre-test, pemetaan, materi wajib, dan hasil simulasi putaran lama tidak dihapus.

**Penutupan otomatis**

Command simulasi:tutup-kedaluwarsa berjalan tiap menit dengan withoutOverlapping. Ia mengambil hasil\_simulasi yang disubmit\_pada-nya kosong dan batas\_pada ditambah toleransi sudah lewat, lalu menjalankan alur submit yang sama atas nama sistem. Jawaban yang sudah tersimpan dinilai; yang kosong dihitung salah.

**Review**

Hanya terbuka bila selesai\_pada terisi dan percobaan itu milik siswa. Percobaan yang belum dinilai dibalas 409 SIMULASI\_BELUM\_DINILAI; milik siswa lain 404. Balasan memakai SoalReviewResource: jawaban siswa, status benar, kunci, dan pembahasan.

## Dashboard dan riwayat (BE-15, BE-16)

Kedua endpoint ini hanya membaca; tidak ada aturan bisnis baru, dan kelulusan tidak dievaluasi di sini.

| Endpoint | Isi balasan |
| --- | --- |
| GET /api/dashboard | Tingkat aktif, tahap siswa, materi wajib beserta status dan nilai latihan, rincian syarat simulasi, sisa kuota simulasi, hasil simulasi terakhir, dan status lulus tiap tingkat |
| GET /api/riwayat?jenis=&tingkat\_id= | Baris dari view riwayat\_hasil milik siswa, terbaru lebih dulu, dengan pagination |

- DashboardService menyusun balasan dari PutaranService dan SyaratSimulasiService, jadi angka di dashboard selalu sama dengan yang dipakai penjaga simulasi.
- RiwayatHasil adalah model baca saja di atas view, tanpa timestamps dan tanpa operasi tulis.
- Riwayat latihan dari putaran yang sudah habis tidak muncul lagi, karena barisnya dihapus.

## Admin (BE-12 sampai BE-14, BE-17 sampai BE-20)

Semua endpoint admin berada di bawah /api/admin/\* dengan CRUD standar; yang perlu diperhatikan adalah aturan validasi dan penghapusan di tiap resource, karena data admin dirujuk oleh hasil pengerjaan siswa.

| Resource | Validasi khusus | Aturan ubah dan hapus |
| --- | --- | --- |
| Siswa | Tidak ada | Menonaktifkan atau soft delete mencabut semua token siswa itu. Menu ini hanya menyentuh akun ber-role siswa (aktif maupun nonaktif); akun admin dibalas 404 |
| Tingkat | Hanya nama dan deskripsi yang bisa diubah | Tidak bisa ditambah atau dihapus |
| Kompetensi | Nama unik per tingkat | Hapus ditolak bila masih punya materi |
| Materi | Kompetensi harus dari tingkat yang sama; urutan unik per tingkat | Hapus ditolak bila punya soal, latihan, atau dirujuk hasil siswa |
| Cerita soal | Tingkat wajib | Hapus ditolak bila masih dipakai soal |
| Soal | Materi dan cerita harus dari tingkat yang sama. Pilihan ganda wajib punya minimal 2 pilihan dan kunci harus salah satu pilihan. Isian tidak punya pilihan. Pilihan ditulis sebagai objek berkunci huruf ({"A": "...", "B": "..."}) dan kunci adalah hurufnya, sama dengan format impor | Hapus = soft delete. Soal yang sudah dipakai pengerjaan tidak boleh diubah kunci, level, peruntukan, atau materinya; teks boleh diperbaiki |
| Pembahasan | Satu per soal | Simpan = buat atau perbarui |
| Simulasi | jumlah\_soal dan durasi\_menit lebih dari 0 | is\_aktif hanya bisa dinyalakan bila bank soal cukup untuk satu percobaan. Hapus ditolak bila punya hasil (409 SIMULASI\_MASIH\_DIGUNAKAN) |
| Latihan | Satu per materi; jumlah\_soal minimal latihan\_min\_soal | Hapus ditolak bila punya pengerjaan (409 LATIHAN\_MASIH\_DIGUNAKAN) |
| Aturan pemetaan | Lihat di bawah | Hanya nilai yang bisa diubah; parameter tidak bisa ditambah atau dihapus |

**Validasi aturan pemetaan**

- Tiga persen level pre-test berjumlah 100; begitu juga tiga persen level simulasi.
- Bobot minimal 1. passing\_grade dan latihan\_min\_nilai antara 0 dan 100.
- jumlah\_materi\_wajib tidak melebihi jumlah materi di tingkat itu.
- pretest\_min\_soal\_per\_materi dikali jumlah materi tidak melebihi pretest\_jumlah\_soal.
- Perubahan hanya berlaku untuk pengerjaan baru, karena bobot disalin ke baris jawaban dan hasil lulus disimpan saat dinilai.

**Kecukupan bank soal** (GET /api/admin/bank-soal/kecukupan?tingkat\_id=)

| Bagian | Yang dihitung | Dinyatakan kurang bila |
| --- | --- | --- |
| Pre-test per level | Jumlah soal tersedia dibanding kuota satu pre-test | Tersedia lebih kecil dari kuota |
| Pre-test per materi | Jumlah soal pre-test tiap materi | Lebih kecil dari pretest\_min\_soal\_per\_materi |
| Putaran pre-test | Berapa pre-test tanpa soal berulang yang bisa dilayani: nilai terkecil dari tersedia dibagi kuota di tiap level | Kurang dari 2 (ambang peringatan, usulan) |
| Simulasi per level | Jumlah soal tersedia dibanding kuota satu simulasi | Tersedia lebih kecil dari kuota |
| Latihan per materi | Jumlah soal latihan tiap materi dibanding quiz.jumlah\_soal | Lebih kecil, atau materi belum punya latihan |

BankSoalService memakai fungsi hitung kuota yang sama dengan SoalPickerService, supaya laporan admin dan perilaku nyata tidak pernah berbeda. "Tersedia" berarti stok bank (tingkat dan peruntukan yang sesuai, belum dihapus). Soal yang pernah dipakai siswa lain tetap dihitung, karena larangan mengulang soal pre-test berlaku per siswa.

**Dashboard Super Admin** (GET /api/admin/dashboard): jumlah siswa aktif, jumlah pengerjaan per jenis, jumlah siswa per tingkat aktif, dan rata-rata nilai per jenis.

## Klien Python dan job ulang (BE-11)

Semua panggilan ke Python lewat satu interface, sehingga service lain tidak tahu soal HTTP dan logikanya bisa dipindah ke PHP dengan mengganti satu kelas.

**PerhitunganClientInterface**

| Method | Endpoint Python | Dipakai oleh |
| --- | --- | --- |
| hitungPenilaian(daftar soal) | POST /hitung/penilaian | Latihan, simulasi |
| hitungPretest(daftar soal, daftar materi, aturan) | POST /hitung/pretest | Pre-test |

**Perilaku PerhitunganClient**

- Konfigurasi di config/services.php: url, token, timeout 5 detik, retry 2 kali.
- Setiap permintaan membawa header X-Internal-Token.
- Retry hanya untuk kegagalan koneksi, timeout, dan status 5xx.

| Balasan Python | Exception | Balasan API | Job ulang |
| --- | --- | --- | --- |
| Timeout, koneksi gagal, atau 5xx setelah retry | PerhitunganTidakTersediaException | 503 HASIL\_SEDANG\_DIPROSES | Ya |
| 403 atau 422 | PerhitunganKonfigurasiException | 502 LAYANAN\_HITUNG\_SALAH\_KONFIGURASI | Tidak; dicatat sebagai error kritis |
| 200 tetapi isinya tidak cocok dengan yang dikirim | PerhitunganKonfigurasiException | 502 | Tidak |

**NilaiUlangJob**

- Menerima jenis pengerjaan (pretest, latihan, simulasi) dan id-nya, lalu memanggil method selesaikanPenilaian di service yang sesuai.
- Idempoten: bila selesai\_pada sudah terisi, job selesai tanpa menulis apa pun.
- Mencoba 5 kali dengan jeda yang makin panjang (10, 30, 60, 120, 300 detik; angka usulan). Setelah itu masuk failed\_jobs dan dicatat untuk admin.
- Untuk simulasi, KelulusanService ikut dijalankan di job ini, jadi penghapusan saat putaran habis tetap terjadi walaupun nilainya terlambat.

**Pengujian**

- Semua feature test memakai Http::fake; tidak ada test yang memanggil Python sungguhan.
- Satu test kontrak membandingkan contoh permintaan dan balasan di dokumen alur dengan yang dikirim PerhitunganClient, supaya perubahan kontrak di sisi Python ketahuan.
- ARCHITECTURE\_RULES bagian 3.5 direvisi lebih dulu, agar Service boleh bergantung pada Client interface.

## Impor konten dan gambar (BE-21)

Bank soal 1.719 butir dan 15 materi yang sudah ada dimasukkan lewat satu command. Setelah impor awal, database menjadi sumber asli: perubahan materi dilakukan lewat menu admin, bukan dengan mengimpor ulang file materi.

**Susunan folder impor**

```
impor/
  materi/          kab-01.md, kab-02.md, prov-01.md, ...
  soal_kabupaten.json
  soal_provinsi.json
  gambar/
    soal/          kab-2020-001.png, ...
    materi/        kab-01-gambar-1.png, ...
```

**Satu soal di JSON**

```json
{
  "id_sumber": "kab-2020-001",
  "tingkat": "kabupaten",
  "materi": "kab-01",
  "tingkat_kesulitan": "sedang",
  "peruntukan": "pretest",
  "deskripsi_soal": "Cerita bersama, atau null",
  "soal": "Teks pertanyaan, termasuk blok kode bila ada",
  "pilihan": { "A": "...", "B": "...", "C": "..." },
  "jawaban_benar": "C",
  "pembahasan": "...",
  "gambar": "kab-2020-001.png"
}
```

**Satu materi sebagai file Markdown**

```text
---
id_sumber: kab-01
tingkat: kabupaten
kompetensi: Nama kelompok materi
urutan: 1
judul: Aljabar Boolean & Teori Himpunan
---

## 1. Konsep Dasar

Isi materi, dengan rumus $a + b$ dan gambar:

![Diagram Venn](../gambar/materi/kab-01-gambar-1.png)
```

**Langkah command** (`php artisan impor:konten {folder}`, dengan opsi `--dry-run`)

1. Baca tiap file materi: bagian keterangan di atas menjadi kolom, isi di bawahnya masuk ke isi\_materi. Kompetensi dibuat bila namanya belum ada di tingkat itu.
2. Salin gambar materi ke penyimpanan dan sesuaikan alamatnya di teks.
3. Baca tiap soal dan periksa (tabel di bawah). Tipe soal ditentukan dari pilihan: kosong berarti isian.
4. deskripsi\_soal menjadi baris konteks\_soal; teks cerita yang sama di tingkat yang sama memakai satu baris. pembahasan masuk ke tabel pembahasan.
5. Salin gambar soal ke penyimpanan dan isi kolom gambar.
6. Cetak laporan: jumlah yang masuk, diperbarui, dan ditolak beserta alasannya.

Materi dan soal dicocokkan lewat id\_sumber, jadi menjalankan impor dua kali memperbarui baris yang sama dan tidak membuat duplikat.

| Pemeriksaan | Bila gagal |
| --- | --- |
| id\_sumber tidak boleh kembar dalam satu impor | Seluruh impor dibatalkan |
| materi pada soal harus ada dan tingkatnya sama | Soal ditolak |
| tingkat\_kesulitan harus mudah, sedang, atau sulit; peruntukan harus pretest, latihan, atau simulasi | Soal ditolak |
| jawaban\_benar pilihan ganda harus salah satu huruf di pilihan | Soal ditolak |
| File gambar yang disebut harus ada di folder | Soal ditolak |
| Soal yang sudah dipakai pengerjaan siswa tidak boleh berubah kunci, level, peruntukan, atau materinya | Perubahan itu dilewati dan dilaporkan |

Soal pemrograman (kuncinya bukan jawaban yang bisa dicocokkan) tidak dimasukkan ke file impor; ia dikecualikan atau ditulis ulang lebih dulu. Gambar yang di data sumber berupa tautan luar diunduh dulu dan dijadikan file lokal.

**Aturan teks dan gambar**

- pertanyaan, isi\_konteks, isi\_pembahasan, dan isi\_materi disimpan sebagai Markdown, bukan HTML. Rumus ditulis sebagai LaTeX di antara tanda dolar.
- Laravel tidak mengubah Markdown menjadi HTML. Vue yang menampilkannya, lewat penampil yang membuang skrip.
- Gambar soal: satu file per soal di penyimpanan publik. Kolom gambar menyimpan nama file, dan API Resource mengirim alamatnya.
- Gambar materi: admin mengunggah lewat POST /api/admin/materi/gambar; Laravel memeriksa jenis dan ukuran file (png, jpg, webp, maksimal 2 MB; angka usulan), menyimpannya, dan membalas alamat yang lalu ditaruh editor di dalam teks.
- Alamat gambar di dalam teks ditulis tanpa nama domain, supaya tetap benar bila domain berganti.
- Admin mengedit materi lewat editor visual yang menyimpan Markdown. Pilihan editornya urusan frontend dan belum ditetapkan.

Untuk prototype, gambar disajikan dari penyimpanan publik, jadi siapa pun yang tahu nama filenya bisa membukanya tanpa login. Gambar yang sudah diunggah lalu dihapus dari teks tetap tersimpan sebagai file.
