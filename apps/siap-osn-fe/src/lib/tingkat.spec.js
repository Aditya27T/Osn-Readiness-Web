import { describe, expect, it } from 'vitest'
import { segeraDatang } from './tingkat.js'

describe('segeraDatang', () => {
  it('menandai tingkat Provinsi sebagai segera datang', () => {
    expect(segeraDatang({ nama: 'Provinsi' })).toBe(true)
    expect(segeraDatang({ nama: 'provinsi' })).toBe(true)
  })

  it('tidak menandai tingkat lain', () => {
    expect(segeraDatang({ nama: 'Kabupaten/Kota' })).toBe(false)
    expect(segeraDatang({ nama: 'Nasional' })).toBe(false)
  })

  it('aman untuk input null atau tanpa nama', () => {
    expect(segeraDatang(null)).toBe(false)
    expect(segeraDatang({})).toBe(false)
  })
})
