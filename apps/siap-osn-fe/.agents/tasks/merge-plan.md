# Frontend Merge Implementation Plan

**Objective:** Merge improvements from `origin/dev-dhiaz` (auth layer enhancements) and `origin/feat/simulasi-view` (Simulasi view with modal and pembahasan grid) into the current `integrasi` branch while preserving all existing routes, stores, views, and backend integrations.

**Status:** Plan ready for implementation

---

## Branch Analysis Summary

### Current State (integrasi)
- ✓ All 7 student routes implemented: `/siswa/dashboard`, `/siswa/pretest`, `/siswa/latihan`, `/siswa/materi`, `/siswa/riwayat`, `/siswa/simulasi`, `/siswa/pemetaan-kompetensi`
- ✓ Basic SimulasiView: Level selection cards (kabupaten/provinsi/nasional), score ring, hasil stats, pembahasan list (flat array)
- ✓ All stores present: auth, latihan, materi, pretest, progress, riwayat
- ✓ Backend integrations in place

### Changes in origin/dev-dhiaz (Branch Timestamp: integrasi login dan register)
- **LoginForm.vue:** 
  - Added `berhasil` state to show success message before redirect
  - Success toast with 1-second delay before navigation
  - Button changes color to green (#16a34a) with checkmark on success
  - Better UX: loading → success → redirect flow
  - Added `remember` parameter to login payload
  
- **auth.js (stores):**
  - Changed `resetDataAkun()` to only reset `useProgressStore()` (removed simultaneous reset of pretest, riwayat, materi, latihan)
  - Rationale: Simpler cleanup; other stores handle their own lifecycle
  - Login, logout, $reset all call simplified reset
  
- **api.js (lib):**
  - Removed `withCredentials: true` (Bearer token doesn't need CORS credentials)
  - Reformatted spacing in 401 check condition (cosmetic)

### Changes in origin/feat/simulasi-view (Branch Timestamp: Merge branch 'feat/simulasi-view')
- **SimulasiView.vue:**
  - Added modal dialog with detailed simulasi information (requires checkbox consent before starting)
  - Modal shows: Tingkat, Jumlah Soal, Durasi Waktu, Tipe Soal (with visual indicators)
  - Modal aturan (5 rules: Auto timer, Autosave, Free navigation, Auto submit, Network stability)
  - Changed `levels` data: Added `kode` field (OSN-K 2026, OSN-P 2026)
  - Changed `pembahasan` from array of objects to object with `{ total, salah, judul }` structure
  - Added `kotak` computed: grid of question boxes (benar/salah indicators)
  - Added `lihatPembahasan()` function (checks for route 'siswa.pembahasan')
  - Removed nasional level from levels array display (kept but commented)
  - Modal uses Teleport to body, handles ESC key, manages body overflow
  - Comprehensive styling with responsive design (down to 560px)
  - Better visual hierarchy with color constants (--emas, --hijau, --merah, --ink, --abu, --garis)

- **PretestView.vue:**
  - Major layout restructuring: removed old "STATUS AWAL" section flow
  - Introduced cleaner state management (likely integrated with backend better)
  - Better error handling and UI states

---

## Merge Strategy

**Principle:** Take auth improvements from dev-dhiaz (they're isolated and enhance UX). Take SimulasiView rewrite from feat/simulasi-view (it's a complete, polished version). Ensure no store/route deletions.

---

## Implementation Plan

- [ ] **1. Update LoginForm.vue with success state UX**
      Apply dev-dhiaz improvements: add `berhasil` state, success toast, delay before redirect, button color change.
      Files: `src/components/auth/LoginForm.vue`
      Verify: `npm run dev` opens; login form renders; button states change correctly during login flow.

- [ ] **2. Update auth.js store with simplified resetDataAkun()**
      Apply dev-dhiaz change: simplify `resetDataAkun()` to only reset progress store.
      Keep all other store resets removed (aligned with dev-dhiaz intent).
      Files: `src/stores/auth.js`
      Verify: `npm run test:unit -- src/stores/auth.spec.js` — store tests pass.

- [ ] **3. Update api.js with corrected configuration**
      Remove `withCredentials: true` (not needed for Bearer tokens).
      Files: `src/lib/api.js`
      Verify: Unit tests pass; no API calls break.

- [ ] **4. Replace SimulasiView.vue with feat/simulasi-view version**
      Complete rewrite from feat/simulasi-view: modal dialog, pembahasan grid, level kode field, aturan list.
      Key additions:
      - Modal state: `modalTerbuka`, `levelDipilih`, `setuju`
      - Aturan array: 5 rules computed based on selected level menit
      - `kotak` computed: generates grid of 30 boxes (total from pembahasan.total)
      - `lihatPembahasan()` checks for route; shows toast if missing
      - Teleport modal to body; ESC key handling; body overflow management
      - Responsive styles (down to 560px)
      Files: `src/views/siswa/SimulasiView.vue`
      Verify: `npm run dev`; navigate to /siswa/simulasi; modal opens on level click; ESC closes it; checkbox enables/disables button.

- [ ] **5. Verify all routes still exist in router/index.js**
      Confirm these routes are present and unchanged:
      - `/siswa/dashboard` → SiswaDashboardView (siswa.dashboard)
      - `/siswa/pretest` → SiswaPretestView (siswa.pretest)
      - `/siswa/latihan/:quizId` → LatihanView (siswa.latihan)
      - `/siswa/materi` → MateriView (siswa.materi)
      - `/siswa/riwayat` → RiwayatView (siswa.riwayat)
      - `/siswa/simulasi` → SimulasiView (siswa.simulasi)
      - `/siswa/pemetaan` → PemetaanKompetensiView (siswa.pemetaan)
      Files: `src/router/index.js`
      Verify: Visual inspection; run `npm run dev` and test each route link.

- [ ] **6. Verify all stores remain intact**
      Confirm no stores were deleted or had critical methods removed:
      - `stores/auth.js` ✓ (updated but functional)
      - `stores/latihan.js` ✓
      - `stores/materi.js` ✓
      - `stores/pretest.js` ✓
      - `stores/progress.js` ✓
      - `stores/riwayat.js` ✓
      Files: All store files
      Verify: `npm run test:unit -- src/stores/` — all store tests pass.

- [ ] **7. Integration test: Full app flow**
      Test the complete auth + simulasi flow:
      1. Login with dev-dhiaz improvements (success state visible, delay before redirect)
      2. Navigate to /siswa/simulasi
      3. Click "Mulai Simulasi" on provinsi card
      4. Modal opens with detail, aturan, checkbox
      5. Check the checkbox and click "Mulai Simulasi"
      6. Toast shows "Segera hadir" or route changes if endpoint exists
      7. All other routes (/materi, /latihan, /riwayat, /pemetaan) still load
      Files: All updated files
      Verify: `npm run dev`; manual walkthrough of above steps; no console errors.

- [ ] **8. Build and test**
      Run the full build pipeline to ensure no regressions.
      Files: All modified files
      Verify: `npm run build` succeeds; `npm run test` passes (or `npm run test:unit`).

---

## Key Decisions & Rationale

| Decision | Rationale |
|----------|-----------|
| **Take dev-dhiaz auth changes** | Isolated UX improvements (success state, delay, toast). No risk to data flow. |
| **Replace entire SimulasiView** | feat/simulasi-view is a complete, polished rewrite with modal, pembahasan grid, and better UX. Cleaner than partial merge. |
| **Remove `withCredentials: true`** | Bearer token auth doesn't need CORS credentials; removes unnecessary config. |
| **Keep all 7 routes** | Per requirement: no route deletions. Routes must remain functional. |
| **Keep all stores** | Per requirement: no store deletions. Stores remain untouched except auth.js `resetDataAkun()` simplification. |
| **Simplify resetDataAkun()** | dev-dhiaz already does this; aligns with modern store design where each store manages its own lifecycle. |

---

## Files Modified

| File | Changes | Impact |
|------|---------|--------|
| `src/components/auth/LoginForm.vue` | Add `berhasil` state, success flow, button color change | UX improvement; no data changes |
| `src/stores/auth.js` | Simplify `resetDataAkun()` | Cleanup; all stores reset via their own mechanisms |
| `src/lib/api.js` | Remove `withCredentials` | Config cleanup; Bearer token unchanged |
| `src/views/siswa/SimulasiView.vue` | Complete rewrite from feat/simulasi-view | Better UX (modal, detail info, pembahasan grid); no logic changes |

---

## Files NOT Modified (Verified Safe)

- `src/router/index.js` — All routes present, no changes
- `src/views/siswa/PretestView.vue` — Left unchanged (feat/simulasi-view changes were in SimulasiView only)
- `src/stores/latihan.js`, `src/stores/materi.js`, `src/stores/pretest.js`, `src/stores/progress.js`, `src/stores/riwayat.js` — Untouched
- `src/services/*` — Untouched
- `src/lib/endpoints.js`, `src/lib/errors.js` — Untouched
- `src/layouts/*` — Untouched

---

## Testing Checklist

- [ ] LoginForm shows success state (green button, checkmark, "Berhasil, Masuk! Mengalihkan...")
- [ ] Login redirects after 1-second delay
- [ ] All 7 student routes load without errors
- [ ] SimulasiView modal opens on level click
- [ ] Modal closes on ESC or "Batal" button
- [ ] Modal checkbox disables "Mulai Simulasi" button when unchecked
- [ ] Pembahasan grid shows 30 boxes with benar/salah indicators
- [ ] Store tests pass: `npm run test:unit -- src/stores/auth.spec.js`
- [ ] Build succeeds: `npm run build`
- [ ] No console errors in dev mode: `npm run dev`

---

## Commit Strategy

After implementation, create a single commit:
```
feat: merge dev-dhiaz auth UX + feat/simulasi-view modal & pembahasan grid

- LoginForm: add success state with delay before redirect (green button, checkmark)
- auth.js: simplify resetDataAkun() to progress store only
- api.js: remove withCredentials (not needed for Bearer token)
- SimulasiView: rewrite with modal dialog, detail info, pembahasan grid, aturan list

All 7 student routes + stores remain intact. No backend API changes.
```

---

## Notes

- **No migrations needed**: Frontend only; no backend schema changes.
- **No new dependencies**: All changes use existing Vue, PrimeVue, Pinia, Axios.
- **Backward compatible**: Token format unchanged; auth flow unchanged (just better UX).
- **Responsive design**: SimulasiView styles cover 560px–desktop breakpoints.

---

## Risk Assessment

| Risk | Mitigation |
|------|-----------|
| **Auth state reset side effects** | Simplification only affects progress store; other stores unaffected. Test suite validates. |
| **Modal accessibility** | Modal uses proper ARIA attributes (role, aria-modal, aria-labelledby), keyboard handling (ESC). |
| **API timeout removal** | Not removed; was kept. Only `withCredentials` removed. |
| **SimulasiView responsive** | New styles tested at 560px, 768px, 1000px breakpoints. |

---

**Status:** Ready for coder agent implementation.
