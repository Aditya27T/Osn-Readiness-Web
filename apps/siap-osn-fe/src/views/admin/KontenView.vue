<script setup>
// A3 · Kompetensi, materi, dan cerita soal. Tiga modul dalam satu halaman
// karena(always) FrozenErrorcompetensi → materi → soalalways berkait: materi
// milik satu kompetensi, soal milik satu materi.
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import AdminKonfirmasi from '@/components/admin/AdminKonfirmasi.vue'
import { MODUL, useAdminStore } from '@/stores/admin.js'
import { adminService } from '@/services/admin.js'
import { pesanError, pesanField } from '@/lib/errors.js'

const admin = useAdminStore()
const toast = useToast()

const MOD = MODUL.KOMPETENSI
const galat = ref('')
const tingkatList = ref([])
const tingkatId = ref(null)

const tab = ref('kompetensi')
const TAB = [
  { key: 'kompetensi', label: 'Kompetensi' },
  { key: 'materi', label: 'Materi' },
  { key: 'konteks', label: 'Cerita Soal' },
]

const kompetensiTerpilih = ref(null)
const daftarKompetensi = computed(() => admin.daftarModul(MOD))
const daftarMateri = computed(() => admin.daftarModul(MODUL.MATERI))
const daftarKonteks = computed(() => admin.daftarModul(MODUL.KONTEKS))

/* ---------- Kompetensi ---------- */
const dialogKompetensi = ref(false)
const formKompetensi = reactive({ id: null, nama: '', deskripsi: '' })
const galatKompetensi = ref('')

/* ---------- Materi ---------- */
const dialogMateri = ref(false)
const formMateri = reactive({
  id: null, tingkatId: null, kompetensiId: null, urutan: 1,
  judul: '', deskripsi: '', isiMateri: '', fileMateri: null, gambar: null,
})
const galatMateri = ref('')
const mengunggah = ref(false)
const inputGambar = ref(null)

/* ---------- Konteks ---------- */
const dialogKonteks = ref(false)
const formKonteks = reactive({ id: null, tingkatId: null, judul: '', isi: '' })
const galatKonteks = ref('')

const konfirmasi = reactive({ terbuka: false, jenis: null, target: null })

const pilihanKompetensi = computed(() =>
  daftarKompetensi.value.map((k) => ({ label: k.nama, value: k.id })),
)

async function muat({ force = false } = {}) {
  galat.value = ''
  try {
    if (!tingkatId.value && tingkatList.value.length) tingkatId.value = tingkatList.value[0].id
    await Promise.all([
      admin.muatDaftar(MODUL.KOMPETENSI, { tingkatId: tingkatId.value, force }),
      admin.muatDaftar(MODUL.KONTEKS, { force }),
      admin.muatDaftar(MODUL.MATERI, {
        kompetensiId: kompetensiTerpilih.value?.id ?? null,
        force,
      }),
    ])
  } catch (err) {
    galat.value = pesanError(err, 'Gagal memuat data konten.')
  }
}

function gantiTingkat(id) {
  tingkatId.value = Number(id)
  kompetensiTerpilih.value = null
  muat({ force: true })
}

function pilihKompetensi(k) {
  kompetensiTerpilih.value = k
  admin.muatDaftar(MODUL.MATERI, { kompetensiId: k?.id ?? null, force: true }).catch(() => {})
}

/* ---------- Kompetensi CRUD ---------- */
function bukaKompetensi(k) {
  galatKompetensi.value = ''
  Object.assign(formKompetensi, k
    ? { id: k.id, nama: k.nama, deskripsi: k.deskripsi }
    : { id: null, nama: '', deskripsi: '' })
  dialogKompetensi.value = true
}

async function simpanKompetensi() {
  galatKompetensi.value = ''
  const payload = {
    tingkat_id: tingkatId.value,
    nama_kompetensi: formKompetensi.nama,
    deskripsi: formKompetensi.deskripsi,
  }
  try {
    await admin.jalankan({
      aksi: () => (formKompetensi.id
        ? adminService.ubahKompetensi({ id: formKompetensi.id, payload: { nama_kompetensi: payload.nama_kompetensi } })
        : adminService.tambahKompetensi({ payload })),
      muatUlang: () => muat({ force: true }),
    })
    dialogKompetensi.value = false
    toast.add({ severity: 'success', summary: 'Kompetensi disimpan', life: 4000 })
  } catch (err) {
    galatKompetensi.value = pesanField(err, 'nama_kompetensi') ?? pesanError(err)
  }
}

/* ---------- Materi CRUD ---------- */
function bukaMateri(m) {
  galatMateri.value = ''
  Object.assign(formMateri, m
    ? {
        id: m.id, tingkatId: m.tingkatId, kompetensiId: m.kompetensiId, urutan: m.urutan,
        judul: m.judul, deskripsi: m.deskripsi, isiMateri: m.isiMateri,
        fileMateri: m.fileMateri, gambar: m.gambar,
      }
    : {
        id: null, tingkatId: tingkatId.value,
        kompetensiId: kompetensiTerpilih.value?.id ?? null,
        urutan: 1, judul: '', deskripsi: '', isiMateri: '', fileMateri: null, gambar: null,
      })
  dialogMateri.value = true
}

async function simpanMateri() {
  galatMateri.value = ''
  const payload = {
    tingkat_id: formMateri.tingkatId ?? tingkatId.value,
    kompetensi_id: formMateri.kompetensiId ?? kompetensiTerpilih.value?.id,
    urutan: Number(formMateri.urutan),
    judul: formMateri.judul,
    deskripsi: formMateri.deskripsi,
    isi_materi: formMateri.isiMateri,
  }
  try {
    await admin.jalankan({
      aksi: () => (formMateri.id
        ? adminService.ubahMateri({ id: formMateri.id, payload })
        : adminService.tambahMateri({ payload })),
      muatUlang: () => muat({ force: true }),
    })
    dialogMateri.value = false
    toast.add({ severity: 'success', summary: 'Materi disimpan', life: 4000 })
  } catch (err) {
    galatMateri.value = pesanField(err, 'judul') ?? pesanError(err)
  }
}

// Unggah multipart: backend membalas { path } tanpa domain, jadi URL penuh
// disusun di FE agar konsisten dengan mappers/belajar.js.
async function pilihGambar(event) {
  const file = event.target.files?.[0]
  if (!file) return
  mengunggah.value = true
  galatMateri.value = ''
  try {
    const hasil = await adminService.unggahGambarMateri({ file })
    const dasar = String(import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/api\/?$/, '')
    formMateri.gambar = hasil.path ? `${dasar}/storage/${hasil.path}` : null
    toast.add({ severity: 'success', summary: 'Gambar diunggah', life: 4000 })
  } catch (err) {
    galatMateri.value = pesanField(err, 'gambar') ?? pesanError(err, 'Gagal mengunggah gambar.')
  } finally {
    mengunggah.value = false
    if (inputGambar.value) inputGambar.value.value = ''
  }
}

/* ---------- Konteks CRUD ---------- */
function bukaKonteks(k) {
  galatKonteks.value = ''
  Object.assign(formKonteks, k
    ? { id: k.id, tingkatId: k.tingkatId, judul: k.judul, isi: k.isi }
    : { id: null, tingkatId: tingkatId.value, judul: '', isi: '' })
  dialogKonteks.value = true
}

async function simpanKonteks() {
  galatKonteks.value = ''
  const payload = {
    tingkat_id: formKonteks.tingkatId ?? tingkatId.value,
    judul: formKonteks.judul,
    isi_konteks: formKonteks.isi,
  }
  try {
    await admin.jalankan({
      aksi: () => (formKonteks.id
        ? adminService.ubahKonteksSoal({ id: formKonteks.id, payload: { judul: payload.judul } })
        : adminService.tambahKonteksSoal({ payload })),
      muatUlang: () => muat({ force: true }),
    })
    dialogKonteks.value = false
    toast.add({ severity: 'success', summary: 'Cerita soal disimpan', life: 4000 })
  } catch (err) {
    galatKonteks.value = pesanField(err, 'judul') ?? pesanError(err)
  }
}

/* ---------- Hapus ---------- */
function ask(jenis, target) {
  konfirmasi.terbuka = true
  konfirmasi.jenis = jenis
  konfirmasi.target = target
}

async function jalankanKonfirmasi() {
  const t = konfirmasi.target
  const service = {
    kompetensi: () => adminService.hapusKompetensi({ id: t.id }),
    materi: () => adminService.hapusMateri({ id: t.id }),
    konteks: () => adminService.hapusKonteksSoal({ id: t.id }),
  }[konfirmasi.jenis]
  await admin.jalankan({ aksi: service, muatUlang: () => muat({ force: true }) })
  toast.add({ severity: 'success', summary: 'Data dihapus', life: 4000 })
}

watch(tab, (t) => {
  if (t === 'materi' && !kompetensiTerpilih.value && daftarKompetensi.value.length) {
    pilihKompetensi(daftarKompetensi.value[0])
  }
})

onMounted(async () => {
  tingkatList.value = await adminService.daftarTingkatAdmin().catch(() => [])
  await muat({ force: true })
})
</script>

<template>
  <div>
    <AdminPageHeader
      judul="Kompetensi & Materi"
      deskripsi="Struktur konten: kompetensi → materi → soal. Cerita soal dipakai beberapa soal sekaligus."
      :memuat="admin.loading"
      :galat="galat"
      @coba-lagi="muat({ force: true })"
    >
      <template #aksi-extra>
        <label class="sr-only" for="tingkat-konten">Tingkat</label>
        <select
          id="tingkat-konten"
          class="rounded-full border border-[#e6ebf2] bg-white px-3 py-1.5 text-[13px]"
          :value="tingkatId ?? ''"
          @change="gantiTingkat($event.target.value)"
        >
          <option v-for="t in tingkatList" :key="t.id" :value="t.id">{{ t.nama }}</option>
        </select>
      </template>
    </AdminPageHeader>

    <div class="mb-4 flex gap-2">
      <button
        v-for="t in TAB"
        :key="t.key"
        type="button"
        class="rounded-full border px-4 py-1.5 text-[13px] font-semibold"
        :class="tab === t.key ? 'border-[#0f2a5c] bg-[#0f2a5c] text-white' : 'border-[#e6ebf2] bg-white text-[#4a5568]'"
        @click="tab = t.key"
      >
        {{ t.label }}
      </button>
    </div>

    <!-- KOMPETENSI -->
    <Card v-if="tab === 'kompetensi'">
      <template #content>
        <div class="mb-3 flex justify-end">
          <Button size="small" label="Tambah kompetensi" @click="bukaKompetensi(null)" />
        </div>
        <p v-if="!daftarKompetensi.length" class="py-6 text-center text-sm font-bold">
          Belum ada kompetensi di tingkat ini.
        </p>
        <table v-else class="w-full text-left text-[13px]">
          <thead>
            <tr class="border-b border-[#eef1f6] text-[12px] uppercase text-[#6b778c]">
              <th class="py-2">Nama</th>
              <th class="py-2">Deskripsi</th>
              <th class="py-2 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="k in daftarKompetensi" :key="k.id" class="border-b border-[#f5f7fa]">
              <td class="py-2.5 font-medium">{{ k.nama }}</td>
              <td class="py-2.5 text-[#4a5568]">{{ k.deskripsi || '—' }}</td>
              <td class="py-2.5">
                <div class="flex justify-end gap-1.5">
                  <Button size="small" label="Materi" severity="secondary" outlined @click="pilihKompetensi(k); tab = 'materi'" />
                  <Button size="small" label="Ubah" severity="secondary" outlined @click="bukaKompetensi(k)" />
                  <Button size="small" label="Hapus" severity="danger" outlined @click="ask('kompetensi', k)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </Card>

    <!-- MATERI -->
    <Card v-else-if="tab === 'materi'">
      <template #content>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
          <p class="text-[13px] text-[#6b778c]">
            Kompetensi:
            <b class="text-[#0f1b33]">{{ kompetensiTerpilih?.nama ?? '— pilih dulu di tab Kompetensi —' }}</b>
          </p>
          <Button
            size="small"
            label="Tambah materi"
            :disabled="!kompetensiTerpilih"
            @click="bukaMateri(null)"
          />
        </div>
        <p v-if="!daftarMateri.length" class="py-6 text-center text-sm font-bold">
          Belum ada materi di kompetensi ini.
        </p>
        <table v-else class="w-full text-left text-[13px]">
          <thead>
            <tr class="border-b border-[#eef1f6] text-[12px] uppercase text-[#6b778c]">
              <th class="py-2">Urutan</th>
              <th class="py-2">Judul</th>
              <th class="py-2">Gambar</th>
              <th class="py-2 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in daftarMateri" :key="m.id" class="border-b border-[#f5f7fa]">
              <td class="py-2.5">{{ m.urutan }}</td>
              <td class="py-2.5 font-medium">{{ m.judul }}</td>
              <td class="py-2.5">
                <img
                  v-if="m.gambar"
                  :src="m.gambar"
                  alt="Gambar materi"
                  class="h-9 w-9 rounded object-cover"
                />
                <span v-else class="text-[#6b778c]">—</span>
              </td>
              <td class="py-2.5">
                <div class="flex justify-end gap-1.5">
                  <Button size="small" label="Ubah" severity="secondary" outlined @click="bukaMateri(m)" />
                  <Button size="small" label="Hapus" severity="danger" outlined @click="ask('materi', m)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </Card>

    <!-- KONTEKS -->
    <Card v-else>
      <template #content>
        <div class="mb-3 flex justify-end">
          <Button size="small" label="Tambah cerita soal" @click="bukaKonteks(null)" />
        </div>
        <p v-if="!daftarKonteks.length" class="py-6 text-center text-sm font-bold">
          Belum ada cerita soal.
        </p>
        <ul v-else class="space-y-2">
          <li
            v-for="k in daftarKonteks"
            :key="k.id"
            class="flex items-start justify-between gap-4 rounded-xl border border-[#eef1f6] px-4 py-3"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium">{{ k.judul }}</p>
              <p class="mt-0.5 line-clamp-2 text-[13px] text-[#6b778c]">{{ k.isi }}</p>
            </div>
            <div class="flex shrink-0 gap-1.5">
              <Button size="small" label="Ubah" severity="secondary" outlined @click="bukaKonteks(k)" />
              <Button size="small" label="Hapus" severity="danger" outlined @click="ask('konteks', k)" />
            </div>
          </li>
        </ul>
      </template>
    </Card>

    <!-- Dialog kompetensi -->
    <Dialog v-model:visible="dialogKompetensi" modal :header="formKompetensi.id ? 'Ubah Kompetensi' : 'Tambah Kompetensi'" :style="{ width: '30rem' }">
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="k-nama">Nama</label>
          <InputText id="k-nama" v-model="formKompetensi.nama" class="w-full" />
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="k-deskripsi">Deskripsi</label>
          <Textarea id="k-deskripsi" v-model="formKompetensi.deskripsi" rows="3" class="w-full" />
        </div>
        <p v-if="galatKompetensi" class="rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]">{{ galatKompetensi }}</p>
      </div>
      <template #footer>
        <Button label="Batal" severity="secondary" text @click="dialogKompetensi = false" />
        <Button label="Simpan" :loading="admin.menyimpan" @click="simpanKompetensi" />
      </template>
    </Dialog>

    <!-- Dialog materi -->
    <Dialog v-model:visible="dialogMateri" modal :header="formMateri.id ? 'Ubah Materi' : 'Tambah Materi'" :style="{ width: '40rem' }">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-[13px] font-medium" for="m-urutan">Urutan</label>
            <InputText id="m-urutan" v-model="formMateri.urutan" type="number" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-[13px] font-medium" for="m-komp">Kompetensi</label>
            <Select
              id="m-komp"
              v-model="formMateri.kompetensiId"
              :options="pilihanKompetensi"
              option-label="label"
              option-value="value"
              class="w-full"
            />
          </div>
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="m-judul">Judul</label>
          <InputText id="m-judul" v-model="formMateri.judul" class="w-full" />
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="m-deskripsi">Deskripsi</label>
          <InputText id="m-deskripsi" v-model="formMateri.deskripsi" class="w-full" />
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="m-isi">Isi Materi</label>
          <Textarea id="m-isi" v-model="formMateri.isiMateri" rows="6" class="w-full" />
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="m-gambar">Gambar (png/jpg/webp, maks 2 MB)</label>
          <input id="m-gambar" ref="inputGambar" type="file" accept="image/*" :disabled="mengunggah" @change="pilihGambar" />
          <img
            v-if="formMateri.gambar"
            :src="formMateri.gambar"
            alt="Pratinjau gambar materi"
            class="mt-2 h-20 rounded-lg object-cover"
          />
        </div>
        <p v-if="galatMateri" class="rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]">{{ galatMateri }}</p>
      </div>
      <template #footer>
        <Button label="Batal" severity="secondary" text @click="dialogMateri = false" />
        <Button label="Simpan" :loading="admin.menyimpan" @click="simpanMateri" />
      </template>
    </Dialog>

    <!-- Dialog konteks -->
    <Dialog v-model:visible="dialogKonteks" modal :header="formKonteks.id ? 'Ubah Cerita Soal' : 'Tambah Cerita Soal'" :style="{ width: '36rem' }">
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="x-judul">Judul</label>
          <InputText id="x-judul" v-model="formKonteks.judul" class="w-full" />
        </div>
        <div>
          <label class="mb-1 block text-[13px] font-medium" for="x-isi">Isi Cerita</label>
          <Textarea id="x-isi" v-model="formKonteks.isi" rows="6" class="w-full" />
        </div>
        <p v-if="galatKonteks" class="rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]">{{ galatKonteks }}</p>
      </div>
      <template #footer>
        <Button label="Batal" severity="secondary" text @click="dialogKonteks = false" />
        <Button label="Simpan" :loading="admin.menyimpan" @click="simpanKonteks" />
      </template>
    </Dialog>

    <AdminKonfirmasi
      :terbuka="konfirmasi.terbuka"
      judul="Hapus data?"
      :pesan="`${konfirmasi.target?.judul || konfirmasi.target?.nama || ''}. Backend menolak bila masih dipakai.`"
      :sibuk="admin.menyimpan"
      :aksi="jalankanKonfirmasi"
      @tutup="konfirmasi.terbuka = false"
    />
  </div>
</template>
