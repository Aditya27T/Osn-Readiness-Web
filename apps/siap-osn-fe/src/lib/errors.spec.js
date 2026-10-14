import { describe, expect, it } from 'vitest'
import { kodeError, pesanError, pesanField, statusError } from '@/lib/errors.js'

describe('pesanField', () => {
  it('422 Laravel email ganda diterjemahkan', () => {
    const err = { response: { status: 422, data: { errors: { email: ['The email has already been taken.'] } } } }
    expect(pesanField(err, 'email')).toBe('Email sudah terdaftar')
  })

  it('400 Go lama tetap dibaca', () => {
    const err = { response: { status: 400, data: { message: 'Email sudah terdaftar' } } }
    expect(pesanField(err, 'email')).toBe('Email sudah terdaftar')
  })

  it('bukan error validasi menjadi null', () => {
    expect(pesanField({ response: { status: 500, data: {} } }, 'email')).toBeNull()
    expect(pesanField({}, 'email')).toBeNull()
  })
})

describe('pesanError', () => {
  it('fallback bila tak ada pesan', () => {
    expect(pesanError({})).toBe('Terjadi kesalahan, coba lagi')
  })

  it('pesan backend diteruskan', () => {
    const err = { response: { status: 401, data: { message: 'Email atau password salah' } } }
    expect(pesanError(err)).toBe('Email atau password salah')
  })

  it('offline dan timeout memakai pesan Indonesia', () => {
    expect(pesanError({ isAxiosError: true, code: 'ERR_NETWORK' })).toMatch(/Tidak dapat terhubung/)
    expect(pesanError({ isAxiosError: true, code: 'ECONNABORTED' })).toMatch(/terlalu lama/)
  })

  it('5xx tidak menampilkan pesan Inggris dari Laravel', () => {
    const err = { response: { status: 500, data: { message: 'Server Error' } } }
    expect(pesanError(err, 'Gagal menyimpan')).toBe('Gagal menyimpan')
  })

  it('kode bisnis backend diterjemahkan', () => {
    const err = (kode, status = 409) => ({ response: { status, data: { message: 'x', kode, detail: '' } } })
    expect(pesanError(err('TINGKAT_TERKUNCI', 403))).toBe('Tingkat ini belum terbuka untukmu')
    expect(pesanError(err('SUDAH_LULUS'))).toBe('Kamu sudah lulus tingkat ini')
    expect(pesanError(err('BANK_SOAL_TIDAK_CUKUP', 503), 'Gagal')).toBe(
      'Bank soal belum mencukupi, coba lagi nanti',
    )
    expect(pesanError(err('KODE_ASING'))).toBe('x')
    expect(pesanError(err('BELUM_PRETEST', 409))).toBe(
      'Selesaikan pre-test dulu untuk membuka fitur ini',
    )
    expect(pesanError(err('SIMULASI_BELUM_DINILAI', 409))).toMatch(/masih dinilai/)
    expect(pesanError(err('LAYANAN_HITUNG_SALAH_KONFIGURASI', 502), 'Gagal')).toBe(
      'Penilaian sedang bermasalah, hubungi admin',
    )
  })
})

describe('kodeError', () => {
  it('membaca kode bisnis backend', () => {
    expect(kodeError({ response: { status: 409, data: { kode: 'SUDAH_DISUBMIT' } } })).toBe(
      'SUDAH_DISUBMIT',
    )
  })

  it('null bila tidak ada kode atau respons', () => {
    expect(kodeError({ response: { status: 500, data: {} } })).toBeNull()
    expect(kodeError({})).toBeNull()
  })
})

describe('statusError', () => {
  it('membaca status HTTP', () => {
    expect(statusError({ response: { status: 404, data: {} } })).toBe(404)
  })

  it('null untuk error jaringan atau abort', () => {
    expect(statusError({ isAxiosError: true, code: 'ERR_NETWORK' })).toBeNull()
    expect(statusError({})).toBeNull()
  })
})
