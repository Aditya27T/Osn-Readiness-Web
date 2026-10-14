import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { dashboardKosong } from '@/services/mappers/dashboard.js'
import { belajarService } from '@/services/belajar.js'
import { riwayatService } from '@/services/riwayat.js'
import { siswaService } from '@/services/siswa.js'
import { usePretestStore } from '@/stores/pretest.js'
import { useRiwayatStore } from '@/stores/riwayat.js'

// Satu sumber data untuk semua angka di dashboard siswa dan streak di sidebar.
// Kontrak API + bentuk data: services/mappers/dashboard.js.

// Selama rentang ini data dianggap masih segar: pindah halaman lalu kembali
// ke dashboard tidak memicu request baru. Pakai { force: true } untuk memaksa.
const SEGAR_MS = 60_000

export const useProgressStore = defineStore('progress', () => {
  const data = ref(dashboardKosong())
  const loading = ref(false)
  const loaded = ref(false)
  const error = ref(false)

  let inflight = null // mencegah request ganda (sidebar + dashboard sama-sama memanggil)
  let controller = null // membatalkan request milik akun lama saat $reset
  let dimuatPada = 0

  const progressPersen = computed(() => {
    const { materiSelesai, materiTotal } = data.value
    return materiTotal ? Math.round((materiSelesai / materiTotal) * 100) : 0
  })

  function fetchDashboard({ force = false } = {}) {
    if (inflight) return inflight
    if (!force && loaded.value && Date.now() - dimuatPada < SEGAR_MS) return Promise.resolve()

    controller = new AbortController()
    const { signal } = controller
    loading.value = true
    error.value = false

    inflight = siswaService
      .dashboard({ signal })
      .then((hasil) => {
        if (signal.aborted) return
        data.value = {
          ...hasil,
          // Server yang jadi acuan. `||` lama membuat flag menempel selama
          // sesi walau server sudah bilang belum; markPreTestCompleted()
          // tetap memberi update optimistis sampai fetch berikutnya.
          preTestSelesai: hasil.preTestSelesai,
        }
        loaded.value = true
        dimuatPada = Date.now()
        // Statistik materi (P3): agregat daftar materi semua tingkat terbuka.
        // Cuplikan riwayat ikut diambil di sini, setelah daftar tingkat ada,
        // supaya judulnya memuat nama tingkat.
        muatStatistikMateri(signal)
      })
      .catch(() => {
        // Data lama dibiarkan agar tampilan tidak salah menunjukkan "belum ada progres".
        if (!signal.aborted) error.value = true
      })
      .finally(() => {
        if (signal.aborted) return
        loading.value = false
        inflight = null
      })

    return inflight
  }

  // Agregat materi untuk kartu statistik + kompetensi + rekomendasi.
  // Dipanggil fire-and-forget dari fetchDashboard; selalu aman gagal.
  async function muatStatistikMateri(signal) {
    try {
      const pretest = usePretestStore()
      await pretest.muatTingkat()
      if (signal.aborted) return
      const ids = pretest.tingkatList.filter((t) => t.terbuka).map((t) => t.id)
      const perTingkat = await Promise.all(ids.map((id) => belajarService.daftar({ tingkatId: id, signal })))
      if (signal.aborted) return
      const semua = perTingkat.flat()
      data.value = {
        ...data.value,
        materiSelesai: semua.filter((m) => m.progress?.status === 'selesai').length,
        materiTotal: semua.length,
        kompetensi: semua
          .filter((m) => m.nilaiTerbaik != null)
          .map((m) => ({ nama: m.judul, skor: Math.round(m.nilaiTerbaik), target: null })),
        rekomendasi: semua
          .filter((m) => m.wajib)
          .map((m) => ({
            judul: m.judul,
            sub: m.prioritas ? `Prioritas ${m.prioritas}` : 'Wajib dipelajari',
            badge: 'Wajib',
          })),
      }
      // Statistik simulasi (P4): kartu "Rata-rata Nilai Simulasi" masih
      // nol karena tidak ada endpoint agregat. Diambil dari riwayat
      // simulasi, jadi batasnya 100 baris terbaru.
      const simulasi = await riwayatService
        .daftar({ jenis: 'simulasi', perPage: 100, signal })
        .catch(() => null)
      if (simulasi) {
        const nilai = simulasi.items.map((i) => i.nilai).filter((n) => n != null)
        data.value = {
          ...data.value,
          simulasiDiikuti: simulasi.total,
          rataRataNilai: nilai.length
            ? Math.round(nilai.reduce((a, b) => a + b, 0) / nilai.length)
            : 0,
        }
      }

      // Cuplikan riwayat terbaru: opsional — gagal dimuat tidak menandai
      // error dashboard (bagian ini punya kondisi kosong sendiri).
      if (signal.aborted) return
      const items = await useRiwayatStore().terbaris().catch(() => [])
      if (signal.aborted) return
      data.value = { ...data.value, riwayat: items }
    } catch {
      // Biarkan nol/kosong — UI menampilkan kondisi kosong.
    }
  }

  // Wajib dipanggil saat login/logout supaya data siswa sebelumnya tidak terlihat akun berikutnya.
  function $reset() {
    controller?.abort()
    controller = null
    inflight = null
    dimuatPada = 0
    data.value = dashboardKosong()
    loading.value = false
    loaded.value = false
    error.value = false
  }

  function markPreTestCompleted() {
    data.value = { ...data.value, preTestSelesai: true }
  }

  return { data, loading, loaded, error, progressPersen, fetchDashboard, markPreTestCompleted, $reset }
})
