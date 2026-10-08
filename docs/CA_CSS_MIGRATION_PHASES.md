# Color Admin CSS Migration — new-scoring

**Prinsip:** Color Admin Ultimate = 100% desain (CSS/JS UI).  
**new-scoring** = logic saja (CI4, API, DataTables data, permission, bisnis).

Custom CSS yang boleh tersisa: **thin override** untuk bug/layout shell saja — bukan redesign.

---

## Temuan audit (baseline)

| Sumber | Ukuran | Masalah |
|--------|--------|---------|
| `app.css` | ~6583 baris, **430× `!important`**, 313× `font-size` | Menimpa hampir semua komponen CA |
| `ca-widgets.css` | ~384 baris | Sebagian memperbaiki, sebagian masih override |
| `access-phase5.css` | ~496 baris | Typography/spacing custom |
| `shell-phase1.css` | ~177 baris | Header/search — OK tipis |
| Phase 3–9 CSS | kecil | Relatif aman |

### Root cause visual card-header-btn (screenshot)

CA (`app.min.css`):

```css
.btn.btn-icon.btn-xs { width:18px; height:18px; font-size:8px; border-radius:100px }
.card-header-btn { gap:.35rem; margin-top:-.8rem; margin-bottom:-.8rem }
```

new-scoring `app.css` (load **setelah** CA) menimpa:

```css
.btn {
  min-height: 32px;          /* ← merusak 18px */
  padding: 6px 12px;
  border-radius: var(--radius); /* 10px → kotak bulat, bukan lingkaran */
  font-size: var(--font-size-sm);
}
.btn i { font-size: 13px; }  /* ← merusak icon 8px */
--bs-border-radius / --bs-btn-border-radius: 10px; /* CA = 8px */
--bs-card-border-radius: 12px; /* CA = 8px */
```

---

## Fase migrasi

### Phase 0 — Inventory & gate (done)
- [x] Inventory file CSS + hitungan override
- [x] Identifikasi root cause widget buttons
- [x] Dokumen fase ini

### Phase 1 — Stop fighting CA components (CRITICAL)
**Scope:** Tombol, card header widget, token radius/spacing yang dipakai CA.

**Perbaikan:**
1. Hapus / scope-kan global `.btn`, `.btn i`, warna btn brand di `app.css` agar tidak apply di `#content` / shell CA
2. Kembalikan token `--bs-border-radius`, `--bs-btn-border-radius`, `--bs-card-border-radius` ke nilai CA (8px / default CA)
3. Pastikan `.btn-icon.btn-xs` = 18×18 circle tanpa `min-height`
4. `ca-widgets.css` hanya patch yang perlu (bukan re-size)

**Uji lulus jika:**
- [ ] Computed: `.card-header-btn .btn-icon.btn-xs` → width/height **18px**, border-radius **≥50%**/100px, font-size **8px**
- [ ] Expand = abu muda + ikon gelap; success/warning/danger = warna CA
- [ ] Title `.card-header-title` = **0.875rem / 700**
- [ ] Screenshot sidebar cards ≈ CA ui_widget_boxes

### Phase 2 — Design tokens & typography
**Scope:** `:root` di `app.css` tidak menimpa CA.

**Perbaikan:**
1. Hapus override `--bs-body-font-*` yang bertentangan; biarkan CA (Noto Sans, 0.875rem)
2. Hapus force `font-family` massal ke setiap komponen
3. Heading/body di `#content` ikut CA

**Uji lulus jika:**
- [x] Static: `app.css` tidak redeclare `--bs-body-*` / `--bs-font-sans-serif`
- [x] Static: alias `--font-family-base` / `--body-bg` → token CA
- [x] Static: `#app .form-control` pakai `var(--bs-body-font-size)` + `min-height: unset`
- [x] Static: mass `font-family` / heading `#0c2b6b` dihapus
- [ ] Browser (user): body ≈ 14px Noto Sans setelah hard refresh

### Phase 3 — Quarantine legacy `app.css` chrome
**Scope:** Layout lama (topbar/sidebar custom, product-card mockup, dash-stat custom, dll.) tidak boleh apply di shell CA.

**Perbaikan:**
1. `html.ca-ui` di shell Color Admin
2. Sidebar-island + topbar-pill → `app-legacy.css` (**tidak di-load**)
3. `.card` / `.table` / `.alert` / `.badge` legacy di-scope `html:not(.ca-ui)`
4. `ca-phase3-quarantine.css` — reset min-height + KPI solid colors

**Uji lulus jika:**
- [x] Static 12/12: legacy not linked, card/table/alert scoped, quarantine loaded
- [x] `app-legacy.css` ~869 baris archived (not in assets.php)
- [ ] Browser (user): hard refresh — KPI solid, btn tanpa min-height 32px

### Phase 4 — Page CSS tipis (Master / Access / Scoring)
**Scope:** `master-phase4`, `access-phase5`, `scoring-phase6`, `ca-widgets` filter/table.

**Perbaikan:**
1. `master-phase4.css` → ~41 baris (hooks saja; no card-header restyle)
2. `access-phase5.css` → domain UI only; hapus header/btn + DT height 31px
3. `scoring-phase6.css` → token font CA
4. `ca-widgets` DataTables/filter → `--bs-body-font-size`, CA card spacer

**Uji lulus jika:**
- [x] Static 14/14
- [ ] Browser (user): Products / Users / Parameters ≈ CA table + form-elements

### Phase 5 — Transactions / Approvals / Reports / Dashboard
**Scope:** phase7–9 + `dash-phase3` + home markup.

**Perbaikan:**
1. Legacy `.dash-*` (~377 baris) → `app-legacy.css` (not loaded)
2. `reports-phase9.css` thin (scroll + JSON only)
3. `transactions-phase7.css` token CA
4. Home: empty state body size, no `font-size:11px`, Link Terkait + widget btn

**Uji lulus jika:**
- [x] Static 7/7 (+ prior checks)
- [ ] Browser (user): Home / Approvals / Reports ≈ CA cards

### Phase 6 — Auth
**Scope:** `auth-phase2.css` + login/MFA views.

**Perbaikan:**
1. Legacy login/MFA di `app.css` (~398 baris) → `app-legacy.css` (not loaded)
2. Auth pages: `html.ca-ui` + stack CA only (`assets_auth.php`, no `app.css`)
3. `auth-phase2.css`: token tipografi/radius CA; keep brand media + MFA overlay

**Uji lulus jika:**
- [x] Static 6/6
- [ ] Browser (user): Login/MFA Noto Sans, form CA, background brand tetap

### Phase 7 — Cleanup & gate CI
**Perbaikan:**
1. Hapus dead CSS + archive sisa mockup di `app.css` (product-modal, btn-modal-*, module-banner, …)
2. **Gap audit (ketemu setelah Phase 6):** scan wajib untuk selector `#id` / `.btn-*-gradient` / `min-height:32px` yang masih aktif di bawah `html.ca-ui`
3. Checklist regresi visual per halaman (Produk = Debitur header actions)
4. Gate: larangan redesign baru di `app.css` kecuali `html:not(.ca-ui)`

**Sudah ditutup (gap fix 2026-10-05):**
- [x] `#btnTambahProdukBaru` gradient override
- [x] `.btn-primary-gradient` / `.btn-danger-gradient` → `html:not(.ca-ui)`
- [x] `#filterSearch` height/padding force → `html:not(.ca-ui)`

**Masih backlog Phase 7:**
- [ ] `.btn-modal-submit` / `.btn-modal-cancel` (modal produk/debitur)
- [ ] `.product-modal-*` mockup styles (~besar di `app.css`)
- [ ] `.module-banner-*` / premium mockup blocks
- [ ] Scan ulang `#btn*` di seluruh CSS loaded

---

## Aturan kontribusi setelah migrasi

1. **Jangan** tambah `font-size` / `border-radius` / warna btn di custom CSS kecuali bugfix
2. Markup baru = copy dari demo CA (ui_widget_boxes, form-elements, table-basic)
3. Logic JS tetap di `public/js/` + Controllers — bukan di CSS

---

## Hasil uji (diisi per fase)

| Phase | Tanggal | Status | Catatan uji |
|-------|---------|--------|-------------|
| 0 | 2026-10-05 | PASS | Inventory + root cause `.btn min-height:32px` |
| 1 | 2026-10-05 | PASS (static 8/8) | Hapus redesign `.btn`; token radius 8px; hard-refresh visual QA masih perlu user |
| 2 | 2026-10-05 | PASS (static 13/13) | Hapus force tipografi; form 0.875rem; body/heading/link ikut token CA |
| 3 | 2026-10-05 | PASS (static 12/12) | `ca-ui` + app-legacy unlinked + card/table/alert scoped + quarantine CSS |
| 4 | 2026-10-05 | PASS (static 14/14) | master/access/scoring thin; DT/filter pakai token CA |
| 5 | 2026-10-05 | PASS (static 7/7) | dash→legacy; reports/tx thin; home markup CA |
| 6 | 2026-10-05 | PASS (static 6/6) | auth→legacy; login/mfa ca-ui; auth-phase2 tokens CA |
| 7 | — | PENDING | |
