<?php
$profile  = $profile ?? [];
$debtors  = $debtors ?? [];
$branches = $branches ?? [];
$totalDebtors = count($debtors);
$activeDebtors = 0;
foreach ($debtors as $d) {
    if (! empty($d['is_active'])) {
        $activeDebtors++;
    }
}
$inactiveDebtors = max(0, $totalDebtors - $activeDebtors);
$activeDebtorPct = $totalDebtors > 0 ? (int) round(($activeDebtors / $totalDebtors) * 100) : 0;
$inactiveDebtorPct = $totalDebtors > 0 ? (int) round(($inactiveDebtors / $totalDebtors) * 100) : 0;
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Master Debitur']) ?>

<?php if (! empty($message)) : ?>
    <div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
    <div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($import)) : ?>
    <div data-swal="info"
        data-swal-title="Import Selesai"
        data-swal-message="<?= esc((int)($import['created'] ?? 0) . ' data baru, ' . (int)($import['updated'] ?? 0) . ' diperbarui, ' . count($import['rejected'] ?? []) . ' ditolak.') ?>"
        hidden></div>
<?php endif; ?>

<!-- BEGIN KPI — Color Admin index_v2 gradient cards -->
<div class="row mb-3">
    <div class="col-xl-4 col-md-4">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-indigo bg-gradient-to-purple overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Total Debitur</div>
                <div class="h2 mb-4"><?= esc((string) $totalDebtors) ?></div>
                <div class="progress h-5px bg-black mb-2">
                    <div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width: 100%;"></div>
                </div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Semua debitur terdaftar</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="text-black text-opacity-30" style="font-size: 150px"></iconify-icon>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mt-3 mt-md-0">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-teal bg-gradient-to-cyan overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Aktif</div>
                <div class="h2 mb-4"><?= esc((string) $activeDebtors) ?></div>
                <div class="progress h-5px bg-black mb-2">
                    <div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width: <?= esc((string) $activeDebtorPct) ?>%;"></div>
                </div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1"><?= esc((string) $activeDebtorPct) ?>% dari total debitur</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:check-circle-bold-duotone" class="text-black text-opacity-30" style="font-size: 150px"></iconify-icon>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mt-3 mt-md-0">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-orange bg-gradient-to-pink overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Nonaktif</div>
                <div class="h2 mb-4"><?= esc((string) $inactiveDebtors) ?></div>
                <div class="progress h-5px bg-black mb-2">
                    <div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width: <?= esc((string) $inactiveDebtorPct) ?>%;"></div>
                </div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1"><?= esc((string) $inactiveDebtorPct) ?>% dari total debitur</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:close-circle-bold-duotone" class="text-black text-opacity-30" style="font-size: 150px"></iconify-icon>
            </div>
        </div>
    </div>
</div>
<!-- END KPI -->

<!-- BEGIN Filter -->
<div class="card card-borderless mb-3 filter-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:filter-bold-duotone"></iconify-icon>
            Filter Data
        </h4>
        <div class="card-header-btn">
            <button type="button" class="btn btn-default btn-xs" id="btnToggleFilter" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                <span id="filterToggleText">Sembunyikan Filter</span>
                <i class="fa fa-chevron-up ms-1" id="filterToggleIcon"></i>
            </button>
        </div>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body filter-card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label" for="filterSearchDebtor">Search Debitur</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" id="filterSearchDebtor" placeholder="Cari NIK, nama, CIS, CIF...">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label" for="filterBranchDebtor">Cabang</label>
                    <select class="form-select filter-select2" id="filterBranchDebtor" data-placeholder="Semua Cabang">
                        <option value=""></option>
                        <?php foreach ($branches as $branch) : ?>
                            <option value="<?= esc($branch['name']) ?>"><?= esc($branch['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label" for="filterStatusDebtor">Status Debitur</label>
                    <select class="form-select filter-select2" id="filterStatusDebtor" data-placeholder="Semua Status">
                        <option value=""></option>
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label" for="filterGenderDebtor">Jenis Kelamin</label>
                    <select class="form-select filter-select2" id="filterGenderDebtor" data-placeholder="Semua Gender">
                        <option value=""></option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="button" class="btn btn-theme btn-sm" id="btnApplyFilter">
                    <i class="fa fa-search me-1"></i> Terapkan Filter
                </button>
                <button type="button" class="btn btn-default btn-sm" id="btnResetFilter">
                    <i class="fa fa-rotate-left me-1"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div>
<!-- END Filter -->

<!-- BEGIN Debtors Table -->
<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="me-1"></iconify-icon>
            Daftar Data Debitur
        </h4>
        <div class="card-header-btn d-flex align-items-center gap-2 table-card-actions">
            <div class="dropdown">
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" id="btnExportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-upload me-1"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="btnExportDropdown">
                    <li>
                        <button type="button" class="dropdown-item" id="btnExportExcel">
                            <i class="fa fa-file-excel text-success me-2"></i> Export Excel (.xls)
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" id="btnExportPdf">
                            <i class="fa fa-file-pdf text-danger me-2"></i> Export PDF
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" id="btnExportCsv">
                            <i class="fa fa-file-csv text-info me-2"></i> Export CSV (.csv)
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fa fa-file-import text-success me-2"></i> Import Excel (.xlsx)
                        </button>
                    </li>
                </ul>
            </div>
            <button type="button" class="btn btn-theme btn-sm" id="btnTambahDebiturBaru" data-bs-toggle="modal" data-bs-target="#createDebtorModal">
                <i class="fa fa-plus me-1"></i> Tambah Debitur Baru
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php
        $branchMap = [];
        if (!empty($branches)) {
            foreach ($branches as $b) {
                $branchMap[$b['id']] = $b;
            }
        }
        $religions = [
            'Islam',
            'Protestan',
            'Katolik',
            'Buddha',
            'Hindu',
            'Konghucu',
            'Kepercayaan',
        ];
        ?>
        <div class="table-responsive">
            <table id="debtorsTable" class="table table-striped table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center fw-bold">NO</th>
                        <th style="width: 170px;" class="text-center fw-bold">NIK</th>
                        <th style="width: 130px;" class="text-center fw-bold">CIS ID</th>
                        <th class="text-center fw-bold">NAMA LENGKAP</th>
                        <th style="width: 120px;" class="text-center fw-bold">CIF ID</th>
                        <th style="width: 150px;" class="text-center fw-bold">CABANG</th>
                        <th style="width: 130px;" class="text-center fw-bold">GENDER / TTL</th>
                        <th style="width: 140px;" class="text-center fw-bold">TELEPON</th>
                        <th class="text-center fw-bold" style="width: 100px;">STATUS</th>
                        <th class="text-center fw-bold" style="width: 70px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($debtors as $index => $debtor) : 
                        $isActive = !empty($debtor['is_active']);
                        $cis = !empty($debtor['cis_id']) ? (string) $debtor['cis_id'] : '';
                        $cif = !empty($debtor['cif_id']) ? (string) $debtor['cif_id'] : '';
                        $nik = !empty($debtor['nik']) ? (string) $debtor['nik'] : '';
                        $phone = !empty($debtor['phone']) ? (string) $debtor['phone'] : '';
                        $gender = !empty($debtor['gender']) ? $debtor['gender'] : '-';
                        $bdate = !empty($debtor['birth_date']) ? $debtor['birth_date'] : '-';
                        $bCode = $debtor['branch_code'] ?? ($branchMap[$debtor['branch_id']]['code'] ?? '');
                        $bName = $debtor['branch_name'] ?? ($branchMap[$debtor['branch_id']]['name'] ?? '-');
                        $debtor['branch_code'] = $bCode;
                        $debtor['branch_name'] = $bName;
                    ?>
                        <tr data-debtor='<?= json_encode($debtor, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
                            <td class="text-center cell-index">
                                <span class="cell-no-badge"><?= (int) ($index + 1) ?></span>
                            </td>
                            <td>
                                <?php if ($nik !== '') : ?>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace"><?= esc($nik) ?></span>
                                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($nik) ?>" title="Salin NIK">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                <?php else : ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cis !== '') : ?>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="badge bg-info bg-opacity-15 text-info py-6px font-monospace"><?= esc($cis) ?></span>
                                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($cis) ?>" title="Salin CIS ID">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                <?php else : ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-15 text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:11px;">
                                        <?= esc(strtoupper(substr($debtor['full_name'] ?? 'U', 0, 1))) ?>
                                    </div>
                                    <span class="fw-semibold"><?= esc($debtor['full_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ($cif !== '') : ?>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="badge bg-teal bg-opacity-15 text-teal py-6px font-monospace"><?= esc($cif) ?></span>
                                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($cif) ?>" title="Salin CIF ID">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                <?php else : ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-column align-items-start gap-1">
                                    <span class="fw-semibold small"><?= esc($bName) ?></span>
                                    <?php if (! empty($bCode)) : ?>
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <span class="badge bg-secondary bg-opacity-15 text-secondary py-6px font-monospace"><?= esc($bCode) ?></span>
                                            <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($bCode) ?>" title="Salin Kode Cabang">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="font-size: 11.5px; line-height: 1.4;">
                                <div class="text-dark fw-medium"><?= esc($gender) ?></div>
                                <div class="text-muted small"><i class="fa fa-calendar me-1"></i><?= esc($bdate) ?></div>
                            </td>
                            <td>
                                <?php if ($phone !== '' && $phone !== '-') : ?>
                                    <div class="d-inline-flex align-items-center gap-1 font-monospace" style="font-size: 12px;">
                                        <span class="text-dark fw-medium"><?= esc($phone) ?></span>
                                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($phone) ?>" title="Salin Nomor Telepon">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                <?php else : ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($isActive) : ?>
                                    <span class="badge-status-active"><i class="fa-solid fa-circle-check"></i> Aktif</span>
                                <?php else : ?>
                                    <span class="badge-status-inactive"><i class="fa-solid fa-circle-xmark"></i> Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn-action-trigger" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Menu Aksi">
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end dropdown-action-menu shadow-lg">
                                        <li>
                                            <button type="button" class="dropdown-action-item btn-view-debtor">
                                                <span class="action-icon-circle action-icon-green"><i class="fa-solid fa-circle-info"></i></span>
                                                <div class="action-text-group"><span class="action-title">Lihat Detail</span><span class="action-desc">Informasi lengkap profil debitur</span></div>
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-action-item btn-edit-debtor">
                                                <span class="action-icon-circle action-icon-blue"><i class="fa-solid fa-pen-to-square"></i></span>
                                                <div class="action-text-group"><span class="action-title">Edit Debitur</span><span class="action-desc">Ubah data identitas & alamat</span></div>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     MODALS SECTION
     ============================================================ -->

<!-- Modal: Tambah Debitur Baru (Redesigned matching Master Produk layout) -->
<div class="modal fade product-modal-custom debtor-modal-custom" id="createDebtorModal" aria-labelledby="createDebtorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <form method="post" action="<?= site_url('master/debtors') ?>" class="modal-content product-modal-content border-0 shadow-2xl" id="createDebtorForm">
            <?= csrf_field() ?>
            <div class="modal-body p-0">
                <div class="product-modal-grid">
                    <!-- Sisi Kiri: Visual Banner -->
                    <div class="product-modal-sidebar" style="background-image: url('<?= base_url('assets/images/tambah-debitur.png') ?>');">
                        <div class="product-modal-sidebar-overlay"></div>
                        <div class="product-modal-sidebar-content">
                            <!-- Tips Box di Bawah Sisi Kiri -->
                            <div class="product-modal-tips-card mt-auto">
                                <div class="product-tips-header">
                                    <div class="product-tips-icon">
                                        <i class="fa-solid fa-lightbulb"></i>
                                    </div>
                                    <span class="product-tips-title">Tips Pendaftaran Debitur</span>
                                </div>
                                <ul class="product-tips-list">
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Pastikan 16 digit NIK valid sesuai KTP fisik.</span></li>
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Pilih unit kerja cabang pemroses yang tepat.</span></li>
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Debitur berstatus aktif dapat langsung digunakan untuk kalkulasi scoring.</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sisi Kanan: Form Konten -->
                    <div class="product-modal-main">
                        <!-- Header Form -->
                        <div class="product-modal-main-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="product-header-icon-box">
                                    <i class="fa-solid fa-user-plus text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="product-header-title mb-1" id="createDebtorModalLabel">Pendaftaran Debitur Baru</h5>
                                    <p class="product-header-subtitle mb-0">Lengkapi data profil debitur untuk database scoring kredit.</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <!-- Form Body Inputs -->
                        <div class="product-modal-main-body">
                            <div class="row g-3">
                                <!-- NIK -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_nik">Nomor Induk Kependudukan (NIK) <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-id-card modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="create_nik" name="nik"
                                               pattern="[0-9]{16}" maxlength="16" placeholder="16 digit NIK" required>
                                    </div>
                                    <div class="modal-input-hint">Contoh: 1271012304950001</div>
                                </div>

                                <!-- Nama Lengkap -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_full_name">Nama Lengkap Sesuai KTP <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-user modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="create_full_name" name="full_name"
                                               placeholder="Nama lengkap debitur" maxlength="150" required>
                                    </div>
                                </div>

                                <!-- Cabang -->
                                <div class="col-12">
                                    <label class="modal-form-label" for="create_branch_id">Cabang Pemroses <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-building modal-input-icon"></i>
                                        <select class="form-select modal-select2" id="create_branch_id" name="branch_id" data-placeholder="Pilih cabang..." required>
                                            <option value=""></option>
                                            <?php foreach ($branches as $branch) : ?>
                                                <option value="<?= esc($branch['id']) ?>">
                                                    <?= esc(!empty($branch['code']) ? '[' . $branch['code'] . '] ' . $branch['name'] : $branch['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Tanggal Lahir -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_birth_date">Tanggal Lahir</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-calendar modal-input-icon"></i>
                                        <input type="date" class="form-control modal-input-control" id="create_birth_date" name="birth_date">
                                    </div>
                                </div>

                                <!-- Tempat Lahir -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_birth_place">Tempat Lahir</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-map-pin modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="create_birth_place" name="birth_place" placeholder="Kota kelahiran">
                                    </div>
                                </div>

                                <!-- Telepon -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_phone">Nomor Telepon / WhatsApp</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-phone modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="create_phone" name="phone" placeholder="Contoh: 081234567890">
                                    </div>
                                </div>

                                <!-- Jenis Kelamin -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_gender">Jenis Kelamin</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-venus-mars modal-input-icon"></i>
                                        <select class="form-select modal-select2" id="create_gender" name="gender" data-placeholder="Pilih Gender">
                                            <option value=""></option>
                                            <option value="Laki-laki">Laki-laki</option>
                                            <option value="Perempuan">Perempuan</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Agama -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_religion">Agama</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-hands-praying modal-input-icon"></i>
                                        <select class="form-select modal-select2" id="create_religion" name="religion" data-placeholder="Pilih Agama">
                                            <option value=""></option>
                                            <?php foreach ($religions as $rel) : ?>
                                                <option value="<?= esc($rel) ?>"><?= esc($rel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- NPWP -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_npwp">NPWP</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-file-invoice modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="create_npwp" name="npwp" placeholder="Nomor Pokok Wajib Pajak">
                                    </div>
                                </div>

                                <!-- CIS ID -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_cis_id">CIS ID</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-barcode modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control bg-light cursor-not-allowed font-monospace fw-bold" id="create_cis_id" name="cis_id" placeholder="0226XXXXXX (Otomatis)" readonly maxlength="10">
                                    </div>
                                    <div class="modal-input-hint text-primary"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Dibuat otomatis oleh sistem (10 digit: 02 + Tahun + Nomor Urut)</div>
                                </div>

                                <!-- CIF ID -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_cif_id">CIF ID</label>
                                    <div class="modal-input-group">
                                        <div class="modal-input-wrap flex-grow-1">
                                            <i class="fa-solid fa-fingerprint modal-input-icon"></i>
                                            <input type="text" class="form-control modal-input-control input-with-btn font-monospace" id="create_cif_id" name="cif_id" placeholder="Customer Information File ID" maxlength="30">
                                        </div>
                                        <button type="button" class="btn btn-inquiry-cif" id="btnInquiryCifCreate" title="Inquiry CIF dari Core Banking">
                                            <i class="fa-solid fa-magnifying-glass me-1"></i> Inquiry
                                        </button>
                                    </div>
                                </div>

                                <!-- Alamat -->
                                <div class="col-12">
                                    <label class="modal-form-label" for="create_address">Alamat Lengkap</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-house-chimney modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="create_address" name="address" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota">
                                    </div>
                                </div>

                                <!-- Nama Ibu Kandung -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_mother_name">Nama Gadis Ibu Kandung</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-person-breastfeeding modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="create_mother_name" name="mother_name" placeholder="Nama ibu kandung untuk verifikasi">
                                    </div>
                                </div>

                                <!-- Debitur Aktif Toggle -->
                                <div class="col-12 col-md-6 d-flex align-items-center">
                                    <div class="modal-status-toggle-card w-100">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input modal-switch-input" type="checkbox" id="create_isActive" name="is_active" value="1" checked>
                                            <div class="modal-switch-text">
                                                <label class="modal-switch-label" for="create_isActive">Debitur Aktif</label>
                                                <div class="modal-switch-desc">Dapat langsung diproses dalam engine scoring kredit.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="product-modal-main-footer">
                            <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">
                                <i class="fa-solid fa-xmark me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn-modal-submit" id="btnSubmitCreateDebtor">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Debitur
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Debitur -->
<div class="modal fade product-modal-custom debtor-modal-custom" id="editDebtorModal" aria-labelledby="editDebtorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <form method="post" action="" class="modal-content product-modal-content border-0 shadow-2xl" id="editDebtorForm">
            <?= csrf_field() ?>
            <div class="modal-body p-0">
                <div class="product-modal-grid">
                    <!-- Sisi Kiri: Visual Banner -->
                    <div class="product-modal-sidebar" style="background-image: url('<?= base_url('assets/images/edit-debitur.png') ?>');">
                        <div class="product-modal-sidebar-overlay"></div>
                        <div class="product-modal-sidebar-content">
                            <!-- Tips Box di Bawah Sisi Kiri -->
                            <div class="product-modal-tips-card mt-auto">
                                <div class="product-tips-header">
                                    <div class="product-tips-icon">
                                        <i class="fa-solid fa-pen-nib"></i>
                                    </div>
                                    <span class="product-tips-title">Pembaruan Profil Debitur</span>
                                </div>
                                <ul class="product-tips-list">
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Pastikan perubahan NIK dan nama sesuai data kependudukan.</span></li>
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Perubahan cabang mempengaruhi delegasi persetujuan limit.</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sisi Kanan: Form Konten -->
                    <div class="product-modal-main">
                        <!-- Header Form -->
                        <div class="product-modal-main-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="product-header-icon-box">
                                    <i class="fa-solid fa-user-pen text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="product-header-title mb-1" id="editDebtorModalLabel">Edit Data Debitur</h5>
                                    <p class="product-header-subtitle mb-0">Perbarui informasi profil dan identitas debitur.</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <!-- Form Body Inputs -->
                        <div class="product-modal-main-body">
                            <div class="row g-3">
                                <!-- NIK -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_nik">Nomor Induk Kependudukan (NIK) <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-id-card modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="edit_nik" name="nik"
                                               pattern="[0-9]{16}" maxlength="16" required>
                                    </div>
                                </div>

                                <!-- Nama Lengkap -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_full_name">Nama Lengkap Sesuai KTP <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-user modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="edit_full_name" name="full_name" required maxlength="150">
                                    </div>
                                </div>

                                <!-- Cabang -->
                                <div class="col-12">
                                    <label class="modal-form-label" for="edit_branch_id">Cabang Pemroses <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-building modal-input-icon"></i>
                                        <select class="form-select modal-select2" id="edit_branch_id" name="branch_id" data-placeholder="Pilih cabang..." required>
                                            <option value=""></option>
                                            <?php foreach ($branches as $branch) : ?>
                                                <option value="<?= esc($branch['id']) ?>">
                                                    <?= esc(!empty($branch['code']) ? '[' . $branch['code'] . '] ' . $branch['name'] : $branch['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Tanggal Lahir -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_birth_date">Tanggal Lahir</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-calendar modal-input-icon"></i>
                                        <input type="date" class="form-control modal-input-control" id="edit_birth_date" name="birth_date">
                                    </div>
                                </div>

                                <!-- Tempat Lahir -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_birth_place">Tempat Lahir</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-map-pin modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="edit_birth_place" name="birth_place">
                                    </div>
                                </div>

                                <!-- Telepon -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_phone">Nomor Telepon</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-phone modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="edit_phone" name="phone">
                                    </div>
                                </div>

                                <!-- Jenis Kelamin -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_gender">Jenis Kelamin</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-venus-mars modal-input-icon"></i>
                                        <select class="form-select modal-select2" id="edit_gender" name="gender" data-placeholder="Pilih Gender">
                                            <option value=""></option>
                                            <option value="Laki-laki">Laki-laki</option>
                                            <option value="Perempuan">Perempuan</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Agama -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_religion">Agama</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-hands-praying modal-input-icon"></i>
                                        <select class="form-select modal-select2" id="edit_religion" name="religion" data-placeholder="Pilih Agama">
                                            <option value=""></option>
                                            <?php foreach ($religions as $rel) : ?>
                                                <option value="<?= esc($rel) ?>"><?= esc($rel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- NPWP -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_npwp">NPWP</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-file-invoice modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="edit_npwp" name="npwp">
                                    </div>
                                </div>

                                <!-- CIS ID -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_cis_id">CIS ID</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-barcode modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control bg-light cursor-not-allowed font-monospace fw-bold" id="edit_cis_id" name="cis_id" placeholder="Customer Information System ID" readonly maxlength="10">
                                    </div>
                                    <div class="modal-input-hint text-muted"><i class="fa-solid fa-lock me-1"></i> Terdaftar di sistem (10 digit)</div>
                                </div>

                                <!-- CIF ID -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_cif_id">CIF ID</label>
                                    <div class="modal-input-group">
                                        <div class="modal-input-wrap flex-grow-1">
                                            <i class="fa-solid fa-fingerprint modal-input-icon"></i>
                                            <input type="text" class="form-control modal-input-control input-with-btn font-monospace" id="edit_cif_id" name="cif_id" placeholder="Customer Information File ID" maxlength="30">
                                        </div>
                                        <button type="button" class="btn btn-inquiry-cif" id="btnInquiryCifEdit" title="Inquiry CIF dari Core Banking">
                                            <i class="fa-solid fa-magnifying-glass me-1"></i> Inquiry
                                        </button>
                                    </div>
                                </div>

                                <!-- Alamat -->
                                <div class="col-12">
                                    <label class="modal-form-label" for="edit_address">Alamat Lengkap</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-house-chimney modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="edit_address" name="address">
                                    </div>
                                </div>

                                <!-- Nama Ibu Kandung -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_mother_name">Nama Gadis Ibu Kandung</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-person-breastfeeding modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="edit_mother_name" name="mother_name">
                                    </div>
                                </div>

                                <!-- Debitur Aktif Toggle -->
                                <div class="col-12 col-md-6 d-flex align-items-center">
                                    <div class="modal-status-toggle-card w-100">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input modal-switch-input" type="checkbox" id="edit_isActive" name="is_active" value="1">
                                            <div class="modal-switch-text">
                                                <label class="modal-switch-label" for="edit_isActive">Debitur Aktif</label>
                                                <div class="modal-switch-desc">Dapat langsung diproses dalam engine scoring kredit.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="product-modal-main-footer">
                            <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">
                                <i class="fa-solid fa-xmark me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn-modal-submit" id="btnSubmitEditDebtor">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Detail Debitur (Matching mockup-detail-debitur.png) -->
<div class="modal fade detail-product-modal-custom detail-debtor-modal-custom" id="detailDebtorModal" aria-labelledby="detailDebtorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content detail-modal-content border-0 shadow-2xl">
            <!-- Modal Header -->
            <div class="debtor-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="debtor-header-icon-box">
                        <i class="fa-solid fa-address-card"></i>
                    </div>
                    <div>
                        <h5 class="debtor-header-title" id="detailDebtorModalLabel">Informasi Detail Debitur</h5>
                        <p class="debtor-header-subtitle mb-0">Rincian lengkap identitas dan profil debitur.</p>
                    </div>
                </div>
                <button type="button" class="btn-close-circle" data-bs-dismiss="modal" aria-label="Close" title="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-0">
                <!-- Hero Profile Card -->
                <div class="debtor-hero-card">
                    <!-- Left: Large Circular Avatar -->
                    <div class="debtor-avatar-wrapper">
                        <div class="debtor-avatar-circle">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </div>

                    <!-- Center: Debtor Info & Pills -->
                    <div class="debtor-hero-info">
                        <h3 class="debtor-hero-name" id="det_full_name">DESI RATNA SARI</h3>
                        
                        <!-- Row 1: NIK, CIS ID & Status -->
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            <div class="debtor-hero-pill">
                                <i class="fa-regular fa-address-card text-muted"></i>
                                <span>NIK <strong id="det_nik">-</strong></span>
                                <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline ms-1" id="btnCopyNik" data-clipboard="" title="Salin NIK">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                            <div class="debtor-hero-pill">
                                <i class="fa-solid fa-fingerprint text-muted"></i>
                                <span>CIS <strong id="det_cis_hero">-</strong></span>
                                <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline ms-1" id="btnCopyCisHero" data-clipboard="" title="Salin CIS ID">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                            <div id="det_status_badge">
                                <span class="debtor-hero-pill-status pill-active"><i class="fa-solid fa-circle-check"></i> Aktif</span>
                            </div>
                        </div>

                        <!-- Row 2: Cabang & Tipe -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="debtor-hero-pill">
                                <i class="fa-solid fa-building text-primary"></i>
                                <span id="det_hero_branch">[001] KANTOR PUSAT</span>
                                <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline ms-1" id="btnCopyBranchCode" data-clipboard="" title="Salin Kode Cabang" hidden>
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                            <div class="debtor-hero-pill">
                                <i class="fa-solid fa-user text-primary"></i>
                                <span>Individu</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Decorative 3D Building Art (Matches Mockup) -->
                    <div class="debtor-hero-building-art">
                        <svg width="210" height="135" viewBox="0 0 210 135" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M120 135C150 120 180 125 210 130V135H120Z" fill="#E0F2FE" fill-opacity="0.6"/>
                            <path d="M152 42L182 54V135H152V42Z" fill="#93C5FD"/>
                            <path d="M128 32L152 42V135H128V32Z" fill="#BFDBFE"/>
                            <path d="M128 32L158 20L182 54L152 42L128 32Z" fill="#DBEAFE"/>
                            <path d="M122 135V24L82 8V135H122Z" fill="#EFF6FF"/>
                            <path d="M122 24L158 38V135H122V24Z" fill="#BAE6FD"/>
                            <path d="M82 8L118 0L158 38L122 24L82 8Z" fill="#FFFFFF"/>
                            <rect x="90" y="30" width="8" height="95" rx="2" fill="#BAE6FD"/>
                            <rect x="105" y="35" width="8" height="90" rx="2" fill="#BAE6FD"/>
                            <rect x="128" y="44" width="7" height="85" rx="1.5" fill="#7DD3FC"/>
                            <rect x="140" y="49" width="7" height="80" rx="1.5" fill="#7DD3FC"/>
                            <path d="M52 55L82 42V135H52V55Z" fill="#F0F9FF"/>
                            <path d="M82 42L102 50V135H82V42Z" fill="#E0F2FE"/>
                            <rect x="58" y="65" width="7" height="60" rx="1.5" fill="#BAE6FD"/>
                            <rect x="69" y="62" width="7" height="63" rx="1.5" fill="#BAE6FD"/>
                            <circle cx="48" cy="130" r="14" fill="#34D399"/>
                            <circle cx="78" cy="132" r="11" fill="#10B981"/>
                            <circle cx="178" cy="128" r="15" fill="#34D399"/>
                            <circle cx="198" cy="132" r="12" fill="#059669"/>
                        </svg>
                    </div>
                </div>

                <!-- Section 01: Informasi Identitas & Administrasi -->
                <div class="debtor-section-header">
                    <div class="debtor-section-num debtor-num-blue">01</div>
                    <div class="debtor-section-text">
                        <h6 class="debtor-section-title">Informasi Identitas & Administrasi</h6>
                        <span class="debtor-section-desc">Data administrasi utama debitur.</span>
                    </div>
                    <div class="debtor-section-line"></div>
                </div>

                <div class="row g-3">
                    <!-- Cabang Pemroses -->
                    <div class="col-12 col-md-4">
                        <div class="debtor-attr-card card-cabang">
                            <div class="debtor-attr-icon icon-cabang">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div class="debtor-attr-text">
                                <span class="debtor-attr-label">Cabang Pemroses</span>
                                <span class="debtor-attr-val" id="det_branch_name">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Nomor Telepon -->
                    <div class="col-12 col-md-4">
                        <div class="debtor-attr-card card-telepon">
                            <div class="debtor-attr-icon icon-telepon">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div class="debtor-attr-text">
                                <span class="debtor-attr-label">Nomor Telepon</span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="debtor-attr-val" id="det_phone">-</span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" id="btnCopyPhone" data-clipboard="" title="Salin Nomor Telepon">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CIS ID / CIF ID -->
                    <div class="col-12 col-md-4">
                        <div class="debtor-attr-card card-ciscif">
                            <div class="debtor-attr-icon icon-ciscif">
                                <i class="fa-solid fa-fingerprint"></i>
                            </div>
                            <div class="debtor-attr-text w-100">
                                <span class="debtor-attr-label">CIS ID / CIF ID</span>
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small text-muted">CIS</span>
                                        <span class="debtor-attr-val font-monospace" id="det_cis">-</span>
                                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" id="btnCopyCis" data-clipboard="" title="Salin CIS ID">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small text-muted">CIF</span>
                                        <span class="debtor-attr-val font-monospace" id="det_cif">-</span>
                                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" id="btnCopyCif" data-clipboard="" title="Salin CIF ID">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <span class="debtor-attr-val d-none" id="det_ciscif">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 02: Data Pribadi -->
                <div class="debtor-section-header">
                    <div class="debtor-section-num debtor-num-purple">02</div>
                    <div class="debtor-section-text">
                        <h6 class="debtor-section-title">Data Pribadi</h6>
                        <span class="debtor-section-desc">Informasi data diri debitur.</span>
                    </div>
                    <div class="debtor-section-line"></div>
                </div>

                <div class="row g-3">
                    <!-- Tempat, Tanggal Lahir & Jenis Kelamin -->
                    <div class="col-12 col-md-4">
                        <div class="debtor-attr-card card-ttl">
                            <div class="debtor-attr-icon icon-ttl">
                                <i class="fa-regular fa-calendar-days"></i>
                            </div>
                            <div class="debtor-attr-text">
                                <span class="debtor-attr-label">Tempat, Tanggal Lahir & Jenis Kelamin</span>
                                <span class="debtor-attr-val" id="det_birth_info">-</span>
                                <div id="det_gender_container"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Ibu Kandung -->
                    <div class="col-12 col-md-4">
                        <div class="debtor-attr-card card-ibu">
                            <div class="debtor-attr-icon icon-ibu">
                                <i class="fa-solid fa-person-breastfeeding"></i>
                            </div>
                            <div class="debtor-attr-text">
                                <span class="debtor-attr-label">Ibu Kandung</span>
                                <span class="debtor-attr-val" id="det_mother_name">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Agama -->
                    <div class="col-12 col-md-4">
                        <div class="debtor-attr-card card-agama">
                            <div class="debtor-attr-icon icon-agama">
                                <i class="fa-solid fa-place-of-worship"></i>
                            </div>
                            <div class="debtor-attr-text">
                                <span class="debtor-attr-label">Agama</span>
                                <span class="debtor-attr-val" id="det_religion">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 03: Informasi Tambahan -->
                <div class="debtor-section-header">
                    <div class="debtor-section-num debtor-num-green">03</div>
                    <div class="debtor-section-text">
                        <h6 class="debtor-section-title">Informasi Tambahan</h6>
                        <span class="debtor-section-desc">Data pendukung lainnya.</span>
                    </div>
                    <div class="debtor-section-line"></div>
                </div>

                <div class="row g-3 mb-2">
                    <!-- NPWP -->
                    <div class="col-12 col-md-5">
                        <div class="debtor-attr-card card-npwp">
                            <div class="debtor-attr-icon icon-npwp">
                                <i class="fa-solid fa-file-invoice"></i>
                            </div>
                            <div class="debtor-attr-text">
                                <span class="debtor-attr-label">NPWP</span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="debtor-attr-val" id="det_npwp">-</span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" id="btnCopyNpwp" data-clipboard="" title="Salin NPWP">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div class="col-12 col-md-7">
                        <div class="debtor-attr-card card-alamat">
                            <div class="debtor-attr-icon icon-alamat">
                                <i class="fa-solid fa-house"></i>
                            </div>
                            <div class="debtor-attr-text">
                                <span class="debtor-attr-label">Alamat Lengkap</span>
                                <span class="debtor-attr-val" id="det_address">-</span>
                            </div>
                            <!-- 3D Folded Map & Pin Art -->
                            <div class="debtor-map-pin-art">
                                <svg width="56" height="40" viewBox="0 0 56 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 14L18 8L34 16L48 10V32L34 38L18 30L4 36V14Z" fill="#E2E8F0" stroke="#CBD5E1" stroke-width="1.5" stroke-linejoin="round"/>
                                    <path d="M18 8L34 16V38L18 30V8Z" fill="#F1F5F9"/>
                                    <path d="M4 14L18 8V30L4 36V14Z" fill="#E2E8F0"/>
                                    <path d="M34 16L48 10V32L34 38V16Z" fill="#CBD5E1"/>
                                    <line x1="18" y1="8" x2="18" y2="30" stroke="#94A3B8" stroke-width="1"/>
                                    <line x1="34" y1="16" x2="34" y2="38" stroke="#94A3B8" stroke-width="1"/>
                                    <g filter="drop-shadow(0 3px 5px rgba(239, 68, 68, 0.4))">
                                        <path d="M34 6C30.6863 6 28 8.68629 28 12C28 16.5 34 22 34 22C34 22 40 16.5 40 12C40 8.68629 37.3137 6 34 6Z" fill="#EF4444"/>
                                        <circle cx="34" cy="12" r="2.5" fill="#FFFFFF"/>
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Import Excel -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title d-flex align-items-center gap-2" id="importModalLabel" style="font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-file-excel text-success" style="font-size: 20px;"></i>
                    <span>Import Data Debitur Excel</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="importForm" method="post" action="<?= site_url('master/debtors/import') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 d-flex gap-2" style="background: #eff6ff; border-radius: 12px; font-size: 12px;">
                        <i class="fa-solid fa-circle-info text-primary mt-1"></i>
                        <div>
                            <strong>Ketentuan Format File:</strong>
                            <p class="mb-0 mt-1">Kolom wajib pada template: <strong>NIK</strong> dan <strong>Nama</strong>. Data NIK yang sudah ada di database akan diperbarui namanya secara otomatis.</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="importFile" style="font-size: 13px;">Pilih File Excel (.xlsx / .xls) <span class="text-danger">*</span></label>
                        <input id="importFile" name="file" type="file" accept=".xlsx, .xls" class="form-control" style="border-radius: 10px; font-size: 13px;" required>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 600; border-radius: 8px;">Batal</button>
                    <button type="submit" id="btnImport" class="btn btn-success" style="font-size: 13px; font-weight: 600; border-radius: 8px;">
                        <i class="fa-solid fa-upload me-1"></i> Unggah & Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // 1. Initialize Select2 on Filter and Modals
    App.initSelect2('#filterBranchDebtor', {
        placeholder: 'Semua Cabang',
        allowClear: true,
        width: '100%'
    });
    App.initSelect2('#filterStatusDebtor', {
        placeholder: 'Semua Status',
        allowClear: true,
        width: '100%'
    });
    App.initSelect2('#filterGenderDebtor', {
        placeholder: 'Semua Gender',
        allowClear: true,
        width: '100%'
    });

    $('#createDebtorModal').on('show.bs.modal', function () {
        // Fetch predicted next CIS ID from server (10 digit format)
        $.get('<?= site_url('master/debtors/next-cis-id') ?>', function (res) {
            if (res && res.result && res.result.cis_id) {
                $('#create_cis_id').val(res.result.cis_id);
            }
        });
    });

    $('#createDebtorModal').on('shown.bs.modal', function () {
        App.initSelect2InModal('#create_branch_id', '#createDebtorModal', { placeholder: 'Pilih cabang...', width: '100%' });
        App.initSelect2InModal('#create_gender', '#createDebtorModal', { placeholder: 'Pilih Gender', minimumResultsForSearch: Infinity, width: '100%' });
        App.initSelect2InModal('#create_religion', '#createDebtorModal', { placeholder: 'Pilih Agama', minimumResultsForSearch: Infinity, width: '100%' });
    });

    $('#editDebtorModal').on('shown.bs.modal', function () {
        App.initSelect2InModal('#edit_branch_id', '#editDebtorModal', { placeholder: 'Pilih cabang...', width: '100%' });
        App.initSelect2InModal('#edit_gender', '#editDebtorModal', { placeholder: 'Pilih Gender', minimumResultsForSearch: Infinity, width: '100%' });
        App.initSelect2InModal('#edit_religion', '#editDebtorModal', { placeholder: 'Pilih Agama', minimumResultsForSearch: Infinity, width: '100%' });
    });

    // CIF Inquiry Function
    function handleCifInquiry(cifInputSelector, btnSelector, isEdit) {
        var cif = $(cifInputSelector).val().trim();
        if (!cif) {
            App.toastWarning('Silakan masukkan nomor CIF terlebih dahulu.');
            $(cifInputSelector).focus();
            return;
        }
        var $btn = $(btnSelector);
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Mencari...');

        $.get('<?= site_url('master/debtors/inquiry-cif') ?>/' + encodeURIComponent(cif))
            .done(function (res) {
                if (res && res.result && res.result.found && res.result.debtor) {
                    var d = res.result.debtor;
                    var prefix = isEdit ? '#edit_' : '#create_';
                    if (d.full_name) $(prefix + 'full_name').val(d.full_name);
                    if (d.nik) $(prefix + 'nik').val(d.nik);
                    if (d.phone) $(prefix + 'phone').val(d.phone);
                    if (d.birth_date) $(prefix + 'birth_date').val(d.birth_date);
                    if (d.birth_place) $(prefix + 'birth_place').val(d.birth_place);
                    if (d.mother_name) $(prefix + 'mother_name').val(d.mother_name);
                    if (d.npwp) $(prefix + 'npwp').val(d.npwp);
                    if (d.address) $(prefix + 'address').val(d.address);
                    if (d.gender) $(prefix + 'gender').val(d.gender).trigger('change');
                    if (d.religion) $(prefix + 'religion').val(d.religion).trigger('change');
                    if (d.branch_id) $(prefix + 'branch_id').val(d.branch_id).trigger('change');
                    if (d.cis_id) $(prefix + 'cis_id').val(d.cis_id);

                    if (res.result.source === 'gateway') {
                        App.toastSuccess('Data nasabah CIF ' + cif + ' berhasil ditarik dari Core Banking Gateway! (' + d.full_name + ')');
                    } else {
                        App.toastSuccess('Data nasabah dengan CIF ' + cif + ' ditemukan di database lokal (' + d.full_name + ')');
                    }
                } else {
                    var infoMsg = (res && res.message) ? res.message : ('CIF ' + cif + ' belum terdaftar di sistem. Siap didaftarkan sebagai debitur baru.');
                    App.toastInfo(infoMsg);
                }
            })
            .fail(function (xhr) {
                var msg = 'Gagal melakukan inquiry CIF.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                App.toastError(msg);
            })
            .always(function () {
                $btn.prop('disabled', false).html(originalHtml);
            });
    }

    $('#btnInquiryCifCreate').on('click', function () {
        handleCifInquiry('#create_cif_id', '#btnInquiryCifCreate', false);
    });

    $('#btnInquiryCifEdit').on('click', function () {
        handleCifInquiry('#edit_cif_id', '#btnInquiryCifEdit', true);
    });

    // Enter key triggers inquiry
    $('#create_cif_id').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnInquiryCifCreate').click();
        }
    });

    $('#edit_cif_id').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnInquiryCifEdit').click();
        }
    });

    // 2. Filter Collapse Button Animation
    $('#filterCollapse').on('show.bs.collapse', function () {
        $('#filterToggleText').text('Sembunyikan Filter');
        $('#filterToggleIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    });

    $('#filterCollapse').on('hide.bs.collapse', function () {
        $('#filterToggleText').text('Tampilkan Filter');
        $('#filterToggleIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    });

    // 3. Initialize DataTables
    var dataTable = $('#debtorsTable').DataTable({
        pageLength: 5,
        lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
        order: [[3, 'asc']], // Order by Full Name ASC
        autoWidth: false,
        responsive: true,
        dom: "<'products-table-wrapper't>" +
             "<'products-table-footer d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 p-3'<'table-footer-left text-muted small'i><'table-footer-center d-flex align-items-center gap-2'l><'table-footer-right'p>>",
        language: {
            processing: '<div class="d-flex align-items-center gap-2 py-2"><span class="spinner-border spinner-border-sm text-primary"></span> <span>Memuat data...</span></div>',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
            infoEmpty: 'Menampilkan 0 dari 0 data',
            infoFiltered: '(disaring dari total _MAX_ data)',
            lengthMenu: 'Tampilkan _MENU_ data per halaman',
            zeroRecords: '<div class="empty-state py-5"><i class="fa-solid fa-users-slash fa-2x text-muted mb-2 opacity-50"></i><p class="mb-0 text-muted">Tidak ada data debitur yang sesuai.</p></div>',
            emptyTable: '<div class="empty-state py-5"><i class="fa-solid fa-users fa-2x text-muted mb-2 opacity-50"></i><p class="mb-0 text-muted">Belum ada data debitur.</p></div>',
            paginate: {
                first:    '<i class="fa-solid fa-angles-left"></i>',
                previous: '<i class="fa-solid fa-angle-left"></i>',
                next:     '<i class="fa-solid fa-angle-right"></i>',
                last:     '<i class="fa-solid fa-angles-right"></i>'
            }
        },
        columnDefs: [
            { orderable: false, targets: [0, 9] },
            { searchable: false, targets: [0, 9] }
        ],
        drawCallback: function (settings) {
            var api = this.api();
            var rows = api.rows({ page: 'current' }).nodes();
            var subtleClasses = ['row-subtle-blue', 'row-subtle-green', 'row-subtle-purple', 'row-subtle-orange', 'row-subtle-red', 'row-subtle-cyan'];
            
            $(rows).removeClass('row-subtle-blue row-subtle-green row-subtle-purple row-subtle-orange row-subtle-red row-subtle-cyan');
            
            rows.each(function (row, idx) {
                $(row).addClass(subtleClasses[idx % subtleClasses.length]);
                var displayIndex = api.page.info().start + idx + 1;
                $(row).find('.cell-no-badge').text(displayIndex);
            });
        }
    });

    // 4. Custom Filter Handler
    function applyDebtorFilter() {
        var keyword = $('#filterSearchDebtor').val().trim();
        var branch  = $('#filterBranchDebtor').val();
        var status  = $('#filterStatusDebtor').val();
        var gender  = $('#filterGenderDebtor').val();

        dataTable.search(keyword); // Global search on NIK, name, etc.

        // Column 5: Cabang
        if (branch) {
            dataTable.column(5).search(branch, true, false);
        } else {
            dataTable.column(5).search('');
        }

        // Column 8: Status
        if (status === 'Aktif') {
            dataTable.column(8).search('(^|\\s)Aktif(\\s|$)', true, false);
        } else if (status === 'Nonaktif') {
            dataTable.column(8).search('Nonaktif', true, false);
        } else {
            dataTable.column(8).search('');
        }

        // Column 6: Gender
        if (gender) {
            dataTable.column(6).search(gender, true, false);
        } else {
            dataTable.column(6).search('');
        }

        dataTable.draw();
    }

    $('#btnApplyFilter').on('click', function () {
        applyDebtorFilter();
        App.toastSuccess('Filter debitur berhasil diterapkan.');
    });

    $('#filterSearchDebtor').on('keyup', function (e) {
        if (e.key === 'Enter') {
            applyDebtorFilter();
        }
    });

    $('#btnResetFilter').on('click', function () {
        $('#filterSearchDebtor').val('');
        $('#filterBranchDebtor').val('').trigger('change');
        $('#filterStatusDebtor').val('').trigger('change');
        $('#filterGenderDebtor').val('').trigger('change');

        dataTable.search('').columns().search('').draw();
        App.toastSuccess('Filter berhasil direset.');
    });

    // 5. Detail Debtor Handler
    $('#debtorsTable tbody').on('click', '.btn-view-debtor', function () {
        var tr = $(this).closest('tr');
        var data = tr.data('debtor');
        if (!data) return;

        var fullName = (data.full_name || '-').toUpperCase();
        $('#det_full_name').text(fullName);
        $('#det_nik').text(data.nik || '-');
        $('#btnCopyNik').attr('data-clipboard', data.nik || '').prop('hidden', !data.nik);
        $('#det_cis_hero').text(data.cis_id || '-');
        $('#btnCopyCisHero').attr('data-clipboard', data.cis_id || '').prop('hidden', !data.cis_id);
        
        var isActive = data.is_active == 1 || data.is_active === true;
        var badgeHtml = isActive 
            ? '<span class="debtor-hero-pill-status pill-active"><i class="fa-solid fa-circle-check"></i> Aktif</span>'
            : '<span class="debtor-hero-pill-status pill-inactive"><i class="fa-solid fa-circle-xmark"></i> Nonaktif</span>';
        $('#det_status_badge').html(badgeHtml);

        var branchText = (data.branch_code ? '[' + data.branch_code + '] ' : '') + (data.branch_name || '-');
        $('#det_hero_branch').text(branchText);
        $('#det_branch_name').text(branchText);
        $('#btnCopyBranchCode').attr('data-clipboard', data.branch_code || '').prop('hidden', !data.branch_code);
        $('#det_phone').text(data.phone || '-');
        $('#btnCopyPhone').attr('data-clipboard', data.phone || '').prop('hidden', !data.phone);
        
        var cis = data.cis_id || '-';
        var cif = data.cif_id || '-';
        $('#det_cis').text(cis);
        $('#det_cif').text(cif);
        $('#det_ciscif').text(cis + ' / ' + cif);
        $('#btnCopyCis').attr('data-clipboard', data.cis_id || '').prop('hidden', !data.cis_id);
        $('#btnCopyCif').attr('data-clipboard', data.cif_id || '').prop('hidden', !data.cif_id);

        var birthPlace = data.birth_place ? data.birth_place.toUpperCase() : '';
        var birthDate = data.birth_date || '';
        var birthText = (birthPlace && birthDate) ? birthPlace + ', ' + birthDate : (birthPlace || birthDate || '-');
        $('#det_birth_info').text(birthText);

        var gender = (data.gender || '').toLowerCase();
        var genderHtml = '';
        if (gender.indexOf('perempuan') !== -1 || gender === 'female') {
            genderHtml = '<span class="badge-gender-pill badge-gender-female"><i class="fa-solid fa-venus"></i> Perempuan</span>';
        } else if (gender.indexOf('laki') !== -1 || gender === 'male') {
            genderHtml = '<span class="badge-gender-pill badge-gender-male"><i class="fa-solid fa-mars"></i> Laki-laki</span>';
        } else if (data.gender) {
            genderHtml = '<span class="badge-gender-pill badge-gender-other"><i class="fa-solid fa-venus-mars"></i> ' + data.gender + '</span>';
        } else {
            genderHtml = '';
        }
        $('#det_gender_container').html(genderHtml);

        $('#det_mother_name').text((data.mother_name || '-').toUpperCase());
        $('#det_religion').text(data.religion || '-');

        $('#det_npwp').text(data.npwp || '-');
        $('#btnCopyNpwp').attr('data-clipboard', data.npwp || '').prop('hidden', !data.npwp);
        $('#det_address').text((data.address || '-').toUpperCase());

        var modal = new bootstrap.Modal(document.getElementById('detailDebtorModal'));
        modal.show();
    });

    // 6. Edit Debtor Handler
    $('#debtorsTable tbody').on('click', '.btn-edit-debtor', function () {
        var tr = $(this).closest('tr');
        var data = tr.data('debtor');
        if (!data) return;

        var form = document.getElementById('editDebtorForm');
        form.action = '<?= site_url('master/debtors') ?>/' + data.id;

        $('#edit_nik').val(data.nik || '');
        $('#edit_full_name').val(data.full_name || '');
        $('#edit_branch_id').val(data.branch_id || '').trigger('change');
        $('#edit_birth_date').val(data.birth_date || '');
        $('#edit_birth_place').val(data.birth_place || '');
        $('#edit_phone').val(data.phone || '');
        $('#edit_gender').val(data.gender || '').trigger('change');
        $('#edit_religion').val(data.religion || '').trigger('change');
        $('#edit_npwp').val(data.npwp || '');
        $('#edit_cis_id').val(data.cis_id || '');
        $('#edit_cif_id').val(data.cif_id || '');
        $('#edit_address').val(data.address || '');
        $('#edit_mother_name').val(data.mother_name || '');
        
        var isActive = data.is_active == 1 || data.is_active === true;
        $('#edit_isActive').prop('checked', isActive);

        var modal = new bootstrap.Modal(document.getElementById('editDebtorModal'));
        modal.show();
    });

    // 7. Form Submit Handlers
    $('#createDebtorForm').on('submit', function () {
        App.btnLoading($('#btnSubmitCreateDebtor'), 'Menyimpan...');
    });

    $('#editDebtorForm').on('submit', function () {
        App.btnLoading($('#btnSubmitEditDebtor'), 'Menyimpan...');
    });

    $('#importForm').on('submit', function () {
        App.btnLoading($('#btnImport'), 'Mengunggah...');
    });

    // 8. Export Helpers (Excel / PDF / CSV)
    function exportDebtorTableToCSV(filename) {
        var csv = [];
        var rows = document.querySelectorAll("#debtorsTable tr");
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");
            // Exclude action column
            for (var j = 0; j < cols.length - 1; j++) {
                var text = cols[j].innerText.replace(/"/g, '""').trim();
                row.push('"' + text + '"');
            }
            csv.push(row.join(","));
        }
        var csvFile = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
        var downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }

    $('#btnExportCsv').on('click', function () {
        exportDebtorTableToCSV('daftar_debitur.csv');
        App.toastSuccess('Data debitur berhasil diekspor ke CSV.');
    });

    $('#btnExportExcel').on('click', function () {
        exportDebtorTableToCSV('daftar_debitur.xls');
        App.toastSuccess('Data debitur berhasil diekspor ke Excel.');
    });

    $('#btnExportPdf').on('click', function () {
        window.print();
    });
});
</script>

<?= view('partials/shell_end') ?>