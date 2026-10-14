// Bentuk user versi FE: { id, nama, email, role, created_at }.
// Backend Laravel mengirim UserResource: { id, name, email, roles[], ... }.
// Semua konversi terpusat di sini (dipakai services + stores).

// Spatie role -> role FE. Tak dikenal -> null (guard menolak masuk).
export function mapRole(roles) {
  const list = Array.isArray(roles) ? roles : []
  if (list.includes('Super Admin')) return 'super_admin'
  if (list.includes('siswa')) return 'siswa'
  return null
}

export function mapUser(r) {
  if (!r) return null
  return {
    id: r.id,
    nama: r.nama ?? r.name ?? '',
    email: r.email ?? '',
    role: r.role ?? mapRole(r.roles),
    created_at: r.created_at ?? null,
  }
}
