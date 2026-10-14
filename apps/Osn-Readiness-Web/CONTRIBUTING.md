# Contributing Guide

Dokumen ini menjelaskan alur kerja kontribusi untuk project **OSN Readiness Web**.
Semua kontributor wajib mengikuti alur berikut tanpa pengecualian.

---

**Quick Navigation:**
[Aturan Branch](#aturan-branch) &nbsp;|&nbsp;
[Alur Kontribusi](#alur-kontribusi) &nbsp;|&nbsp;
[Aturan Pull Request](#aturan-pull-request) &nbsp;|&nbsp;
[Konvensi Commit](#konvensi-commit)

---

## Aturan Branch

| Branch | Fungsi | Siapa yang boleh push langsung |
|--------|--------|-------------------------------|
| `main` | Production, kode stabil | Tidak ada (hanya via PR dari `dev`) |
| `dev` | Development, integrasi fitur | Tidak ada (hanya via PR dari branch fitur) |
| `feat/<nama>` | Fitur baru | Kontributor (dari fork masing-masing) |
| `fix/<nama>` | Perbaikan bug | Kontributor (dari fork masing-masing) |

> Push langsung ke `main` atau `dev` tidak diperbolehkan. Semua perubahan harus melalui Pull Request.

---

## Alur Kontribusi

### 1. Fork repository

Klik tombol **Fork** di halaman [https://github.com/PTI-A-PROJECT/Osn-Readiness-Web](https://github.com/PTI-A-PROJECT/Osn-Readiness-Web) untuk membuat salinan repository ke akun GitHub kamu sendiri.

### 2. Clone fork kamu

```bash
git clone https://github.com/<username-kamu>/Osn-Readiness-Web.git
cd Osn-Readiness-Web
```

### 3. Tambahkan upstream remote

```bash
git remote add upstream https://github.com/PTI-A-PROJECT/Osn-Readiness-Web.git
```

Verifikasi remote:

```bash
git remote -v
# origin    https://github.com/<username-kamu>/Osn-Readiness-Web.git (fetch)
# upstream  https://github.com/PTI-A-PROJECT/Osn-Readiness-Web.git (fetch)
```

### 4. Sinkronisasi dengan branch dev terbaru

Lakukan ini setiap kali sebelum mulai mengerjakan fitur baru:

```bash
git fetch upstream
git checkout dev
git merge upstream/dev
```

### 5. Buat branch fitur dari dev

```bash
# Untuk fitur baru
git checkout -b feat/nama-fitur

# Untuk perbaikan bug
git checkout -b fix/nama-bug
```

### 6. Kerjakan perubahan dan commit

```bash
git add .
git commit -m "feat: tambah halaman dashboard"
```

Lihat [Konvensi Commit](#konvensi-commit) untuk format pesan commit yang benar.

### 7. Push ke fork kamu

```bash
git push origin feat/nama-fitur
```

### 8. Buat Pull Request

- Buka fork kamu di GitHub
- Klik **Compare & pull request**
- Pastikan target branch adalah `dev` di repository **PTI-A-PROJECT/Osn-Readiness-Web**
- Isi judul dan deskripsi PR sesuai template di bawah

---

## Aturan Pull Request

### Target branch

Semua PR dari kontributor harus ditujukan ke branch **`dev`**, bukan `main`.

```
fork/<username>/feat/nama-fitur  -->  PTI-A-PROJECT/dev
```

### Template deskripsi PR

Gunakan format berikut saat membuat PR:

```
## Deskripsi
Jelaskan apa yang diubah dan mengapa.

## Jenis perubahan
- [ ] Fitur baru
- [ ] Perbaikan bug
- [ ] Refactor
- [ ] Dokumentasi

## Cara menguji
Langkah-langkah untuk memverifikasi perubahan ini berfungsi dengan benar.

## Checklist
- [ ] Kode sudah diuji secara lokal
- [ ] Tidak ada konflik dengan branch dev
- [ ] Pesan commit mengikuti konvensi
```

### Syarat PR dapat di-merge

- Minimal 1 review dari anggota tim
- Semua konflik sudah diselesaikan
- Tidak ada kode debug yang tertinggal (misalnya `dd()`, `console.log()`)
- Pesan commit mengikuti konvensi yang ditetapkan

---

## Konvensi Commit

Format pesan commit mengikuti [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>: <deskripsi singkat>
```

| Type | Digunakan untuk |
|------|----------------|
| `feat` | Menambahkan fitur baru |
| `fix` | Memperbaiki bug |
| `docs` | Perubahan dokumentasi saja |
| `style` | Formatting, tidak ada perubahan logika |
| `refactor` | Refactor kode tanpa menambah fitur atau memperbaiki bug |
| `test` | Menambahkan atau mengubah test |
| `chore` | Perubahan build, dependency, atau konfigurasi |

Contoh:

```bash
git commit -m "feat: tambah halaman login"
git commit -m "fix: perbaiki validasi form pendaftaran"
git commit -m "docs: update README instalasi"
```
