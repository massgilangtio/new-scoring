<?php
$total = (int) ($total ?? 0);
$stats = $stats ?? [];
$modules = $modules ?? [];
$actions = $actions ?? [];
$objectTypes = $object_types ?? [];
$actors = $actors ?? [];
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Jejak Audit']) ?>

<?= view('partials/kpi_solid', ['items' => [
    ['label' => 'Total Catatan Audit', 'value' => (int) ($stats['total'] ?? $total), 'sub' => 'Append-only · trigger DB aktif', 'tone' => 'indigo', 'icon' => 'solar:shield-check-bold-duotone', 'id' => 'kpiTotal'],
    ['label' => 'Kegiatan Hari Ini', 'value' => (int) ($stats['today'] ?? 0), 'sub' => 'Aktivitas per hari ini', 'tone' => 'teal', 'icon' => 'solar:calendar-date-bold-duotone', 'id' => 'kpiToday'],
    ['label' => 'Log Autentikasi', 'value' => (int) ($stats['auth'] ?? 0), 'sub' => 'Sesi login, logout, & MFA', 'tone' => 'blue', 'icon' => 'solar:user-id-bold-duotone', 'id' => 'kpiAuth'],
    ['label' => 'Mutasi Data', 'value' => (int) ($stats['data_changes'] ?? 0), 'sub' => 'Payload sebelum / sesudah', 'tone' => 'orange', 'icon' => 'solar:database-bold-duotone', 'id' => 'kpiChanges'],
]]) ?>

<div class="card card-borderless mb-3 filter-card shadow-sm">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0 d-flex align-items-center">
            <iconify-icon icon="solar:filter-bold-duotone" class="me-2 fs-5 text-warning"></iconify-icon>
            Filter &amp; Pencarian Jejak Audit
        </h4>
        <div class="d-flex align-items-center gap-1">
            <div class="btn-group btn-group-xs me-2 d-none d-lg-inline-flex" role="group">
                <button type="button" class="btn btn-outline-light btn-xs btn-quick-date" data-range="today">Hari Ini</button>
                <button type="button" class="btn btn-outline-light btn-xs btn-quick-date" data-range="yesterday">Kemarin</button>
                <button type="button" class="btn btn-outline-light btn-xs btn-quick-date" data-range="7days">7 Hari</button>
                <button type="button" class="btn btn-outline-light btn-xs btn-quick-date" data-range="30days">30 Hari</button>
                <button type="button" class="btn btn-outline-light btn-xs btn-quick-date active" data-range="all">Semua</button>
            </div>
            <?= view('partials/filter_header_btn') ?>
        </div>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body filter-card-body p-3 bg-light bg-opacity-50">
            <div class="row g-2 mb-2">
                <div class="col-12 col-md-6 col-lg-5">
                    <label class="form-label small fw-bold mb-1" for="filterSearch">
                        <i class="fa fa-search me-1 text-primary"></i> Cari Cepat (Aksi, NPP, Nama, Cabang, Objek, Alasan)
                    </label>
                    <div class="input-group input-group-sm flex-nowrap">
                        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Ketik kata kunci pencarian...">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-3">
                    <label class="form-label small fw-bold mb-1" for="filterDateFrom">
                        <i class="fa fa-calendar-alt me-1 text-teal"></i> Tanggal Mulai
                    </label>
                    <input type="date" id="filterDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-lg-3">
                    <label class="form-label small fw-bold mb-1" for="filterDateTo">
                        <i class="fa fa-calendar-check me-1 text-teal"></i> Tanggal Akhir
                    </label>
                    <input type="date" id="filterDateTo" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-lg-1 d-flex align-items-end">
                    <button type="button" id="btnFilterApply" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">
                        <i class="fa fa-filter me-1"></i> Terapkan
                    </button>
                </div>
            </div>

            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-bold mb-1" for="filterModule">
                        <i class="fa fa-layer-group me-1 text-indigo"></i> Modul Sistem
                    </label>
                    <select id="filterModule" class="select2" data-placeholder="Semua Modul">
                        <option value="">Semua Modul</option>
                        <option value="auth">🔐 Autentikasi &amp; Sesi (auth)</option>
                        <option value="access">👥 Hak Akses &amp; Role (access)</option>
                        <option value="master">🏢 Master Data (master)</option>
                        <option value="scoring">📊 Scoring &amp; Transaksi (scoring)</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-bold mb-1" for="filterActor">
                        <i class="fa fa-user me-1 text-success"></i> Aktor / Pegawai
                    </label>
                    <select id="filterActor" class="select2" data-placeholder="Semua Pegawai">
                        <option value="">Semua Pegawai</option>
                        <?php foreach ($actors as $act) : ?>
                            <option value="<?= esc($act['username']) ?>"><?= esc($act['username']) ?> — <?= esc($act['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-bold mb-1" for="filterAction">
                        <i class="fa fa-bolt me-1 text-warning"></i> Jenis Aksi
                    </label>
                    <select id="filterAction" class="select2" data-placeholder="Semua Aksi">
                        <option value="">Semua Aksi</option>
                        <?php foreach ($actions as $act) : ?>
                            <option value="<?= esc($act) ?>"><?= esc($act) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small fw-bold mb-1" for="filterObjectType">
                        <i class="fa fa-cube me-1 text-info"></i> Tipe Objek
                    </label>
                    <select id="filterObjectType" class="select2" data-placeholder="Semua Tipe">
                        <option value="">Semua Tipe</option>
                        <?php foreach ($objectTypes as $ot) : ?>
                            <option value="<?= esc($ot) ?>"><?= esc($ot) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-1 d-flex">
                    <button type="button" id="btnFilterReset" class="btn btn-default btn-sm w-100 fw-semibold" title="Reset filter">
                        <i class="fa fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-borderless table-card shadow-sm">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0 d-flex align-items-center">
            <iconify-icon icon="solar:shield-minimalistic-bold-duotone" class="me-2 fs-5 text-teal"></iconify-icon>
            Jejak Audit Sistem (Audit Trail)
        </h4>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-teal bg-opacity-20 text-teal border border-teal border-opacity-25 px-2 py-1 small">
                <i class="fa fa-lock me-1"></i> Append-Only Immutable
            </span>
            <span class="badge bg-white bg-opacity-15 text-white px-2 py-1" id="tblCountBadge"><?= esc((string) $total) ?> entri</span>
            <?= view('partials/card_widget_btn') ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive d-none d-md-block">
            <table id="auditTable" class="table table-hover table-striped align-middle mb-0 w-100 audit-table">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 140px;">Waktu (WIB)</th>
                        <th style="min-width: 190px;">Pelaku &amp; Cabang</th>
                        <th style="min-width: 230px;">Aksi / Kegiatan</th>
                        <th style="min-width: 180px;">Target Objek</th>
                        <th>Alasan / Ringkasan Perubahan</th>
                        <th style="width: 80px;" class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="auditMobileList" class="audit-mobile-list d-md-none p-3">
            <div class="text-center py-4 text-muted small"><i class="fa fa-spinner fa-spin me-1"></i> Memuat data audit...</div>
        </div>
    </div>
</div>

<!-- Modal Rincian Jejak Audit -->
<div class="modal fade" id="modalAuditDetail" tabindex="-1" aria-labelledby="modalAuditDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:shield-check-bold-duotone" class="fs-3 text-teal"></iconify-icon>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalAuditDetailTitle">Rincian Jejak Audit</h5>
                        <div class="small text-white-50" id="dtlHeaderSubtitle">Log ID &amp; Waktu Kejadian</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <!-- Row Info Identitas -->
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <div class="card border h-100 bg-light bg-opacity-50">
                            <div class="card-body p-3">
                                <div class="text-uppercase small fw-bold text-muted mb-2 d-flex align-items-center gap-1">
                                    <i class="fa fa-user-circle text-primary"></i> Identitas Pelaku (Actor)
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary bg-opacity-15 text-primary fw-bold" id="dtlRoleName">-</span>
                                    <span class="badge bg-light text-dark border" id="dtlBranchBadge">-</span>
                                </div>
                                <div class="fs-6 fw-bold text-dark d-flex align-items-center gap-2">
                                    <span id="dtlUsername" class="font-monospace">-</span>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-copy" id="btnCopyUser" title="Salin NPP/Username">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                                <div class="small text-muted" id="dtlFullName">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="card border h-100 bg-light bg-opacity-50">
                            <div class="card-body p-3">
                                <div class="text-uppercase small fw-bold text-muted mb-2 d-flex align-items-center gap-1">
                                    <i class="fa fa-bullseye text-teal"></i> Target Operasi &amp; Objek
                                </div>
                                <div class="mb-2">
                                    <span id="dtlActionBadge" class="badge px-2 py-1">-</span>
                                    <div class="font-monospace small text-muted mt-1" id="dtlActionCode">-</div>
                                </div>
                                <div class="small text-muted mb-1">Target Objek:</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary border fw-semibold" id="dtlObjectType">-</span>
                                    <span class="font-monospace fw-bold text-dark" id="dtlObjectId">-</span>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-copy" id="btnCopyObjId" title="Salin Object ID">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alasan / Catatan jika ada -->
                <div class="alert alert-warning border-warning border-opacity-25 d-none mb-3 py-2 px-3 small" id="dtlReasonAlert">
                    <div class="fw-bold text-dark mb-1 d-flex align-items-center gap-1">
                        <i class="fa fa-info-circle text-warning"></i> Alasan / Catatan Sistem:
                    </div>
                    <div id="dtlReasonText" class="text-dark"></div>
                </div>

                <!-- Perubahan Data Tabs -->
                <div class="card border">
                    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                        <ul class="nav nav-pills card-header-pills" id="auditDetailTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-1 px-3 small fw-bold" id="tab-diff" data-bs-toggle="tab" data-bs-target="#pane-diff" type="button" role="tab">
                                    <i class="fa fa-columns me-1"></i> Perbandingan Nilai (Diff)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 px-3 small fw-bold" id="tab-json-before" data-bs-toggle="tab" data-bs-target="#pane-json-before" type="button" role="tab">
                                    <i class="fa fa-code me-1 text-danger"></i> JSON Sebelum
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 px-3 small fw-bold" id="tab-json-after" data-bs-toggle="tab" data-bs-target="#pane-json-after" type="button" role="tab">
                                    <i class="fa fa-code me-1 text-success"></i> JSON Sesudah
                                </button>
                            </li>
                        </ul>
                        <div id="diffSummaryBadge" class="badge bg-secondary-subtle text-secondary border">0 Perubahan</div>
                    </div>
                    <div class="card-body p-3">
                        <div class="tab-content" id="auditDetailTabContent">
                            <!-- Pane Diff -->
                            <div class="tab-pane fade show active" id="pane-diff" role="tabpanel">
                                <div id="diffContainer" class="table-responsive">
                                    <!-- Rendered dynamically -->
                                </div>
                            </div>

                            <!-- Pane JSON Before -->
                            <div class="tab-pane fade" id="pane-json-before" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted fw-semibold">State Data Sebelum Eksekusi</span>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" id="btnCopyJsonBefore">
                                        <i class="fa fa-copy me-1"></i> Salin JSON
                                    </button>
                                </div>
                                <pre class="p-3 bg-dark text-white rounded small mb-0 font-monospace overflow-auto" style="max-height: 320px;" id="dtlJsonBefore">{}</pre>
                            </div>

                            <!-- Pane JSON After -->
                            <div class="tab-pane fade" id="pane-json-after" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted fw-semibold">State Data Sesudah Eksekusi</span>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" id="btnCopyJsonAfter">
                                        <i class="fa fa-copy me-1"></i> Salin JSON
                                    </button>
                                </div>
                                <pre class="p-3 bg-dark text-white rounded small mb-0 font-monospace overflow-auto" style="max-height: 320px;" id="dtlJsonAfter">{}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // 1. Kamus Aksi Ramah Pengguna & Kategori Warna untuk Tim IT
    var ACTION_DICT = {
        'auth.login_succeeded': { label: 'Login Berhasil', mod: 'AUTH', badge: 'bg-success-subtle text-success border border-success border-opacity-25' },
        'auth.login_failed': { label: 'Login Gagal', mod: 'AUTH', badge: 'bg-danger-subtle text-danger border border-danger border-opacity-25' },
        'auth.mfa_failed': { label: 'MFA Gagal / Ditolak', mod: 'AUTH', badge: 'bg-danger-subtle text-danger border border-danger border-opacity-25' },
        'auth.mfa_enabled': { label: 'Aktivasi Barcode MFA', mod: 'AUTH', badge: 'bg-teal-subtle text-teal border border-teal border-opacity-25' },
        'auth.mfa_reset': { label: 'Reset MFA Mandiri', mod: 'AUTH', badge: 'bg-warning-subtle text-warning border border-warning border-opacity-25' },
        'auth.logout': { label: 'Logout Sesi', mod: 'AUTH', badge: 'bg-secondary-subtle text-secondary border' },

        'access.user_created': { label: 'Tambah Pengguna Baru', mod: 'ACCESS', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'access.user_updated': { label: 'Perbarui Data Pengguna', mod: 'ACCESS', badge: 'bg-info-subtle text-info border border-info border-opacity-25' },
        'access.mfa_reset': { label: 'Reset Barcode MFA User', mod: 'ACCESS', badge: 'bg-warning-subtle text-warning border border-warning border-opacity-25' },
        'access.mfa_toggle': { label: 'Ubah Status Wajib MFA', mod: 'ACCESS', badge: 'bg-indigo-subtle text-indigo border border-indigo border-opacity-25' },
        'access.userhris_synced': { label: 'Sinkronisasi Pegawai HRIS', mod: 'ACCESS', badge: 'bg-purple-subtle text-purple border border-purple border-opacity-25' },
        'access.role_created': { label: 'Tambah Role Baru', mod: 'ACCESS', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'access.role_updated': { label: 'Perbarui Role', mod: 'ACCESS', badge: 'bg-info-subtle text-info border border-info border-opacity-25' },
        'access.permissions_updated': { label: 'Ubah Hak Akses Role', mod: 'ACCESS', badge: 'bg-indigo-subtle text-indigo border border-indigo border-opacity-25' },
        'access.job_group_created': { label: 'Tambah Kelompok Jabatan', mod: 'ACCESS', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'access.job_group_updated': { label: 'Perbarui Kelompok Jabatan', mod: 'ACCESS', badge: 'bg-info-subtle text-info border border-info border-opacity-25' },
        'access.job_groups_synced': { label: 'Sinkron Kelompok Jabatan', mod: 'ACCESS', badge: 'bg-purple-subtle text-purple border border-purple border-opacity-25' },

        'master.branch_created': { label: 'Tambah Cabang Baru', mod: 'MASTER', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'master.branch_updated': { label: 'Perbarui Data Cabang', mod: 'MASTER', badge: 'bg-info-subtle text-info border border-info border-opacity-25' },
        'master.branches_synced_gateway': { label: 'Sinkron Cabang Core Gateway', mod: 'MASTER', badge: 'bg-purple-subtle text-purple border border-purple border-opacity-25' },
        'master.product_created': { label: 'Tambah Produk Baru', mod: 'MASTER', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'master.product_updated': { label: 'Perbarui Data Produk', mod: 'MASTER', badge: 'bg-info-subtle text-info border border-info border-opacity-25' },
        'master.debtor_created': { label: 'Tambah Debitur Baru', mod: 'MASTER', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'master.debtor_updated': { label: 'Perbarui Data Debitur', mod: 'MASTER', badge: 'bg-info-subtle text-info border border-info border-opacity-25' },
        'master.debtor_imported': { label: 'Import Debitur Excel', mod: 'MASTER', badge: 'bg-teal-subtle text-teal border border-teal border-opacity-25' },

        'scoring.master_parameter_created': { label: 'Tambah Master Parameter', mod: 'SCORING', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'scoring.master_parameter_updated': { label: 'Perbarui Master Parameter', mod: 'SCORING', badge: 'bg-info-subtle text-info border border-info border-opacity-25' },
        'scoring.master_parameter_deleted': { label: 'Hapus Master Parameter', mod: 'SCORING', badge: 'bg-danger-subtle text-danger border border-danger border-opacity-25' },
        'scoring.passing_score_setting_updated': { label: 'Ubah Passing Score Global', mod: 'SCORING', badge: 'bg-warning-subtle text-warning border border-warning border-opacity-25' },
        'scoring.mapping_created': { label: 'Tambah Mapping Produk', mod: 'SCORING', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'scoring.mapping_updated': { label: 'Perbarui Mapping Produk', mod: 'SCORING', badge: 'bg-info-subtle text-info border border-info border-opacity-25' },
        'scoring.mapping_deleted': { label: 'Hapus Mapping Produk', mod: 'SCORING', badge: 'bg-danger-subtle text-danger border border-danger border-opacity-25' },
        'scoring.credit_scoring_saved': { label: 'Simpan Scoring Kredit', mod: 'SCORING', badge: 'bg-success-subtle text-success border border-success border-opacity-25' },
        'scoring.transaction_created': { label: 'Buat Transaksi Scoring', mod: 'SCORING', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'scoring.answers_saved': { label: 'Simpan Jawaban Draft', mod: 'SCORING', badge: 'bg-info-subtle text-info border border-info border-opacity-25' },
        'scoring.transaction_submitted': { label: 'Submit Scoring Kredit', mod: 'SCORING', badge: 'bg-purple-subtle text-purple border border-purple border-opacity-25' },
        'scoring.transaction_duplicated': { label: 'Scoring Ulang (Duplikasi)', mod: 'SCORING', badge: 'bg-teal-subtle text-teal border border-teal border-opacity-25' },
        'scoring.approver_assigned': { label: 'Penugasan Approver', mod: 'SCORING', badge: 'bg-amber-subtle text-amber border border-amber border-opacity-25' },
        'scoring.decision_recorded': { label: 'Keputusan Persetujuan', mod: 'SCORING', badge: 'bg-indigo-subtle text-indigo border border-indigo border-opacity-25' },
        'scoring.version_created': { label: 'Buat Versi Scoring', mod: 'SCORING', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'scoring.version_copied': { label: 'Salin Versi Scoring', mod: 'SCORING', badge: 'bg-teal-subtle text-teal border border-teal border-opacity-25' },
        'scoring.parameter_created': { label: 'Tambah Parameter Versi', mod: 'SCORING', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'scoring.option_created': { label: 'Tambah Opsi Nilai', mod: 'SCORING', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'scoring.threshold_created': { label: 'Tambah Threshold Versi', mod: 'SCORING', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'scoring.field_created': { label: 'Tambah Field Dinamis', mod: 'SCORING', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'scoring.field_option_created': { label: 'Tambah Opsi Field', mod: 'SCORING', badge: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        'scoring.version_activated': { label: 'Aktivasi Versi Scoring', mod: 'SCORING', badge: 'bg-success-subtle text-success border border-success border-opacity-25' },
        'scoring.parameter_deleted': { label: 'Hapus Parameter Versi', mod: 'SCORING', badge: 'bg-danger-subtle text-danger border border-danger border-opacity-25' },
        'scoring.rescore_requested': { label: 'Permintaan Rescore', mod: 'SCORING', badge: 'bg-warning-subtle text-warning border border-warning border-opacity-25' },
        'scoring.rescore_approved': { label: 'Persetujuan Rescore', mod: 'SCORING', badge: 'bg-success-subtle text-success border border-success border-opacity-25' },
        'scoring.duplicate_setting_updated': { label: 'Ubah Setting Duplikasi', mod: 'SCORING', badge: 'bg-indigo-subtle text-indigo border border-indigo border-opacity-25' }
    };

    // Helper Action Resolver
    function resolveAction(act) {
        if (ACTION_DICT[act]) return ACTION_DICT[act];
        var parts = String(act || '').split('.');
        var mod = (parts[0] || 'SYSTEM').toUpperCase();
        return {
            label: act,
            mod: mod,
            badge: 'bg-secondary-subtle text-secondary border'
        };
    }

    // Init Select2
    App.initSelect2('#filterModule', { placeholder: 'Semua Modul', allowClear: true });
    App.initSelect2('#filterActor', { placeholder: 'Semua Pegawai', allowClear: true });
    App.initSelect2('#filterAction', { placeholder: 'Semua Aksi', allowClear: true });
    App.initSelect2('#filterObjectType', { placeholder: 'Semua Tipe', allowClear: true });

    var escHtml = function (v) {
        return $('<div>').text(v == null ? '' : String(v)).html();
    };

    // Format ISO string to WIB
    function formatWib(isoStr) {
        if (!isoStr) return '-';
        try {
            var d = new Date(isoStr);
            if (isNaN(d.getTime())) return isoStr;
            var pad = function (n) { return n < 10 ? '0' + n : n; };
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            var day = pad(d.getDate());
            var mon = months[d.getMonth()];
            var year = d.getFullYear();
            var hours = pad(d.getHours());
            var mins = pad(d.getMinutes());
            var secs = pad(d.getSeconds());
            return day + ' ' + mon + ' ' + year + ', ' + hours + ':' + mins + ':' + secs;
        } catch (e) {
            return isoStr;
        }
    }

    // Cache of loaded rows for quick modal viewing
    var cachedRows = {};

    // Render Data Comparison / Visual Diff
    function renderVisualDiff(before, after) {
        var $c = $('#diffContainer').empty();
        var b = (before && typeof before === 'object') ? before : null;
        var a = (after && typeof after === 'object') ? after : null;

        if (!b && !a) {
            $('#diffSummaryBadge').text('Catatan Kejadian').attr('class', 'badge bg-secondary-subtle text-secondary border');
            $c.html(
                '<div class="alert alert-light border text-center py-4 my-2">' +
                '<i class="fa fa-info-circle fs-3 text-muted mb-2 d-block"></i>' +
                '<div class="fw-bold text-dark">Pencatatan Event Sistem</div>' +
                '<div class="small text-muted">Aksi ini merupakan catatan kejadian sistem (tanpa mutasi atribut tabel/payload).</div>' +
                '</div>'
            );
            return;
        }

        // Union of keys
        var allKeys = {};
        if (b) Object.keys(b).forEach(function (k) { allKeys[k] = true; });
        if (a) Object.keys(a).forEach(function (k) { allKeys[k] = true; });
        var keys = Object.keys(allKeys);

        var changedCount = 0;
        var html = '<table class="table table-bordered table-sm align-middle mb-0 font-monospace small">';
        html += '<thead class="table-light"><tr><th style="width: 25%;">Atribut / Parameter</th><th style="width: 37.5%;" class="text-danger">Nilai Sebelum (Before)</th><th style="width: 37.5%;" class="text-success">Nilai Sesudah (After)</th></tr></thead><tbody>';

        keys.forEach(function (k) {
            var valB = b ? b[k] : undefined;
            var valA = a ? a[k] : undefined;
            var strB = valB !== undefined ? (typeof valB === 'object' ? JSON.stringify(valB) : String(valB)) : '<span class="text-muted fst-italic">(tidak ada)</span>';
            var strA = valA !== undefined ? (typeof valA === 'object' ? JSON.stringify(valA) : String(valA)) : '<span class="text-muted fst-italic">(tidak ada)</span>';
            var isDifferent = (valB !== valA);

            if (isDifferent) changedCount++;

            var rowClass = isDifferent ? 'table-warning bg-opacity-25' : '';
            var bCellClass = isDifferent && valB !== undefined ? 'text-danger fw-semibold bg-danger-subtle bg-opacity-10' : 'text-muted';
            var aCellClass = isDifferent && valA !== undefined ? 'text-success fw-bold bg-success-subtle bg-opacity-10' : '';

            html += '<tr class="' + rowClass + '">';
            html += '<td class="fw-bold text-dark">' + escHtml(k) + (isDifferent ? ' <i class="fa fa-pencil-alt text-warning ms-1" title="Berubah"></i>' : '') + '</td>';
            html += '<td class="' + bCellClass + '">' + (isDifferent && valB !== undefined ? '<del>' + escHtml(strB) + '</del>' : escHtml(strB)) + '</td>';
            html += '<td class="' + aCellClass + '">' + escHtml(strA) + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $c.html(html);

        if (!b && a) {
            $('#diffSummaryBadge').text(keys.length + ' Atribut Dibuat').attr('class', 'badge bg-success-subtle text-success border border-success');
        } else if (b && !a) {
            $('#diffSummaryBadge').text(keys.length + ' Atribut Dihapus').attr('class', 'badge bg-danger-subtle text-danger border border-danger');
        } else {
            $('#diffSummaryBadge').text(changedCount + ' dari ' + keys.length + ' Atribut Berubah').attr('class', 'badge bg-warning-subtle text-warning border border-warning');
        }
    }

    // Open Detail Modal
    function openAuditDetail(item) {
        if (!item) return;
        $('#dtlId').text(item.id);
        $('#modalAuditDetailTitle').text('Rincian Jejak Audit #' + item.id);
        $('#dtlHeaderSubtitle').text(formatWib(item.occurred_at) + ' WIB');

        var actionMeta = resolveAction(item.action);
        $('#dtlActionBadge').attr('class', 'badge ' + actionMeta.badge).text(actionMeta.label);
        $('#dtlActionCode').text(item.action);

        $('#dtlRoleName').text(item.actor_role_name || '-');
        var branchText = (item.actor_branch_code ? item.actor_branch_code + ' - ' : '') + (item.actor_branch_name || ('Cabang #' + item.actor_branch_id));
        $('#dtlBranchBadge').html('<i class="fa fa-building me-1 text-muted"></i>' + escHtml(branchText));

        $('#dtlUsername').text(item.actor_username || ('ID: ' + item.actor_user_id));
        $('#dtlFullName').text(item.actor_full_name || '-');

        $('#dtlObjectType').text(item.object_type || '-');
        $('#dtlObjectId').text(item.object_id || '-');

        if (item.reason && String(item.reason).trim()) {
            $('#dtlReasonText').text(item.reason);
            $('#dtlReasonAlert').removeClass('d-none');
        } else {
            $('#dtlReasonAlert').addClass('d-none');
        }

        // Setup JSON panes
        $('#dtlJsonBefore').text(item.before ? JSON.stringify(item.before, null, 2) : '// Tidak ada data sebelum perubahan');
        $('#dtlJsonAfter').text(item.after ? JSON.stringify(item.after, null, 2) : '// Tidak ada data sesudah perubahan');

        // Setup Diff
        renderVisualDiff(item.before, item.after);

        // Reset to first tab
        $('#tab-diff').tab('show');

        // Show Modal
        var myModal = new bootstrap.Modal(document.getElementById('modalAuditDetail'));
        myModal.show();
    }

    // Copy to clipboard helper
    function copyText(txt, label) {
        if (!txt) return;
        navigator.clipboard.writeText(txt).then(function () {
            if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: (label || 'Data') + ' disalin',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        });
    }

    $('#btnCopyUser').on('click', function () { copyText($('#dtlUsername').text(), 'NPP/Username'); });
    $('#btnCopyObjId').on('click', function () { copyText($('#dtlObjectId').text(), 'Object ID'); });
    $('#btnCopyJsonBefore').on('click', function () { copyText($('#dtlJsonBefore').text(), 'JSON Sebelum'); });
    $('#btnCopyJsonAfter').on('click', function () { copyText($('#dtlJsonAfter').text(), 'JSON Sesudah'); });

    // Click handler for detail buttons (table & mobile)
    $(document).on('click', '.btn-view-audit', function () {
        var id = $(this).data('id');
        var item = cachedRows[id];
        if (item) {
            openAuditDetail(item);
        } else {
            // Fetch via API endpoint if not cached
            $.getJSON('<?= site_url('audit/detail') ?>/' + id, function (res) {
                if (res.result) {
                    cachedRows[id] = res.result;
                    openAuditDetail(res.result);
                }
            });
        }
    });

    // Mobile Renderer
    var renderMobile = function (rows) {
        var $list = $('#auditMobileList').empty();
        if (!rows || !rows.length) {
            $list.html('<div class="text-center py-5 text-muted px-3"><div class="fw-semibold">Belum Ada Catatan Audit</div></div>');
            return;
        }
        rows.forEach(function (item) {
            cachedRows[item.id] = item;
            var act = resolveAction(item.action);
            var branchInfo = (item.actor_branch_code ? item.actor_branch_code : ('Cabang #' + item.actor_branch_id));

            $list.append(
                '<div class="card border rounded-3 p-3 mb-2 shadow-sm">' +
                '<div class="d-flex justify-content-between align-items-start gap-2 mb-2">' +
                '<div>' +
                '<span class="badge ' + act.badge + ' fw-semibold mb-1">' + escHtml(act.label) + '</span>' +
                '<div class="small text-muted font-monospace"><i class="fa fa-clock me-1"></i>' + formatWib(item.occurred_at) + ' WIB</div>' +
                '</div>' +
                '<button type="button" class="btn btn-outline-primary btn-xs btn-view-audit" data-id="' + item.id + '">' +
                '<i class="fa fa-eye me-1"></i> Detail' +
                '</button>' +
                '</div>' +
                '<div class="d-flex flex-wrap gap-1 align-items-center mb-2 small">' +
                '<span class="badge bg-primary bg-opacity-15 text-primary fw-semibold">' + escHtml(item.actor_role_name || '-') + '</span>' +
                '<span class="badge bg-light text-dark border font-monospace"><i class="fa fa-user me-1 text-muted"></i>' + escHtml(item.actor_username || ('ID:' + item.actor_user_id)) + '</span>' +
                '<span class="badge bg-light text-muted border"><i class="fa fa-building me-1"></i>' + escHtml(branchInfo) + '</span>' +
                '</div>' +
                '<div class="small mb-1">' +
                '<strong>Target:</strong> <span class="badge bg-secondary-subtle text-secondary border font-monospace">' + escHtml(item.object_type) + ' #' + escHtml(item.object_id) + '</span>' +
                '</div>' +
                (item.reason ? '<div class="small text-muted fst-italic border-start border-3 ps-2 py-1 mt-1">' + escHtml(item.reason) + '</div>' : '') +
                '</div>'
            );
        });
    };

    var isDesktop = window.matchMedia('(min-width: 768px)').matches;
    var dataTable = null;

    if (isDesktop) {
        dataTable = App.initDT('#auditTable', {
            serverSide: true,
            processing: true,
            searching: false,
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: '<?= site_url('audit/datatables') ?>',
                type: 'GET',
                data: function (d) {
                    d.filterSearch = $('#filterSearch').val();
                    d.filterDateFrom = $('#filterDateFrom').val();
                    d.filterDateTo = $('#filterDateTo').val();
                    d.filterModule = $('#filterModule').val();
                    d.filterActor = $('#filterActor').val();
                    d.filterAction = $('#filterAction').val();
                    d.filterObjectType = $('#filterObjectType').val();
                },
                dataSrc: function (json) {
                    var stats = json.stats || {};
                    var t = stats.total != null ? stats.total : (json.recordsTotal || 0);
                    $('#kpiTotal').text(t);
                    if (stats.today != null) $('#kpiToday').text(stats.today);
                    if (stats.auth != null) $('#kpiAuth').text(stats.auth);
                    if (stats.data_changes != null) $('#kpiChanges').text(stats.data_changes);
                    $('#tblCountBadge').text((json.recordsFiltered || 0) + ' entri');

                    var data = json.data || [];
                    data.forEach(function (row) {
                        cachedRows[row.id] = row;
                    });
                    return data;
                }
            },
            columns: [
                // 1. Waktu
                {
                    data: 'occurred_at',
                    className: 'small text-nowrap',
                    render: function (d) {
                        return '<div class="fw-bold text-dark font-monospace">' + formatWib(d) + '</div>' +
                            '<div class="small text-muted">WIB (UTC+7)</div>';
                    }
                },
                // 2. Pelaku & Cabang
                {
                    data: null,
                    render: function (row) {
                        var roleBadge = '<span class="badge bg-primary bg-opacity-15 text-primary fw-semibold">' + escHtml(row.actor_role_name || '-') + '</span>';
                        var userNpp = row.actor_username ? escHtml(row.actor_username) : ('ID: ' + escHtml(row.actor_user_id));
                        var userName = row.actor_full_name ? '<div class="small text-muted text-truncate" style="max-width:180px;" title="' + escHtml(row.actor_full_name) + '">' + escHtml(row.actor_full_name) + '</div>' : '';
                        var branchTxt = row.actor_branch_code ? (row.actor_branch_code + ' - ' + (row.actor_branch_name || '')) : ('Cabang #' + row.actor_branch_id);
                        return '<div class="d-flex flex-column gap-1">' +
                            '<div>' + roleBadge + '</div>' +
                            '<div class="small fw-bold text-dark"><i class="fa fa-user me-1 text-muted"></i>' + userNpp + '</div>' +
                            userName +
                            '<div class="small text-muted text-truncate" style="max-width:180px;" title="' + escHtml(branchTxt) + '"><i class="fa fa-building me-1 text-muted"></i>' + escHtml(branchTxt) + '</div>' +
                            '</div>';
                    }
                },
                // 3. Aksi / Kegiatan
                {
                    data: 'action',
                    render: function (d) {
                        var act = resolveAction(d);
                        return '<div class="d-flex flex-column gap-1">' +
                            '<div><span class="badge ' + act.badge + ' fw-semibold">' + escHtml(act.label) + '</span></div>' +
                            '<div class="font-monospace small text-muted text-nowrap"><i class="fa fa-code me-1"></i>' + escHtml(d) + '</div>' +
                            '</div>';
                    }
                },
                // 4. Target Objek
                {
                    data: null,
                    render: function (row) {
                        return '<div class="d-flex flex-column gap-1">' +
                            '<div><span class="badge bg-secondary-subtle text-secondary border font-monospace">' + escHtml(row.object_type) + '</span></div>' +
                            '<div class="d-flex align-items-center gap-1">' +
                            '<span class="fw-bold text-dark font-monospace">#' + escHtml(row.object_id) + '</span>' +
                            '<button type="button" class="btn btn-xs btn-link p-0 text-muted btn-copy" onclick="navigator.clipboard.writeText(\'' + escHtml(row.object_id) + '\');" title="Salin ID">' +
                            '<i class="fa fa-copy"></i>' +
                            '</button>' +
                            '</div>' +
                            '</div>';
                    }
                },
                // 5. Alasan / Ringkasan
                {
                    data: null,
                    render: function (row) {
                        var resHtml = '';
                        if (row.reason && String(row.reason).trim()) {
                            var safeReason = escHtml(row.reason);
                            resHtml += '<div class="small text-dark mb-1"><i class="fa fa-comment-alt text-warning me-1"></i><span style="max-width:200px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + safeReason + '">' + safeReason + '</span></div>';
                        }
                        if (row.before && row.after) {
                            resHtml += '<div><span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 small"><i class="fa fa-exchange-alt me-1"></i>Perubahan Payload</span></div>';
                        } else if (row.after) {
                            resHtml += '<div><span class="badge bg-success-subtle text-success border border-success border-opacity-25 small"><i class="fa fa-plus-circle me-1"></i>Data Dibuat</span></div>';
                        } else if (row.before) {
                            resHtml += '<div><span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 small"><i class="fa fa-trash-alt me-1"></i>Data Dihapus</span></div>';
                        } else if (!row.reason) {
                            resHtml += '<span class="small text-muted">-</span>';
                        }
                        return resHtml;
                    }
                },
                // 6. Action Detail Button
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (row) {
                        return '<button type="button" class="btn btn-outline-primary btn-xs btn-view-audit shadow-sm" data-id="' + row.id + '" title="Lihat Detail & Perbandingan Data">' +
                            '<i class="fa fa-eye me-1"></i> Detail' +
                            '</button>';
                    }
                }
            ]
        });
    } else {
        // Mobile view loader
        function loadMobile() {
            $.getJSON('<?= site_url('audit/datatables') ?>', {
                draw: 1,
                start: 0,
                length: 25,
                filterSearch: $('#filterSearch').val(),
                filterDateFrom: $('#filterDateFrom').val(),
                filterDateTo: $('#filterDateTo').val(),
                filterModule: $('#filterModule').val(),
                filterActor: $('#filterActor').val(),
                filterAction: $('#filterAction').val(),
                filterObjectType: $('#filterObjectType').val()
            }).done(function (json) {
                var stats = json.stats || {};
                var t = stats.total != null ? stats.total : (json.recordsTotal || 0);
                $('#kpiTotal').text(t);
                if (stats.today != null) $('#kpiToday').text(stats.today);
                if (stats.auth != null) $('#kpiAuth').text(stats.auth);
                if (stats.data_changes != null) $('#kpiChanges').text(stats.data_changes);
                $('#tblCountBadge').text((json.recordsFiltered || 0) + ' entri');
                renderMobile(json.data || []);
            });
        }
        window.__auditMobileReload = loadMobile;
        loadMobile();
    }

    function reload() {
        if (dataTable) dataTable.ajax.reload();
        else if (window.__auditMobileReload) window.__auditMobileReload();
    }

    // Quick Date Range buttons
    $('.btn-quick-date').on('click', function () {
        $('.btn-quick-date').removeClass('active');
        $(this).addClass('active');
        var range = $(this).data('range');
        var now = new Date();
        var pad = function (n) { return n < 10 ? '0' + n : n; };
        var formatYmd = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };

        if (range === 'today') {
            var todayStr = formatYmd(now);
            $('#filterDateFrom').val(todayStr);
            $('#filterDateTo').val(todayStr);
        } else if (range === 'yesterday') {
            var yest = new Date();
            yest.setDate(yest.getDate() - 1);
            var yestStr = formatYmd(yest);
            $('#filterDateFrom').val(yestStr);
            $('#filterDateTo').val(yestStr);
        } else if (range === '7days') {
            var past7 = new Date();
            past7.setDate(past7.getDate() - 6);
            $('#filterDateFrom').val(formatYmd(past7));
            $('#filterDateTo').val(formatYmd(now));
        } else if (range === '30days') {
            var past30 = new Date();
            past30.setDate(past30.getDate() - 29);
            $('#filterDateFrom').val(formatYmd(past30));
            $('#filterDateTo').val(formatYmd(now));
        } else {
            // all
            $('#filterDateFrom').val('');
            $('#filterDateTo').val('');
        }
        reload();
    });

    $('#btnFilterApply').on('click', reload);
    $('#btnFilterReset').on('click', function () {
        $('#filterSearch').val('');
        $('#filterDateFrom').val('');
        $('#filterDateTo').val('');
        $('#filterModule').val(null).trigger('change');
        $('#filterActor').val(null).trigger('change');
        $('#filterAction').val(null).trigger('change');
        $('#filterObjectType').val(null).trigger('change');
        $('.btn-quick-date').removeClass('active');
        $('.btn-quick-date[data-range="all"]').addClass('active');
        reload();
    });

    $('#filterSearch').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            reload();
        }
    });

    $('#filterModule, #filterActor, #filterAction, #filterObjectType, #filterDateFrom, #filterDateTo').on('change', function () {
        reload();
    });
});
</script>

<?= view('partials/shell_end') ?>
