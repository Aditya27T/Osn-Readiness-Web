<script setup>
// A5 · Latihan & simulasi. Dua modul berbagi halaman karena keduanya
// menentukan materi soal yang boleh dipakai siswa.
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import ToggleSwitch from 'primevue/toggleswitch'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import AdminPaginasi from '@/components/admin/AdminPaginasi.vue'
import AdminKonfirmasi from '@/components/admin/AdminKonfirmasi.vue'
import { MODUL, useAdminStore } from '@/stores/admin.js'
import { adminService } from '@/services/admin.js'
import { pesanError, pesanField } from '@/lib/errors.js'

const admin = useAdminStore()
const toast = useToast()

const galat = ref('')
const tingkatList = ref([])
const tingkatId = ref(null)
const daftarMateri = ref([])

const tab = ref('latihan')
const daftarLatihan = computed(() => admin.daftarModul(MODUL.LATIHAN))
const metaLatihan = computed(() => admin.metaModul(MODUL.LATIHAN))
const daftarSimulasi = computed(() => admin.daftarModul(MODUL.SIMULASI))
const metaSimulasi = computed(() => admin.metaModul(MODUL.SIMULASI))

const pilihanMateri = computed(() => daftarMateri.value.map((m) => ({ label: m.judul, value: m.id })))
const pilihanTingkat = computed(() => tingkatList.value.map((t) => ({ label: t.nama, value: t.id })))

/* ---------- Latihan ---------- */
const dialogLatihan = ref(false)
const galatLatihan = ref('')
const formLatihan = reactive({ id: null, materiId: null, nama: '', deskripsi: '', jumlahSoal: 10 })

/* ---------- Simulasi ---------- */
const dialogSimulasi = ref(false)
const galatSimulasi = ref('')
const formSimulasi = reactive({
  id: null, tingkatId: null, nama: '', deskripsi: '',
  jumlahSoal: 30, durasiMenit: 90, aktif: true,
})

const konfirmasi = reactive({ terbuka: false, jenis: null, target: null })

async function muat({ force = false } = {}) {
  galat.value = ''
  try {
    if (!tingkatId.value && tingkatList.value.length) tingkatId.value = tingkatList.value[0].id
    daftarMateri.value = await adminService.daftarMateriAdmin({}).catch(() => [])
    await Promise.all([
      admin.muatDaftar(MODUL.LATIHAN, { force }),
      admin.muatDaftar(MODUL.SIMULASI, { force }),
    ])
  } catch (err) {
    galat.value = pesanError(err, 'Gagal memuat data ujian.')
  }
}

function bukaLatihan(l) {
  galatLatihan.value = ''
  Object.assign(formLatihan, l
    ? { id: l.id, materiId: l.materiId, nama: l.nama, deskripsi: l.deskripsi, jumlahSoal: l.jumlahSoal }
    : { id: null, materiId: daftarMateri.value[0]?.id ?? null, nama: '', deskripsi: '', jumlahSoal: 10 })
  dialogLatihan.value = true
}

async function simpanLatihan() {
  galatLatihan.value = ''
  const payload = {
    materi_id: formLatihan.materiId,
    nama_quiz: formLatihan.nama,
    deskripsi: formLatihan.deskripsi,
    jumlah_soal: Number(formLatihan.jumlahSoal),
  }
  try {
    await admin.jalankan({
      aksi: () => (formLatihan.id
        ? adminService.ubahLatihan({ id: formLatihan.id, payload })
        : adminService.tambahLatihan({ payload })),
      muatUlang: () => muat({ force: true }),
    })
    dialogLatihan.value = false
    toast.add({ severity: 'success', summary: 'Latihan disimpan', life: 4000 })
  } catch (err) {
    galatLatihan.value = pesanField(err, 'nama_quiz') ?? pesanField(err, 'jumlah_soal') ?? pesanError(err)
  }
}

function bukaSimulasi(s) {
  galatSimulasi.value = ''
  Object.assign(formSimulasi, s
    ? {
        id: s.id, tingkatId: s.tingkatId, nama: s.nama, deskripsi: s.deskripsi,
        jumlahSoal: s.jumlahSoal, durasiMenit: s.durasiMenit, aktif: s.aktif,
      }
    : {
        id: null, tingkatId: tingkatId.value, nama: '', deskripsi: '',
        jumlahSoal: 30, durasiMenit: 90, aktif: true,
      })
  dialogSimulasi.value = true
}

// Mengaktifkan simulasi hanya boleh bila bank soal cukup — backend yang
// memutuskan (422 pada is_aktif), jadi pesan error ditampilkan apa adanya.
async function simpanSimulasi() {
  galatSimulasi.value = ''
  const payload = {
    tingkat_id: formSimulasi.tingkatId ?? tingkatId.value,
    nama_simulasi: formSimulasi.nama,
    deskripsi: formSimulasi.deskripsi,
    jumlah_soal: Number(formSimulasi.jumlahSoal),
    durasi_menit: Number(formSimulasi.durasiMenit),
    is_aktif: formSimulasi.aktif,
  }
  try {
    await admin.jalankan({
      aksi: () => (formSimulasi.id
        ? adminService.ubahSimulasi({ id: formSimulasi.id, payload })
        : adminService.tambahSimulasi({ payload })),
      muatUlang: () => muat({ force: true }),
    })
    dialogSimulasi.value = false
    toast.add({ severity: 'success', summary: 'Simulasi disimpan', life: 4000 })
  } catch (err) {
    galatSimulasi.value = pesanField(err, 'nama_simulasi')
      ?? pesanField(err, 'jumlah_soal')
      ?? pesanError(err)
  }
}

function ask(jenis, target) {
  konfirmasi.terbuka = true
  konfirmasi.jenis = jenis
  konfirmasi.target = target
}

async function jalankanKonfirmasi() {
  const t = konfirmasi.target
  await admin.jalankan({
    aksi: konfirmasi.jenis === 'latihan'
      ? () => adminService.hapusLatihan({ id: t.id })
      : () => adminService.hapusSimulasi({ id: t.id }),
    muatUlang: () => muat({ force: true }),
  })
  toast.add({ severity: 'success', summary: 'Data dihapus', life: 4000 })
}

onMounted(() => muat({ force: true }))
</script>

<template>
  <div>
    <AdminPageHeader
      judul="Latihan & Simulasi"
      deskripsi="Satu latihan per materi; simulasi berlaku per tingkat."
      :memuat="admin.loading"
      :galat="galat"
      @coba-lagi="muat({ force: true })"
    >
      <template #aksi-extra>
        <Button
          v-if="tab === 'latihan'"
          size="small"
          label="Tambah latihan"
          @click="bukaLatihan(null)"
        />
        <Button
          v-else
          size="small"
          label="Tambah simulasi"
          @click="bukaSimulasi(null)"
        />
      </template>
    </AdminPageHeader>

    <div class="mb-4 flex gap-2">
      <button
        v-for="t in [
          { key: 'latihan', label: 'Latihan' },
          { key: 'simulasi', label: 'Simulasi' },
        ]"
        :key="t.key"
        type="button"
        class="rounded-full border px-4 py-1.5 text-[13px] font-semibold"
        :class="tab === t.key ? 'border-[#0f2a5c] bg-[#0f2a5c] text-white' : 'border-[#e6ebf2] bg-white text-[#4a5568]'"
        @click="tab = t.key"
      >
        {{ t.label }}
      </button>
    </div>

    <Card v-if="tab === 'latihan'">
      <template #content>
        <p v-if="!daftarLatihan.length" class="py-6 text-center text-sm font-bold">
          Belum ada latihan. Latihan dibuat per materi.
        </p>
        <table v-else class="w-full text-left text-[13px]">
          <thead>
            <tr class="border-b border-[#eef1f6] text-[12px] uppercase text-[#6b778c]">
              <th class="py-2">Nama</th>
              <th class="py-2">Materi</th>
              <th class="py-2">Jumlah Soal</th>
              <th class="py-2 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="l in daftarLatihan" :key="l.id" class="border-b border-[#f5f7fa]">
              <td class="py-2.5 font-medium">{{ l.nama }}</td>
              <td class="py-2.5 text-[#4a5568]">{{ l.materi?.judul ?? '—' }}</td>
              <td class="py-2.5">{{ l.jumlahSoal }}</td>
              <td class="py-2.5">
                <div class="flex justify-end gap-1.5">
                  <Button size="small" label="Ubah" severity="secondary" outlined @click="bukaLatihan(l)" />
                  <Button size="small" label="Hapus" severity="danger" outlined @click="ask('latihan', l)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <AdminPaginasi :meta="metaLatihan" :memuat="admin.loading" @ganti="(h) => admin.keHalaman(MODUL.LATIHAN, h)" />
      </template>
    </Card>

    <Card v-else>
      <template #content>
        <p v-if="!daftarSimulasi.length" class="py-6 text-center text-sm font-bold">
          Belum ada simulasi.
        </p>
        <table v-else class="w-full text-left text-[13px]">
          <thead>
            <tr class="border-b border-[#eef1f6] text-[12px] uppercase text-[#6b778c]">
              <th class="py-2">Nama</th>
              <th class="py-2">Tingkat</th>
              <th class="py-2">Soal</th>
              <th class="py-2">Durasi</th>
              <th class="py-2">Status</th>
              <th class="py-2 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in daftarSimulasi" :key="s.id" class="border-b border-[#f5f7fa]">
              <td class="py-2.5 font-medium">{{ s.nama }}</td>
              <td class="py-2.5 text-[#4a5568]">{{ s.tingkat?.nama ?? '—' }}</td>
              <td class="py-2.5">{{ s.jumlahSoal }}</td>
              <td class="py-2.5">{{ s.durasiMenit }} mnt</td>
              <td class="py-2.5">
                <span
                  class="rounded-full px-2.5 py-1 text-[11.5px] font-semibold"
                  :class="s.aktif ? 'bg-[#f3fcf6] text-[#15803d]' : 'bg-[#f3f5f9] text-[#6b7280]'"
                >
                  {{ s.aktif ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="py-2.5">
                <div class="flex justify-end gap-1.5">
                  <Button size="small" label="Ubah" severity="secondary" outlined @click="bukaSimulasi(s)" />
                  <Button size="small" label="Hapus" severity="danger" outlined @click="ask('simulasi', s)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <AdminPaginasi :meta="metaSimulasi" :memuat="admin.loading" @ganti="(h) => admin.keHalaman(MODUL.SIMULASI, h)" />
      </template>
    </Card>

    <!-- Dialog latihan -->
    <Dialog v-model:visible="dialogLatihan" modal :header="formLatihan.id ? 'Ubah Latihan' : 'Tambah Latihan'" :style="{ width: '32rem' }">
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="l-materi">Materi</label>
          <Select
            id="l-materi"
            v-model="formLatihan.materiId"
            :options="pilihanMateri"
            option-label="label"
            option-value="value"
            filter
            class="w-full"
          />
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="l-nama">Nama Latihan</label>
          <InputText id="l-nama" v-model="formLatihan.nama" class="w-full" />
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="l-deskripsi">Deskripsi</label>
          <Textarea id="l-deskripsi" v-model="formLatihan.deskripsi" rows="2" class="w-full" />
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="l-soal">Jumlah Soal</label>
          <InputNumber id="l-soal" v-model="formLatihan.jumlahSoal" :min="1" class="w-full" />
        </div>
        <p v-if="galatLatihan" class="rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]">{{ galatLatihan }}</p>
      </div>
      <template #footer>
        <Button label="Batal" severity="secondary" text @click="dialogLatihan = false" />
        <Button label="Simpan" :loading="admin.menyimpan" @click="simpanLatihan" />
      </template>
    </Dialog>

    <!-- Dialog simulasi -->
    <Dialog v-model:visible="dialogSimulasi" modal :header="formSimulasi.id ? 'Ubah Simulasi' : 'Tambah Simulasi'" :style="{ width: '32rem' }">
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="si-tingkat">Tingkat</label>
          <Select
            id="si-tingkat"
            v-model="formSimulasi.tingkatId"
            :options="pilihanTingkat"
            option-label="label"
            option-value="value"
            class="w-full"
          />
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="si-nama">Nama Simulasi</label>
          <InputText id="si-nama" v-model="formSimulasi.nama" class="w-full" />
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="si-deskripsi">Deskripsi</label>
          <Textarea id="si-deskripsi" v-model="formSimulasi.deskripsi" rows="2" class="w-full" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-[13px] font-medium" for="si-soal">Jumlah Soal</label>
            <InputNumber id="si-soal" v-model="formSimulasi.jumlahSoal" :min="1" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-[13px] font-medium" for="si-durasi">Durasi (menit)</label>
            <InputNumber id="si-durasi" v-model="formSimulasi.durasiMenit" :min="1" class="w-full" />
          </div>
        </div>
        <div class="flex items-center justify-between rounded-xl bg-[#fafbfd] px-4 py-3">
          <label class="text-[13px] font-medium" for="si-aktif">Aktif</label>
          <ToggleSwitch id="si-aktif" v-model="formSimulasi.aktif" />
        </div>
        <p v-if="galatSimulasi" class="rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]">{{ galatSimulasi }}</p>
      </div>
      <template #footer>
        <Button label="Batal" severity="secondary" text @click="dialogSimulasi = false" />
        <Button label="Simpan" :loading="admin.menyimpan" @click="simpanSimulasi" />
      </template>
    </Dialog>

    <AdminKonfirmasi
      :terbuka="konfirmasi.terbuka"
      judul="Hapus data?"
      :pesan="konfirmasi.target?.nama"
      :sibuk="admin.menyimpan"
      :aksi="jalankanKonfirmasi"
      @tutup="konfirmasi.terbuka = false"
    />
  </div>
</template>
