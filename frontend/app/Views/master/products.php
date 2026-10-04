<?php
$profile        = $profile ?? [];
$products       = $products ?? [];
$types          = $types ?? [];
$codes          = $codes ?? [];
$productOptions = $productOptions ?? [];
$stats          = $stats ?? [];

// Calculate stats for KPIs
$totalProducts = (int) ($stats['total'] ?? count($products));
$activeCount   = (int) ($stats['active'] ?? 0);
$inactiveCount = (int) ($stats['inactive'] ?? 0);
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Master Produk']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- 1. Page Title Header Card (Mockup Redesign Standard) -->
<div class="module-header module-header-card">
    <div class="module-header-panel-wrap">
        <div class="module-header-content">
            <div class="module-icon-cube">
                <i class="fa-solid fa-cube"></i>
            </div>
            <div class="module-header-divider"></div>
            <div class="module-title-box text-start">
                <div class="d-flex align-items-center justify-content-start gap-2 flex-wrap text-start">
                    <h2 class="module-title text-start mb-0">Master Produk</h2>
                    <span class="module-badge-pill"><?= $totalProducts ?> Produk</span>
                </div>
                <p class="module-subtitle text-start mb-0">Kelola katalog produk kredit yang digunakan dalam engine perhitungan scoring.</p>
            </div>
        </div>
    </div>
</div>

<!-- 3. Filter Data Card -->
<div class="filter-card">
    <div class="filter-card-header">
        <div class="filter-header-left">
            <div class="filter-icon-badge">
                <i class="fa-solid fa-filter"></i>
            </div>
            <div>
                <h6 class="filter-title">Filter Data</h6>
                <p class="filter-subtitle">Gunakan filter berikut untuk mencari data produk dengan lebih spesifik.</p>
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
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="filter-form-label" for="filterSearch">Search Produk</label>
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-magnifying-glass filter-input-icon"></i>
                        <input type="text" class="form-control" id="filterSearch" placeholder="Cari kode atau nama produk..." style="padding-left: 42px !important;">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="filter-form-label" for="filterCode">Kode Produk</label>
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-tag filter-input-icon"></i>
                        <select class="form-select filter-select2" id="filterCode" data-placeholder="Semua Kode Produk">
                            <option value=""></option>
                            <?php 
                            if (! empty($productOptions)) {
                                $optionsList = $productOptions;
                            } else {
                                $tempProducts = $products;
                                usort($tempProducts, function ($a, $b) {
                                    $aActive = !empty($a['is_active']) ? 1 : 0;
                                    $bActive = !empty($b['is_active']) ? 1 : 0;
                                    if ($aActive !== $bActive) {
                                        return $bActive <=> $aActive; // 1. Yang aktif saja duluan
                                    }
                                    $aBranch = isset($a['business_unit']) ? (int) $a['business_unit'] : 0;
                                    $bBranch = isset($b['business_unit']) ? (int) $b['business_unit'] : 0;
                                    if ($aBranch !== $bBranch) {
                                        return $aBranch <=> $bBranch; // 2. Konvensional (0) duluan
                                    }
                                    return strcmp($a['code'] ?? '', $b['code'] ?? ''); // 3. Kode produk ASC
                                });
                                $optionsList = [];
                                $seen = [];
                                foreach ($tempProducts as $tp) {
                                    $c = $tp['code'] ?? '';
                                    if ($c !== '' && !isset($seen[$c])) {
                                        $seen[$c] = true;
                                        $optionsList[] = ['code' => $c, 'name' => $tp['name'] ?? ''];
                                    }
                                }
                            }
                            foreach ($optionsList as $opt) : 
                                $optCode = is_array($opt) ? ($opt['code'] ?? '') : (string) $opt;
                                $optName = is_array($opt) ? ($opt['name'] ?? '') : '';
                                $label = $optName !== '' ? $optCode . ' - ' . $optName : $optCode;
                            ?>
                                <option value="<?= esc($optCode) ?>"><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="filter-form-label" for="filterStatus">Status Produk</label>
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-layer-group filter-input-icon"></i>
                        <select class="form-select filter-select2" id="filterStatus" data-placeholder="Semua Status">
                            <option value=""></option>
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="filter-form-label" for="filterProductType">Jenis Produk</label>
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-shapes filter-input-icon"></i>
                        <select class="form-select filter-select2" id="filterProductType" data-placeholder="Semua Jenis">
                            <option value=""></option>
                            <?php foreach ($types as $type) : ?>
                                <option value="<?= esc($type['name']) ?>"><?= esc($type['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="filter-form-label" for="filterBranch">Status Branch</label>
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-building filter-input-icon"></i>
                        <select class="form-select filter-select2" id="filterBranch" data-placeholder="Semua Cabang">
                            <option value=""></option>
                            <option value="Konvensional">Konvensional</option>
                            <option value="Syariah">Syariah</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="filter-actions">
                <button type="button" class="btn-apply-filter" id="btnApplyFilter">
                    <i class="fa-solid fa-magnifying-glass"></i> Terapkan Filter
                </button>
                <button type="button" class="btn-reset-filter" id="btnResetFilter">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div>



<!-- 5. Products Table Card -->
<div class="table-card">
    <div class="table-card-header">
        <h5 class="table-card-title">
            <i class="fa-solid fa-list-check"></i> Daftar Produk Kredit
        </h5>
        <div class="table-card-actions d-flex align-items-center gap-2">
            <!-- Dropdown Export Button -->
            <div class="dropdown">
                <button type="button" class="btn-export dropdown-toggle" id="btnExportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border" aria-labelledby="btnExportDropdown" style="font-size: 12px; min-width: 180px; border-radius: 10px;">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="btnExportExcel">
                            <i class="fa-solid fa-file-excel text-success" style="font-size: 13px; width: 16px;"></i>
                            <span>Export Excel (.xls)</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="btnExportPdf">
                            <i class="fa-solid fa-file-pdf text-danger" style="font-size: 13px; width: 16px;"></i>
                            <span>Export PDF</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="btnExportCsv">
                            <i class="fa-solid fa-file-csv text-info" style="font-size: 13px; width: 16px;"></i>
                            <span>Export CSV (.csv)</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tambah Produk Baru Action Button (Green / Hijau) -->
            <button type="button" class="btn btn-success-gradient" id="btnTambahProdukBaru" data-bs-toggle="modal" data-bs-target="#createProductModal">
                <i class="fa-solid fa-plus"></i> Tambah Produk Baru
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="productsTable" class="table table-striped table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width: 55px;" class="text-center fw-bold">NO</th>
                        <th style="width: 145px;" class="text-center fw-bold">KODE PRODUK</th>
                        <th class="text-center fw-bold">NAMA PRODUK</th>
                        <th style="width: 155px;" class="text-center fw-bold">STATUS BRANCH</th>
                        <th style="width: 150px;" class="text-center fw-bold">JENIS PRODUK</th>
                        <th class="text-center fw-bold" style="width: 130px;">SUKU BUNGA</th>
                        <th class="text-center fw-bold" style="width: 125px;">STATUS</th>
                        <th class="text-center fw-bold" style="width: 70px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data rendered dynamically via DataTables Server-Side Processing -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     MODALS SECTION
     ============================================================ -->

<!-- Modal: Tambah Produk Baru -->
<div class="modal fade product-modal-custom" id="createProductModal" aria-labelledby="createProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <form method="post" action="<?= site_url('master/products') ?>" class="modal-content product-modal-content border-0 shadow-2xl" id="createProductForm">
            <?= csrf_field() ?>
            <div class="modal-body p-0">
                <div class="product-modal-grid">
                    <!-- Sisi Kiri: Visual Banner (tambah-produk.png) -->
                    <div class="product-modal-sidebar" style="background-image: url('<?= base_url('assets/images/tambah-produk.png') ?>');">
                        <div class="product-modal-sidebar-overlay"></div>
                        <div class="product-modal-sidebar-content">
                            <!-- Tips Box di Bawah Sisi Kiri -->
                            <div class="product-modal-tips-card mt-auto">
                                <div class="product-tips-header">
                                    <div class="product-tips-icon">
                                        <i class="fa-solid fa-lightbulb"></i>
                                    </div>
                                    <span class="product-tips-title">Tips</span>
                                </div>
                                <ul class="product-tips-list">
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Pastikan kode produk unik.</span></li>
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Isi nama produk sesuai ketentuan.</span></li>
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Produk aktif akan dapat digunakan dalam formulir scoring kredit.</span></li>
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
                                    <i class="fa-solid fa-file-lines text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="product-header-title mb-1" id="createProductModalLabel">Informasi Produk</h5>
                                    <p class="product-header-subtitle mb-0">Lengkapi informasi dasar produk kredit.</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <!-- Form Body Inputs -->
                        <div class="product-modal-main-body">
                            <div class="row g-3">
                                <!-- Kode Produk -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_code">Kode Produk <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-hashtag modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="create_code" name="code" placeholder="Contoh: KMG" maxlength="30" required>
                                    </div>
                                    <div class="modal-input-hint">Gunakan kode unik, misal: KMG / 0532 / 0792</div>
                                </div>

                                <!-- Nama Produk -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_name">Nama Produk <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-file-lines modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="create_name" name="name" placeholder="Contoh: Kredit Multiguna" maxlength="150" required>
                                    </div>
                                </div>

                                <!-- Status Branch -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_businessUnit">Status Branch <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-building modal-input-icon"></i>
                                        <select class="form-select modal-select2" id="create_businessUnit" name="business_unit" required>
                                            <option value="0" selected>Konvensional</option>
                                            <option value="1">Syariah</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Jenis Produk -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_productType">Jenis Produk</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-layer-group modal-input-icon"></i>
                                        <select class="form-select modal-select2" id="create_productType" name="product_type_id" data-placeholder="-- Pilih Jenis Produk --">
                                            <option value="">-- Pilih Jenis Produk --</option>
                                            <?php foreach ($types as $type) : ?>
                                                <option value="<?= esc($type['id']) ?>"><?= esc($type['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Suku Bunga -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="create_interestRate">Suku Bunga (%)</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-percent modal-input-icon"></i>
                                        <input type="number" step="0.01" class="form-control modal-input-control" id="create_interestRate" name="interest_rate" placeholder="Contoh: 9.60">
                                    </div>
                                </div>

                                <!-- Produk Aktif Toggle -->
                                <div class="col-12 col-md-6 d-flex align-items-center">
                                    <div class="modal-status-toggle-card w-100">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input modal-switch-input" type="checkbox" id="create_isActive" name="is_active" value="1" checked>
                                            <div class="modal-switch-text">
                                                <label class="modal-switch-label" for="create_isActive">Produk Aktif</label>
                                                <div class="modal-switch-desc">Dapat digunakan dalam formulir scoring kredit.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informasi Tambahan Alert Banner -->
                            <div class="modal-info-alert mt-4">
                                <div class="modal-info-alert-icon">
                                    <i class="fa-solid fa-circle-info"></i>
                                </div>
                                <div class="modal-info-alert-text">
                                    <h6 class="modal-info-alert-title mb-1">Informasi Tambahan</h6>
                                    <p class="modal-info-alert-desc mb-0">Data produk yang disimpan akan muncul pada daftar produk dan dapat digunakan untuk proses pengajuan kredit.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="product-modal-main-footer">
                            <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">
                                <i class="fa-solid fa-xmark me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn-modal-submit">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Produk
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Ubah Produk -->
<div class="modal fade product-modal-custom" id="editProductModal" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <form method="post" action="" class="modal-content product-modal-content border-0 shadow-2xl" id="editProductForm">
            <?= csrf_field() ?>
            <div class="modal-body p-0">
                <div class="product-modal-grid">
                    <!-- Sisi Kiri: Visual Banner (edit-produk.png) -->
                    <div class="product-modal-sidebar" style="background-image: url('<?= base_url('assets/images/edit-produk.png') ?>');">
                        <div class="product-modal-sidebar-overlay"></div>
                        <div class="product-modal-sidebar-content">
                            <!-- Tips Box di Bawah Sisi Kiri -->
                            <div class="product-modal-tips-card mt-auto">
                                <div class="product-tips-header">
                                    <div class="product-tips-icon">
                                        <i class="fa-solid fa-lightbulb"></i>
                                    </div>
                                    <span class="product-tips-title">Informasi Edit</span>
                                </div>
                                <ul class="product-tips-list">
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Perubahan data produk akan langsung diterapkan pada sistem.</span></li>
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Pastikan kode produk tidak bertabrakan dengan produk lain.</span></li>
                                    <li><i class="fa-solid fa-circle-check"></i> <span>Nonaktifkan produk jika sudah tidak dipasarkan.</span></li>
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
                                    <i class="fa-solid fa-pen-to-square text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="product-header-title mb-1" id="editProductModalLabel">Edit Data Produk</h5>
                                    <p class="product-header-subtitle mb-0">Perbarui informasi dan konfigurasi produk kredit.</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <!-- Form Body Inputs -->
                        <div class="product-modal-main-body">
                            <div class="row g-3">
                                <!-- Kode Produk -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_code">Kode Produk <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-hashtag modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="edit_code" name="code" placeholder="Contoh: KMG" maxlength="30" required>
                                    </div>
                                    <div class="modal-input-hint">Gunakan kode unik, misal: KMG / 0532 / 0792</div>
                                </div>

                                <!-- Nama Produk -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_name">Nama Produk <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-file-lines modal-input-icon"></i>
                                        <input type="text" class="form-control modal-input-control" id="edit_name" name="name" placeholder="Contoh: Kredit Multiguna" maxlength="150" required>
                                    </div>
                                </div>

                                <!-- Status Branch -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_businessUnit">Status Branch <span class="text-danger">*</span></label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-building modal-input-icon"></i>
                                        <select class="form-select modal-select2" id="edit_businessUnit" name="business_unit" required>
                                            <option value="0">Konvensional</option>
                                            <option value="1">Syariah</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Jenis Produk -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_productType">Jenis Produk</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-layer-group modal-input-icon"></i>
                                        <select class="form-select modal-select2" id="edit_productType" name="product_type_id" data-placeholder="-- Pilih Jenis Produk --">
                                            <option value="">-- Pilih Jenis Produk --</option>
                                            <?php foreach ($types as $type) : ?>
                                                <option value="<?= esc($type['id']) ?>"><?= esc($type['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Suku Bunga -->
                                <div class="col-12 col-md-6">
                                    <label class="modal-form-label" for="edit_interestRate">Suku Bunga (%)</label>
                                    <div class="modal-input-wrap">
                                        <i class="fa-solid fa-percent modal-input-icon"></i>
                                        <input type="number" step="0.01" class="form-control modal-input-control" id="edit_interestRate" name="interest_rate" placeholder="Contoh: 9.60">
                                    </div>
                                </div>

                                <!-- Produk Aktif Toggle -->
                                <div class="col-12 col-md-6 d-flex align-items-center">
                                    <div class="modal-status-toggle-card w-100">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input modal-switch-input" type="checkbox" id="edit_isActive" name="is_active" value="1">
                                            <div class="modal-switch-text">
                                                <label class="modal-switch-label" for="edit_isActive">Produk Aktif</label>
                                                <div class="modal-switch-desc">Dapat digunakan dalam formulir scoring kredit.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informasi Tambahan Alert Banner -->
                            <div class="modal-info-alert mt-4">
                                <div class="modal-info-alert-icon">
                                    <i class="fa-solid fa-circle-info"></i>
                                </div>
                                <div class="modal-info-alert-text">
                                    <h6 class="modal-info-alert-title mb-1">Catatan Pembaruan</h6>
                                    <p class="modal-info-alert-desc mb-0">Pastikan suku bunga dan jenis produk telah dikonfirmasi dengan regulasi produk terkini.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="product-modal-main-footer">
                            <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">
                                <i class="fa-solid fa-xmark me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn-modal-submit">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Detail Produk (Redesigned matching informasi-detail-produk.png) -->
<div class="modal fade detail-product-modal-custom" id="detailProductModal" tabindex="-1" aria-labelledby="detailProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content detail-modal-content border-0 shadow-2xl">
            <!-- Modal Header -->
            <div class="detail-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="detail-header-icon-box">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div>
                        <h5 class="detail-header-title mb-1" id="detailProductModalLabel">Informasi Detail Produk</h5>
                        <p class="detail-header-subtitle mb-0">Berikut adalah informasi lengkap dari produk yang dipilih.</p>
                    </div>
                </div>
                <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="detail-modal-body">
                <!-- Hero Banner Card (Using background-card.png) -->
                <div class="detail-hero-banner" style="background-image: url('<?= base_url('assets/images/background-card.png') ?>');">
                    <div class="detail-hero-cubebox">
                        <div class="detail-cube-icon-wrapper">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                    </div>
                    <div class="detail-hero-info">
                        <h4 class="detail-hero-title mb-2" id="detail_name">-</h4>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="detail-code-badge" id="detail_code_badge">
                                <i class="fa-solid fa-hashtag me-1"></i><span id="detail_code">-</span>
                            </span>
                            <span id="detail_status_badge"></span>
                        </div>
                    </div>
                </div>

                <!-- Section Title: Detail Informasi -->
                <div class="detail-section-divider">
                    <div class="detail-section-title">
                        <i class="fa-solid fa-bars-staggered"></i>
                        <span>Detail Informasi</span>
                    </div>
                    <div class="detail-section-line"></div>
                </div>

                <!-- 4 Attribute Cards Grid -->
                <div class="row g-3">
                    <!-- 1. Status Branch (Purple/Blue Tone) -->
                    <div class="col-12 col-md-6">
                        <div class="detail-attr-card detail-card-purple">
                            <div class="detail-attr-left">
                                <div class="detail-attr-icon detail-icon-purple">
                                    <i class="fa-solid fa-building"></i>
                                </div>
                                <div class="detail-attr-text">
                                    <span class="detail-attr-label">Status Branch</span>
                                    <span class="detail-attr-val" id="detail_branch">-</span>
                                </div>
                            </div>
                            <div class="detail-attr-watermark">
                                <i class="fa-solid fa-city"></i>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Jenis Produk (Orange/Amber Tone) -->
                    <div class="col-12 col-md-6">
                        <div class="detail-attr-card detail-card-orange">
                            <div class="detail-attr-left">
                                <div class="detail-attr-icon detail-icon-orange">
                                    <i class="fa-solid fa-layer-group"></i>
                                </div>
                                <div class="detail-attr-text">
                                    <span class="detail-attr-label">Jenis Produk</span>
                                    <span class="detail-attr-val" id="detail_type">-</span>
                                </div>
                            </div>
                            <div class="detail-attr-watermark">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Suku Bunga (Green/Emerald Tone) -->
                    <div class="col-12 col-md-6">
                        <div class="detail-attr-card detail-card-green">
                            <div class="detail-attr-left">
                                <div class="detail-attr-icon detail-icon-green">
                                    <i class="fa-solid fa-percent"></i>
                                </div>
                                <div class="detail-attr-text">
                                    <span class="detail-attr-label">Suku Bunga</span>
                                    <span class="detail-attr-val font-monospace" id="detail_rate">-</span>
                                </div>
                            </div>
                            <div class="detail-attr-watermark">
                                <i class="fa-solid fa-chart-column"></i>
                            </div>
                        </div>
                    </div>

                    <!-- 4. ID Produk (Cyan/Sky Tone) -->
                    <div class="col-12 col-md-6">
                        <div class="detail-attr-card detail-card-blue">
                            <div class="detail-attr-left">
                                <div class="detail-attr-icon detail-icon-blue">
                                    <i class="fa-solid fa-tag"></i>
                                </div>
                                <div class="detail-attr-text">
                                    <span class="detail-attr-label">ID Produk</span>
                                    <span class="detail-attr-val font-monospace" id="detail_id">-</span>
                                </div>
                            </div>
                            <div class="detail-attr-watermark">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="detail-modal-footer">
                <button type="button" class="btn-detail-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // 1. Inisialisasi DataTables (Custom Layout & Pagination Persis Mockup)
    var dataTable = null;
    if ($.fn.DataTable) {
        dataTable = $('#productsTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: '<?= site_url('master/products/datatables') ?>',
                type: 'GET',
                data: function (d) {
                    d.filterSearch      = $('#filterSearch').val();
                    d.filterCode        = $('#filterCode').val();
                    d.filterStatus      = $('#filterStatus').val();
                    d.filterProductType = $('#filterProductType').val();
                    d.filterBranch      = $('#filterBranch').val();
                }
            },
            order: [], // Default order diatur dari server (Aktif -> Konvensional -> Kode ASC)
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
            autoWidth: false,
            responsive: true,
            dom: "<'products-table-wrapper't>" +
                 "<'products-table-footer d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 p-3'<'table-footer-left text-muted small'i><'table-footer-center d-flex align-items-center gap-2'l><'table-footer-right'p>>",
            language: {
                processing: '<div class="d-flex align-items-center gap-2 py-2"><span class="spinner-border spinner-border-sm text-primary"></span> <span>Memuat data dari server...</span></div>',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 dari 0 data',
                infoFiltered: '(disaring dari total _MAX_ data)',
                lengthMenu: 'Tampilkan _MENU_ data per halaman',
                zeroRecords: '<div class="empty-state py-5"><i class="fa-solid fa-box-open fa-2x text-muted mb-2 opacity-50"></i><p class="mb-0 text-muted">Tidak ada data produk yang sesuai.</p></div>',
                emptyTable: '<div class="empty-state py-5"><i class="fa-solid fa-boxes-stacked fa-2x text-muted mb-2 opacity-50"></i><p class="mb-0 text-muted">Belum ada data produk.</p></div>',
                paginate: {
                    first:    '<i class="fa-solid fa-angles-left"></i>',
                    previous: '<i class="fa-solid fa-angle-left"></i>',
                    next:     '<i class="fa-solid fa-angle-right"></i>',
                    last:     '<i class="fa-solid fa-angles-right"></i>'
                }
            },
            columns: [
                // 0. NO
                {
                    data: null,
                    orderable: false,
                    className: 'text-center cell-index',
                    render: function (data, type, row, meta) {
                        var num = meta.settings._iDisplayStart + meta.row + 1;
                        return '<span class="cell-no-badge">' + num + '</span>';
                    }
                },
                // 1. KODE PRODUK
                // 1. KODE PRODUK
                {
                    data: 'code',
                    name: 'code',
                    render: function (data, type, row) {
                        var safeCode = $('<div>').text(data || '').html();
                        return '<div class="d-inline-flex align-items-center gap-1">' +
                               '<span class="badge-code-pill"><i class="fa-solid fa-tag me-1" style="font-size: 10px;"></i>' + safeCode + '</span>' +
                               '<button type="button" class="btn-copy-inline" data-clipboard="' + safeCode + '" title="Salin Kode Produk">' +
                               '<i class="fa-regular fa-copy"></i>' +
                               '</button>' +
                               '</div>';
                    }
                },
                // 2. NAMA PRODUK
                {
                    data: 'name',
                    name: 'name',
                    render: function (data, type, row) {
                        var safeName = $('<div>').text(data || '').html();
                        return '<div class="d-flex align-items-center gap-2">' +
                               '<i class="fa-solid fa-file-contract row-icon-themed"></i>' +
                               '<span class="fw-bold product-name-text">' + safeName + '</span>' +
                               '</div>';
                    }
                },
                // 3. STATUS BRANCH
                {
                    data: 'business_unit',
                    name: 'business_unit',
                    render: function (data, type, row) {
                        var isSharia = parseInt(data, 10) === 1;
                        var icon = isSharia ? 'fa-mosque' : 'fa-building';
                        var label = isSharia ? 'Syariah' : 'Konvensional';
                        return '<span class="badge-branch-pill">' +
                               '<i class="fa-solid ' + icon + ' me-1" style="font-size: 10px;"></i>' + label +
                               '</span>';
                    }
                },
                // 4. JENIS PRODUK
                {
                    data: 'product_type_name',
                    name: 'product_type_name',
                    render: function (data, type, row) {
                        var safeType = $('<div>').text(data || '-').html();
                        return '<span class="text-muted fw-medium">' +
                               '<i class="fa-solid fa-layer-group row-icon-themed me-1" style="font-size: 11px;"></i>' + safeType +
                               '</span>';
                    }
                },
                // 5. SUKU BUNGA
                {
                    data: 'interest_rate',
                    name: 'interest_rate',
                    className: 'text-center',
                    render: function (data, type, row) {
                        if (data && parseFloat(data) > 0) {
                            var rawRate = parseFloat(data).toFixed(2);
                            var formatted = rawRate + '%';
                            return '<div class="d-inline-flex align-items-center justify-content-center gap-1">' +
                                   '<span class="fw-bold font-monospace interest-rate-text">' +
                                   '<i class="fa-solid fa-chart-line row-icon-themed me-1" style="font-size: 11px;"></i>' + formatted +
                                   '</span>' +
                                   '<button type="button" class="btn-copy-inline" data-clipboard="' + rawRate + '" title="Salin Suku Bunga">' +
                                   '<i class="fa-regular fa-copy"></i>' +
                                   '</button>' +
                                   '</div>';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                // 6. STATUS
                {
                    data: 'is_active',
                    name: 'is_active',
                    className: 'text-center',
                    render: function (data, type, row) {
                        var active = Boolean(data);
                        if (active) {
                            return '<span class="badge-status-active"><i class="fa-solid fa-circle-check"></i> Aktif</span>';
                        }
                        return '<span class="badge-status-inactive"><i class="fa-solid fa-circle-xmark"></i> Nonaktif</span>';
                    }
                },
                // 7. AKSI
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        var id = row.id;
                        var safeCode = $('<div>').text(row.code || '').html();
                        var safeName = $('<div>').text(row.name || '').html();
                        var bUnit = row.business_unit !== undefined ? row.business_unit : 0;
                        var pTypeId = row.product_type_id || '';
                        var pTypeName = $('<div>').text(row.product_type_name || '-').html();
                        var rate = row.interest_rate || '';
                        var rateFormatted = rate ? parseFloat(rate).toFixed(2) + '%' : '-';
                        var isActive = row.is_active ? '1' : '0';
                        var isSharia = parseInt(bUnit, 10) === 1;
                        var branchText = isSharia ? 'Syariah' : 'Konvensional';
                        var statusText = row.is_active ? 'Aktif' : 'Nonaktif';
                        var toggleActionText = row.is_active ? 'Nonaktifkan' : 'Aktifkan';
                        var toggleIcon = row.is_active ? 'fa-ban' : 'fa-check';
                        var toggleIconClass = row.is_active ? 'action-icon-red' : 'action-icon-green';
                        var toggleTitleClass = row.is_active ? 'text-danger' : 'text-success';
                        var toggleDesc = row.is_active ? 'Ubah status menjadi nonaktif' : 'Aktifkan kembali produk';
                        var updateUrl = '<?= site_url('master/products/') ?>' + id;

                        return '<div class="dropdown">' +
                               '<button class="btn-action-trigger" type="button" data-bs-toggle="dropdown" data-bs-popper-config=\'{"strategy":"fixed"}\' aria-expanded="false" title="Menu Aksi">' +
                               '<i class="fa-solid fa-ellipsis"></i>' +
                               '</button>' +
                               '<ul class="dropdown-menu dropdown-menu-end dropdown-action-menu shadow-lg">' +
                               '<li>' +
                               '<button type="button" class="dropdown-action-item btn-edit-product" data-id="' + id + '" data-code="' + safeCode + '" data-name="' + safeName + '" data-business-unit="' + bUnit + '" data-product-type="' + pTypeId + '" data-interest-rate="' + rate + '" data-is-active="' + isActive + '">' +
                               '<span class="action-icon-circle action-icon-blue"><i class="fa-solid fa-pen-to-square"></i></span>' +
                               '<div class="action-text-group"><span class="action-title">Edit Produk</span><span class="action-desc">Ubah data produk</span></div>' +
                               '</button>' +
                               '</li>' +
                               '<li>' +
                               '<button type="button" class="dropdown-action-item btn-view-detail" data-id="' + id + '" data-code="' + safeCode + '" data-name="' + safeName + '" data-branch="' + branchText + '" data-type="' + pTypeName + '" data-rate="' + rateFormatted + '" data-status="' + statusText + '">' +
                               '<span class="action-icon-circle action-icon-green"><i class="fa-solid fa-circle-info"></i></span>' +
                               '<div class="action-text-group"><span class="action-title">Lihat Detail</span><span class="action-desc">Informasi lengkap produk</span></div>' +
                               '</button>' +
                               '</li>' +
                               '<li>' +
                               '<button type="button" class="dropdown-action-item btn-duplicate-product" data-code="' + safeCode + '" data-name="' + safeName + '" data-business-unit="' + bUnit + '" data-product-type="' + pTypeId + '" data-interest-rate="' + rate + '">' +
                               '<span class="action-icon-circle action-icon-orange"><i class="fa-solid fa-copy"></i></span>' +
                               '<div class="action-text-group"><span class="action-title">Duplikasi Produk</span><span class="action-desc">Buat produk baru dari data ini</span></div>' +
                               '</button>' +
                               '</li>' +
                               '<li>' +
                               '<a href="<?= site_url('reports/products') ?>?id=' + id + '" class="dropdown-action-item">' +
                               '<span class="action-icon-circle action-icon-purple"><i class="fa-solid fa-clock-rotate-left"></i></span>' +
                               '<div class="action-text-group"><span class="action-title">Riwayat Perubahan</span><span class="action-desc">Log perubahan data</span></div>' +
                               '</a>' +
                               '</li>' +
                               '<li><hr class="dropdown-divider my-1"></li>' +
                               '<li>' +
                               '<form method="post" action="' + updateUrl + '" class="form-toggle-status m-0 p-0">' +
                               '<?= csrf_field() ?>' +
                               '<input type="hidden" name="code" value="' + safeCode + '">' +
                               '<input type="hidden" name="name" value="' + safeName + '">' +
                               '<input type="hidden" name="business_unit" value="' + bUnit + '">' +
                               '<input type="hidden" name="interest_rate" value="' + rate + '">' +
                               '<input type="hidden" name="product_type_id" value="' + pTypeId + '">' +
                               '<input type="hidden" name="is_active" value="' + (row.is_active ? '0' : '1') + '">' +
                               '<button type="button" class="dropdown-action-item btn-toggle-status" data-active="' + isActive + '" data-product-name="' + safeName + '">' +
                               '<span class="action-icon-circle ' + toggleIconClass + '"><i class="fa-solid ' + toggleIcon + '"></i></span>' +
                               '<div class="action-text-group"><span class="action-title ' + toggleTitleClass + '">' + toggleActionText + '</span><span class="action-desc">' + toggleDesc + '</span></div>' +
                               '</button>' +
                               '</form>' +
                               '</li>' +
                               '</ul>' +
                               '</div>';
                    }
                }
            ],
            createdRow: function (row, data, dataIndex) {
                var subtleClasses = ['row-subtle-blue', 'row-subtle-green', 'row-subtle-purple', 'row-subtle-orange', 'row-subtle-red', 'row-subtle-cyan'];
                $(row).addClass(subtleClasses[dataIndex % subtleClasses.length]);
            },
            drawCallback: function (settings) {
                var api = this.api();
                var json = api.ajax.json();
                if (json && json.stats) {
                    if (json.stats.total !== undefined) {
                        $('#kpiTotal').text(json.stats.total);
                        $('.module-badge-pill').text(json.stats.total + ' Produk');
                    }
                    if (json.stats.active !== undefined) {
                        $('#kpiActive').text(json.stats.active);
                    }
                    if (json.stats.inactive !== undefined) {
                        $('#kpiInactive').text(json.stats.inactive);
                    }
                }
            }
        });

        // Row Click Selection (Execute.md Standard: selected background #E3EEFF & border-left 3px solid #1E60D5)
        $('#productsTable tbody').on('click', 'tr', function (e) {
            // Ignore if clicking inside dropdown action button or menu
            if ($(e.target).closest('.dropdown, button, a, form').length) {
                return;
            }
            if ($(this).hasClass('row-selected')) {
                $(this).removeClass('row-selected');
            } else {
                $('#productsTable tbody tr.row-selected').removeClass('row-selected');
                $(this).addClass('row-selected');
            }
        });
    }

    // 2. Inisialisasi Select2 pada Filter
    App.initSelect2('#filterCode', {
        placeholder: 'Semua Kode Produk',
        allowClear: true,
        width: '100%'
    });
    App.initSelect2('#filterStatus', {
        placeholder: 'Semua Status',
        allowClear: true,
        width: '100%'
    });
    App.initSelect2('#filterProductType', {
        placeholder: 'Semua Jenis',
        allowClear: true,
        width: '100%'
    });
    App.initSelect2('#filterBranch', {
        placeholder: 'Semua Cabang',
        allowClear: true,
        width: '100%'
    });

    // Inisialisasi Select2 pada Modal Tambah dan Ubah
    $('#createProductModal').on('shown.bs.modal', function () {
        App.initSelect2InModal('#create_businessUnit', '#createProductModal', {
            minimumResultsForSearch: Infinity,
            width: '100%'
        });
        App.initSelect2InModal('#create_productType', '#createProductModal', {
            placeholder: '-- Pilih Jenis Produk --',
            allowClear: true,
            width: '100%'
        });
    });

    $('#editProductModal').on('shown.bs.modal', function () {
        App.initSelect2InModal('#edit_businessUnit', '#editProductModal', {
            minimumResultsForSearch: Infinity,
            width: '100%'
        });
        App.initSelect2InModal('#edit_productType', '#editProductModal', {
            placeholder: '-- Pilih Jenis Produk --',
            allowClear: true,
            width: '100%'
        });
    });

    // 3. Custom Filter Logic
    function applyCustomFilters() {
        if (!dataTable) return;
        dataTable.ajax.reload();
    }

    $('#btnApplyFilter').on('click', function () {
        applyCustomFilters();
    });

    $('#filterSearch').on('keyup', function (e) {
        if (e.key === 'Enter') {
            applyCustomFilters();
        }
    });

    $('#filterCode, #filterStatus, #filterProductType, #filterBranch').on('change', function () {
        applyCustomFilters();
    });

    $('#btnResetFilter').on('click', function () {
        $('#filterSearch').val('');
        $('#filterCode').val('').trigger('change.select2');
        $('#filterStatus').val('').trigger('change.select2');
        $('#filterProductType').val('').trigger('change.select2');
        $('#filterBranch').val('').trigger('change.select2');

        if (dataTable) {
            dataTable.ajax.reload();
        }
    });

    // 4. Toggle Filter Card Header Animation
    $('#filterCollapse').on('show.bs.collapse', function () {
        $('#filterToggleText').text('Sembunyikan Filter');
        $('#filterToggleIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }).on('hide.bs.collapse', function () {
        $('#filterToggleText').text('Tampilkan Filter');
        $('#filterToggleIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    });

    // 5. Modal Edit Handler
    $(document).on('click', '.btn-edit-product', function () {
        var id = $(this).data('id');
        var code = $(this).data('code');
        var name = $(this).data('name');
        var businessUnit = $(this).data('business-unit');
        var productType = $(this).data('product-type');
        var interestRate = $(this).data('interest-rate');
        var isActive = $(this).data('is-active') == '1';

        $('#editProductForm').attr('action', '<?= site_url('master/products') ?>/' + id);
        $('#edit_code').val(code);
        $('#edit_name').val(name);
        $('#edit_businessUnit').val(businessUnit).trigger('change');
        $('#edit_productType').val(productType).trigger('change');
        $('#edit_interestRate').val(interestRate);
        $('#edit_isActive').prop('checked', isActive);

        var modal = new bootstrap.Modal(document.getElementById('editProductModal'));
        modal.show();
    });

    // 6. Lihat Detail Handler
    $(document).on('click', '.btn-view-detail', function () {
        var id = $(this).data('id');
        var code = $(this).data('code');
        var name = $(this).data('name');
        var branch = $(this).data('branch');
        var type = $(this).data('type');
        var rate = $(this).data('rate');
        var status = $(this).data('status');

        $('#detail_id').text('#' + id);
        $('#detail_code').text(code);
        $('#detail_name').text(name);
        $('#detail_branch').text(branch);
        $('#detail_type').text(type || '-');
        $('#detail_rate').text(rate);

        var badgeHtml = status === 'Aktif'
            ? '<span class="detail-status-pill-active"><i class="fa-solid fa-circle-check"></i> Aktif</span>'
            : '<span class="detail-status-pill-inactive"><i class="fa-solid fa-circle-xmark"></i> Nonaktif</span>';
        $('#detail_status_badge').html(badgeHtml);

        var modal = new bootstrap.Modal(document.getElementById('detailProductModal'));
        modal.show();
    });

    // 7. Duplikasi Produk Handler
    $(document).on('click', '.btn-duplicate-product', function () {
        var code = $(this).data('code');
        var name = $(this).data('name');
        var businessUnit = $(this).data('business-unit');
        var productType = $(this).data('product-type');
        var interestRate = $(this).data('interest-rate');

        $('#create_code').val(code + '-COPY');
        $('#create_name').val(name + ' (Copy)');
        $('#create_businessUnit').val(businessUnit).trigger('change');
        $('#create_productType').val(productType).trigger('change');
        $('#create_interestRate').val(interestRate);
        $('#create_isActive').prop('checked', true);

        var modal = new bootstrap.Modal(document.getElementById('createProductModal'));
        modal.show();
    });

    // 8. Toggle Aktif/Nonaktif SweetAlert Handler
    $(document).on('click', '.btn-toggle-status', function (e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var isCurrentlyActive = $(this).data('active') == '1';
        var productName = $(this).data('product-name');

        var actionText = isCurrentlyActive ? 'Nonaktifkan' : 'Aktifkan';
        var title = actionText + ' Produk?';
        var text = 'Apakah Anda yakin ingin me-' + actionText.toLowerCase() + ' produk "' + productName + '"?';

        App.confirm({
            title: title,
            text: text,
            icon: isCurrentlyActive ? 'warning' : 'question',
            confirmButtonText: isCurrentlyActive ? '<i class="fa-solid fa-ban me-1"></i> Ya, Nonaktifkan' : '<i class="fa-solid fa-check me-1"></i> Ya, Aktifkan',
            customClass: {
                confirmButton: isCurrentlyActive ? 'btn btn-danger px-4' : 'btn btn-success px-4',
                cancelButton: 'btn btn-light px-4 me-2'
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // 8. Export Helpers (Excel / PDF / CSV)
    function exportTableToCSV(filename) {
        var csv = [];
        var rows = document.querySelectorAll("#productsTable tr");
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

    $('#btnExportCSV, #btnExportCsv').on('click', function () {
        exportTableToCSV('daftar_produk_kredit.csv');
        App.toastSuccess('Data berhasil diekspor ke CSV.');
    });

    $('#btnExportExcel').on('click', function () {
        exportTableToCSV('daftar_produk_kredit.xls');
        App.toastSuccess('Data berhasil diekspor ke Excel.');
    });

    $('#btnExportPdf').on('click', function () {
        window.print();
    });
});
</script>

<?= view('partials/shell_end') ?>
