/*
 * Mapper domain simulasi. Kontrak backend (terverifikasi 2026-10-06 lewat
 * HasilsSimulasiController + *Resource di Laravel):
 * - GET /simulasi/syarat?tingkat_id= -> Resource LANGSUNG { terpenuhi,
 *   alasan: 'belum_pretest'|null, rincian: [{ materi_id, judul, prioritas,
 *   selesai, nilai_latihan, batas, latihan_belum_tersedia }] } (tanpa message)
 * - GET /simulasi?tingkat_id= -> { message, data: [{ id, nama_simulasi,
 *   jumlah_soal, durasi_menit, is_aktif, sisa_kuota }] }
 * - POST /simulasi/{id}/mulai -> 201/200 { message, data: { id (=percobaan),
 *   simulasi_id, pretest_id, mulai_pada, batas_pada, disubmit_pada,
 *   selesai_pada, nilai, soal: [SoalPengerjaan] } }
 * - POST .../submit -> { message, data: { id, simulasi_id, pretest_id, nilai,
 *   jumlah_benar, jumlah_salah, lulus, disubmit_pada, selesai_pada,
 *   jawaban: [{ soal_id, urutan, bobot, jawaban_user, status_benar }] } }
 * - GET /hasil-simulasi/{id}/review -> hasil + soal: [{ urutan, bobot,
 *   jawaban_user, status_benar, soal: { id, ..., kunci_jawaban, pembahasan } }]
 *   (soal BERSARANG di sini; di pengerjaan soalnya datar)
 *
 * Soal memakai mapSoal dari mappers/pretest.js — bentuknya sama, dan
 * kunci_jawaban tidak pernah ikut di sana (hanya di review).
 */

import { mapSoal } from '@/services/mappers/pretest.js'

const angka = (v) => {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}
const nilaiAtauNull = (v) => (v == null || !Number.isFinite(Number(v)) ? null : Number(v))
const daftar = (v) => (Array.isArray(v) ? v : [])

export function mapSyarat(r) {
  return {
    terpenuhi: Boolean(r?.terpenuhi),
    alasan: r?.alasan ?? null,
    rincian: daftar(r?.rincian).map((x) => ({
      materiId: x.materi_id,
      judul: x.judul ?? '',
      prioritas: angka(x.prioritas),
      selesai: Boolean(x.selesai),
      nilaiLatihan: nilaiAtauNull(x.nilai_latihan),
      batas: angka(x.batas),
      latihanTersedia: !x.latihan_belum_tersedia,
    })),
  }
}

export function mapSimulasi(r) {
  return {
    id: r.id,
    nama: r.nama_simulasi ?? '',
    jumlahSoal: angka(r.jumlah_soal),
    durasiMenit: angka(r.durasi_menit),
    aktif: Boolean(r.is_aktif),
    // Kuota berlaku per putaran, belum tentu per simulasi.
    sisaKuota: r.sisa_kuota == null ? null : angka(r.sisa_kuota),
  }
}

export function mapHasilSimulasi(r) {
  return {
    id: r.id,
    simulasiId: r.simulasi_id ?? null,
    pretestId: r.pretest_id ?? null,
    nilai: nilaiAtauNull(r.nilai),
    jumlahBenar: r.jumlah_benar == null ? null : angka(r.jumlah_benar),
    jumlahSalah: r.jumlah_salah == null ? null : angka(r.jumlah_salah),
    lulus: r.lulus == null ? null : Boolean(r.lulus),
    disubmitPada: r.disubmit_pada ?? null,
    selesaiPada: r.selesai_pada ?? null,
    jawaban: daftar(r.jawaban)
      .map((j) => ({
        soalId: j.soal_id,
        urutan: angka(j.urutan),
        bobot: angka(j.bobot),
        jawabanUser: j.jawaban_user ?? null,
        benar: Boolean(j.status_benar),
      }))
      .sort((a, b) => a.urutan - b.urutan),
  }
}

// GET hasil-simulasi/{id} bercabang: ada `soal` -> pengerjaan/menunggu,
// selain itu -> hasil.
export function paketSimulasi(r) {
  if (Array.isArray(r?.soal)) {
    return {
      // Sudah disubmit tapi belum dinilai -> jawaban terkunci, tunggu nilai.
      jenis: r.disubmit_pada ? 'menunggu' : 'pengerjaan',
      id: r.id,
      simulasiId: r.simulasi_id ?? null,
      pretestId: r.pretest_id ?? null,
      mulaiPada: r.mulai_pada ?? null,
      // Batas waktu Deterministik dari server: timer FE dihitung dari sini
      // supaya reload tidak menambah waktu.
      batasPada: r.batas_pada ?? null,
      soal: [...r.soal].sort((a, b) => angka(a.urutan) - angka(b.urutan)).map(mapSoal),
    }
  }
  return { jenis: 'hasil', hasil: mapHasilSimulasi(r ?? {}) }
}

// Review: soal bersarang, plus kunci dan pembahasan (hanya di endpoint ini).
export function mapReview(r) {
  return {
    ...mapHasilSimulasi({ ...r, jawaban: [] }),
    soal: daftar(r.soal)
      .map((b) => ({
        ...mapSoal({
          ...b.soal,
          urutan: b.urutan,
          bobot: b.bobot,
          jawaban_user: b.jawaban_user,
        }),
        benar: Boolean(b.status_benar),
        kunci: b.soal?.kunci_jawaban ?? null,
        pembahasan: b.soal?.pembahasan ?? null,
      }))
      .sort((a, b) => a.urutan - b.urutan),
  }
}
