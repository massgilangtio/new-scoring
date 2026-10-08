# MASTER PROMPT — UI/TEMPLATE MIGRATION

## new-credit-score → new-scoring

## CodeIgniter 4 + Color Admin Ultimate

## 1. TUJUAN PROJECT

Saya sedang melakukan migrasi tampilan aplikasi dari:

`/new-credit-score`

ke:

`/new-scoring`

Aplikasi existing menggunakan CodeIgniter 4.

Migrasi ini adalah **MIGRASI UI / TEMPLATE SAJA**.

Tidak boleh melakukan perubahan terhadap pola fungsional aplikasi existing.

Tujuan utama:

> Memindahkan tampilan dan template `/new-credit-score` agar mengikuti desain, struktur visual, component pattern, dan styling dari `/ColorAdminUltimate`, kemudian menerapkannya secara bertahap ke `/new-scoring`.

---

# 2. SOURCE OF TRUTH

Terdapat 2 sumber utama:

### A. `/new-credit-score`

Berfungsi sebagai:

**SOURCE OF TRUTH UNTUK FUNCTIONALITY**

Gunakan folder ini untuk memahami:

- route
- controller
- model
- service
- API integration
- database interaction
- business logic
- validation
- AJAX
- JavaScript behavior
- form submission
- workflow
- scoring logic
- parameter logic
- mapping
- permission/role
- existing functional behavior

Semua behavior tersebut harus dipertahankan.

### B. `/ColorAdminUltimate`

Berfungsi sebagai:

**SOURCE OF TRUTH UNTUK UI / TEMPLATE**

Gunakan folder ini untuk menentukan:

- layout
- navbar
- sidebar
- content structure
- card
- form
- input
- select
- table
- modal
- tab
- button
- badge
- alert
- breadcrumb
- typography
- spacing
- icon
- color
- responsive behavior
- component pattern
- visual hierarchy

Jika terdapat perbedaan antara UI existing dan Color Admin, gunakan Color Admin sebagai acuan tampilan.

Namun jangan mengubah functionality existing hanya karena struktur UI berbeda.

---

# 3. TARGET

Target hasil migrasi adalah:

`/new-scoring`

Repository:

`https://github.com/massgilangtio/new-scoring.git`

Semua hasil migrasi harus diterapkan ke repository tersebut.

---

# 4. ATURAN MUTLAK

## RULE 1 — JANGAN UBAH FUNCTIONALITY

Jangan mengubah:

- business logic
- scoring calculation
- database query
- API contract
- API endpoint
- controller logic yang berkaitan dengan business process
- validation logic
- route behavior
- permission
- role
- workflow
- data mapping
- parameter calculation
- AJAX functionality
- submit behavior

kecuali perubahan tersebut memang benar-benar diperlukan untuk menghubungkan UI baru dengan functionality existing.

Jika perubahan terhadap functionality tidak diperlukan, JANGAN dilakukan.

---

## RULE 2 — UI BOLEH DIROMBAK TOTAL

UI boleh diubah secara signifikan.

Contohnya:

- layout lama → layout Color Admin
- table lama → table style Color Admin
- form lama → form style Color Admin
- modal lama → modal style Color Admin
- sidebar lama → sidebar Color Admin
- navbar lama → navbar Color Admin
- card lama → card Color Admin
- button lama → button Color Admin
- typography lama → typography Color Admin
- spacing lama → spacing Color Admin

Selama functionality tetap sama.

---

# 5. MIGRASI HARUS BERTAHAP

Jangan melakukan migrasi seluruh aplikasi sekaligus.

Setiap permintaan migrasi harus diperlakukan sebagai satu tahap/work unit.

Untuk setiap tahap:

1. Pelajari halaman existing.
2. Pelajari functionality halaman tersebut.
3. Cari component/layout yang sesuai di `/ColorAdminUltimate`.
4. Tentukan mapping existing UI → Color Admin UI.
5. Implementasikan UI baru.
6. Pertahankan seluruh functionality.
7. Pastikan asset/path benar.
8. Test.
9. Review perubahan.
10. Commit.
11. Push ke repository.

Jangan mengerjakan module lain yang belum diminta.

---

# 6. ATURAN ASSET / GAMBAR

Saya akan memberikan asset/gambar baru ke:

`D:\Project-Migrasi-Scoring\images`

Contoh instruksi dari saya:

"Tolong terapkan menjadi images/xxxxx.png"

Artinya:

1. Cari file source:
   `D:\Project-Migrasi-Scoring\images\xxxxx.png`

2. Gunakan file tersebut sebagai asset UI.

3. Jika perlu, lakukan konversi/optimasi format agar sesuai dengan struktur `/new-scoring`.

4. Simpan pada lokasi asset yang sesuai di `/new-scoring`.

5. Gunakan path asset yang benar di aplikasi.

6. Jangan menghapus atau mengubah source asset di:
   `D:\Project-Migrasi-Scoring\images`

kecuali saya secara eksplisit meminta.

---

# 7. JANGAN MENGARANG COMPONENT

Sebelum membuat component baru, selalu periksa:

`/ColorAdminUltimate`

Apakah component tersebut sudah tersedia?

Jika tersedia:

> gunakan/adaptasi component tersebut.

Jika tidak tersedia:

> buat component baru dengan visual language yang konsisten dengan Color Admin.

Jangan membuat desain yang keluar dari visual language Color Admin tanpa alasan.

---

# 8. PERTAHANKAN EXISTING FUNCTIONAL SELECTOR

Perhatikan selector yang digunakan oleh JavaScript existing.

Contoh:

- id
- class
- name
- data attribute
- form selector
- table selector
- modal selector

Jangan sembarangan mengganti selector jika selector tersebut digunakan oleh JavaScript/AJAX existing.

Jika UI baru membutuhkan perubahan selector:

1. identifikasi dependency-nya;
2. pertahankan compatibility;
3. ubah hanya jika benar-benar diperlukan;
4. pastikan functionality tetap berjalan.

---

# 9. JAVASCRIPT

JavaScript existing adalah bagian penting dari functionality.

Jangan melakukan refactor JavaScript hanya untuk alasan clean code jika tidak diperlukan untuk migrasi UI.

Prioritas:

> Existing JS behavior tetap berjalan dengan UI baru.

Jika terdapat:

- DataTables
- Select2
- SweetAlert
- AJAX
- modal
- dependent dropdown
- inputmask
- event handler
- validation
- dynamic form
- upload handler

pastikan semuanya tetap berfungsi.

---

# 10. CODEIGNITER 4

Aplikasi menggunakan CodeIgniter 4.

Pertahankan struktur dan pattern CodeIgniter 4 yang sudah digunakan.

Jangan melakukan migrasi framework.

Jangan mengganti:

- CodeIgniter 4 menjadi framework lain
- PHP architecture
- backend API architecture
- database architecture

Fokus hanya pada presentation layer/template/UI.

---

# 11. RESPONSIVE DESIGN

UI hasil migrasi harus responsive.

Prioritas breakpoint:

- desktop
- laptop
- tablet
- mobile

Gunakan responsive pattern dari `/ColorAdminUltimate` sebagai referensi.

Jangan membuat desktop-only UI jika Color Admin memiliki pattern responsive yang dapat digunakan.

---

# 12. VISUAL CONSISTENCY

Semua halaman hasil migrasi harus terasa sebagai satu aplikasi.

Perhatikan konsistensi:

- typography
- heading
- button
- form
- input
- table
- card
- modal
- alert
- badge
- icon
- spacing
- border radius
- shadow
- color
- sidebar
- navbar
- breadcrumb

Jangan membuat setiap halaman mempunyai gaya berbeda.

---

# 13. SEBELUM MENGUBAH FILE

Sebelum melakukan perubahan:

1. Baca struktur project.
2. Identifikasi file yang berhubungan dengan halaman.
3. Identifikasi controller.
4. Identifikasi view.
5. Identifikasi JavaScript.
6. Identifikasi CSS.
7. Identifikasi asset.
8. Identifikasi dependency terhadap component tersebut.

Jangan langsung overwrite file tanpa memahami dependency-nya.

---

# 14. SETIAP PERUBAHAN HARUS TERUKUR

Jangan melakukan perubahan massal yang tidak diminta.

Jika saya meminta:

"Migrasikan halaman X"

Maka fokus pada halaman X dan dependency UI yang diperlukan.

Jangan otomatis:

- redesign halaman lain
- refactor seluruh CSS
- rename seluruh class
- migrasi seluruh JavaScript
- mengubah database
- mengubah API
- mengubah workflow

---

# 15. GIT WORKFLOW — WAJIB

Setiap perubahan yang sudah diterapkan wajib:

1. Check git status.
2. Review diff.
3. Pastikan tidak ada perubahan tidak sengaja.
4. Test perubahan.
5. Commit.
6. Push.

Repository:

`https://github.com/massgilangtio/new-scoring.git`

Gunakan commit message yang jelas dan menggambarkan perubahan.

Contoh:

`feat(ui): migrate login page to Color Admin`

`feat(ui): migrate scoring parameter page`

`refactor(ui): adapt scoring form to Color Admin`

`fix(ui): preserve Select2 behavior after template migration`

---

# 16. JANGAN COMMIT FILE YANG TIDAK BERKAITAN

Sebelum commit:

Periksa:

`git status`

dan:

`git diff`

Jangan commit:

- temporary files
- debug files
- local configuration
- credentials
- secrets
- unrelated changes
- generated files yang tidak diperlukan

Jika terdapat perubahan unrelated, jangan ikut commit.

---

# 17. PUSH POLICY

Setelah perubahan valid dan sudah di-test:

Commit lalu push ke repository:

`https://github.com/massgilangtio/new-scoring.git`

Jangan hanya melakukan commit lokal.

Target workflow:

`Change → Test → Review → Commit → Push`

---

# 18. JIKA TERJADI ERROR

Jika setelah migrasi muncul error:

1. Identifikasi apakah error berasal dari UI migration.
2. Jangan langsung mengubah business logic.
3. Cari root cause.
4. Perbaiki dengan perubahan seminimal mungkin.
5. Pastikan functionality existing tetap sama.
6. Test ulang.
7. Commit perubahan perbaikannya.
8. Push.

---

# 19. PRIORITAS KEPUTUSAN

Jika terjadi konflik antara beberapa requirement, gunakan prioritas:

1. Existing functionality `/new-credit-score`
2. UI/Template `/ColorAdminUltimate`
3. Responsive behavior
4. Visual consistency
5. Clean implementation

Dengan prinsip:

> FUNCTIONALITY FIRST, VISUAL MIGRATION SECOND.

Namun secara visual, hasil akhir harus semaksimal mungkin mengikuti Color Admin.

---

# 20. MODE KERJA

Jangan melakukan perubahan apa pun sebelum saya memberikan instruksi migrasi.

Untuk tahap awal, cukup:

- memahami struktur
- memahami relationship antar folder
- memahami source of truth
- memahami target
- memahami asset workflow
- memahami Git workflow

Jangan membuat perubahan hanya karena menemukan sesuatu yang menurutmu perlu diperbaiki.

Tunggu instruksi saya.

---

# 21. FORMAT INSTRUKSI DARI USER

Saya dapat memberikan instruksi sederhana seperti:

"Migrasikan halaman login."

atau:

"Redesign halaman parameter."

atau:

"Terapkan images/login-bg.png."

atau:

"Tolong terapkan menjadi images/xxxxx.png."

Instruksi tersebut harus dipahami dalam konteks master rule ini.

---

# 22. DEFINISI SELESAI

Sebuah tahap migrasi dianggap selesai apabila:

- UI mengikuti Color Admin
- functionality existing tetap berjalan
- responsive
- asset sudah benar
- tidak ada perubahan business logic yang tidak diperlukan
- tidak ada unrelated changes
- perubahan sudah ditest
- git diff sudah diperiksa
- sudah commit
- sudah push ke repository target

---

# FINAL PRINCIPLE

INGAT:

`/new-credit-score`
= FUNCTIONAL REFERENCE

`/ColorAdminUltimate`
= UI / TEMPLATE SOURCE OF TRUTH

`/new-scoring`
= MIGRATION TARGET

`D:\Project-Migrasi-Scoring\images`
= USER-PROVIDED ASSET SOURCE

Repository:
`https://github.com/massgilangtio/new-scoring.git`

Tujuan akhir:

> Membuat `/new-scoring` memiliki tampilan modern dan konsisten mengikuti `/ColorAdminUltimate`, tetapi seluruh pola functionality, business logic, workflow, API, database interaction, dan behavior dari `/new-credit-score` tetap dipertahankan.
