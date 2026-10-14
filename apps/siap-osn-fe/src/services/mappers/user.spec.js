import { describe, expect, it } from 'vitest'
import { mapRole, mapUser } from '@/services/mappers/user.js'

const userLaravel = {
  id: 1,
  name: 'Budi',
  email: 'budi@example.com',
  roles: ['siswa'],
  created_at: '2026-09-25T00:00:00Z',
}

describe('mapRole', () => {
  it('Super Admin menjadi super_admin', () => {
    expect(mapRole(['Super Admin'])).toBe('super_admin')
  })

  it('siswa tetap siswa', () => {
    expect(mapRole(['siswa'])).toBe('siswa')
  })

  it('tak dikenal / kosong menjadi null', () => {
    expect(mapRole(['alien'])).toBeNull()
    expect(mapRole([])).toBeNull()
    expect(mapRole(null)).toBeNull()
  })
})

describe('mapUser', () => {
  it('memetakan UserResource ke bentuk FE', () => {
    expect(mapUser(userLaravel)).toEqual({
      id: 1,
      nama: 'Budi',
      email: 'budi@example.com',
      role: 'siswa',
      created_at: '2026-09-25T00:00:00Z',
    })
  })

  it('null menjadi null', () => {
    expect(mapUser(null)).toBeNull()
  })
})
