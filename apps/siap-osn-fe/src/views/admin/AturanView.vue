<script setup>
// A6 · Tingkat & aturan pemetaan. Tingkat hanya bisa diubah nama dan
// deskripsi (backend menolak tambah/hapus). Aturan pemetaan 16 parameter,
// semua wajib dikirim; store sudah memvalidasi jumlah persen level = 100.
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import { useAdminStore } from '@/stores/admin.js'
import { adminService } from '@/services/admin.js'
import { pesanError, pesanField } from '@/lib/errors.js'

const admin = useAdminStore()
const toast = useToast()

const galat = ref('')
const galatAturan = ref('')
const tingkatList = ref([])
const tingkatId = ref(null)

// Grouping field untuk form — ATURAN_FIELDS tetap jadi acuan nama kolom.
const GROUPS = [
  {
    judul: 'Bobot per level',
    fields: [
      { key: 'bobot_mudah', label: 'Bobot mudah' },
      { key: 'bobot_sedang', label: 'Bobot sedang' },
      { key: 'bobot_sulit', label: 'Bobot sulit' },
    ],
  },
  {
    judul: 'Pre-test',
    fields: [
      { key: 'pretest_jumlah_soal', label: 'Jumlah soal' },
      { key: 'pretest_persen_mudah', label: 'Persen mudah' },
      { key: 'pretest_persen_sedang', label: 'Persen sedang' },
      { key: 'pretest_persen_sulit', label: 'Persen sulit' },
      { key: 'pretest_min_soal_per_materi', label: 'Min soal per materi' },
    ],
  },
  {
    judul: 'Materi wajib & latihan',
    fields: [
      { key: 'jumlah_materi_wajib', label: 'Jumlah materi wajib' },
      { key: 'latihan_min_soal', label: 'Min soal latihan' },
      { key: 'latihan_min_nilai', label: 'Min nilai latihan' },
    ],
  },
  {
    judul: 'Simulasi',
    fields: [
      { key: 'simulasi_persen_mudah', label: 'Persen mudah' },
      { key: 'simulasi_persen_sedang', label: 'Persen sedang' },
      { key: 'simulasi_persen_sulit', label: 'Persen sulit' },
      { key: 'simulasi_maks_percobaan', label: 'Maks percobaan' },
      { key: 'passing_grade', label: 'Passing grade' },
    ],
  },
]

const form = reactive({
  id: null, nama: '', deskripsi: '',
})
const galatTingkat = ref('')

const aturan = computed(() => admin.aturan)
const totalPersenPretest = computed(() => {
  const a = aturan.value
  if (!a) return 0
  return a.pretest_persen_mudah + a.pretest_persen_sedang + a.pretest_persen_sulit
})
const totalPersenSimulasi = computed(() => {
  const a = aturan.value
  if (!a) return 0
  return a.simulasi_persen_mudah + a.simulasi_persen_sedang + a.simulasi_persen_sulit
})

async function muatTingkat() {
  try {
    tingkatList.value = await adminService.daftarTingkatAdmin()
    if (!tingkatId.value && tingkatList.value.length) tingkatId.value = tingkatList.value[0].id
  } catch (err) {
    galat.value = pesanError(err, 'Gagal memuat daftar tingkat.')
  }
}

async function muatAturan(force = false) {
  if (!tingkatId.value) return
  galatAturan.value = ''
  const t = tingkatList.value.find((x) => x.id === Number(tingkatId.value))
  if (t) {
    form.id = t.id
    form.nama = t.nama
    form.deskripsi = t.deskripsi
  }
  try {
    await admin.muatAturan({ tingkatId: tingkatId.value, force })
  } catch (err) {
    galatAturan.value = pesanError(err, 'Gagal memuat aturan pemetaan.')
  }
}

function gantiTingkat(id) {
  tingkatId.value = Number(id)
  muatAturan(true)
}

async function simpanTingkat() {
  galatTingkat.value = ''
  try {
    await admin.jalankan({
      aksi: () => adminService.ubahTingkat({
        id: form.id,
        payload: { nama_tingkat: form.nama, deskripsi: form.deskripsi },
      }),
      muatUlang: muatTingkat,
    })
    toast.add({ severity: 'success', summary: 'Tingkat diperbarui', life: 4000 })
  } catch (err) {
    galatTingkat.value = pesanField(err, 'nama_tingkat') ?? pesanError(err)
  }
}

async function simpanAturan() {
  galatAturan.value = ''
  try {
    await admin.simpanAturan()
    toast.add({ severity: 'success', summary: 'Aturan pemetaan disimpan', life: 4000 })
  } catch (err) {
    galatAturan.value = pesanField(err, 'passing_grade') ?? pesanError(err)
  }
}

onMounted(async () => {
  await muatTingkat()
  await muatAturan()
})
</script>

<template>
  <div>
    <AdminPageHeader
      judul="Tingkat & Aturan Pemetaan"
      deskripsi="Tingkat tidak bisa ditambah atau dihapus. Aturan pemetaan berlaku untuk satu tingkat."
      :memuat="admin.loading"
      :galat="galat"
      @coba-lagi="muatTingkat()"
    >
      <template #aksi-extra>
        <label class="sr-only" for="tingkat-aturan">Tingkat</label>
        <select
          id="tingkat-aturan"
          class="rounded-full border border-[#e6ebf2] bg-white px-3 py-1.5 text-[13px]"
          :value="tingkatId ?? ''"
          @change="gantiTingkat($event.target.value)"
        >
          <option v-for="t in tingkatList" :key="t.id" :value="t.id">{{ t.nama }}</option>
        </select>
      </template>
    </AdminPageHeader>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
      <!-- NAMA TINGKAT -->
      <Card>
        <template #content>
          <h2 class="mb-3 text-base font-bold">Data Tingkat</h2>
          <div class="space-y-3">
            <div>
              <label class="mb-1 block text-[13px] font-medium" for="t-nama">Nama Tingkat</label>
              <InputText id="t-nama" v-model="form.nama" class="w-full" />
            </div>
            <div>
              <label class="mb-1 block text-[13px] font-medium" for="t-deskripsi">Deskripsi</label>
              <Textarea id="t-deskripsi" v-model="form.deskripsi" rows="3" class="w-full" />
            </div>
            <p v-if="galatTingkat" class="rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]">
              {{ galatTingkat }}
            </p>
            <Button label="Simpan tingkat" :loading="admin.menyimpan" @click="simpanTingkat" />
          </div>
        </template>
      </Card>

      <!-- ATURAN PEMETAAN -->
      <Card>
        <template #content>
          <h2 class="mb-1 text-base font-bold">Aturan Pemetaan</h2>
          <p class="mb-3 text-[13px] text-[#6b778c]">
            Semua {{ admin.ATURAN_FIELDS.length }} parameter wajib diisi. Tiga persen level
            pre-test dan simulasi masing-masing harus berjumlah 100.
          </p>

          <p
            v-if="galatAturan"
            class="mb-3 rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]"
          >
            {{ galatAturan }}
          </p>
          <p v-else-if="admin.aturanValid?.pesan" class="mb-3 rounded-xl bg-[#fffaef] px-3 py-2 text-[13px] text-[#8a5a12]">
            {{ admin.aturanValid.pesan }}
          </p>

          <div v-if="aturan" class="space-y-4">
            <div
              v-for="g in GROUPS"
              :key="g.judul"
              class="rounded-xl border border-[#eef1f6] p-3"
            >
              <h3 class="mb-2 text-[13px] font-semibold">{{ g.judul }}</h3>
              <div class="grid grid-cols-2 gap-2">
                <div v-for="f in g.fields" :key="f.key">
                  <label class="mb-1 block text-[12px] text-[#6b778c]" :for="`a-${f.key}`">
                    {{ f.label }}
                  </label>
                  <InputNumber
                    :id="`a-${f.key}`"
                    v-model="aturan[f.key]"
                    :min="0"
                    class="w-full"
                  />
                </div>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 text-[12.5px]">
              <span
                class="rounded-full px-2.5 py-1 font-semibold"
                :class="totalPersenPretest === 100 ? 'bg-[#f3fcf6] text-[#15803d]' : 'bg-[#fffaef] text-[#8a5a12]'"
              >
                Total persen pre-test: {{ totalPersenPretest }}
              </span>
              <span
                class="rounded-full px-2.5 py-1 font-semibold"
                :class="totalPersenSimulasi === 100 ? 'bg-[#f3fcf6] text-[#15803d]' : 'bg-[#fffaef] text-[#8a5a12]'"
              >
                Total persen simulasi: {{ totalPersenSimulasi }}
              </span>
            </div>

            <Button
              label="Simpan aturan pemetaan"
              :loading="admin.menyimpan"
              :disabled="!admin.aturanValid.valid"
              @click="simpanAturan"
            />
          </div>
          <p v-else-if="!admin.loading" class="text-[13px] text-[#6b778c]">
            Pilih tingkat untuk melihat aturan pemetaannya.
          </p>
        </template>
      </Card>
    </section>
  </div>
</template>
