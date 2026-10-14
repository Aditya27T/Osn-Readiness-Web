import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { routes, setupGuard } from '@/router/index.js'

vi.mock('@/views/LandingPage.vue', () => ({ default: { name: 'LandingPage' } }))
vi.mock('@/views/LoginView.vue', () => ({ default: { name: 'LoginView' } }))

const siswa = { id: '1', nama: 'Budi', email: 'budi@example.com', role: 'siswa' }
const admin = { id: '2', nama: 'Admin', email: 'admin@example.com', role: 'super_admin' }

async function pushAs(path, user) {
  setActivePinia(createPinia())
  const auth = useAuthStore()
  auth.initialized = true
  auth.user = user
  const router = setupGuard(createRouter({ history: createMemoryHistory(), routes }))
  await router.push(path)
  return router.currentRoute.value.name
}

describe('router guard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('tamu buka /admin diarahkan ke login', async () => {
    expect(await pushAs('/admin', null)).toBe('login')
  })

  it('siswa buka /admin diarahkan ke forbidden', async () => {
    expect(await pushAs('/admin', siswa)).toBe('forbidden')
  })

  it('super_admin buka /admin lolos ke admin.dashboard', async () => {
    expect(await pushAs('/admin', admin)).toBe('admin.dashboard')
  })

  it('user login buka /login dilempar ke dashboard sesuai role', async () => {
    expect(await pushAs('/login', siswa)).toBe('siswa.dashboard')
    expect(await pushAs('/login', admin)).toBe('admin.dashboard')
  })

  it('tamu buka /login tetap di login', async () => {
    expect(await pushAs('/login', null)).toBe('login')
  })

  it('bypass dev membuka /siswa tanpa backend', async () => {
    vi.stubEnv('VITE_BYPASS_AUTH', 'true')
    vi.stubEnv('VITE_BYPASS_ROLE', 'siswa')
    try {
      setActivePinia(createPinia())
      const router = setupGuard(createRouter({ history: createMemoryHistory(), routes }))
      await router.push('/siswa')
      expect(router.currentRoute.value.name).toBe('siswa.dashboard')
    } finally {
      vi.unstubAllEnvs()
    }
  })

  it('siswa buka /siswa lolos ke siswa.dashboard', async () => {
    expect(await pushAs('/siswa', siswa)).toBe('siswa.dashboard')
  })

  it('siswa dapat membuka route pretest', async () => {
    expect(await pushAs('/siswa/pretest', siswa)).toBe('siswa.pretest')
  })

  it('super_admin buka /siswa diarahkan ke forbidden', async () => {
    expect(await pushAs('/siswa', admin)).toBe('forbidden')
  })

  it('tamu buka /siswa diarahkan ke login', async () => {
    expect(await pushAs('/siswa', null)).toBe('login')
  })
})
