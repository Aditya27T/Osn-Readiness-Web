"""Susun paket impor Laravel dari bank soal terbaru dan dokumen Materi.

Sumber soal ada di ``soal/`` (diambil dari branch Fakhri), sedangkan bahan
ajar tetap di ``Materi/`` branch integrasi. JSON soal telah membawa
``materi_id`` dan ``level`` sendiri, sementara isi materi siswa berasal dari
dokumen DOCX yang sudah ditinjau.

Keluaran dapat langsung diberikan ke ``php artisan impor:konten``. Soal cerita
diratakan menjadi butir soal; soal pemrograman dengan kunci berupa source code
dilewati karena Laravel belum mempunyai penilai kode.
"""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import shutil
import sys
from collections import Counter
from collections.abc import Iterator
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "src"))

from data_analytics.materi_docx import baca_folder_materi  # noqa: E402

AKAR = Path(__file__).resolve().parents[1]
SUMBER = AKAR / "soal"
MATERI = AKAR / "Materi"
MIN_PILIHAN = 2
LEVEL_VALID = {"mudah", "sedang", "sulit"}
# Sumber soal tidak menyimpan peruntukan. Ini kebijakan sementara, bukan
# metadata asli soal.
PERUNTUKAN = ((2012, "pretest"), (2019, "latihan"))
POLA_TAHUN = re.compile(r"_(20\d{2})_terstruktur$")


@dataclass
class Laporan:
    masuk: int = 0
    dilewati: list[dict[str, str]] = field(default_factory=list)
    alasan: Counter[str] = field(default_factory=Counter)

    def lewati(self, id_sumber: str, alasan: str, rinci: str = "") -> None:
        self.alasan[alasan] += 1
        self.dilewati.append({"id_sumber": id_sumber, "alasan": alasan, "rinci": rinci})


def peruntukan_dari_tahun(tahun: int) -> str:
    for batas, nilai in PERUNTUKAN:
        if tahun <= batas:
            return nilai
    return "simulasi"


def sumber_dan_tingkat() -> list[tuple[Path, str, str, int]]:
    """JSON sumber terurut bersama tingkat, kode keluaran, dan tahun."""
    konfigurasi = (
        ("Kabupaten/soal_kabupaten_terupdate", "kabupaten", "kab"),
        ("Provinsi/soal_provinsi_terupdate", "provinsi", "prov"),
    )
    hasil: list[tuple[Path, str, str, int]] = []
    for folder, tingkat, kode in konfigurasi:
        for path in sorted((SUMBER / folder).glob("soal_*_terstruktur.json")):
            cocok = POLA_TAHUN.search(path.stem)
            if cocok is None:
                raise ValueError(f"Tahun tidak terbaca dari nama berkas: {path}")
            hasil.append((path, tingkat, kode, int(cocok.group(1))))
    if not hasil:
        raise ValueError(f"Tidak ada JSON soal pada {SUMBER}")
    return hasil


def baca_katalog_materi() -> list[dict[str, Any]]:
    katalog = json.loads((SUMBER / "materi.json").read_text(encoding="utf-8"))
    if not isinstance(katalog, list):
        raise ValueError("soal/materi.json harus berupa daftar")
    return katalog


def tulis_materi(keluar: Path) -> dict[str, dict[str, Any]]:
    """Tulis materi Markdown dengan ID katalog baru dan isi DOCX integrasi.

    Pemetaan menggunakan pasangan (tingkat, topik), sehingga perubahan ID dari
    katalog lama tidak memutus relasi butir soal dengan materinya.
    """
    katalog = baca_katalog_materi()
    dokumen = baca_folder_materi(MATERI)
    folder_materi = keluar / "materi"
    folder_materi.mkdir(parents=True, exist_ok=True)
    indeks: dict[str, dict[str, Any]] = {}
    for item in katalog:
        for kunci in ("id", "tingkat", "topik", "judul"):
            if kunci not in item:
                raise ValueError(f"Katalog materi tanpa {kunci}: {item}")
        kunci_docx = (str(item["tingkat"]), int(item["topik"]))
        sumber = dokumen.get(kunci_docx)
        if sumber is None:
            raise ValueError(
                f"Dokumen Materi tidak ditemukan untuk {item['id']} ({kunci_docx})."
            )
        isi = "\n\n".join(f"## {hal.judul}\n\n{hal.konten}" for hal in sumber.halaman).strip()
        frontmatter = "\n".join(
            (
                "---",
                f"id_sumber: {item['id']}",
                f"tingkat: {item['tingkat']}",
                f"kompetensi: {item['judul']}",
                f"urutan: {item['topik']}",
                f"judul: {item['judul']}",
                "---",
            )
        )
        (folder_materi / f"{item['id']}.md").write_text(
            f"{frontmatter}\n{isi}\n", encoding="utf-8"
        )
        indeks[str(item["id"])] = item
    return indeks


def salin_gambar(keluar: Path) -> dict[tuple[str, str], str]:
    """Salin gambar soal ke nama unik dan indekskan menurut tingkat + nama."""
    tujuan = keluar / "gambar" / "soal"
    tujuan.mkdir(parents=True, exist_ok=True)
    indeks: dict[tuple[str, str], str] = {}
    konfigurasi = (
        ("kabupaten", "Kabupaten/gambar_kabupaten"),
        ("provinsi", "Provinsi/gambar_provinsi"),
    )
    for tingkat, folder in konfigurasi:
        for path in sorted((SUMBER / folder).glob("*")):
            if not path.is_file():
                continue
            nama = f"{tingkat}__{path.name}"
            target = tujuan / nama
            if target.exists() and hashlib.sha256(target.read_bytes()).digest() != hashlib.sha256(path.read_bytes()).digest():
                raise ValueError(f"Nama gambar bentrok dengan isi berbeda: {path.name}")
            shutil.copy2(path, target)
            indeks[(tingkat, path.name)] = nama
    return indeks


def gambar_terdaftar(
    gambar: Any, *, tingkat: str, tersedia: dict[tuple[str, str], str], id_sumber: str, laporan: Laporan
) -> str | None:
    if gambar in (None, "", []):
        return None
    kandidat = gambar if isinstance(gambar, list) else [gambar]
    for item in kandidat:
        if isinstance(item, str):
            nama = tersedia.get((tingkat, Path(item).name))
            if nama is not None:
                return nama
    laporan.lewati(id_sumber, "gambar hilang", ", ".join(map(str, kandidat)))
    return None


def gabungkan_konteks(*bagian: Any) -> str | None:
    teks = [str(x).strip() for x in bagian if isinstance(x, str) and x.strip()]
    return "\n\n".join(teks) or None


def iter_butir(baris: dict[str, Any]) -> Iterator[tuple[dict[str, Any], str | None, Any]]:
    """Ratakan variasi cerita Kabupaten/Provinsi dan soal tunggal."""
    if isinstance(baris.get("pertanyaan"), list):
        for butir in baris["pertanyaan"]:
            yield butir, baris.get("deskripsi_soal"), baris.get("gambar_lokal_deskripsi")
        return
    if isinstance(baris.get("soal"), list):
        detail = baris.get("detail_cerita") or {}
        konteks = detail.get("teks") if isinstance(detail, dict) else None
        gambar = detail.get("gambar_lokal") if isinstance(detail, dict) else None
        for butir in baris["soal"]:
            yield butir, konteks, gambar
        return
    yield baris, None, None


def nilai(butir: dict[str, Any], *nama: str) -> Any:
    for kunci in nama:
        if kunci in butir and butir[kunci] is not None:
            return butir[kunci]
    return None


def jawaban_adalah_kode(jawaban: str) -> bool:
    return "#include" in jawaban or "int main(" in jawaban or "using namespace std" in jawaban


def normalisasi_butir(
    butir: dict[str, Any], *, tingkat: str, tahun: int, konteks: str | None,
    gambar_konteks: Any, materi: dict[str, dict[str, Any]],
    gambar: dict[tuple[str, str], str], laporan: Laporan,
) -> dict[str, Any] | None:
    id_sumber = str(nilai(butir, "soal_id") or "")
    pertanyaan = str(nilai(butir, "soal", "pertanyaan") or "").strip()
    materi_id = str(nilai(butir, "materi_id") or "")
    level = str(nilai(butir, "level") or "")
    jawaban = str(nilai(butir, "kunci", "jawaban_benar") or "")
    if not id_sumber or not pertanyaan or not jawaban:
        laporan.lewati(id_sumber or "<tanpa-id>", "data wajib kosong")
        return None
    if materi_id not in materi or materi[materi_id]["tingkat"] != tingkat:
        laporan.lewati(id_sumber, "materi tidak dikenal atau tingkat berbeda", materi_id)
        return None
    if level not in LEVEL_VALID:
        laporan.lewati(id_sumber, "level tak dikenal", level)
        return None
    if jawaban_adalah_kode(jawaban):
        laporan.lewati(id_sumber, "jawaban program belum didukung")
        return None
    pilihan = nilai(butir, "pilihan") or {}
    if not isinstance(pilihan, dict):
        laporan.lewati(id_sumber, "pilihan bukan objek")
        return None
    if pilihan:
        if len(pilihan) < MIN_PILIHAN:
            laporan.lewati(id_sumber, "pilihan < 2")
            return None
        if jawaban not in pilihan:
            laporan.lewati(id_sumber, "kunci di luar pilihan", jawaban)
            return None
    nama_gambar = nilai(butir, "gambar_lokal", "gambar_soal_lokal") or gambar_konteks
    gambar_impor = gambar_terdaftar(
        nama_gambar, tingkat=tingkat, tersedia=gambar, id_sumber=id_sumber, laporan=laporan
    )
    if nama_gambar and gambar_impor is None:
        return None
    # Kontrak Laravel tidak memiliki kolom kode; letakkan di konteks agar
    # potongan kode tidak hilang ketika soal diimpor.
    konteks_lengkap = gabungkan_konteks(konteks, nilai(butir, "kode", "kode_soal"))
    return {
        "id_sumber": id_sumber,
        "tingkat": tingkat,
        "materi": materi_id,
        "tingkat_kesulitan": level,
        "peruntukan": peruntukan_dari_tahun(tahun),
        "deskripsi_soal": konteks_lengkap,
        "soal": pertanyaan,
        "pilihan": pilihan,
        "jawaban_benar": jawaban,
        "pembahasan": str(nilai(butir, "pembahasan") or ""),
        "gambar": gambar_impor,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--keluar", type=Path, default=SUMBER / "impor")
    parser.add_argument("--hanya", choices=["kabupaten", "provinsi"])
    args = parser.parse_args()
    keluar: Path = args.keluar
    if keluar.exists():
        shutil.rmtree(keluar)
    keluar.mkdir(parents=True)
    materi = tulis_materi(keluar)
    gambar = salin_gambar(keluar)
    laporan = Laporan()
    total_sumber = 0
    id_ditemukan: set[str] = set()
    for berkas, tingkat, kode, tahun in sumber_dan_tingkat():
        if args.hanya and tingkat != args.hanya:
            continue
        keluaran: list[dict[str, Any]] = []
        data = json.loads(berkas.read_text(encoding="utf-8"))
        if not isinstance(data, list):
            raise ValueError(f"{berkas} harus berupa daftar")
        for baris in data:
            if not isinstance(baris, dict):
                laporan.lewati("<bukan-objek>", "baris bukan objek", berkas.name)
                continue
            for butir, konteks, gambar_konteks in iter_butir(baris):
                total_sumber += 1
                hasil = normalisasi_butir(
                    butir, tingkat=tingkat, tahun=tahun, konteks=konteks,
                    gambar_konteks=gambar_konteks, materi=materi, gambar=gambar, laporan=laporan,
                )
                if hasil is None:
                    continue
                if hasil["id_sumber"] in id_ditemukan:
                    laporan.lewati(hasil["id_sumber"], "id_sumber duplikat")
                    continue
                id_ditemukan.add(hasil["id_sumber"])
                keluaran.append(hasil)
        if keluaran:
            (keluar / f"soal_{kode}_{tahun}.json").write_text(
                json.dumps(keluaran, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
            )
            laporan.masuk += len(keluaran)
    laporan_path = keluar / "laporan.json"
    laporan_path.write_text(
        json.dumps(
            {
                "sumber": "soal/ (origin/Fakhri)",
                "materi": "Materi/ (branch integrasi)",
                "kebijakan_peruntukan": "<=2012 pretest, <=2019 latihan, selebihnya simulasi",
                "baris_sumber": total_sumber,
                "siap_impor": laporan.masuk,
                "dilewati": len(laporan.dilewati),
                "alasan": dict(laporan.alasan),
                "rinci": laporan.dilewati,
            }, ensure_ascii=False, indent=2,
        ) + "\n", encoding="utf-8",
    )
    print(f"sumber        : {total_sumber} soal")
    print(f"siap impor    : {laporan.masuk}")
    print(f"dilewati      : {len(laporan.dilewati)}")
    for alasan, jumlah in laporan.alasan.most_common():
        print(f"  - {alasan}: {jumlah}")
    print(f"\nfolder        : {keluar}")
    print(f"laporan       : {laporan_path}")
    print("\nlangkah:")
    print(f"  php artisan impor:konten {keluar} --dry-run")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
