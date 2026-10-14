import { describe, expect, it } from 'vitest'
import { hitungRingkasan, kelasNomer, nomorSoal, sudahDijawab } from './soal.js'

describe('sudahDijawab', () => {
  it('mentreat string kosong dan spasi sebagai belum diisi', () => {
    expect(sudahDijawab({ 0: '   ' }, 0)).toBe(false)
    expect(sudahDijawab({ 0: '' }, 0)).toBe(false)
    expect(sudahDijawab({ 0: 'B' }, 0)).toBe(true)
  })

  it('mentreat null/undefined sebagai belum diisi', () => {
    expect(sudahDijawab({}, 0)).toBe(false)
    expect(sudahDijawab({ 0: null }, 0)).toBe(false)
    expect(sudahDijawab(undefined, 0)).toBe(false)
  })
})

describe('hitungRingkasan', () => {
  const soal = [{ id: 1 }, { id: 2 }, { id: 3 }, { id: 4 }]

  it('menghitung terisi, ragu, dan kosong', () => {
    // 0 & 1 terisi (1 sekaligus ragu), 3 kosong, 2 ragu tapi kosong.
    const hasil = hitungRingkasan(soal, { 0: 'A', 1: 'isi' }, { 1: true, 2: true })
    expect(hasil.total).toBe(4)
    expect(hasil.terjawab).toBe(2)
    expect(hasil.ragu).toBe(2)
    expect(hasil.belum).toBe(1)
    expect(hasil.kosong).toEqual([3])
  })

  it('soal ragu tapi kosong tetap dihitung sebagai ragu, bukan kosong', () => {
    const hasil = hitungRingkasan(soal, {}, { 2: true })
    expect(hasil.ragu).toBe(1)
    expect(hasil.kosong).toEqual([0, 1, 3])
  })

  it('nol soal tidak error', () => {
    const hasil = hitungRingkasan([], {}, {})
    expect(hasil).toMatchObject({ total: 0, terjawab: 0, ragu: 0, belum: 0, kosong: [] })
  })
})

describe('kelasNomer', () => {
  it('memberi kelas sesuai status soal', () => {
    const jawaban = { 0: 'A' }
    const ragu = { 2: true }
    expect(kelasNomer(1, { aktif: 1, ragu, jawaban })).toHaveProperty('n-open', true)
    expect(kelasNomer(0, { aktif: 1, ragu, jawaban })).toHaveProperty('n-done', true)
    expect(kelasNomer(2, { aktif: 0, ragu, jawaban })).toHaveProperty('n-ragu', true)
    expect(kelasNomer(3, { aktif: 0, ragu, jawaban })).toHaveProperty('n-empty', true)
  })
})

describe('nomorSoal', () => {
  it('memformat dua digit', () => {
    expect(nomorSoal(0)).toBe('01')
    expect(nomorSoal(9)).toBe('10')
  })
})
