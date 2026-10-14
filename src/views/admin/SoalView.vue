<script setup>
// A4 · Soal & pembahasan. Daftar soal berpaginasi dengan kunci jawaban
// (admin berhak melihatnya), tambah/ubah/delete soal, dan kelola
// pembahasan per soal.
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import { renderKontenSatuan } from '@/lib/richtext.js'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import AdminPaginasi from '@/components/admin/AdminPaginasi.vue'
import AdminKonfirmasi from '@/components/admin/AdminKonfirmasi.vue'
import { MODUL, useAdminStore } from '@/stores/admin.js'
import { adminService } from '@/services/admin.js'
import { labelLevel, labelPeruntukan } from '@/services/mappers/admin.js'
import { pesanError, pesanField } from '@/lib/errors.js'

const admin = useAdminStore()
const toast = useToast()

const MOD = MODUL.SOAL
const galat = ref('')

const tingkatList = ref([])
const tingkatId = ref(null)
const daftarMateri = ref([])
const daftarKonteks = ref([])

const daftar = computed(() => admin.daftarModul(MOD))
const meta = computed(() => admin.metaModul(MOD))

/* ---------- Form soal ---------- */
const dialogSoal = ref(false)
const galatSoal = ref('')
const HURUF = ['A', 'B', 'C', 'D', 'E']
const form = reactive({
  id: null, tingkatId: null, materiId: null, konteksId: null,
  level: 'mudah', peruntukan: 'pretest', tipe: 'ganda',
  pertanyaan: '', opsi: { A: '', B: '', C: '', D: '', E: '' },
  kunci: '', gambar: null,
})

/* ---------- Pembahasan ---------- */
const dialogPembahasan = ref(false)
const galatPembahasan = ref('')
const formPembahasan = reactive({ id: null, nomor: 0, isi: '' })

const konfirmasi = reactive({ terbuka: false, target: null })

const pilihanMateri = computed(() => daftarMateri.value.map((m) => ({ label: m.judul, value: m.id })))
const pilihanKonteks = computed(() => [
  { label: 'Tanpa cerita', value: null },
  ...daftarKonteks.value.map((k) => ({ label: k.judul, value: k.id })),
])
const hurufKunci = computed(() => HURUF.slice(0, Object.values(form.opsi).filter((v) => v.trim()).length))
const opsiTerisi = computed(() => Object.values(form.opsi).filter((v) => v.trim()).length)

async function muat({ force = false } = {}) {
  galat.value = ''
  try {
    if (!tingkatId.value && tingkatList.value.length) tingkatId.value = tingkatList.value[0].id
    await Promise.all([
      admin.muatDaftar(MOD, { force }),
      muatReferensi(),
    ])
  } catch (err) {
    galat.value = pesanError(err, 'Gagal memuat daftar soal.')
  }
}

async function muatReferensi() {
  try {
    daftarMateri.value = await adminService.daftarMateriAdmin({})
  } catch {
    daftarMateri.value = []
  }
  try {
    daftarKonteks.value = await adminService.daftarKonteksSoal()
  } catch {
    daftarKonteks.value = []
  }
}

// Daftar soal di panel admin dirender sebaris: teksnya bisa berisi Markdown
// dan rumus, dan daftarnya bukan dokumen panjang seperti isi materi.
function soalHtml(teks) {
  return renderKontenSatuan(teks)
}

function gantiTingkat(id) {
  tingkatId.value = Number(id)
  muat({ force: true })
}

/* ---------- Tambah / ubah ---------- */
function bukaSoal(s) {
  galatSoal.value = ''
  if (s) {
    const opsi = { A: '', B: '', C: '', D: '', E: '' }
    for (const [k, v] of Object.entries(s.pilihan ?? {})) {
      if (k in opsi) opsi[k] = v
    }
    Object.assign(form, {
      id: s.id, tingkatId: s.tingkatId, materiId: s.materiId, konteksId: s.konteksId,
      level: s.level, peruntukan: s.peruntukan, tipe: s.tipe, pertanyaan: s.pertanyaan,
      opsi, kunci: s.kunci ?? '', gambar: s.gambar,
    })
  } else {
    Object.assign(form, {
      id: null, tingkatId: tingkatId.value, materiId: null, konteksId: null,
      level: 'mudah', peruntukan: 'pretest', tipe: 'ganda',
      pertanyaan: '', opsi: { A: '', B: '', C: '', D: '', E: '' }, kunci: '', gambar: null,
    })
  }
  dialogSoal.value = true
}

// Tipe ganda mengirim pilihan_jawaban + kunci; isian hanya kunci (teks).
async function simpanSoal() {
  galatSoal.value = ''
  const dasar = {
    tingkat_id: form.tingkatId ?? tingkatId.value,
    materi_id: form.materiId,
    konteks_id: form.konteksId ?? null,
    level: form.level,
    peruntukan: form.peruntukan,
    tipe_soal: form.tipe === 'isian' ? 'isian' : 'pilihan_ganda',
    pertanyaan: form.pertanyaan,
    kunci_jawaban: form.kunci,
  }
  const payload = form.tipe === 'isian'
    ? dasar
    : { ...dasar, pilihan_jawaban: Object.fromEntries(Object.entries(form.opsi).filter(([, v]) => v.trim())) }
  try {
    await admin.jalankan({
      aksi: () => (form.id
        ? adminService.ubahSoal({ id: form.id, payload })
        : adminService.tambahSoal({ payload })),
      muatUlang: () => muat({ force: true }),
    })
    dialogSoal.value = false
    toast.add({ severity: 'success', summary: 'Soal disimpan', life: 4000 })
  } catch (err) {
    galatSoal.value = pesanField(err, 'kunci_jawaban') ?? pesanField(err, 'pertanyaan') ?? pesanError(err)
  }
}

/* ---------- Pembahasan ---------- */
async function bukaPembahasan(s) {
  galatPembahasan.value = ''
  formPembahasan.id = s.id
  formPembahasan.nomor = daftar.value.findIndex((x) => x.id === s.id) + 1
  formPembahasan.isi = s.pembahasan ?? ''
  dialogPembahasan.value = true
  // 404 = belum ada pembahasan; store sudah mengubahnya jadi null.
  const dariServer = await admin.muatPembahasan({ id: s.id }).catch(() => null)
  formPembahasan.isi = dariServer?.isi_pembahasan ?? ''
}

async function simpanPembahasan() {
  galatPembahasan.value = ''
  try {
    await admin.jalankan({
      aksi: () => adminService.simpanPembahasan({
        id: formPembahasan.id,
        isiPembahasan: formPembahasan.isi,
      }),
      muatUlang: () => muat({ force: true }),
    })
    dialogPembahasan.value = false
    toast.add({ severity: 'success', summary: 'Pembahasan disimpan', life: 4000 })
  } catch (err) {
    galatPembahasan.value = pesanField(err, 'isi_pembahasan') ?? pesanError(err)
  }
}

async function hapusPembahasan() {
  try {
    await admin.jalankan({
      aksi: () => adminService.hapusPembahasan({ id: formPembahasan.id }),
      muatUlang: () => muat({ force: true }),
    })
    dialogPembahasan.value = false
    toast.add({ severity: 'success', summary: 'Pembahasan dihapus', life: 4000 })
  } catch (err) {
    galatPembahasan.value = pesanError(err)
  }
}

function ask(s) {
  konfirmasi.terbuka = true
  konfirmasi.target = s
}

async function jalankanKonfirmasi() {
  await admin.jalankan({
    aksi: () => adminService.hapusSoal({ id: konfirmasi.target.id }),
    muatUlang: () => muat({ force: true }),
  })
  toast.add({ severity: 'success', summary: 'Soal dihapus', life: 4000 })
}

onMounted(() => muat({ force: true }))
</script>

<template>
  <div>
    <AdminPageHeader
      judul="Soal & Pembahasan"
      deskripsi="Bank soal per tingkat. Admin melihat kunci jawaban; siswa tidak."
      :memuat="admin.loading"
      :galat="galat"
      aksi-teks="Tambah soal"
      @aksi="bukaSoal(null)"
      @coba-lagi="muat({ force: true })"
    >
      <template #aksi-extra>
        <label class="sr-only" for="tingkat-soal">Tingkat</label>
        <select
          id="tingkat-soal"
          class="rounded-full border border-[#e6ebf2] bg-white px-3 py-1.5 text-[13px]"
          :value="tingkatId ?? ''"
          @change="gantiTingkat($event.target.value)"
        >
          <option v-for="t in tingkatList" :key="t.id" :value="t.id">{{ t.nama }}</option>
        </select>
      </template>
    </AdminPageHeader>

    <Card>
      <template #content>
        <p v-if="admin.loading && !daftar.length" class="text-sm text-[#6b778c]">Memuat soal…</p>
        <p v-else-if="!daftar.length" class="py-6 text-center text-sm font-bold">Belum ada soal.</p>

        <ul v-else class="space-y-2">
          <li
            v-for="s in daftar"
            :key="s.id"
            class="rounded-xl border border-[#eef1f6] px-4 py-3"
          >
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium" v-html="soalHtml(s.pertanyaan)"></p>
                <p class="mt-1 flex flex-wrap items-center gap-2 text-[12px] text-[#6b778c]">
                  <span class="rounded bg-[#f3f5f9] px-1.5 py-0.5">#{{ meta.halaman }}·{{ s.id }}</span>
                  <span class="rounded bg-[#e8f0fe] px-1.5 py-0.5 text-[#1d4ed8]">{{ labelLevel(s.level) }}</span>
                  <span class="rounded bg-[#fdf1d3] px-1.5 py-0.5 text-[#92400e]">{{ labelPeruntukan(s.peruntukan) }}</span>
                  <span>{{ s.materi?.judul ?? '—' }}</span>
                  <span>· kunci: <b>{{ s.kunci || '—' }}</b></span>
                  <span
                    v-if="s.pembahasan"
                    class="rounded bg-[#f3fcf6] px-1.5 py-0.5 text-[#15803d]"
                  >punya pembahasan</span>
                </p>
              </div>
              <div class="flex shrink-0 gap-1.5">
                <Button size="small" label="Pembahasan" severity="secondary" outlined @click="bukaPembahasan(s)" />
                <Button size="small" label="Ubah" severity="secondary" outlined @click="bukaSoal(s)" />
                <Button size="small" label="Hapus" severity="danger" outlined @click="ask(s)" />
              </div>
            </div>
          </li>
        </ul>

        <AdminPaginasi :meta="meta" :memuat="admin.loading" @ganti="(h) => admin.keHalaman(MOD, h)" />
      </template>
    </Card>

    <!-- Dialog soal -->
    <Dialog v-model:visible="dialogSoal" modal :header="form.id ? 'Ubah Soal' : 'Tambah Soal'" :style="{ width: '44rem' }">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-[13px] font-medium" for="s-materi">Materi</label>
            <Select
              id="s-materi"
              v-model="form.materiId"
              :options="pilihanMateri"
              option-label="label"
              option-value="value"
              filter
              class="w-full"
            />
          </div>
          <div>
            <label class="mb-1 block text-[13px] font-medium" for="s-konteks">Cerita Soal</label>
            <Select
              id="s-konteks"
              v-model="form.konteksId"
              :options="pilihanKonteks"
              option-label="label"
              option-value="value"
              class="w-full"
            />
          </div>
          <div>
            <label class="mb-1 block text-[13px] font-medium" for="s-level">Level</label>
            <Select
              id="s-level"
              v-model="form.level"
              :options="[
                { label: 'Mudah', value: 'mudah' },
                { label: 'Sedang', value: 'sedang' },
                { label: 'Sulit', value: 'sulit' },
              ]"
              option-label="label"
              option-value="value"
              class="w-full"
            />
          </div>
          <div>
            <label class="mb-1 block text-[13px] font-medium" for="s-peruntukan">Peruntukan</label>
            <Select
              id="s-peruntukan"
              v-model="form.peruntukan"
              :options="[
                { label: 'Pre-test', value: 'pretest' },
                { label: 'Latihan', value: 'latihan' },
                { label: 'Simulasi', value: 'simulasi' },
              ]"
              option-label="label"
              option-value="value"
              class="w-full"
            />
          </div>
          <div>
            <label class="mb-1 block text-[13px] font-medium" for="s-tipe">Tipe</label>
            <Select
              id="s-tipe"
              v-model="form.tipe"
              :options="[
                { label: 'Pilihan Ganda', value: 'ganda' },
                { label: 'Isian', value: 'isian' },
              ]"
              option-label="label"
              option-value="value"
              class="w-full"
            />
          </div>
        </div>

        <div>
          <label class="mb-1 block text-[13px] font-medium" for="s-pertanyaan">Pertanyaan</label>
          <Textarea id="s-pertanyaan" v-model="form.pertanyaan" rows="3" class="w-full" />
        </div>

        <div v-if="form.tipe === 'ganda'">
          <label class="mb-1 block text-[13px] font-medium">Pilihan ({{ opsiTerisi }} terisi)</label>
          <div class="space-y-1.5">
            <div v-for="h in HURUF" :key="h" class="flex items-center gap-2">
              <b class="w-5 text-[13px] text-[#1e3a8a]">{{ h }}</b>
              <InputText v-model="form.opsi[h]" class="w-full" :placeholder="`Teks pilihan ${h}`" />
            </div>
          </div>
        </div>

        <div>
          <label class="mb-1 block text-[13px] font-medium" for="s-kunci">
            Kunci jawaban
            <span v-if="form.tipe === 'ganda'" class="text-[#6b778c]">(pilih huruf yang terisi)</span>
          </label>
          <Select
            v-if="form.tipe === 'ganda' && opsiTerisi > 0"
            id="s-kunci"
            v-model="form.kunci"
            :options="hurufKunci"
            class="w-full"
          />
          <InputText v-else id="s-kunci" v-model="form.kunci" class="w-full" placeholder="Jawaban benar" />
        </div>

        <p v-if="galatSoal" class="rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]">{{ galatSoal }}</p>
      </div>
      <template #footer>
        <Button label="Batal" severity="secondary" text @click="dialogSoal = false" />
        <Button label="Simpan" :loading="admin.menyimpan" @click="simpanSoal" />
      </template>
    </Dialog>

    <!-- Dialog pembahasan -->
    <Dialog v-model:visible="dialogPembahasan" modal :header="`Pembahasan soal #${formPembahasan.nomor}`" :style="{ width: '36rem' }">
      <div class="space-y-3">
        <label class="mb-1 block text-[13px] font-medium" for="p-isi">Isi Pembahasan</label>
        <Textarea id="p-isi" v-model="formPembahasan.isi" rows="8" class="w-full" />
        <p v-if="galatPembahasan" class="rounded-xl bg-[#fdf0f0] px-3 py-2 text-[13px] text-[#a33333]">{{ galatPembahasan }}</p>
      </div>
      <template #footer>
        <Button
          v-if="formPembahasan.isi"
          label="Hapus"
          severity="danger"
          text
          :loading="admin.menyimpan"
          @click="hapusPembahasan"
        />
        <Button label="Batal" severity="secondary" text @click="dialogPembahasan = false" />
        <Button label="Simpan" :loading="admin.menyimpan" @click="simpanPembahasan" />
      </template>
    </Dialog>

    <AdminKonfirmasi
      :terbuka="konfirmasi.terbuka"
      judul="Hapus soal?"
      pesan="Soal dihapus soft delete, jadi tetap tidak muncul di bank soal tapi riwayat jawaban siswa aman."
      :sibuk="admin.menyimpan"
      :aksi="jalankanKonfirmasi"
      @tutup="konfirmasi.terbuka = false"
    />
  </div>
</template>
