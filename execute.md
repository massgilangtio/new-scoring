Perbaiki dan refactor menu konfigurasi scoring dengan logic berikut:

1. Rename menu “Konfigurasi Produk” menjadi “Konfigurasi Parameter”.

Form input Nama Parameter (contoh: Status Kawin).
Sediakan Sub Parameter multi-row/dynamic yang dapat ditambah.
Field: Kode, Deskripsi, Bobot, Nilai, Jumlah.
Jumlah otomatis dihitung: Bobot × Nilai dan tidak editable.
Contoh: 01 | Belum Menikah | 5 | 3 | 15.
Tambahkan tombol Simpan.

2. Tambahkan menu “Mapping Parameter & Produk”.

Pilih Kode Produk (contoh: 0526 - KMG ONLINE).
Pilih Parameter secara multi-select.
Setelah parameter dipilih, tampilkan seluruh sub parameter: Kode, Deskripsi, Bobot, Nilai, Jumlah.
Field Bobot, Nilai, dan Jumlah editable pada tahap mapping.
Input Nama Versi.
Upload Lampiran/Dokumen.
Tombol Simpan.

3. Tambahkan menu “Scoring Kredit”.

Pilih CIS ID/Debitur, lalu tampilkan data debitur yang dapat diedit.
Pilih Kode Produk yang sudah memiliki mapping parameter.
Tampilkan parameter hasil mapping beserta Kode dan Deskripsi.
Tombol Simpan.
Setelah simpan, tampilkan warning/confirmation: “Apakah scoring kredit ini akan dikirim ke Supervisi?”
Jika Ya, tampilkan pilihan Supervisi / Pimpinan Unit (Pimunit).
Jika Tidak, simpan tanpa mengirim approval.

Catatan: gunakan UI existing dan style aplikasi yang sudah ada, pertahankan konsistensi Bootstrap 5, responsive, dynamic row, validation, calculation otomatis, dan jangan mengubah API/backend yang sudah berjalan kecuali memang diperlukan.
