<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Konfigurasi Parameter']) ?>

<?php
$parameters = $parameters ?? [];
$totalParams = count($parameters);
$totalSub = (int) ($totalSubParams ?? 0);
$avgSub = $totalParams > 0 ? round($totalSub / $totalParams, 1) : 0;
?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- 1. Page Title Header Card (Mockup Redesign Standard - Source of Truth: Produk) -->
<div class="module-header module-header-card">
    <div class="module-header-panel-wrap">
        <div class="module-header-content">
            <div class="module-icon-cube">
                <i class="fa-solid fa-sliders"></i>
            </div>
            <div class="module-header-divider"></div>
            <div class="module-title-box text-start">
                <div class="d-flex align-items-center justify-content-start gap-2 flex-wrap text-start">
                    <h2 class="module-title text-start mb-0">Konfigurasi Parameter</h2>
                    <span class="module-badge-pill"><?= $totalParams ?> Parameter</span>
                </div>
                <p class="module-subtitle text-start mb-0">Kelola master parameter dan sub parameter dinamis untuk engine perhitungan scoring kredit.</p>
            </div>
        </div>
    </div>
</div>

<!-- 2. KPI / Summary Metric Cards (Matching Produk Design Standard) -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Parameter -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white d-flex flex-row align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center rounded-3 text-white" style="width: 52px; height: 52px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.28);">
                <i class="fa-solid fa-sliders fa-lg"></i>
            </div>
            <div>
                <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Total Parameter</span>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold mb-0 text-dark"><?= $totalParams ?></h3>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 small" style="font-size: 11px;">Master</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Sub Parameter -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white d-flex flex-row align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center rounded-3 text-white" style="width: 52px; height: 52px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.28);">
                <i class="fa-solid fa-layer-group fa-lg"></i>
            </div>
            <div>
                <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Total Sub Parameter</span>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold mb-0 text-dark"><?= $totalSub ?></h3>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 small" style="font-size: 11px;">Aktif</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Rata-rata Sub per Parameter -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white d-flex flex-row align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center rounded-3 text-white" style="width: 52px; height: 52px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); box-shadow: 0 4px 12px rgba(139, 92, 246, 0.28);">
                <i class="fa-solid fa-calculator fa-lg"></i>
            </div>
            <div>
                <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Rata-Rata Sub / Param</span>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold mb-0 text-dark"><?= $avgSub ?></h3>
                    <span class="badge rounded-pill px-2 py-1 small" style="font-size: 11px; background: #ede9fe; color: #6d28d9;">Kategori</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. Filter Data Card (Standard Produk) -->
<div class="filter-card">
    <div class="filter-card-header">
        <div class="filter-header-left">
            <div class="filter-icon-badge">
                <i class="fa-solid fa-filter"></i>
            </div>
            <div>
                <h6 class="filter-title">Filter Data</h6>
                <p class="filter-subtitle">Gunakan filter berikut untuk mencari parameter scoring dengan cepat.</p>
            </div>
        </div>
        <div>
            <button type="button" class="filter-toggle-btn" id="btnToggleFilter" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                <span id="filterToggleText">Sembunyikan Filter</span>
                <i class="fa-solid fa-chevron-up" id="filterToggleIcon"></i>
            </button>
        </div>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="filter-card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-5">
                    <label class="filter-form-label" for="filterSearchParam">Search Parameter</label>
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-magnifying-glass filter-input-icon"></i>
                        <input type="text" class="form-control" id="filterSearchParam" placeholder="Cari nama parameter atau sub parameter..." style="padding-left: 36px !important;">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="filter-form-label" for="filterParamName">Pilih Kategori Parameter</label>
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-tag filter-input-icon"></i>
                        <select class="form-select" id="filterParamName" style="padding-left: 36px !important;">
                            <option value="">Semua Parameter</option>
                            <?php foreach ($parameters as $p) : ?>
                                <option value="<?= esc($p['name']) ?>"><?= esc($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-lg-3 d-flex align-items-end">
                    <div class="filter-actions w-100">
                        <button type="button" class="btn-apply-filter flex-grow-1" id="btnApplyFilter">
                            <i class="fa-solid fa-magnifying-glass"></i> Terapkan
                        </button>
                        <button type="button" class="btn-reset-filter" id="btnResetFilter">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. Table Card (Standard Produk) -->
<div class="table-card">
    <div class="table-card-header">
        <h5 class="table-card-title">
            <i class="fa-solid fa-list-check"></i> Daftar Konfigurasi Parameter
        </h5>
        <div class="table-card-actions d-flex align-items-center gap-2">
            <!-- Dropdown Export Button -->
            <div class="dropdown">
                <button type="button" class="btn-export dropdown-toggle" id="btnExportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border" aria-labelledby="btnExportDropdown" style="font-size: 12px; min-width: 170px; border-radius: 10px;">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" onclick="window.print()">
                            <i class="fa-solid fa-file-pdf text-danger" style="font-size: 13px; width: 16px;"></i>
                            <span>Export PDF / Cetak</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tambah Parameter Baru Action Button (Green / Hijau) -->
            <button type="button" class="btn btn-success-gradient" id="btnTambahParamTabel" data-bs-toggle="modal" data-bs-target="#parameterModal">
                <i class="fa-solid fa-plus"></i> Tambah Parameter Baru
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="parametersTable" class="table table-striped table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width: 55px;" class="text-center fw-bold">NO</th>
                        <th style="width: 140px;" class="text-center fw-bold">ID PARAM</th>
                        <th class="fw-bold" style="min-width: 180px;">NAMA PARAMETER</th>
                        <th style="width: 150px;" class="text-center fw-bold">SUB PARAMETER</th>
                        <th class="fw-bold" style="min-width: 320px;">DETAIL SUB PARAMETER (KODE, DESKRIPSI, BOBOT × NILAI)</th>
                        <th class="text-center fw-bold" style="width: 110px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parameters)) : ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-sliders fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6 class="fw-bold">Belum Ada Konfigurasi Parameter</h6>
                            <p class="small mb-0">Klik tombol <strong>Tambah Parameter Baru</strong> di atas untuk membuat konfigurasi pertama.</p>
                        </td>
                    </tr>
                    <?php else : ?>
                        <?php 
                        $subtleClasses = ['row-subtle-blue', 'row-subtle-green', 'row-subtle-purple', 'row-subtle-orange', 'row-subtle-red', 'row-subtle-cyan'];
                        ?>
                        <?php foreach ($parameters as $idx => $param) : ?>
                            <?php 
                            $subs = $param['sub_parameters'] ?? []; 
                            $paramJson = json_encode($param, JSON_HEX_APOS | JSON_HEX_QUOT);
                            $rowClass = $subtleClasses[$idx % count($subtleClasses)];
                            ?>
                            <tr class="param-row <?= $rowClass ?>" data-name="<?= esc(strtolower($param['name'])) ?>">
                                <td class="text-center align-middle cell-index">
                                    <span class="cell-no-badge"><?= $idx + 1 ?></span>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-light text-primary border px-2 py-1 rounded-pill fw-bold" style="font-size: 11px;">
                                        <i class="fa-solid fa-hashtag me-1"></i>PRM-<?= str_pad((string)$param['id'], 3, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <div class="fw-bold text-dark fs-6"><?= esc($param['name']) ?></div>
                                    <small class="text-muted"><i class="fa-solid fa-check-double text-success me-1"></i>Master Scoring</small>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-bold" style="font-size: 11.5px;">
                                        <?= count($subs) ?> Sub Item
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <!-- List Kebawah & Format Integer Saja -->
                                    <div class="d-flex flex-column gap-1.5 py-1">
                                        <?php foreach ($subs as $sub) : ?>
                                            <?php 
                                            $w = (int) round((float) ($sub['weight'] ?? 0));
                                            $v = (int) round((float) ($sub['value'] ?? 0));
                                            $t = (int) round((float) ($sub['total'] ?? ($w * $v)));
                                            ?>
                                            <div class="d-flex align-items-center gap-2 text-start">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-0.5 rounded-pill" style="font-size: 11px; min-width: 28px; text-align: center;">
                                                    <?= esc($sub['code']) ?>
                                                </span>
                                                <span class="fw-semibold text-dark small" style="font-size: 12px;">
                                                    <?= esc($sub['description']) ?>
                                                </span>
                                                <span class="text-muted small" style="font-size: 11.5px;">
                                                    (<?= $w ?> × <?= $v ?> = <strong class="text-success"><?= $t ?></strong>)
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <!-- Button Dropdown Aksi (Persis Seperti Produk) -->
                                    <div class="dropdown">
                                        <button class="btn-action-trigger" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Menu Aksi">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end dropdown-action-menu shadow-lg">
                                            <li>
                                                <button type="button" class="dropdown-action-item btn-detail-param" data-param='<?= $paramJson ?>'>
                                                    <span class="action-icon-circle action-icon-green"><i class="fa-solid fa-circle-info"></i></span>
                                                    <div class="action-text-group">
                                                        <span class="action-title">Lihat Detail</span>
                                                        <span class="action-desc">Rincian seluruh sub-parameter</span>
                                                    </div>
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-action-item btn-edit-param" data-param='<?= $paramJson ?>'>
                                                    <span class="action-icon-circle action-icon-blue"><i class="fa-solid fa-pen-to-square"></i></span>
                                                    <div class="action-text-group">
                                                        <span class="action-title">Edit Parameter</span>
                                                        <span class="action-desc">Ubah nama & sub-parameter</span>
                                                    </div>
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <button type="button" class="dropdown-action-item btn-delete-param text-danger" data-id="<?= esc($param['id']) ?>" data-name="<?= esc($param['name']) ?>">
                                                    <span class="action-icon-circle action-icon-red"><i class="fa-solid fa-trash-can"></i></span>
                                                    <div class="action-text-group">
                                                        <span class="action-title text-danger">Hapus Parameter</span>
                                                        <span class="action-desc">Hapus parameter dari master</span>
                                                    </div>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: TAMBAH & UBAH PARAMETER (Matching mockup-add-produk.png)
     ============================================================ -->
<div class="modal fade product-modal-custom" id="parameterModal" tabindex="-1" aria-labelledby="parameterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <form method="post" action="<?= site_url('scoring/parameters/store') ?>" class="modal-content product-modal-content border-0 shadow-2xl" id="parameterForm">
            <?= csrf_field() ?>
            <input type="hidden" name="param_id" id="modalParamId" value="">
            <div class="modal-body p-0">
                <div class="product-modal-grid">
                    <!-- Sisi Kiri: Visual Banner (setup-parameter.png) -->
                    <div class="product-modal-sidebar" style="background-image: url('<?= base_url('assets/images/setup-parameter.png?v=' . filemtime(FCPATH . 'assets/images/setup-parameter.png')) ?>');">
                        <div class="product-modal-sidebar-overlay"></div>
                        <div class="product-modal-sidebar-content">
                            <!-- Tips Box di Bawah Sisi Kiri (Source of Truth Mockup Add Produk) -->
                            <div class="product-modal-tips-card mt-auto">
                                <div class="product-tips-header">
                                    <div class="product-tips-icon">
                                        <i class="fa-solid fa-lightbulb"></i>
                                    </div>
                                    <span class="product-tips-title">Tips Parameter</span>
                                </div>
                                <ul class="product-tips-list">
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Gunakan nama parameter yang deskriptif dan unik.</span></li>
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Sub parameter dapat ditambah sebanyak kebutuhan penilaian.</span></li>
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Kolom <strong>Jumlah</strong> otomatis dihitung (Bobot × Nilai).</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sisi Kanan: Form Konten -->
                    <div class="product-modal-main flex-grow-1 p-4 d-flex flex-column">
                        <!-- Header Form -->
                        <div class="product-modal-main-header d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <div class="product-header-icon-box" style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                    <i class="fa-solid fa-sliders"></i>
                                </div>
                                <div>
                                    <h5 class="product-header-title mb-1 fw-bold text-dark" id="parameterModalLabel">Informasi Parameter</h5>
                                    <p class="product-header-subtitle mb-0 text-muted small">Lengkapi kategori parameter utama dan entri sub-parameter dinamis.</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <!-- Form Body Inputs -->
                        <div class="product-modal-main-body flex-grow-1">
                            <!-- Nama Parameter -->
                            <div class="mb-4">
                                <label class="modal-form-label fw-bold text-dark mb-1" for="modalParamName">
                                    Nama Parameter <span class="text-danger">*</span>
                                </label>
                                <div class="modal-input-wrap position-relative">
                                    <i class="fa-solid fa-tag modal-input-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                    <input type="text" class="form-control modal-input-control ps-5" id="modalParamName" name="name" placeholder="Contoh: Status Kawin" maxlength="150" required>
                                </div>
                                <div class="form-text text-muted small mt-1">Contoh: Status Kawin, Jenis Pekerjaan, Lama Bekerja, Kapasitas Finansial.</div>
                            </div>

                            <!-- Dynamic Sub Parameter Section (Redesign matching mockup-parameter.png) -->
                            <div class="subparam-section">
                                <div class="subparam-header-bar">
                                    <div class="subparam-title-wrap">
                                        <i class="fa-solid fa-table-cells subparam-title-icon"></i>
                                        <span class="subparam-title-text">Sub Parameter (Multi-Row Dinamis)</span>
                                        <span class="subparam-required-star">*</span>
                                    </div>
                                    <button type="button" class="btn-subparam-add" id="btnModalAddSub">
                                        <i class="fa-solid fa-plus"></i> Tambah Sub Parameter
                                    </button>
                                </div>

                                <!-- Dynamic Sub Parameter Table Card -->
                                <div class="subparam-table-card">
                                    <div class="subparam-table-wrapper">
                                        <table class="table subparam-table align-middle mb-0" id="modalSubParamTable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 14%;">KODE <span class="th-star">*</span></th>
                                                    <th style="width: 38%;">DESKRIPSI <span class="th-star">*</span></th>
                                                    <th style="width: 13%;">BOBOT <span class="th-star">*</span></th>
                                                    <th style="width: 13%;">NILAI <span class="th-star">*</span></th>
                                                    <th style="width: 14%;">JUMLAH</th>
                                                    <th class="text-center" style="width: 8%;">AKSI</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modalSubParamTbody">
                                                <!-- Dynamic multi-row inserted via JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Info Alert Box -->
                                <div class="subparam-info-box">
                                    <i class="fa-solid fa-circle-info subparam-info-icon"></i>
                                    <span class="subparam-info-text">
                                        Kolom <strong>Jumlah</strong> otomatis dihitung: <strong>Bobot × Nilai</strong> (readonly).
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer Action Bar -->
                        <div class="product-modal-footer d-flex align-items-center justify-content-end gap-2 pt-3 mt-auto border-top">
                            <button type="button" class="btn btn-light px-4 fw-semibold" data-bs-dismiss="modal">
                                <i class="fa-solid fa-xmark me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-success-gradient px-4" id="btnSubmitParam">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Parameter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL: DETAIL SUB PARAMETER
     ============================================================ -->
<div class="modal fade" id="detailParameterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-2xl rounded-4 overflow-hidden">
            <div class="modal-header bg-light py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white p-2 rounded-2">
                        <i class="fa-solid fa-sliders"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="detailParamTitle">Detail Sub Parameter</h5>
                        <small class="text-muted" id="detailParamIdBadge"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive rounded-3 border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr style="font-size: 12px;">
                                <th style="width: 50px;" class="text-center">No</th>
                                <th style="width: 100px;">Kode</th>
                                <th>Deskripsi Sub Parameter</th>
                                <th style="width: 100px;" class="text-end">Bobot</th>
                                <th style="width: 100px;" class="text-end">Nilai</th>
                                <th style="width: 120px;" class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody id="detailSubParamTbody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 border-top">
                <button type="button" class="btn btn-secondary px-4 btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Form delete parameter dummy -->
<form id="deleteParamForm" method="post" action="" style="display: none;">
    <?= csrf_field() ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('parameterModal');
    var paramModal = new bootstrap.Modal(modalEl);
    var detailModal = new bootstrap.Modal(document.getElementById('detailParameterModal'));

    var form = document.getElementById('parameterForm');
    var modalParamId = document.getElementById('modalParamId');
    var modalParamName = document.getElementById('modalParamName');
    var tbody = document.getElementById('modalSubParamTbody');
    var modalTitle = document.getElementById('parameterModalLabel');
    var modalSidebarTitle = document.getElementById('modalSidebarTitle');

    function createSubParamRow(code, desc, weight, value) {
        code = code || '';
        desc = desc || '';
        weight = (weight !== undefined && weight !== null) ? weight : '';
        value = (value !== undefined && value !== null) ? value : '';
        var total = '';
        if (weight !== '' && value !== '') {
            var w = parseFloat(weight) || 0;
            var v = parseFloat(value) || 0;
            total = (w * v).toFixed(2).replace(/\.00$/, '');
        }

        var tr = document.createElement('tr');
        tr.className = 'sub-row';
        tr.innerHTML = `
            <td>
                <input type="text" class="subparam-input sub-code" name="sub_code[]" value="${escapeHtml(code)}" placeholder="01" required>
            </td>
            <td>
                <input type="text" class="subparam-input sub-desc" name="sub_desc[]" value="${escapeHtml(desc)}" placeholder="Deskripsi opsi" required>
            </td>
            <td>
                <input type="number" step="any" class="subparam-input sub-weight" name="sub_weight[]" value="${escapeHtml(String(weight))}" placeholder="0" required>
            </td>
            <td>
                <input type="number" step="any" class="subparam-input sub-value" name="sub_value[]" value="${escapeHtml(String(value))}" placeholder="0" required>
            </td>
            <td>
                <input type="text" class="subparam-total-input sub-total" name="sub_total[]" value="${escapeHtml(String(total))}" placeholder="0" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn-del-subparam btn-del-row" title="Hapus baris">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;
        return tr;
    }

    function calculateRow(tr) {
        var wInput = tr.querySelector('.sub-weight');
        var vInput = tr.querySelector('.sub-value');
        var totInput = tr.querySelector('.sub-total');

        var w = parseFloat(wInput.value) || 0;
        var v = parseFloat(vInput.value) || 0;
        var tot = (w * v).toFixed(2).replace(/\.00$/, '');
        totInput.value = tot;
    }

    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('sub-weight') || e.target.classList.contains('sub-value')) {
            var tr = e.target.closest('tr');
            if (tr) calculateRow(tr);
        }
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-del-row');
        if (btn) {
            var tr = btn.closest('tr');
            if (tbody.querySelectorAll('tr').length <= 1) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Peringatan', text: 'Minimal harus ada 1 Sub Parameter' });
                } else {
                    alert('Minimal harus ada 1 Sub Parameter');
                }
                return;
            }
            tr.remove();
        }
    });

    document.getElementById('btnModalAddSub').addEventListener('click', function () {
        var nextCode = String(tbody.querySelectorAll('tr').length + 1).padStart(2, '0');
        tbody.appendChild(createSubParamRow(nextCode, '', '', ''));
    });

    // Reset to Add Mode
    function resetToAddMode() {
        modalParamId.value = '';
        modalParamName.value = '';
        tbody.innerHTML = '';
        form.action = '<?= site_url('scoring/parameters/store') ?>';
        if (modalTitle) modalTitle.textContent = 'Tambah Parameter Baru';
        if (modalSidebarTitle) modalSidebarTitle.textContent = 'Tambah Parameter';

        // Sample initial rows
        tbody.appendChild(createSubParamRow('01', 'Belum Menikah', '5', '3'));
        tbody.appendChild(createSubParamRow('02', 'Menikah', '5', '4'));
    }

    var btnTambah = document.getElementById('btnTambahParamTabel');
    if (btnTambah) {
        btnTambah.addEventListener('click', function () {
            resetToAddMode();
        });
    }

    // 1. Inisialisasi DataTables (Custom Layout, Pagination & Filter Persis Master Produk)
    var dataTable = null;
    if ($.fn.DataTable) {
        dataTable = $('#parametersTable').DataTable({
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
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
                zeroRecords: '<div class="empty-state py-5 text-center"><i class="fa-solid fa-sliders fa-2x text-muted mb-2 opacity-50"></i><p class="mb-0 text-muted">Tidak ada data parameter yang sesuai.</p></div>',
                emptyTable: '<div class="empty-state py-5 text-center"><i class="fa-solid fa-boxes-stacked fa-2x text-muted mb-2 opacity-50"></i><p class="mb-0 text-muted">Belum ada data parameter.</p></div>',
                paginate: {
                    first:    '<i class="fa-solid fa-angles-left"></i>',
                    previous: '<i class="fa-solid fa-angle-left"></i>',
                    next:     '<i class="fa-solid fa-angle-right"></i>',
                    last:     '<i class="fa-solid fa-angles-right"></i>'
                }
            },
            columnDefs: [
                { orderable: false, targets: [0, 4, 5] } // NO, DETAIL, AKSI non-orderable
            ],
            createdRow: function (row, data, dataIndex) {
                var subtleClasses = ['row-subtle-blue', 'row-subtle-green', 'row-subtle-purple', 'row-subtle-orange', 'row-subtle-red', 'row-subtle-cyan'];
                $(row).addClass(subtleClasses[dataIndex % subtleClasses.length]);
            },
            drawCallback: function (settings) {
                var api = this.api();
                var rows = api.rows({ page: 'current' }).nodes();
                var subtleClasses = ['row-subtle-blue', 'row-subtle-green', 'row-subtle-purple', 'row-subtle-orange', 'row-subtle-red', 'row-subtle-cyan'];
                $(rows).removeClass('row-subtle-blue row-subtle-green row-subtle-purple row-subtle-orange row-subtle-red row-subtle-cyan');
                api.rows({ page: 'current' }).every(function (rowIdx, tableLoop, rowLoop) {
                    $(this.node()).addClass(subtleClasses[rowLoop % subtleClasses.length]);
                });
            }
        });
    }

    // 2. Delegated Edit Handler (DataTables Safe)
    $(document).on('click', '.btn-edit-param', function (e) {
        e.preventDefault();
        var rawData = $(this).attr('data-param') || '{}';
        var data = {};
        try {
            data = JSON.parse(rawData);
        } catch (err) {
            console.error('Gagal parse data parameter:', err);
        }

        modalParamId.value = data.id || '';
        modalParamName.value = data.name || '';
        tbody.innerHTML = '';
        form.action = '<?= site_url('scoring/parameters') ?>/' + data.id + '/update';
        if (modalTitle) modalTitle.textContent = 'Ubah Parameter: ' + (data.name || '');
        if (modalSidebarTitle) modalSidebarTitle.textContent = 'Ubah Parameter';

        var subs = data.sub_parameters || [];
        if (subs.length === 0) {
            tbody.appendChild(createSubParamRow('01', '', '', ''));
        } else {
            subs.forEach(function (s) {
                var wInt = Math.round(Number(s.weight || 0));
                var vInt = Math.round(Number(s.value || 0));
                tbody.appendChild(createSubParamRow(s.code, s.description, wInt, vInt));
            });
        }
        
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('parameterModal'));
        modal.show();
    });

    // 3. Delegated Detail Modal Handler (DataTables Safe & Format Integer Saja)
    $(document).on('click', '.btn-detail-param', function () {
        var data = JSON.parse($(this).attr('data-param') || '{}');
        document.getElementById('detailParamTitle').textContent = data.name || 'Detail Parameter';
        document.getElementById('detailParamIdBadge').textContent = 'ID Parameter: #PRM-' + String(data.id || 0).padStart(3, '0');

        var detailTbody = document.getElementById('detailSubParamTbody');
        detailTbody.innerHTML = '';
        var subs = data.sub_parameters || [];
        if (subs.length === 0) {
            detailTbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">Tidak ada sub-parameter.</td></tr>';
        } else {
            subs.forEach(function (s, i) {
                var w = Math.round(Number(s.weight || 0));
                var v = Math.round(Number(s.value || 0));
                var t = Math.round(Number(s.total || (w * v)));
                var tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center text-muted fw-bold">${i + 1}</td>
                    <td><span class="badge bg-light text-primary border">${escapeHtml(s.code)}</span></td>
                    <td class="fw-semibold text-dark">${escapeHtml(s.description)}</td>
                    <td class="text-end fw-semibold">${w}</td>
                    <td class="text-end fw-semibold">${v}</td>
                    <td class="text-end fw-bold text-success">${t}</td>
                `;
                detailTbody.appendChild(tr);
            });
        }
        detailModal.show();
    });

    // 4. Delegated Delete Handler (DataTables Safe)
    $(document).on('click', '.btn-delete-param', function () {
        var id = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        var deleteForm = document.getElementById('deleteParamForm');
        deleteForm.action = '<?= site_url('scoring/parameters') ?>/' + id + '/delete';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Parameter?',
                text: 'Apakah Anda yakin ingin menghapus parameter "' + name + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    deleteForm.submit();
                }
            });
        } else {
            if (confirm('Apakah Anda yakin ingin menghapus parameter "' + name + '"?')) {
                deleteForm.submit();
            }
        }
    });

    // 5. DataTables Filter Integration
    function applyFilters() {
        var searchVal = ($('#filterSearchParam').val() || '').trim();
        var catVal = ($('#filterParamName').val() || '').trim();

        if (dataTable) {
            if (catVal) {
                dataTable.column(2).search(catVal);
            } else {
                dataTable.column(2).search('');
            }
            dataTable.search(searchVal).draw();
        } else {
            // Fallback plain DOM filtering
            document.querySelectorAll('#parametersTable tbody tr.param-row').forEach(function (tr) {
                var rowText = tr.textContent.toLowerCase();
                var rowName = tr.getAttribute('data-name') || '';
                var matchSearch = !searchVal || rowText.indexOf(searchVal.toLowerCase()) !== -1;
                var matchCat = !catVal || rowName.indexOf(catVal.toLowerCase()) !== -1;
                tr.style.display = (matchSearch && matchCat) ? '' : 'none';
            });
        }
    }

    $('#btnApplyFilter').on('click', applyFilters);
    $('#filterSearchParam').on('keyup', function (e) {
        if (e.key === 'Enter') applyFilters();
    });
    $('#filterParamName').on('change', applyFilters);

    $('#btnResetFilter').on('click', function () {
        $('#filterSearchParam').val('');
        $('#filterParamName').val('').trigger('change');
        if (dataTable) {
            dataTable.search('').columns().search('').draw();
        } else {
            applyFilters();
        }
    });

    // Helper escape html
    function escapeHtml(text) {
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, function (m) { return map[m]; });
    }
});
</script>

<?= view('partials/shell_end') ?>
