<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import { useAdminStore } from '@/stores/admin.js'
import { adminService } from '@/services/admin.js'
import { useAuthStore } from '@/stores/auth.js'
import { pesanError } from '@/lib/errors.js'

// Dashboard admin (Fase 6 A1). Semua angka dari GET /admin/dashboard;
// laporan kecukupan bank soal diambil terpisah karena butuh tingkat_id.

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()
const admin = useAdminStore()

const tingkatId = ref(null)
const tingkatList = ref([])
const galatKecukupan = ref('')
const galatRingkasan = ref('')

const r = computed(() => admin.ringkasan)
const k = computed(() => admin.kecukupan)

async function muatSemuanya() {
  galatRingkasan.value = ''
  galatKecukupan.value = ''
  try {
    await admin.muatRingkasan()
  } catch (err) {
    galatRingkasan.value = pesanError(err, 'Gagal memuat ringkasan admin.')
  }
  try {
    tingkatList.value = await adminService.daftarTingkatAdmin()
    if (!tingkatId.value && tingkatList.value.length) tingkatId.value = tingkatList.value[0].id
    if (tingkatId.value) await admin.muatKecukupan({ tingkatId: tingkatId.value, force: true })
  } catch (err) {
    galatKecukupan.value = pesanError(err, 'Gagal memuat laporan bank soal.')
  }
}

function gantiTingkat(id) {
  tingkatId.value = Number(id)
  admin
    .muatKecukupan({ tingkatId: tingkatId.value, force: true })
    .catch((err) => {
      galatKecukupan.value = pesanError(err, 'Gagal memuat laporan bank soal.')
    })
}

// Bukti role-guard backend bekerja dari browser: hanya super_admin yang
// bisa lolos RequireRole di GET /admin/dashboard.
async function tesAksesAdmin() {
  try {
    await adminService.dashboard()
    toast.add({ severity: 'success', summary: 'Akses admin OK', life: 4000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Akses admin ditolak', detail: pesanError(err), life: 4000 })
  }
}

async function keluar() {
  await auth.logout()
  router.push({ name: 'login' })
}

function formatNilai(n) {
  return n == null ? '—' : String(Math.round(n))
}

onMounted(muatSemuanya)
</script>

<template>
  <div>
    <AdminPageHeader
      :judul="`Halo, ${auth.nama || 'Admin'}`"
      deskripsi="Ringkasan aktivitas siswa dan kecukupan bank soal."
      :galat="galatRingkasan"
      @coba-lagi="muatSemuanya"
    >
      <template #aksi-extra>
        <Button label="Tes akses" severity="secondary" outlined :disabled="admin.loading" @click="tesAksesAdmin" />
        <Button label="Keluar" severity="secondary" @click="keluar" />
      </template>
    </AdminPageHeader>

    <p v-if="admin.loading && !r" class="text-sm text-[#6b778c]">Memuat ringkasan…</p>

    <template v-else-if="r">
      <!-- KARTU RINGKAS -->
      <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Card>
          <template #content>
            <p class="text-[13px] text-[#6b778c]">Siswa Aktif</p>
            <p class="mt-1 text-3xl font-bold leading-none">{{ r.siswaAktif }}</p>
          </template>
        </Card>
        <Card v-for="p in r.pengerjaanPerJenis" :key="p.jenis">
          <template #content>
            <p class="text-[13px] text-[#6b778c]">Pengerjaan {{ p.label }}</p>
            <p class="mt-1 text-3xl font-bold leading-none">{{ p.jumlah }}</p>
          </template>
        </Card>
      </section>

      <!-- PENGHASILAN PER JENIS + SISWA PER TINGKAT -->
      <section class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <Card>
          <template #content>
            <h2 class="mb-3 text-base font-bold">Rata-rata Nilai per Jenis</h2>
            <ul class="space-y-2">
              <li
                v-for="x in r.rataRataNilai"
                :key="x.jenis"
                class="flex items-center justify-between rounded-xl bg-[#fafbfd] px-3 py-2 text-sm"
              >
                <span>{{ x.label }}</span>
                <b>{{ formatNilai(x.nilai) }}</b>
              </li>
            </ul>
            <p v-if="!r.rataRataNilai.length" class="text-[13px] text-[#6b778c]">
              Belum ada nilai yang tercatat.
            </p>
          </template>
        </Card>

        <Card>
          <template #content>
            <h2 class="mb-3 text-base font-bold">Siswa per Tingkat Aktif</h2>
            <ul class="space-y-2">
              <li
                v-for="t in r.siswaPerTingkat"
                :key="t.tingkatId"
                class="flex items-center justify-between rounded-xl bg-[#fafbfd] px-3 py-2 text-sm"
              >
                <span>{{ t.nama }}</span>
                <b>{{ t.jumlahSiswa }}</b>
              </li>
            </ul>
            <p v-if="!r.siswaPerTingkat.length" class="text-[13px] text-[#6b778c]">
              Belum ada data tingkat.
            </p>
          </template>
        </Card>
      </section>

      <!-- KECUKUPAN BANK SOAL -->
      <section class="mt-5">
        <Card>
          <template #content>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
              <h2 class="text-base font-bold">Kecukupan Bank Soal</h2>
              <div class="flex items-center gap-2">
                <label for="tingkat-bank" class="text-[13px] text-[#6b778c]">Tingkat</label>
                <select
                  id="tingkat-bank"
                  class="rounded-full border border-[#e6ebf2] bg-white px-3 py-1.5 text-[13px]"
                  :value="tingkatId ?? ''"
                  @change="gantiTingkat($event.target.value)"
                >
                  <option v-for="t in tingkatList" :key="t.id" :value="t.id">{{ t.nama }}</option>
                </select>
              </div>
            </div>

            <p
              v-if="galatKecukupan"
              class="mb-3 rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]"
            >
              {{ galatKecukupan }}
            </p>

            <template v-if="k">
              <p
                v-if="admin.bankSoalRingkas?.kurang"
                class="mb-3 rounded-xl bg-[#fffaef] px-3 py-2 text-[13px] text-[#8a5a12]"
              >
                {{ admin.bankSoalRingkas.kurang }} bagian belum mencukupi. Laporan di bawah
                ditandai kuning.
              </p>

              <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                  <h3 class="mb-2 text-sm font-semibold">Pre-test per Level</h3>
                  <ul class="space-y-1.5">
                    <li
                      v-for="x in k.pretestPerLevel"
                      :key="x.level"
                      class="flex items-center justify-between rounded-lg px-3 py-1.5 text-[13px]"
                      :class="x.kurang ? 'bg-[#fffaef] text-[#8a5a12]' : 'bg-[#f3fcf6] text-[#15803d]'"
                    >
                      <span>{{ x.label }}</span>
                      <span>{{ x.tersedia }} tersedia · kuota {{ x.kuota }}</span>
                    </li>
                  </ul>

                  <h3 class="mb-2 mt-4 text-sm font-semibold">Putaran Pre-test</h3>
                  <p
                    class="rounded-lg px-3 py-1.5 text-[13px]"
                    :class="k.putaranPretest.kurang ? 'bg-[#fffaef] text-[#8a5a12]' : 'bg-[#f3fcf6] text-[#15803d]'"
                  >
                    {{ k.putaranPretest.putaran }} putaran berjalan · ambang {{ k.putaranPretest.ambang }}
                  </p>
                </div>

                <div>
                  <h3 class="mb-2 text-sm font-semibold">Simulasi per Level</h3>
                  <div v-for="s in k.simulasiPerLevel" :key="s.simulasiId" class="mb-3">
                    <p class="mb-1 text-[13px] font-medium">
                      {{ s.nama }}
                      <span v-if="!s.aktif" class="ml-1 text-xs text-[#b45309]">(tidak aktif)</span>
                    </p>
                    <ul class="space-y-1.5">
                      <li
                        v-for="x in s.perLevel"
                        :key="x.level"
                        class="flex items-center justify-between rounded-lg px-3 py-1.5 text-[13px]"
                        :class="x.kurang ? 'bg-[#fffaef] text-[#8a5a12]' : 'bg-[#f3fcf6] text-[#15803d]'"
                      >
                        <span>{{ x.label }}</span>
                        <span>{{ x.tersedia }} tersedia · kuota {{ x.kuota }}</span>
                      </li>
                    </ul>
                  </div>

                  <h3 class="mb-2 text-sm font-semibold">Latihan per Materi</h3>
                  <ul class="space-y-1.5">
                    <li
                      v-for="x in k.latihanPerMateri"
                      :key="x.materiId"
                      class="flex items-center justify-between rounded-lg px-3 py-1.5 text-[13px]"
                      :class="!x.punyaLatihan || x.kurang ? 'bg-[#fffaef] text-[#8a5a12]' : 'bg-[#f3fcf6] text-[#15803d]'"
                    >
                      <span class="truncate">{{ x.judul }}</span>
                      <span class="shrink-0">
                        {{ x.punyaLatihan ? `${x.tersedia}/${x.dibutuhkan}` : 'belum ada latihan' }}
                      </span>
                    </li>
                  </ul>
                </div>
              </div>
            </template>
            <p v-else-if="!admin.loading" class="text-[13px] text-[#6b778c]">
              Pilih tingkat untuk melihat laporan bank soal.
            </p>
          </template>
        </Card>
      </section>
    </template>
  </div>
</template>
