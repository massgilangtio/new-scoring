# Menu Audit UAT

## Temuan ringkas

- **Pengajuan Scoring** (`/scoring/credit`) menulis `credit_scorings`; sebelum bridge, **Daftar Scoring / Approval / Laporan** membaca `scoring_transactions` (kosong / putus). Bridge UAT sudah ditambahkan agar kirim-ke-supervisi membuat transaksi paralel.
- Menu **Approval** hanya muncul untuk role dengan `scoring.approve` / `scoring.assign` (bukan reviewer).
- Route legacy `/scoring/products`, `/scoring/versions/*`, `/transactions/new` (redirect) masih ada di Routes tapi tidak di sidebar aktif reviewer.

## Menu terlihat — reviewer

- Dashboard → `http://127.0.0.1:8080/`
- Pengajuan Scoring → `http://127.0.0.1:8080/scoring/credit`
- Daftar Scoring → `http://127.0.0.1:8080/transactions`
- Laporan Scoring → `http://127.0.0.1:8080/reports/scoring`
- Notifikasi → `http://127.0.0.1:8080/notifications`

## Menu terlihat — approver

- Dashboard → `http://127.0.0.1:8080/`
- Menunggu Persetujuan → `http://127.0.0.1:8080/approvals`
- Laporan Scoring → `http://127.0.0.1:8080/reports/scoring`
- Notifikasi → `http://127.0.0.1:8080/notifications`

## Menu terlihat — admin

- Dashboard → `http://127.0.0.1:8080/`
- Pengajuan Scoring → `http://127.0.0.1:8080/scoring/credit`
- Daftar Scoring → `http://127.0.0.1:8080/transactions`
- Menunggu Persetujuan → `http://127.0.0.1:8080/approvals`
- Debitur → `http://127.0.0.1:8080/master/debtors`
- Produk → `http://127.0.0.1:8080/master/products`
- Cabang → `http://127.0.0.1:8080/master/branches`
- Konfigurasi Parameter → `http://127.0.0.1:8080/scoring/parameters`
- Mapping Produk → `http://127.0.0.1:8080/scoring/mapping`
- Laporan Scoring → `http://127.0.0.1:8080/reports/scoring`
- Riwayat Debitur → `http://127.0.0.1:8080/reports/debtors`
- Riwayat Produk → `http://127.0.0.1:8080/reports/products`
- Perubahan Parameter → `http://127.0.0.1:8080/reports/changes`
- Audit Trail → `http://127.0.0.1:8080/audit`
- User → `http://127.0.0.1:8080/access/users`
- Kelompok Jabatan → `http://127.0.0.1:8080/access/job-groups`
- Role → `http://127.0.0.1:8080/access/roles`
- Permission → `http://127.0.0.1:8080/access/permissions`
- Notifikasi → `http://127.0.0.1:8080/notifications`

## Hasil crawl

- [OK] (reviewer) Dashboard `http://127.0.0.1:8080/` Dasbor — New Scoring Credit System
- [BROKEN] (reviewer) Pengajuan Scoring `http://127.0.0.1:8080/scoring/credit` Scoring Kredit — New Scoring Credit System
- [OK] (reviewer) Daftar Scoring `http://127.0.0.1:8080/transactions` Pengajuan Scoring — New Scoring Credit System
- [OK] (reviewer) Laporan Scoring `http://127.0.0.1:8080/reports/scoring` Laporan Scoring — New Scoring Credit System
- [OK] (reviewer) Notifikasi `http://127.0.0.1:8080/notifications` Notifikasi — New Scoring Credit System
- [OK] (approver) Dashboard `http://127.0.0.1:8080/` Dasbor — New Scoring Credit System
- [OK] (approver) Menunggu Persetujuan `http://127.0.0.1:8080/approvals` Approval — New Scoring Credit System
- [OK] (approver) Laporan Scoring `http://127.0.0.1:8080/reports/scoring` Laporan Scoring — New Scoring Credit System
- [OK] (approver) Notifikasi `http://127.0.0.1:8080/notifications` Notifikasi — New Scoring Credit System
- [EMPTY?] (admin) Dashboard `http://127.0.0.1:8080/` Dasbor — New Scoring Credit System
- [BROKEN] (admin) Pengajuan Scoring `http://127.0.0.1:8080/scoring/credit` Scoring Kredit — New Scoring Credit System
- [OK] (admin) Daftar Scoring `http://127.0.0.1:8080/transactions` Pengajuan Scoring — New Scoring Credit System
- [OK] (admin) Menunggu Persetujuan `http://127.0.0.1:8080/approvals` Approval — New Scoring Credit System
- [BROKEN] (admin) Debitur `http://127.0.0.1:8080/master/debtors` Master Debitur — New Scoring Credit System
- [BROKEN] (admin) Produk `http://127.0.0.1:8080/master/products` Master Produk — New Scoring Credit System
- [OK] (admin) Cabang `http://127.0.0.1:8080/master/branches` Master Cabang — New Scoring Credit System
- [OK] (admin) Konfigurasi Parameter `http://127.0.0.1:8080/scoring/parameters` Konfigurasi Parameter — New Scoring Credit System
- [OK] (admin) Mapping Produk `http://127.0.0.1:8080/scoring/mapping` Mapping Produk — New Scoring Credit System
- [OK] (admin) Laporan Scoring `http://127.0.0.1:8080/reports/scoring` Laporan Scoring — New Scoring Credit System
- [BROKEN] (admin) Riwayat Debitur `http://127.0.0.1:8080/reports/debtors` Riwayat Debitur — New Scoring Credit System
- [BROKEN] (admin) Riwayat Produk `http://127.0.0.1:8080/reports/products` Riwayat Produk — New Scoring Credit System
- [OK] (admin) Perubahan Parameter `http://127.0.0.1:8080/reports/changes` Perubahan Parameter — New Scoring Credit System
- [OK] (admin) Audit Trail `http://127.0.0.1:8080/audit` Jejak Audit — New Scoring Credit System
- [OK] (admin) User `http://127.0.0.1:8080/access/users` Manajemen User — New Scoring Credit System
- [OK] (admin) Kelompok Jabatan `http://127.0.0.1:8080/access/job-groups` Kelompok Jabatan — New Scoring Credit System
- [OK] (admin) Role `http://127.0.0.1:8080/access/roles` Manajemen Role — New Scoring Credit System
- [OK] (admin) Permission `http://127.0.0.1:8080/access/permissions` Hak Akses / Module — New Scoring Credit System
- [EMPTY?] (admin) Notifikasi `http://127.0.0.1:8080/notifications` Notifikasi — New Scoring Credit System
