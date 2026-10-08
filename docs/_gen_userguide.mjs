/**
 * Generate New Scoring User Guide HTML (SMARTLOAN-style sheets).
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT_DIR = 'D:/DEV/04-DOCUMENTATION/SKORING/setup-userguide/result-setup';
const CSS = fs.readFileSync(path.join(OUT_DIR, 'assets/userguide.css'), 'utf8');

const TOTAL = 28; // will set after building pages

function esc(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function pageHead(modul, pageNo) {
  return `<header class="page-head">
      <div class="head-brand">
        <img class="logo-bank" src="assets/logo-banksumut.png" alt="Bank Sumut">
        <span class="sep" aria-hidden="true"></span>
        <img class="logo-nscs" src="assets/logo-horizontal.png?v=20261005" alt="New Scoring Credit System">
      </div>
      <div class="head-meta">
        <span>${esc(modul)}</span>
        <span class="hal-pill">Halaman ${pageNo}</span>
      </div>
    </header>`;
}

function pageFoot(modul, caption, pageNo, total) {
  return `<footer class="page-foot">
      <b>${esc(modul)}</b>
      <span>${esc(caption)}</span>
      <span>Halaman ${pageNo} dari ${total}</span>
    </footer>`;
}

function stepsTable(rows) {
  const body = rows
    .map(
      (r, i) => `<tr>
          <td class="col-no">${i + 1}</td>
          <td>${r.do}</td>
          <td><span class="info">${esc(r.info)}</span></td>
        </tr>`
    )
    .join('\n');
  return `<table>
      <thead>
        <tr>
          <th class="col-no">No</th>
          <th>Yang dilakukan</th>
          <th>Informasi halaman</th>
        </tr>
      </thead>
      <tbody>
        ${body}
      </tbody>
    </table>`;
}

function contentSheet({ id, pageNo, total, modul, kicker, title, lead, shot, figNo, figCaption, rows, hasil }) {
  return `<!-- HALAMAN ${pageNo} -->
<section class="sheet" id="${id}">
  <div class="page">
    ${pageHead(modul, pageNo)}

    <p class="kicker">${esc(kicker)}</p>
    <h2>${esc(title)}</h2>
    <p class="lead">${lead}</p>

    <figure class="figure">
      <img src="shots/${esc(shot)}" alt="${esc(title)}">
      <figcaption><b>Gambar ${esc(figNo)}</b><span>${esc(figCaption)}</span></figcaption>
    </figure>

    ${stepsTable(rows)}
    <div class="hasil"><b>Hasil.</b> ${hasil}</div>

    ${pageFoot(modul, `Gambar ${figNo} · ${title}`, pageNo, total)}
  </div>
</section>
`;
}

function textSheet({ id, pageNo, total, modul, kicker, title, lead, bodyHtml }) {
  return `<!-- HALAMAN ${pageNo} -->
<section class="sheet" id="${id}">
  <div class="page">
    ${pageHead(modul, pageNo)}
    <p class="kicker">${esc(kicker)}</p>
    <h2>${esc(title)}</h2>
    <p class="lead">${lead}</p>
    ${bodyHtml}
    ${pageFoot(modul, title, pageNo, total)}
  </div>
</section>
`;
}

// ---- Page definitions (content pages only; landing/toc/ending added later) ----
const chapters = [];

chapters.push({
  type: 'text',
  id: 'hal-overview',
  modul: 'Pengantar · Role & Alur',
  kicker: 'Pengantar',
  title: 'Role pengguna & alur scoring',
  lead: 'New Scoring Credit System memakai tiga role resmi. Nama di kolom pengguna adalah <strong>nama pegawai</strong>; badge role menampilkan hak akses.',
  bodyHtml: `
    <div class="prep">
      <div class="card">
        <h3>Role resmi</h3>
        <ol>
          <li><b>Administrator</b> — master data, parameter, mapping, user &amp; access, laporan lengkap.</li>
          <li><b>Reviewer</b> — pengajuan scoring, daftar scoring, laporan scoring (tanpa detail skor).</li>
          <li><b>Approver</b> — keputusan approval, lihat skor + detail parameter (view only), laporan.</li>
        </ol>
      </div>
      <div class="card accent">
        <h3>Istilah yang diseragamkan</h3>
        <ul>
          <li>Gunakan <b>Approver</b> (bukan Supervisi / Pimunit / Operator).</li>
          <li>Dropdown penugasan: <b>Nama Pegawai — Role · Cabang</b>.</li>
          <li>Login memakai Username + Password + MFA (Google Authenticator).</li>
        </ul>
      </div>
    </div>
    <div class="alur">
      <h3>Alur utama (end-to-end)</h3>
      <ol style="grid-template-columns: repeat(4, 1fr);">
        <li><span class="n">1</span><b>Reviewer</b><small>Isi scoring &amp; kirim ke Approver</small></li>
        <li><span class="n">2</span><b>Daftar / Laporan</b><small>Pantau status pengajuan</small></li>
        <li><span class="n">3</span><b>Approver</b><small>Tinjau parameter &amp; putuskan</small></li>
        <li><span class="n">4</span><b>Laporan</b><small>Status approved / rejected</small></li>
      </ol>
    </div>
  `,
});

const reviewerSteps = [
  {
    shot: '01-login-reviewer-dashboard.png',
    title: 'Login sebagai Reviewer',
    kicker: 'Modul 1 · Reviewer · Langkah 1',
    lead: 'Masuk dengan akun Reviewer. Setelah password benar, masukkan kode MFA 6 digit dari Google Authenticator.',
    figNo: '1.1',
    figCaption: 'Dashboard Reviewer setelah login berhasil',
    rows: [
      { do: 'Buka aplikasi New Scoring, isi Username dan Password.', info: 'Halaman Login' },
      { do: 'Saat dialog MFA muncul, masukkan 6 digit kode authenticator.', info: 'Verifikasi MFA' },
      { do: 'Pastikan sidebar menampilkan menu Transaksi Scoring &amp; Laporan.', info: 'Dashboard' },
    ],
    hasil: 'Reviewer masuk ke dashboard dan siap membuat pengajuan scoring.',
  },
  {
    shot: '02-scoring-credit-step1.png',
    title: 'Buka Pengajuan Scoring',
    kicker: 'Modul 1 · Reviewer · Langkah 2',
    lead: 'Dari menu <strong>Transaksi Scoring → Pengajuan Scoring</strong>, wizard 4 langkah dibuka. Mulai dari pemilihan debitur.',
    figNo: '1.2',
    figCaption: 'Wizard langkah 1 — Pilih Debitur',
    rows: [
      { do: 'Klik menu Pengajuan Scoring.', info: 'Sidebar Transaksi Scoring' },
      { do: 'Perhatikan navigator: Debitur → Produk → Penilaian → Ringkasan.', info: 'Form wizard' },
    ],
    hasil: 'Formulir pengajuan scoring siap diisi.',
  },
  {
    shot: '03-scoring-debtor-selected.png',
    title: 'Pilih Debitur',
    kicker: 'Modul 1 · Reviewer · Langkah 3',
    lead: 'Cari dan pilih debitur dari master. Profil ditampilkan read-only; ubah data lewat Master Debitur (Administrator).',
    figNo: '1.3',
    figCaption: 'Debitur terpilih — panel profil read-only',
    rows: [
      { do: 'Cari berdasarkan CIS ID, NIK, atau nama pada dropdown.', info: 'Pilih Debitur' },
      { do: 'Periksa NIK, nama, cabang, lalu lanjut.', info: 'Tombol Berikutnya' },
    ],
    hasil: 'Debitur terpasang pada pengajuan; wizard lanjut ke produk.',
  },
  {
    shot: '04-scoring-product-selected.png',
    title: 'Pilih Produk Kredit',
    kicker: 'Modul 1 · Reviewer · Langkah 4',
    lead: 'Pilih produk yang memiliki mapping parameter aktif. Parameter penilaian akan dimuat dari mapping tersebut.',
    figNo: '1.4',
    figCaption: 'Produk dengan mapping aktif dipilih',
    rows: [
      { do: 'Pilih kode/nama produk kredit.', info: 'Dropdown Produk' },
      { do: 'Lanjut ke langkah penilaian.', info: 'Tombol Berikutnya' },
    ],
    hasil: 'Sistem menyiapkan daftar parameter sesuai mapping produk.',
  },
  {
    shot: '05-scoring-parameters-filled.png',
    title: 'Isi Penilaian Parameter',
    kicker: 'Modul 1 · Reviewer · Langkah 5',
    lead: 'Pilih satu opsi untuk setiap parameter. Reviewer tidak melihat bobot/skor di ringkasan bila tidak punya hak view score details.',
    figNo: '1.5',
    figCaption: 'Seluruh parameter telah dipilih',
    rows: [
      { do: 'Centang/pilih radio untuk tiap kelompok parameter.', info: 'Penilaian Scoring' },
      { do: 'Pastikan tidak ada parameter kosong, lalu lanjut.', info: 'Tombol Berikutnya' },
    ],
    hasil: 'Penilaian lengkap; wizard menampilkan ringkasan konfirmasi.',
  },
  {
    shot: '06-scoring-summary.png',
    title: 'Ringkasan &amp; Simpan',
    kicker: 'Modul 1 · Reviewer · Langkah 6',
    lead: 'Periksa ringkasan debitur, produk, dan pilihan parameter. Klik Simpan untuk mengirim atau menyimpan draft.',
    figNo: '1.6',
    figCaption: 'Ringkasan data scoring sebelum simpan',
    rows: [
      { do: 'Tinjau ringkasan penilaian parameter.', info: 'Ringkasan / Konfirmasi' },
      { do: 'Klik Simpan Scoring Kredit.', info: 'Tombol Simpan' },
      { do: 'Pada konfirmasi, pilih Ya, Kirim ke Approver.', info: 'Dialog konfirmasi' },
    ],
    hasil: 'Modal pemilihan Approver muncul.',
  },
  {
    shot: '08-pick-approver-modal.png',
    title: 'Pilih Approver (nama pegawai)',
    kicker: 'Modul 1 · Reviewer · Langkah 7',
    lead: 'Pilih pejabat berdasarkan <strong>nama pegawai</strong>. Format opsi: Nama Pegawai — Role · Cabang. Role resmi: Approver.',
    figNo: '1.7',
    figCaption: 'Modal Pilih Approver',
    rows: [
      { do: 'Buka dropdown Nama Pegawai (Approver).', info: 'Dropdown Approver' },
      { do: 'Pilih pegawai yang bertugas sebagai Approver.', info: 'Nama — Approver · Cabang' },
      { do: 'Klik Kirim ke Approver.', info: 'Tombol kirim' },
    ],
    hasil: 'Pengajuan tersimpan, status submitted, Approver menerima notifikasi.',
  },
  {
    shot: '10-scoring-saved-success.png',
    title: 'Konfirmasi berhasil dikirim',
    kicker: 'Modul 1 · Reviewer · Langkah 8',
    lead: 'Sistem menampilkan nomor scoring dan status setelah pengiriman.',
    figNo: '1.8',
    figCaption: 'Dialog sukses simpan &amp; kirim',
    rows: [
      { do: 'Catat nomor scoring yang dihasilkan.', info: 'Nomor Scoring' },
      { do: 'Tutup dialog untuk kembali ke formulir / daftar.', info: 'Tombol Selesai' },
    ],
    hasil: 'Pengajuan masuk ke Daftar Scoring dan antrian Approver.',
  },
  {
    shot: '11-daftar-scoring.png',
    title: 'Lihat Daftar Scoring',
    kicker: 'Modul 1 · Reviewer · Langkah 9',
    lead: 'Menu <strong>Daftar Scoring</strong> menampilkan pengajuan beserta status (submitted / approved / dll).',
    figNo: '1.9',
    figCaption: 'Daftar pengajuan scoring Reviewer',
    rows: [
      { do: 'Buka Transaksi Scoring → Daftar Scoring.', info: 'Sidebar' },
      { do: 'Cari nomor / debitur bila perlu; buka detail lewat Aksi.', info: 'Tabel + Aksi' },
    ],
    hasil: 'Status pengajuan terpantau dari daftar.',
  },
  {
    shot: '12-report-scoring-reviewer.png',
    title: 'Laporan Scoring (Reviewer)',
    kicker: 'Modul 1 · Reviewer · Langkah 10',
    lead: 'Laporan Scoring menampilkan riwayat pengajuan. Kolom skor/hasil dapat disembunyikan sesuai permission Reviewer.',
    figNo: '1.10',
    figCaption: 'Laporan Scoring sudut pandang Reviewer',
    rows: [
      { do: 'Buka menu Laporan → Laporan Scoring.', info: 'Sidebar Laporan' },
      { do: 'Gunakan filter status / pencarian bila diperlukan.', info: 'Filter Laporan' },
    ],
    hasil: 'Reviewer dapat memantau riwayat tanpa mengubah keputusan approval.',
  },
];

reviewerSteps.forEach((s) => chapters.push({ type: 'shot', modul: 'Modul 1 · Reviewer', ...s }));

const approverSteps = [
  {
    shot: '18-login-approver-dashboard.png',
    title: 'Login sebagai Approver',
    kicker: 'Modul 2 · Approver · Langkah 1',
    lead: 'Masuk dengan akun Approver (nama pegawai + role Approver). Sidebar menampilkan Approval dan Laporan.',
    figNo: '2.1',
    figCaption: 'Dashboard Approver',
    rows: [
      { do: 'Login Username/Password lalu MFA.', info: 'Halaman Login' },
      { do: 'Pastikan menu Menunggu Persetujuan tersedia.', info: 'Sidebar Approval' },
    ],
    hasil: 'Approver siap memproses antrian keputusan.',
  },
  {
    shot: '19-approvals-inbox.png',
    title: 'Inbox Menunggu Persetujuan',
    kicker: 'Modul 2 · Approver · Langkah 2',
    lead: 'Daftar pengajuan yang ditugaskan ke Anda. Buka detail melalui tombol Aksi (dropdown kuning).',
    figNo: '2.2',
    figCaption: 'Inbox approval Approver',
    rows: [
      { do: 'Buka Approval → Menunggu Persetujuan.', info: 'Sidebar' },
      { do: 'Pilih pengajuan, buka detail keputusan.', info: 'Aksi · Detail' },
    ],
    hasil: 'Halaman keputusan approval terbuka.',
  },
  {
    shot: 'approval-parameter-detail.png',
    title: 'Detail skor &amp; parameter (view only)',
    kicker: 'Modul 2 · Approver · Langkah 3',
    lead: 'Approver melihat total skor, hasil LAYAK/TIDAK LAYAK, serta tabel rincian parameter terpilih (bobot, nilai, skor) dalam mode view only.',
    figNo: '2.3',
    figCaption: 'Banner skor + Rincian Parameter Terpilih',
    rows: [
      { do: 'Tinjau skor dan hasil kelayakan.', info: 'Banner skor' },
      { do: 'Periksa tiap baris parameter &amp; pilihan debitur.', info: 'Tabel parameter' },
      { do: 'Data terkunci; tidak dapat diubah dari halaman ini.', info: 'View only' },
    ],
    hasil: 'Approver memiliki informasi lengkap sebelum memutuskan.',
  },
  {
    shot: '21-approval-filled.png',
    title: 'Berikan Keputusan + Catatan',
    kicker: 'Modul 2 · Approver · Langkah 4',
    lead: 'Pilih Setujui / Tolak / Kembalikan, isi catatan wajib, lalu simpan.',
    figNo: '2.4',
    figCaption: 'Form keputusan dengan catatan',
    rows: [
      { do: 'Pilih keputusan (contoh: Setujui).', info: 'Keputusan' },
      { do: 'Isi catatan keputusan (wajib).', info: 'Catatan' },
      { do: 'Klik Simpan Keputusan dan konfirmasi.', info: 'Tombol simpan' },
    ],
    hasil: 'Status transaksi berubah sesuai keputusan; Reviewer mendapat notifikasi.',
  },
  {
    shot: '23-report-scoring-approver.png',
    title: 'Laporan setelah approval',
    kicker: 'Modul 2 · Approver · Langkah 5',
    lead: 'Pada Laporan Scoring, Approver melihat skor, hasil, dan status (mis. approved).',
    figNo: '2.5',
    figCaption: 'Laporan Scoring — status approved terlihat',
    rows: [
      { do: 'Buka Laporan → Laporan Scoring.', info: 'Sidebar' },
      { do: 'Filter atau cari nomor pengajuan yang diputuskan.', info: 'Filter / Cari' },
    ],
    hasil: 'Riwayat keputusan tercatat di laporan.',
  },
];

approverSteps.forEach((s) => chapters.push({ type: 'shot', modul: 'Modul 2 · Approver', ...s }));

const adminSteps = [
  {
    shot: '39-menu-admin-user.png',
    title: 'Manajemen User (Nama Pegawai vs Role)',
    kicker: 'Modul 3 · Administrator · Langkah 1',
    lead: 'Administrator mengelola pengguna. Kolom <strong>Nama Pegawai</strong> berisi nama orang; kolom <strong>Role</strong> hanya Administrator / Approver / Reviewer.',
    figNo: '3.1',
    figCaption: 'Daftar User — nama pegawai terpisah dari role',
    rows: [
      { do: 'Buka User &amp; Access → User.', info: 'Sidebar' },
      { do: 'Saat menambah/edit, isi Nama Pegawai (bukan nama role).', info: 'Form user' },
      { do: 'Pilih kelompok jabatan yang memetakan ke role resmi.', info: 'Kelompok Jabatan' },
    ],
    hasil: 'Identitas pegawai dan role akses tidak tertukar di UI.',
  },
  {
    shot: '33-menu-admin-mapping-produk.png',
    title: 'Mapping Produk &amp; Parameter',
    kicker: 'Modul 3 · Administrator · Langkah 2',
    lead: 'Pastikan produk memiliki mapping parameter aktif agar Reviewer dapat mengajukan scoring.',
    figNo: '3.2',
    figCaption: 'Halaman Mapping Produk',
    rows: [
      { do: 'Buka Parameter Scoring → Mapping Produk.', info: 'Sidebar' },
      { do: 'Aktifkan versi mapping dan lengkapi item parameter.', info: 'Mapping aktif' },
    ],
    hasil: 'Produk siap dipakai di wizard pengajuan.',
  },
  {
    shot: '28-menu-admin-menunggu_persetujuan.png',
    title: 'Penugasan Approver (opsional)',
    kicker: 'Modul 3 · Administrator · Langkah 3',
    lead: 'Bila pengajuan belum ditugaskan, Administrator dengan hak scoring.assign dapat menugaskan Approver dari menu Approval.',
    figNo: '3.3',
    figCaption: 'Menu Menunggu Persetujuan (Administrator)',
    rows: [
      { do: 'Buka Approval → Menunggu Persetujuan.', info: 'Sidebar' },
      { do: 'Pilih Approver: Nama Pegawai — Role.', info: 'Form penugasan' },
    ],
    hasil: 'Pengajuan masuk inbox Approver yang ditunjuk.',
  },
];

adminSteps.forEach((s) => chapters.push({ type: 'shot', modul: 'Modul 3 · Administrator', ...s }));

// Build numbered pages: 1 landing, 2 toc, 3..N-1 content, N ending
const contentCount = chapters.length;
const total = contentCount + 3; // landing + toc + ending

let pageNo = 3;
const contentHtml = chapters
  .map((ch) => {
    const n = pageNo++;
    if (ch.type === 'text') {
      return textSheet({ ...ch, pageNo: n, total });
    }
    return contentSheet({
      id: `hal-${n}`,
      pageNo: n,
      total,
      modul: ch.modul,
      kicker: ch.kicker,
      title: ch.title,
      lead: ch.lead,
      shot: ch.shot,
      figNo: ch.figNo,
      figCaption: ch.figCaption,
      rows: ch.rows,
      hasil: ch.hasil,
    });
  })
  .join('\n');

// TOC rows
const tocItems = [
  { badge: '0', title: 'Pengantar — Role & Alur', small: 'Administrator · Approver · Reviewer', page: 3, cls: 'blue-soft' },
  { badge: '1', title: 'Modul 1 — Reviewer', small: 'Login → Pengajuan → Approver → Daftar → Laporan', page: 4, cls: 'orange' },
  { badge: '2', title: 'Modul 2 — Approver', small: 'Inbox → Detail parameter → Keputusan → Laporan', page: 4 + reviewerSteps.length, cls: '' },
  { badge: '3', title: 'Modul 3 — Administrator', small: 'User, Mapping, Penugasan', page: 4 + reviewerSteps.length + approverSteps.length, cls: 'blue-soft' },
];

const tocHtml = tocItems
  .map(
    (t) => `<a class="toc-row ${t.cls}" href="#hal-${t.page === 3 ? 'overview' : t.page}">
      <div class="toc-badge">${t.badge}</div>
      <div class="toc-text"><b>${esc(t.title)}</b><small>${esc(t.small)}</small></div>
      <div class="toc-dots"></div>
      <div class="toc-page-no">${t.page}</div>
    </a>`
  )
  .join('\n');

const html = `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panduan Pengguna — New Scoring Credit System · Reviewer, Approver, Administrator</title>
<style>
${CSS}
  /* New Scoring: slightly taller screenshots on content sheets */
  .figure img { height: 95mm; }
  .page-head .head-brand img.logo-nscs {
    height: 28px;
    width: auto;
    display: block;
  }
  .page-head .head-brand img.logo-bank {
    height: 22px;
  }
</style>
</head>
<body>

<div class="screenbar">
  <div><b>Panduan Pengguna New Scoring</b> <span>· Modul Reviewer · Approver · Administrator · ${total} halaman</span></div>
  <div class="screenbar-actions">
    <button type="button" class="btn btn-print" onclick="showPrintGuide()">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12 0v4h12v-4m-12 0h12"/></svg>
      Cetak / Simpan PDF
    </button>
  </div>
</div>

<div id="print-modal" style="display:none; position:fixed; inset:0; background:rgba(11,47,107,0.65); z-index:10000; place-items:center; backdrop-filter:blur(3px);">
  <div style="background:#fff; border-radius:14px; padding:24px 28px; max-width:480px; width:90%; box-shadow:0 12px 40px rgba(0,0,0,0.3); font-family:inherit; color:#1b2836;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <h3 style="margin:0; font-size:18px; color:#0b2f6b;">Petunjuk Simpan PDF di Browser</h3>
      <button type="button" onclick="closePrintGuide()" style="border:0; background:none; font-size:22px; cursor:pointer; color:#777; line-height:1;">&times;</button>
    </div>
    <p style="font-size:13px; color:#5d6d7e; margin:0 0 14px;">
      Agar dokumen terkonversi rapi ke PDF, pastikan opsi berikut pada dialog cetak browser:
    </p>
    <div style="background:#f0f6fc; border:1px solid #d0e2f5; border-radius:10px; padding:12px 14px; margin-bottom:18px; font-size:12.5px; line-height:1.7;">
      <div>1. <b>Tujuan:</b> Save as PDF</div>
      <div>2. <b>Tata Letak:</b> Potret (Portrait)</div>
      <div>3. <b>Ukuran Kertas:</b> A4</div>
      <div>4. <b>Margin:</b> None / Default</div>
      <div>5. Centang <b>Background graphics</b></div>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:8px;">
      <button type="button" onclick="closePrintGuide()" style="padding:8px 14px; border:1px solid #ccc; background:#f7f7f7; border-radius:8px; cursor:pointer; font-weight:600; font-size:12px;">Batal</button>
      <button type="button" onclick="proceedPrint()" style="padding:8px 16px; border:0; background:#e87722; color:#fff; border-radius:8px; cursor:pointer; font-weight:700; font-size:12px;">Buka Dialog Cetak</button>
    </div>
  </div>
</div>

<script>
function showPrintGuide(){ document.getElementById('print-modal').style.display='grid'; }
function closePrintGuide(){ document.getElementById('print-modal').style.display='none'; }
function proceedPrint(){ closePrintGuide(); setTimeout(function(){ window.print(); }, 250); }
</script>

<section class="sheet landing" id="hal-1">
  <img src="assets/landing-page.png?v=20261005" alt="Landing page Panduan Pengguna New Scoring Credit System">
</section>

<section class="sheet toc-sheet" id="hal-2">
  <div class="toc-page">
    <div class="toc-hero">
      <p class="toc-eyebrow">USER GUIDE</p>
      <h1>Daftar Isi</h1>
      <p class="toc-sub">New Scoring Credit System — alur Reviewer, Approver, dan Administrator</p>
    </div>
    <div class="toc-list">
      ${tocHtml}
    </div>
    <footer class="toc-foot">
      <b>New Scoring Credit System</b>
      <span>${total} halaman · Bank Sumut</span>
    </footer>
  </div>
</section>

${contentHtml}

<section class="sheet ending" id="hal-${total}">
  <img src="assets/ending-page.png?v=20261005" alt="Ending page Panduan Pengguna New Scoring Credit System">
</section>

</body>
</html>
`;

const outFile = path.join(OUT_DIR, 'User Guide - New Scoring Credit System.html');
fs.writeFileSync(outFile, html, 'utf8');
console.log('Wrote', outFile);
console.log('Pages', total, 'chapters', chapters.length);
