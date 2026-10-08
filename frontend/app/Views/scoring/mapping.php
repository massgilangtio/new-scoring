<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Mapping Produk']) ?>

<?php
$products = $products ?? [];
$parameters = $parameters ?? [];
$mappings = $mappings ?? [];
$defaultPassingScore = number_format((float) ($defaultPassingScore ?? 350), 2, '.', '');

$totalMappings = count($mappings);
$totalMasterProducts = count($products);

// Count distinct products mapped
$mappedProductIds = [];
foreach ($mappings as $m) {
    if (!empty($m['product_id'])) {
        $mappedProductIds[$m['product_id']] = true;
    }
}
$distinctMappedCount = count($mappedProductIds);
?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<ul class="nav nav-pills scoring-subnav gap-2 mb-3">
    <li class="nav-item"><a class="nav-link" href="<?= site_url('scoring/parameters') ?>"><i class="fa fa-sliders me-1"></i>Parameter</a></li>
    <li class="nav-item"><a class="nav-link active" href="<?= site_url('scoring/mapping') ?>"><i class="fa fa-diagram-project me-1"></i>Mapping</a></li>
</ul>

<?= view('partials/kpi_solid', ['items' => [
    ['label' => 'Produk Terpetakan', 'value' => $distinctMappedCount, 'sub' => 'dari ' . $totalMasterProducts . ' master produk', 'tone' => 'blue', 'icon' => 'solar:box-bold-duotone'],
    ['label' => 'Versi Kebijakan', 'value' => $totalMappings, 'sub' => 'Mapping aktif digunakan', 'tone' => 'teal', 'icon' => 'solar:branching-paths-up-bold-duotone'],
    ['label' => 'Katalog Produk', 'value' => $totalMasterProducts, 'sub' => 'Source of truth master', 'tone' => 'orange', 'icon' => 'solar:database-bold-duotone'],
]]) ?>

<div class="card card-borderless mb-3 passing-score-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:medal-ribbons-star-bold-duotone" class="me-1"></iconify-icon>
            Batas Skor Layak — Semua Produk
        </h4>
        <div class="card-header-btn">
            <button type="button" class="btn btn-success btn-xs" id="btnSaveGlobalPassingScore" form="globalPassingScoreForm">
                <i class="fa fa-floppy-disk"></i><span class="btn-label-full ms-1">Simpan Pengaturan</span>
            </button>
            <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
        </div>
    </div>
    <div class="card-body">
        <form id="globalPassingScoreForm" method="post" action="<?= site_url('scoring/passing-score-setting') ?>">
            <?= csrf_field() ?>
            <div class="row g-3 align-items-center">
                <div class="col-12 col-lg-5">
                    <label class="form-label" for="globalPassingScore">
                        Default Batas Skor Layak <span class="text-danger">*</span>
                    </label>
                    <div class="input-group flex-nowrap">
                        <span class="input-group-text fw-semibold">&ge;</span>
                        <input type="number" step="0.01" min="0" max="1000" class="form-control"
                               id="globalPassingScore" name="passing_score"
                               value="<?= esc($defaultPassingScore) ?>" required>
                        <span class="input-group-text">Poin</span>
                    </div>
                    <div class="form-text">Dipakai sebagai default saat membuat mapping produk baru.</div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="form-check mt-lg-4">
                        <input class="form-check-input" type="checkbox" name="apply_to_all" value="1" id="chkApplyAllPassingScore">
                        <label class="form-check-label" for="chkApplyAllPassingScore">
                            <span class="fw-semibold">Terapkan ke semua produk</span>
                            <span class="text-muted d-block small">Perbarui batas layak pada seluruh mapping produk yang sudah ada.</span>
                        </label>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card card-borderless mb-3 filter-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:filter-bold-duotone"></iconify-icon>
            Filter Data Mapping
        </h4>
        <?= view('partials/filter_header_btn') ?>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body filter-card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="filterSearchMapping">Search Mapping / Versi</label>
                    <div class="input-group flex-nowrap">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" id="filterSearchMapping" placeholder="Cari nama versi, produk, atau kode...">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-5">
                    <label class="form-label" for="filterProductSelect">Filter Master Produk</label>
                    <select class="form-select" id="filterProductSelect">
                        <option value="">Semua Master Produk</option>
                        <?php foreach ($products as $p) : ?>
                            <option value="<?= esc($p['code']) ?>">
                                <?= esc($p['code']) ?> - <?= esc($p['name']) ?> (<?= ! empty($p['business_unit']) ? 'Syariah' : 'Konvensional' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-3 d-flex align-items-end gap-1">
                    <button type="button" class="btn btn-primary btn-xs" id="btnApplyFilter">
                        <i class="fa fa-search me-1"></i> Terapkan
                    </button>
                    <button type="button" class="btn btn-default btn-xs" id="btnResetFilter">
                        <i class="fa fa-rotate-left"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:checklist-minimalistic-bold-duotone" class="me-1"></iconify-icon>
            Daftar Mapping Produk
        </h4>
        <div class="d-flex align-items-center gap-1">
            <?= view('partials/export_header_btn', ['mode' => 'print']) ?>
            <button type="button" class="btn btn-theme btn-xs" id="btnTambahMappingTabel" data-bs-toggle="modal" data-bs-target="#mappingModal">
                <i class="fa fa-plus me-1"></i><span class="btn-label-full"> Buat Mapping</span>
            </button>
            <?= view('partials/card_widget_btn') ?>
        </div>
    </div>
    <div class="card-body">
            <table id="mappingsTable" class="table table-striped table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center fw-bold">NO</th>
                        <th style="width: 140px;" class="text-center fw-bold">KODE PRODUK</th>
                        <th class="fw-bold" style="min-width: 220px;">NAMA PRODUK (SOURCE OF TRUTH)</th>
                        <th style="width: 160px;" class="fw-bold">VERSI KEBIJAKAN</th>
                        <th style="width: 130px;" class="text-center fw-bold">BATAS LAYAK</th>
                        <th class="fw-bold" style="min-width: 240px;">PARAMETER TERPETAKAN</th>
                        <th style="width: 140px;" class="text-center fw-bold">LAMPIRAN</th>
                        <th class="text-center fw-bold" style="width: 100px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mappings)) : ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-diagram-project fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6 class="fw-bold">Belum Ada Mapping Produk</h6>
                            <p class="small mb-0">Klik tombol <strong>Buat Mapping Baru</strong> di atas untuk menghubungkan parameter ke produk kredit.</p>
                        </td>
                    </tr>
                    <?php else : ?>
                        <?php 
                        $subtleClasses = ['row-subtle-blue', 'row-subtle-green', 'row-subtle-purple', 'row-subtle-orange', 'row-subtle-red', 'row-subtle-cyan'];
                        ?>
                        <?php foreach ($mappings as $idx => $m) : ?>
                            <?php 
                            $items = $m['items'] ?? [];
                            $distinctParams = [];
                            foreach ($items as $it) {
                                $pName = $it['parameter_name'] ?? '';
                                if ($pName !== '') $distinctParams[$pName] = true;
                            }
                            $paramList = array_keys($distinctParams);
                            $rowClass = $subtleClasses[$idx % count($subtleClasses)];
                            ?>
                            <tr class="mapping-row <?= $rowClass ?>" data-code="<?= esc(strtolower($m['product_code'] ?? '')) ?>" data-text="<?= esc(strtolower(($m['product_code'] ?? '') . ' ' . ($m['product_name'] ?? '') . ' ' . ($m['version_name'] ?? ''))) ?>">
                                <td class="text-center align-middle cell-index">
                                    <span class="cell-no-badge"><?= $idx + 1 ?></span>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace"><?= esc($m['product_code'] ?? '-') ?></span>
                                        <?php if (! empty($m['product_code'])) : ?>
                                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($m['product_code']) ?>" title="Salin Kode Produk">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="fw-bold text-dark fs-6"><?= esc($m['product_name'] ?? '-') ?></div>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <span class="badge bg-primary-subtle text-primary border-0 rounded-pill" style="font-size: 10px;">
                                            <i class="fa-solid fa-database me-1"></i>Master Produk ID: #<?= esc($m['product_id'] ?? '-') ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-2 fw-semibold" style="font-size: 11.5px;">
                                        <i class="fa-solid fa-code-branch me-1"></i><?= esc($m['version_name'] ?? 'Versi Default') ?>
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill fw-bold" style="font-size: 11.5px;" title="Skor minimal untuk status LAYAK">
                                        <i class="fa-solid fa-award me-1"></i>&ge; <?= esc(number_format((float)($m['passing_score'] ?? 350), 2)) ?>
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($paramList as $pName) : ?>
                                            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill" style="font-size: 11px;">
                                                <i class="fa-solid fa-sliders text-primary me-1"></i><?= esc($pName) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill" style="font-size: 10.5px;">
                                            <?= count($items) ?> Sub Item
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if (!empty($m['attachment_name'])) : ?>
                                        <a href="<?= base_url(ltrim($m['attachment_path'] ?? '#', '/')) ?>" target="_blank" class="badge bg-info-subtle text-info border text-decoration-none px-2 py-1 rounded-pill fw-semibold" title="<?= esc($m['attachment_name']) ?>">
                                            <i class="fa-solid fa-paperclip me-1"></i>Lampiran
                                        </a>
                                    <?php else : ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php
                                    ob_start();
                                    ?>
                                            <li>
                                                <button type="button" class="dropdown-action-item btn-view-mapping" data-id="<?= esc($m['id']) ?>">
                                                    <span class="action-icon-circle action-icon-green"><i class="fa-solid fa-circle-info"></i></span>
                                                    <div class="action-text-group">
                                                        <span class="action-title">Lihat Detail</span>
                                                        <span class="action-desc">Rincian parameter & bobot</span>
                                                    </div>
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <button type="button" class="dropdown-action-item btn-edit-mapping" data-id="<?= esc($m['id']) ?>" data-name="<?= esc(($m['product_code'] ?? '') . ' - ' . ($m['version_name'] ?? '')) ?>">
                                                    <span class="action-icon-circle action-icon-blue"><i class="fa-solid fa-pen-to-square"></i></span>
                                                    <div class="action-text-group">
                                                        <span class="action-title">Edit Mapping</span>
                                                        <span class="action-desc">Ubah parameter & bobot</span>
                                                    </div>
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <button type="button" class="dropdown-action-item btn-delete-mapping text-danger" data-id="<?= esc($m['id']) ?>" data-name="<?= esc(($m['product_code'] ?? '') . ' - ' . ($m['version_name'] ?? '')) ?>">
                                                    <span class="action-icon-circle action-icon-red"><i class="fa-solid fa-trash-can"></i></span>
                                                    <div class="action-text-group">
                                                        <span class="action-title text-danger">Hapus Mapping</span>
                                                        <span class="action-desc">Hapus kebijakan dari sistem</span>
                                                    </div>
                                                </button>
                                            </li>
                                    <?php
                                    echo view('partials/action_dropdown_btn', ['menuHtml' => ob_get_clean()]);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
    </div>
</div>

<!-- ============================================================
     MODAL: KONFIGURASI MAPPING PRODUK & PARAMETER (Matching mockup-mapping-produk.png)
     ============================================================ -->
<?php
$desiredOrder = [
    'Status Kawin'          => 1,
    'Lokasi Usaha'          => 2,
    'Jenis Pekerjaan'       => 3,
    'Lama Usaha'            => 4,
    'Kapasitas Finansial'   => 5,
    'Status Tempat Tinggal' => 6,
];
$sortedParams = $parameters;
usort($sortedParams, function($a, $b) use ($desiredOrder) {
    $orderA = $desiredOrder[$a['name']] ?? 999;
    $orderB = $desiredOrder[$b['name']] ?? 999;
    return $orderA <=> $orderB;
});

$groupThemeMap = [
    'Status Kawin'          => ['theme' => 'purple', 'icon' => 'fa-solid fa-user',         'checked' => true,  'expanded' => true],
    'Lokasi Usaha'          => ['theme' => 'green',  'icon' => 'fa-solid fa-location-dot',  'checked' => true,  'expanded' => false],
    'Jenis Pekerjaan'       => ['theme' => 'blue',   'icon' => 'fa-solid fa-briefcase',     'checked' => true,  'expanded' => false],
    'Lama Usaha'            => ['theme' => 'amber',  'icon' => 'fa-solid fa-clock',         'checked' => false, 'expanded' => false],
    'Kapasitas Finansial'   => ['theme' => 'slate',  'icon' => 'fa-solid fa-chart-simple',  'checked' => false, 'expanded' => false],
    'Status Tempat Tinggal' => ['theme' => 'red',    'icon' => 'fa-solid fa-house',        'checked' => false, 'expanded' => false],
];

$dotColors = ['#ef4444', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#06b6d4'];
?>

<div class="modal fade" id="mappingModal" tabindex="-1" aria-labelledby="mappingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl mapping-modal-dialog">
        <form method="post" action="<?= site_url('scoring/mapping/store') ?>" class="modal-content mapping-modal-content" id="mappingForm">
            <?= csrf_field() ?>
            <input type="hidden" name="attachment_name" id="attachmentName" value="Kebijakan_Scoring_KMG_2026.pdf">
            <input type="hidden" name="attachment_path" id="attachmentPath" value="/uploads/scoring/Kebijakan_Scoring_KMG_2026.pdf">

            <!-- 1. Modal Header Banner with Light Blue Wave Gradient -->
            <div class="mapping-header-banner d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="mapping-header-icon-box">
                        <i class="fa-solid fa-diagram-project"></i>
                    </div>
                    <div>
                        <h4 class="mapping-header-title" id="mappingModalLabel">Konfigurasi Mapping Produk & Parameter</h4>
                        <p class="mapping-header-subtitle" id="mappingModalSubtitle">Pilih kode produk dari master katalog dan tentukan parameter scoring.</p>
                    </div>
                </div>
                <button type="button" class="mapping-header-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- 2. Modal Body -->
            <div class="mapping-modal-body">
                <!-- Top 3 Cards Row -->
                <div class="row g-3 mb-3">
                    <!-- Card 1: Kode Produk -->
                    <div class="col-12 col-lg-4">
                        <div class="mapping-card-white">
                            <div>
                                <label class="mapping-card-label" for="modalProductSelect">
                                    Kode Produk <span class="text-danger">*</span>
                                </label>
                                <div class="mapping-input-icon-wrap">
                                    <i class="fa-solid fa-cube"></i>
                                    <select class="form-select" id="modalProductSelect" name="product_id" required>
                                        <option value="">-- Pilih Kode Produk --</option>
                                        <?php foreach ($products as $p) : ?>
                                            <?php 
                                            $pCode = $p['code'] ?? '';
                                            $pName = $p['name'] ?? '';
                                            $isDefault = ($pCode === '0526');
                                            $catName = !empty($p['business_unit']) ? 'Kredit Syariah' : 'Kredit Konsumer';
                                            $statName = !empty($p['is_active']) ? 'Aktif' : 'Non-Aktif';
                                            ?>
                                            <option value="<?= esc($p['id']) ?>" 
                                                    data-code="<?= esc($pCode) ?>"
                                                    data-name="<?= esc($pName) ?>"
                                                    data-category="<?= esc($catName) ?>"
                                                    data-status="<?= esc($statName) ?>"
                                                    <?= $isDefault ? 'selected' : '' ?>>
                                                [<?= esc($pCode) ?>] <?= esc($pName) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <p class="mapping-card-hint">Daftar produk diambil dari Master Produk yang aktif.</p>
                        </div>
                    </div>

                    <!-- Card 2: Nama Versi Kebijakan & Batas Kelayakan -->
                    <div class="col-12 col-lg-4">
                        <div class="mapping-card-white">
                            <div>
                                <label class="mapping-card-label" for="modalVersionName">
                                    Nama Versi Kebijakan <span class="text-danger">*</span>
                                </label>
                                <div class="mapping-input-icon-wrap mb-2">
                                    <i class="fa-regular fa-file-lines"></i>
                                    <input type="text" class="form-control" id="modalVersionName" name="version_name" value="Versi 1.0 - Kebijakan 2026" placeholder="Versi 1.0 - Kebijakan 2026" maxlength="150" required>
                                </div>
                                <label class="mapping-card-label mt-2" for="modalPassingScore">
                                    Batas Skor Kelayakan (&ge; Nilai Ini = LAYAK) <span class="text-danger">*</span>
                                </label>
                                <div class="mapping-input-icon-wrap">
                                    <i class="fa-solid fa-award text-success"></i>
                                    <input type="number" step="0.01" min="0" max="1000" class="form-control fw-bold text-success" id="modalPassingScore" name="passing_score" value="<?= esc($defaultPassingScore) ?>" placeholder="<?= esc($defaultPassingScore) ?>" required>
                                </div>
                            </div>
                            <p class="mapping-card-hint mt-2">Batas minimal kelayakan untuk produk ini (default global: <?= esc($defaultPassingScore) ?>). Skor &ge; nilai ini berstatus <strong>LAYAK</strong>, selain itu <strong>TIDAK LAYAK</strong>.</p>
                        </div>
                    </div>

                    <!-- Card 3: Informasi Produk Panel -->
                    <div class="col-12 col-lg-4">
                        <div class="mapping-card-info">
                            <div class="mapping-card-info-header">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Informasi Produk</span>
                            </div>
                            <div class="mapping-info-list">
                                <div class="info-list-row">
                                    <span class="info-list-label">Kode Produk</span>
                                    <span class="info-list-colon">:</span>
                                    <span class="info-list-value" id="infoProdCode">0526</span>
                                </div>
                                <div class="info-list-row">
                                    <span class="info-list-label">Nama Produk</span>
                                    <span class="info-list-colon">:</span>
                                    <span class="info-list-value" id="infoProdName">KMG ONLINE</span>
                                </div>
                                <div class="info-list-row">
                                    <span class="info-list-label">Kategori</span>
                                    <span class="info-list-colon">:</span>
                                    <span class="info-list-value" id="infoProdCategory">Kredit Konsumer</span>
                                </div>
                                <div class="info-list-row">
                                    <span class="info-list-label">Batas Layak</span>
                                    <span class="info-list-colon">:</span>
                                    <span class="info-list-value text-success fw-bold" id="infoPassingScore">&ge; <?= esc($defaultPassingScore) ?></span>
                                </div>
                                <div class="info-list-row align-items-center">
                                    <span class="info-list-label">Status</span>
                                    <span class="info-list-colon">:</span>
                                    <span class="info-status-badge" id="infoProdStatus">Aktif</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Parameter & Sub Parameter Header -->
                <div class="mapping-section-header-box">
                    <div class="d-flex align-items-center gap-3">
                        <div class="mapping-section-icon-box">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <div>
                            <h6 class="mapping-section-title">Parameter & Sub Parameter</h6>
                            <p class="mapping-section-subtitle">Pilih parameter yang digunakan untuk produk ini dan atur bobot serta nilai setiap sub parameter.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Total Bobot Live Tracker Badge -->
                        <div class="mapping-total-tracker-badge badge-tracker-warning" id="mappingTotalBobotBadge" title="Total bobot harus tepat 100">
                            <i class="fa-solid fa-scale-balanced me-1"></i>
                            <span id="trackerLabel">Total Bobot:</span>
                            <strong id="trackerValue" class="ms-1">0 / 100</strong>
                            <span id="trackerStatus" class="ms-1 small">(Kurang 100)</span>
                        </div>
                        <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 fw-semibold rounded-3 shadow-sm btn-choose-master-top" id="btnModalChooseMaster">
                            <i class="fa-solid fa-bars-staggered"></i>
                            <span>Pilih dari Master</span>
                        </button>
                    </div>
                </div>

                <!-- Filter & Search Bar -->
                <div class="mapping-filter-bar">
                    <div class="mapping-search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="form-control mapping-search-input" id="mappingSearchParam" placeholder="Cari parameter atau sub parameter...">
                    </div>
                    <button type="button" class="btn-expand-all" id="btnToggleAllAccordions">
                        <i class="fa-solid fa-chevron-down" id="expandAllIcon"></i>
                        <span id="expandAllText">Expand Semua</span>
                    </button>
                </div>

                <!-- Table Column Header Grid -->
                <div class="mapping-grid-header">
                    <div class="text-center">No.</div>
                    <div>Parameter / Sub Parameter</div>
                    <div class="text-center">Kode</div>
                    <div>Deskripsi</div>
                    <div class="text-center">Bobot</div>
                    <div class="text-center">Nilai</div>
                    <div class="text-center">Jumlah</div>
                    <div class="text-center">Aksi</div>
                </div>

                <!-- Accordion Parameter Groups List (Default: Kosong sampai user pilih dari Master) -->
                <div class="mapping-groups-container" id="mappingGroupsContainer">
                    <div class="mapping-empty-state" id="mappingEmptyState">
                        <div class="mapping-empty-icon-circle">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <h6 class="mapping-empty-title">Belum Ada Parameter Terpilih</h6>
                        <p class="mapping-empty-desc">Pilih parameter scoring dari master konfigurasi untuk produk ini.</p>
                        <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm fw-semibold" id="btnEmptyChooseMaster">
                            <i class="fa-solid fa-bars-staggered"></i>
                            <span>Pilih dari Master</span>
                        </button>
                    </div>
                </div>

                <!-- Lampiran / Dokumen Kebijakan (Opsional) 2-Column Card -->
                <div class="mapping-attachment-box">
                    <div class="mapping-attachment-header">
                        <div class="mapping-attachment-icon">
                            <i class="fa-solid fa-paperclip"></i>
                        </div>
                        <div>
                            <h6 class="mapping-attachment-title">Lampiran / Dokumen Kebijakan (Opsional)</h6>
                            <p class="mapping-attachment-subtitle">Upload dokumen pendukung terkait mapping parameter untuk produk ini.</p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Left: Drag & Drop Zone -->
                        <div class="col-12 col-lg-7">
                            <input type="file" id="modalMultiAttachmentInput" class="d-none" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <div class="mapping-dropzone" id="dropZoneArea">
                                <i class="fa-solid fa-cloud-arrow-up mapping-dropzone-icon"></i>
                                <div class="mapping-dropzone-text">
                                    Drag & drop file di sini atau <span class="mapping-dropzone-link">klik untuk memilih file</span>
                                </div>
                                <p class="mapping-dropzone-subtext">Format yang didukung: PDF, DOCX, XLSX, JPG, PNG (Maks 10MB per file)</p>
                            </div>
                        </div>

                        <!-- Right: Uploaded Files List -->
                        <div class="col-12 col-lg-5">
                            <div class="mapping-files-column">
                                <div class="mapping-files-header">
                                    <span class="mapping-files-title">File Terunggah (<span id="uploadedFilesCount">2</span>)</span>
                                    <button type="button" class="btn-remove-all-files" id="btnRemoveAllFiles">Hapus Semua</button>
                                </div>
                                <div class="mapping-files-list" id="uploadedFilesList">
                                    <!-- Default Item 1: PDF -->
                                    <div class="mapping-file-item" data-filename="Kebijakan_Scoring_KMG_2026.pdf">
                                        <div class="mapping-file-left">
                                            <span class="mapping-file-badge badge-pdf">PDF</span>
                                            <div>
                                                <div class="mapping-file-name">Kebijakan_Scoring_KMG_2026.pdf</div>
                                                <div class="mapping-file-meta">245 KB  •  12 Mei 2025 09:15</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-remove-file-item" title="Hapus file">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    <!-- Default Item 2: XLSX -->
                                    <div class="mapping-file-item" data-filename="Parameter_KMG_0526.xlsx">
                                        <div class="mapping-file-left">
                                            <span class="mapping-file-badge badge-xlsx">XLSX</span>
                                            <div>
                                                <div class="mapping-file-name">Parameter_KMG_0526.xlsx</div>
                                                <div class="mapping-file-meta">128 KB  •  12 Mei 2025 09:16</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-remove-file-item" title="Hapus file">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Green Alert Notification Bar -->
                <div class="mapping-green-alert">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Pastikan seluruh parameter dan sub parameter telah diisi dengan benar. Kolom Jumlah akan dihitung otomatis berdasarkan <strong>Bobot x Nilai</strong>.</span>
                </div>

                <!-- Modal Footer Action Bar -->
                <div class="mapping-modal-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="mapping-footer-tracker" id="footerTotalBobotTracker">
                            <span class="text-muted small">Total Bobot:</span>
                            <strong class="fs-6 ms-1 text-dark" id="footerTrackerValue">0 / 100</strong>
                            <span class="badge bg-warning text-dark ms-2" id="footerTrackerBadge">Kurang 100</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <button type="button" class="btn-cancel-mapping" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark"></i> Batal
                        </button>
                        <button type="submit" class="btn-save-mapping" id="btnSubmitMapping">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Mapping
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL: PILIH PARAMETER DARI MASTER
     ============================================================ -->
<div class="modal fade" id="chooseMasterModal" tabindex="-1" aria-labelledby="chooseMasterModalLabel" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 text-white bg-primary" style="width: 40px; height: 40px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">
                        <i class="fa-solid fa-bars-staggered"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="chooseMasterModalLabel">Pilih Parameter dari Master</h5>
                        <small class="text-muted">Pilih satu atau beberapa parameter dari master katalog untuk dipetakan.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Search & Select All Toolbar -->
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3 pb-3 border-bottom">
                    <div class="position-relative flex-grow-1">
                        <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 13px;"></i>
                        <input type="text" class="form-control rounded-3 form-control-sm" id="searchMasterParamsInput" placeholder="Cari nama parameter atau sub parameter..." style="height: 38px; padding-left: 38px; font-size: 13px;">
                    </div>
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-shrink-0">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="checkAllMasterParams" style="cursor: pointer;">
                            <label class="form-check-label fw-semibold text-dark small" for="checkAllMasterParams" style="cursor: pointer;">
                                Pilih Semua
                            </label>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small">
                            <span id="countMasterSelected">0</span> Terpilih
                        </span>
                    </div>
                </div>

                <!-- Parameters List Grid -->
                <div class="master-params-list d-flex flex-column gap-2" id="masterParamsList" style="max-height: 420px; overflow-y: auto; padding-right: 4px;">
                    <!-- Dynamically populated from MASTER_PARAMS_DATA in JavaScript -->
                </div>
            </div>
            <div class="modal-footer bg-light py-3 px-4 border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-4 rounded-3 btn-sm" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" class="btn btn-primary px-4 rounded-3 btn-sm fw-semibold d-inline-flex align-items-center gap-2" id="btnApplyMasterParams">
                    <i class="fa-solid fa-check"></i>
                    <span>Gunakan Parameter Terpilih (<span id="btnApplySelectedCount">0</span>)</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: DETAIL MAPPING PRODUK
     ============================================================ -->
<div class="modal fade" id="detailMappingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-2xl rounded-4 overflow-hidden">
            <div class="modal-header bg-light py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white p-2 rounded-2">
                        <i class="fa-solid fa-diagram-project"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="detailModalTitle">Detail Mapping Produk</h5>
                        <small class="text-muted" id="detailModalSubtitle"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="detailMappingContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted small mt-2">Memuat detail mapping...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 border-top">
                <button type="button" class="btn btn-secondary px-4 btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Form delete mapping dummy -->
<form id="deleteMappingForm" method="post" action="" style="display: none;">
    <?= csrf_field() ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var mappingModal = new bootstrap.Modal(document.getElementById('mappingModal'));
    var detailMappingModal = new bootstrap.Modal(document.getElementById('detailMappingModal'));

    var form = document.getElementById('mappingForm');
    var tbody = document.getElementById('mappingTbody');
    var emptyRow = document.getElementById('emptyMappingRow');
    var totalBadge = document.getElementById('totalMappingItemsBadge');
    var paramToggles = document.querySelectorAll('.param-toggle');

    // ============================================================
    // 0. Inisialisasi Select2 pada Modal Mapping Baru & Sinkron Info Produk
    // ============================================================
    function formatPassingScore(val) {
        var n = parseFloat(val);
        if (isNaN(n)) {
            n = parseFloat(<?= json_encode($defaultPassingScore) ?>) || 350;
        }
        return n.toFixed(2);
    }

    function syncPassingScoreInfo(scoreVal) {
        var el = document.getElementById('infoPassingScore');
        if (!el) return;
        var scoreInput = document.getElementById('modalPassingScore');
        var raw = (scoreVal !== undefined && scoreVal !== null && String(scoreVal) !== '')
            ? scoreVal
            : (scoreInput ? scoreInput.value : <?= json_encode($defaultPassingScore) ?>);
        el.textContent = '\u2265 ' + formatPassingScore(raw);
    }

    function syncProductInfoPanel(selectEl) {
        var opt = selectEl.options[selectEl.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('infoProdCode').textContent = opt.getAttribute('data-code') || '-';
            document.getElementById('infoProdName').textContent = opt.getAttribute('data-name') || '-';
            document.getElementById('infoProdCategory').textContent = opt.getAttribute('data-category') || 'Kredit Konsumer';
            var statusEl = document.getElementById('infoProdStatus');
            var statusVal = opt.getAttribute('data-status') || 'Aktif';
            statusEl.textContent = statusVal;
            if (statusVal === 'Aktif') {
                statusEl.className = 'info-status-badge';
            } else {
                statusEl.className = 'badge bg-secondary-subtle text-secondary fw-bold px-2 py-0';
            }
        } else {
            document.getElementById('infoProdCode').textContent = '-';
            document.getElementById('infoProdName').textContent = '-';
            document.getElementById('infoProdCategory').textContent = '-';
            document.getElementById('infoProdStatus').textContent = '-';
        }
        // Batas Layak ikut nilai form (bukan hardcode 350)
        syncPassingScoreInfo();
    }

    function initModalProductSelect2() {
        var $select = $('#modalProductSelect');
        if (typeof App !== 'undefined' && App.initSelect2InModal) {
            App.initSelect2InModal('#modalProductSelect', '#mappingModal', {
                placeholder: '-- Pilih Kode Produk --',
                allowClear: false,
                width: '100%'
            }).on('change', function () {
                syncProductInfoPanel(this);
            });
        } else if ($.fn.select2) {
            $select.select2({
                theme: 'bootstrap-5',
                selectionCssClass: 'select2--small',
                dropdownCssClass: 'select2--small',
                dropdownParent: $('#mappingModal'),
                placeholder: '-- Pilih Kode Produk --',
                allowClear: false,
                width: '100%'
            }).on('change', function () {
                syncProductInfoPanel(this);
            });
        }
        var pSelect = document.getElementById('modalProductSelect');
        if (pSelect) {
            syncProductInfoPanel(pSelect);
            pSelect.addEventListener('change', function () {
                syncProductInfoPanel(this);
            });
        }

        var scoreInput = document.getElementById('modalPassingScore');
        if (scoreInput) {
            ['input', 'change'].forEach(function (evt) {
                scoreInput.addEventListener(evt, function () {
                    syncPassingScoreInfo(this.value);
                });
            });
            syncPassingScoreInfo(scoreInput.value);
        }
    }

    $('#mappingModal').on('shown.bs.modal', function () {
        initModalProductSelect2();
    });

    // Inisialisasi Select2 pada Filter Master Produk
    if (typeof App !== 'undefined' && App.initSelect2) {
        App.initSelect2('#filterProductSelect', {
            placeholder: 'Semua Master Produk',
            allowClear: true,
            width: '100%'
        });
    } else if ($.fn.select2) {
        $('#filterProductSelect').select2({
            theme: 'bootstrap-5',
            selectionCssClass: 'select2--small',
            dropdownCssClass: 'select2--small',
            placeholder: 'Semua Master Produk',
            allowClear: true,
            width: '100%'
        });
    }

    // ============================================================
    // 1. Master Parameter Selection & Accordion Groups Logic
    // ============================================================
    var MASTER_PARAMS_DATA = <?= json_encode($parameters, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?> || [];

    var groupThemeMap = {
        'Status Kawin':          { theme: 'purple', icon: 'fa-solid fa-user' },
        'Lokasi Usaha':          { theme: 'green',  icon: 'fa-solid fa-location-dot' },
        'Jenis Pekerjaan':       { theme: 'blue',   icon: 'fa-solid fa-briefcase' },
        'Lama Usaha':            { theme: 'amber',  icon: 'fa-solid fa-clock' },
        'Kapasitas Finansial':   { theme: 'slate',  icon: 'fa-solid fa-chart-simple' },
        'Status Tempat Tinggal': { theme: 'red',    icon: 'fa-solid fa-house' }
    };
    var themeKeys = ['blue', 'green', 'purple', 'amber', 'slate', 'red'];
    var iconKeys = ['fa-solid fa-sliders', 'fa-solid fa-chart-pie', 'fa-solid fa-list-check', 'fa-solid fa-layer-group'];
    var dotColors = ['#ef4444', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#06b6d4'];

    var chooseMasterModalEl = document.getElementById('chooseMasterModal');
    var chooseMasterModal = new bootstrap.Modal(chooseMasterModalEl);

    // Keep scroll on mappingModal when chooseMasterModal closes
    chooseMasterModalEl.addEventListener('hidden.bs.modal', function () {
        if (document.getElementById('mappingModal').classList.contains('show')) {
            document.body.classList.add('modal-open');
        }
    });

    function calculateOverallTotalBobot() {
        var container = document.getElementById('mappingGroupsContainer');
        var cards = container.querySelectorAll('.mapping-group-card');
        var total = 0;
        cards.forEach(function (card) {
            var check = card.querySelector('.mapping-group-check');
            if (check && check.checked) {
                var firstWeightInput = card.querySelector('.item-weight');
                if (firstWeightInput) {
                    total += parseFloat(firstWeightInput.value) || 0;
                }
            }
        });
        return Math.round(total * 100) / 100;
    }

    function updateOverallTotalBobotTracker() {
        var total = calculateOverallTotalBobot();
        var topBadge = document.getElementById('mappingTotalBobotBadge');
        var trackerValue = document.getElementById('trackerValue');
        var trackerStatus = document.getElementById('trackerStatus');
        var footerValue = document.getElementById('footerTrackerValue');
        var footerBadge = document.getElementById('footerTrackerBadge');

        if (trackerValue) trackerValue.textContent = total + ' / 100';
        if (footerValue) footerValue.textContent = total + ' / 100';

        if (topBadge && footerBadge) {
            topBadge.classList.remove('badge-tracker-success', 'badge-tracker-warning', 'badge-tracker-danger');
            footerBadge.className = 'badge ms-2';

            if (Math.abs(total - 100) < 0.001) {
                topBadge.classList.add('badge-tracker-success');
                if (trackerStatus) trackerStatus.textContent = '(Pas ✓)';
                footerBadge.classList.add('bg-success', 'text-white');
                footerBadge.textContent = 'Pas (100)';
            } else if (total < 100) {
                var diff = Math.round((100 - total) * 100) / 100;
                topBadge.classList.add('badge-tracker-warning');
                if (trackerStatus) trackerStatus.textContent = '(Kurang ' + diff + ')';
                footerBadge.classList.add('bg-warning', 'text-dark');
                footerBadge.textContent = 'Kurang ' + diff;
            } else {
                var diff = Math.round((total - 100) * 100) / 100;
                topBadge.classList.add('badge-tracker-danger');
                if (trackerStatus) trackerStatus.textContent = '(Lebih ' + diff + ')';
                footerBadge.classList.add('bg-danger', 'text-white');
                footerBadge.textContent = 'Lebih ' + diff;
            }
        }
    }

    function recalculateGroupTotal(paramId) {
        var groupCard = document.getElementById('paramGroup_' + paramId);
        if (!groupCard) return;
        var check = document.getElementById('checkParam_' + paramId);
        var badge = document.getElementById('groupTotalBadge_' + paramId);
        
        var firstWeightInput = groupCard.querySelector('.item-weight');
        var paramWeight = firstWeightInput ? (parseFloat(firstWeightInput.value) || 0) : 0;
        paramWeight = Math.round(paramWeight * 100) / 100;

        if (check && check.checked) {
            badge.textContent = 'Bobot: ' + paramWeight;
            badge.setAttribute('data-param-weight', paramWeight);
        } else {
            badge.textContent = 'Bobot: 0 (Nonaktif)';
            badge.setAttribute('data-param-weight', 0);
        }

        updateOverallTotalBobotTracker();
    }

    function renderEmptyMappingState() {
        var container = document.getElementById('mappingGroupsContainer');
        var empty = document.getElementById('mappingEmptyState');
        if (!empty) {
            container.innerHTML = `
                <div class="mapping-empty-state" id="mappingEmptyState">
                    <div class="mapping-empty-icon-circle">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <h6 class="mapping-empty-title">Belum Ada Parameter Terpilih</h6>
                    <p class="mapping-empty-desc">Pilih parameter scoring dari master konfigurasi untuk produk ini.</p>
                    <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm fw-semibold" id="btnEmptyChooseMaster">
                        <i class="fa-solid fa-bars-staggered"></i>
                        <span>Pilih dari Master</span>
                    </button>
                </div>
            `;
            var btn = document.getElementById('btnEmptyChooseMaster');
            if (btn) btn.addEventListener('click', openChooseMasterModal);
        } else {
            empty.style.display = 'flex';
        }
    }

    function updateMappingState() {
        var container = document.getElementById('mappingGroupsContainer');
        var emptyState = document.getElementById('mappingEmptyState');
        var cards = container.querySelectorAll('.mapping-group-card');
        
        if (cards.length === 0) {
            renderEmptyMappingState();
        } else {
            if (emptyState) emptyState.style.display = 'none';
            cards.forEach(function (card, gIdx) {
                var groupNum = gIdx + 1;
                var numEl = card.querySelector('.mapping-group-num');
                if (numEl) numEl.textContent = groupNum;

                var addBtn = card.querySelector('.btn-add-subparam');
                if (addBtn) addBtn.setAttribute('data-param-num', groupNum);

                var subRows = card.querySelectorAll('.mapping-subparam-grid-row');
                subRows.forEach(function (row, sIdx) {
                    var noEl = row.querySelector('.mapping-subparam-no');
                    if (noEl) noEl.textContent = groupNum + '.' + (sIdx + 1);
                });
            });
        }
        updateOverallTotalBobotTracker();
    }

    // Default weight lookup based on standard Bank Sumut credit scoring policy
    var defaultWeightMap = {
        'status perkawinan': 5,
        'status kawin': 5,
        'penghasilan': 10,
        'jumlah tanggungan': 5,
        'lama bekerja/usaha': 10,
        'lama usaha': 10,
        'rasio angsuran': 15,
        'rasio angsuran terhadap penghasilan bersih/bulan (idir & dar)': 15,
        'rekening gaji/usaha/penghasilan pemohon': 15,
        'fasilitas kredit existing di bank': 15,
        'jarak lokasi kantor/usaha': 10,
        'lokasi usaha': 10,
        'tempat tinggal': 10,
        'status tempat tinggal': 10,
        'jenis agunan': 5,
        'kapasitas finansial': 10,
        'jenis pekerjaan': 10
    };

    // Helper: generate HTML for a new parameter group card
    function createParamGroupCardHtml(param, groupNum) {
        var cfg = groupThemeMap[param.name] || {
            theme: themeKeys[(groupNum - 1) % themeKeys.length],
            icon: iconKeys[(groupNum - 1) % iconKeys.length]
        };
        var theme = cfg.theme;
        var icon = cfg.icon;
        var subs = param.sub_parameters || [];

        var pNameLower = (param.name || '').trim().toLowerCase();
        var paramWeight = defaultWeightMap[pNameLower] !== undefined ? defaultWeightMap[pNameLower] : 5;
        if (subs.length > 0 && subs[0].weight !== undefined && subs[0].weight !== null && parseFloat(subs[0].weight) > 0) {
            paramWeight = parseFloat(subs[0].weight);
        }
        paramWeight = Math.round(paramWeight * 100) / 100;

        var subRowsHtml = '';
        subs.forEach(function (sub, sIdx) {
            var w = paramWeight;
            var v = parseFloat(sub.value) || 0;
            var tot = parseFloat(sub.total) || (w * v);
            tot = Math.round(tot * 100) / 100;
            var dotColor = dotColors[sIdx % dotColors.length];

            subRowsHtml += `
                <div class="mapping-subparam-grid-row" data-sub-desc="${escapeHtml((sub.description || '').toLowerCase())}">
                    <div class="mapping-subparam-no">${groupNum}.${sIdx + 1}</div>
                    <div class="mapping-subparam-ident">
                        <span class="subparam-dot-badge" style="background-color: ${dotColor};">
                            <i class="fa-solid fa-circle" style="font-size: 6px;"></i>
                        </span>
                        <input type="text" class="form-control" value="${escapeHtml(sub.code || '')}" readonly style="background: #f8fafc; font-weight: 500;">
                    </div>
                    <div>
                        <input type="text" class="form-control mapping-code-input" name="mapping_code[]" value="${escapeHtml(sub.code || '')}" required>
                    </div>
                    <div>
                        <input type="text" class="form-control mapping-desc-input" name="mapping_desc[]" value="${escapeHtml(sub.description || '')}" required>
                    </div>
                    <div>
                        <input type="number" step="any" class="form-control mapping-num-input item-weight" name="mapping_weight[]" value="${w}" data-param-id="${param.id}" required>
                    </div>
                    <div>
                        <input type="number" step="any" class="form-control mapping-num-input item-value" name="mapping_value[]" value="${v}" data-param-id="${param.id}" required>
                    </div>
                    <div>
                        <div class="mapping-total-box item-total-box">${tot}</div>
                        <input type="hidden" class="item-total" name="mapping_total[]" value="${tot}">
                        <input type="hidden" class="mapping-param-id" name="mapping_param_id[]" value="${param.id}">
                        <input type="hidden" class="mapping-param-name" name="mapping_param_name[]" value="${escapeHtml(param.name)}">
                    </div>
                    <div>
                        <button type="button" class="btn-del-subparam-row" title="Hapus Sub Parameter">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        return `
            <div class="mapping-group-card group-theme-${theme}" id="paramGroup_${param.id}" data-param-id="${param.id}" data-param-name="${escapeHtml((param.name || '').toLowerCase())}">
                <!-- Group Header Bar -->
                <div class="mapping-group-header" data-param-id="${param.id}">
                    <div class="mapping-group-left">
                        <input type="checkbox" class="form-check-input mapping-group-check" id="checkParam_${param.id}" data-param-id="${param.id}" checked>
                        <span class="mapping-group-num">${groupNum}</span>
                        <span class="mapping-group-icon">
                            <i class="${icon}"></i>
                        </span>
                        <span class="mapping-group-title">${escapeHtml(param.name)}</span>
                    </div>
                    <div class="mapping-group-right">
                        <span class="mapping-total-badge" id="groupTotalBadge_${param.id}" data-param-weight="${paramWeight}">Bobot: ${paramWeight}</span>
                        <button type="button" class="btn-remove-group" data-param-id="${param.id}" title="Hapus Parameter ini">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                        <i class="fa-solid fa-chevron-up mapping-group-chevron" id="chevron_${param.id}"></i>
                    </div>
                </div>

                <!-- Group Body / Sub-parameters Table -->
                <div class="mapping-group-body collapse show" id="groupCollapse_${param.id}">
                    <div class="mapping-subparams-list" id="subparamList_${param.id}">
                        ${subRowsHtml}
                    </div>
                    <div>
                        <button type="button" class="btn-add-subparam-link btn-add-subparam" data-param-id="${param.id}" data-param-name="${escapeHtml(param.name)}" data-param-num="${groupNum}">
                            <i class="fa-solid fa-circle-plus"></i> Tambah Sub Parameter
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Modal Master: Open & Render
    function openChooseMasterModal() {
        var container = document.getElementById('mappingGroupsContainer');
        var currentSelectedIds = new Set();
        container.querySelectorAll('.mapping-group-card').forEach(function (c) {
            currentSelectedIds.add(String(c.getAttribute('data-param-id')));
        });

        var listEl = document.getElementById('masterParamsList');
        listEl.innerHTML = '';

        if (!MASTER_PARAMS_DATA || MASTER_PARAMS_DATA.length === 0) {
            listEl.innerHTML = '<div class="text-center py-4 text-muted small"><i class="fa-solid fa-circle-exclamation me-1"></i> Belum ada data Master Parameter.</div>';
            document.getElementById('countMasterSelected').textContent = '0';
            document.getElementById('btnApplySelectedCount').textContent = '0';
            chooseMasterModal.show();
            return;
        }

        MASTER_PARAMS_DATA.forEach(function (p) {
            var isChecked = currentSelectedIds.has(String(p.id));
            var subs = p.sub_parameters || [];
            var chipsHtml = '';
            subs.slice(0, 4).forEach(function (s) {
                chipsHtml += `<span class="master-subchip">${escapeHtml(s.code)} - ${escapeHtml(s.description)}</span>`;
            });
            if (subs.length > 4) {
                chipsHtml += `<span class="master-subchip">+${subs.length - 4} lainnya</span>`;
            }

            var cardDiv = document.createElement('div');
            cardDiv.className = 'master-param-card' + (isChecked ? ' is-selected' : '');
            cardDiv.setAttribute('data-param-id', p.id);
            cardDiv.setAttribute('data-param-name', (p.name || '').toLowerCase());
            cardDiv.innerHTML = `
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" class="form-check-input master-param-checkbox m-0" value="${p.id}" ${isChecked ? 'checked' : ''} style="cursor: pointer;">
                        <span class="fw-bold text-dark fs-6">${escapeHtml(p.name)}</span>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary small px-2 py-1 rounded-pill">
                        ${subs.length} Sub Parameter
                    </span>
                </div>
                <div class="master-param-subchips">
                    ${chipsHtml}
                </div>
            `;
            listEl.appendChild(cardDiv);
        });

        updateMasterModalCounts();
        document.getElementById('searchMasterParamsInput').value = '';
        chooseMasterModal.show();
    }

    function updateMasterModalCounts() {
        var totalVisible = 0;
        var totalChecked = 0;
        document.querySelectorAll('#masterParamsList .master-param-card').forEach(function (card) {
            var chk = card.querySelector('.master-param-checkbox');
            if (card.style.display !== 'none') {
                totalVisible++;
            }
            if (chk && chk.checked) {
                totalChecked++;
                card.classList.add('is-selected');
            } else {
                card.classList.remove('is-selected');
            }
        });
        document.getElementById('countMasterSelected').textContent = totalChecked;
        document.getElementById('btnApplySelectedCount').textContent = totalChecked;

        var checkAll = document.getElementById('checkAllMasterParams');
        if (checkAll) {
            checkAll.checked = (totalVisible > 0 && totalChecked >= totalVisible);
        }
    }

    // Click on Pilih dari Master buttons
    var btnChooseMasterTop = document.getElementById('btnModalChooseMaster');
    if (btnChooseMasterTop) {
        btnChooseMasterTop.addEventListener('click', openChooseMasterModal);
    }
    var btnEmptyChooseMaster = document.getElementById('btnEmptyChooseMaster');
    if (btnEmptyChooseMaster) {
        btnEmptyChooseMaster.addEventListener('click', openChooseMasterModal);
    }

    // Modal Master: Toggle item on card click
    document.getElementById('masterParamsList').addEventListener('click', function (e) {
        var card = e.target.closest('.master-param-card');
        if (!card) return;
        var chk = card.querySelector('.master-param-checkbox');
        if (e.target !== chk) {
            chk.checked = !chk.checked;
        }
        updateMasterModalCounts();
    });

    // Modal Master: Select All
    document.getElementById('checkAllMasterParams').addEventListener('change', function () {
        var isChecked = this.checked;
        document.querySelectorAll('#masterParamsList .master-param-card').forEach(function (card) {
            if (card.style.display !== 'none') {
                var chk = card.querySelector('.master-param-checkbox');
                if (chk) chk.checked = isChecked;
            }
        });
        updateMasterModalCounts();
    });

    // Modal Master: Search filter
    document.getElementById('searchMasterParamsInput').addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        document.querySelectorAll('#masterParamsList .master-param-card').forEach(function (card) {
            var name = card.getAttribute('data-param-name') || '';
            var text = card.textContent.toLowerCase();
            if (!q || name.indexOf(q) !== -1 || text.indexOf(q) !== -1) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
        updateMasterModalCounts();
    });

    // Modal Master: Apply selected parameters
    document.getElementById('btnApplyMasterParams').addEventListener('click', function () {
        var selectedIds = new Set();
        document.querySelectorAll('#masterParamsList .master-param-checkbox:checked').forEach(function (chk) {
            selectedIds.add(String(chk.value));
        });

        var container = document.getElementById('mappingGroupsContainer');

        // 1. Remove groups that are no longer checked
        container.querySelectorAll('.mapping-group-card').forEach(function (card) {
            var pid = card.getAttribute('data-param-id');
            if (!selectedIds.has(String(pid))) {
                card.remove();
            }
        });

        // 2. Add groups that are newly checked
        var currentCardsCount = container.querySelectorAll('.mapping-group-card').length;
        MASTER_PARAMS_DATA.forEach(function (p) {
            var pid = String(p.id);
            if (selectedIds.has(pid)) {
                var existingCard = document.getElementById('paramGroup_' + pid);
                if (!existingCard) {
                    currentCardsCount++;
                    var cardHtml = createParamGroupCardHtml(p, currentCardsCount);
                    container.insertAdjacentHTML('beforeend', cardHtml);
                }
            }
        });

        updateMappingState();
        chooseMasterModal.hide();
    });

    // Container Delegated Events: Header Click (Collapse), Remove Group, Del Subparam, Add Subparam, Checkbox toggle
    var mappingGroupsContainer = document.getElementById('mappingGroupsContainer');

    // 1. Group Header Click for Collapse
    mappingGroupsContainer.addEventListener('click', function (e) {
        var header = e.target.closest('.mapping-group-header');
        if (!header) return;

        // If clicking checkbox or remove button, do not collapse/expand
        if (e.target.closest('.mapping-group-check') || e.target.closest('.btn-remove-group')) {
            return;
        }

        var paramId = header.getAttribute('data-param-id');
        var collapseEl = document.getElementById('groupCollapse_' + paramId);
        var chevron = document.getElementById('chevron_' + paramId);
        if (collapseEl) {
            var bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
            if (!bsCollapse) {
                bsCollapse = new bootstrap.Collapse(collapseEl, { toggle: false });
            }
            if (collapseEl.classList.contains('show')) {
                bsCollapse.hide();
                if (chevron) {
                    chevron.classList.remove('fa-chevron-up');
                    chevron.classList.add('fa-chevron-down');
                }
            } else {
                bsCollapse.show();
                if (chevron) {
                    chevron.classList.remove('fa-chevron-down');
                    chevron.classList.add('fa-chevron-up');
                }
            }
        }
    });

    // 2. Group Checkbox Toggle (enable/disable inputs)
    mappingGroupsContainer.addEventListener('change', function (e) {
        if (e.target.classList.contains('mapping-group-check')) {
            var paramId = e.target.getAttribute('data-param-id');
            var groupCard = document.getElementById('paramGroup_' + paramId);
            var isChecked = e.target.checked;
            
            if (groupCard) {
                var inputs = groupCard.querySelectorAll('input:not(.mapping-group-check)');
                inputs.forEach(function (inp) {
                    inp.disabled = !isChecked;
                });
            }
            recalculateGroupTotal(paramId);
        }
    });

    // 3. Remove Group Button Click
    mappingGroupsContainer.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.btn-remove-group');
        if (removeBtn) {
            var paramId = removeBtn.getAttribute('data-param-id');
            var groupCard = document.getElementById('paramGroup_' + paramId);
            if (groupCard) {
                groupCard.remove();
                updateMappingState();
            }
        }
    });

    // 4. Delete Sub-parameter Row
    mappingGroupsContainer.addEventListener('click', function (e) {
        var delBtn = e.target.closest('.btn-del-subparam-row');
        if (delBtn) {
            var row = delBtn.closest('.mapping-subparam-grid-row');
            var groupCard = delBtn.closest('.mapping-group-card');
            if (row && groupCard) {
                var paramId = groupCard.getAttribute('data-param-id');
                row.remove();
                recalculateGroupTotal(paramId);
                updateMappingState();
            }
        }
    });

    // 5. Add Sub-parameter Row Dynamically
    mappingGroupsContainer.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.btn-add-subparam');
        if (addBtn) {
            var paramId = addBtn.getAttribute('data-param-id');
            var paramName = addBtn.getAttribute('data-param-name') || '';
            var groupNum = addBtn.getAttribute('data-param-num') || '1';
            var list = document.getElementById('subparamList_' + paramId);
            var check = document.getElementById('checkParam_' + paramId);
            var isChecked = check ? check.checked : true;
            
            if (list) {
                var currentCount = list.querySelectorAll('.mapping-subparam-grid-row').length;
                var nextNum = currentCount + 1;
                var subCode = nextNum < 10 ? '0' + nextNum : String(nextNum);
                var dotColor = dotColors[currentCount % dotColors.length];

                var groupCard = document.getElementById('paramGroup_' + paramId);
                var currentWeight = 5;
                if (groupCard) {
                    var firstW = groupCard.querySelector('.item-weight');
                    if (firstW) currentWeight = parseFloat(firstW.value) || 5;
                }
                currentWeight = Math.round(currentWeight * 100) / 100;
                var defaultTotal = Math.round(currentWeight * 1 * 100) / 100;

                var rowDiv = document.createElement('div');
                rowDiv.className = 'mapping-subparam-grid-row';
                rowDiv.setAttribute('data-sub-desc', '');
                rowDiv.innerHTML = `
                    <div class="mapping-subparam-no">${groupNum}.${nextNum}</div>
                    <div class="mapping-subparam-ident">
                        <span class="subparam-dot-badge" style="background-color: ${dotColor};">
                            <i class="fa-solid fa-circle" style="font-size: 6px;"></i>
                        </span>
                        <input type="text" class="form-control" value="${subCode}" readonly style="background: #f8fafc; font-weight: 500;">
                    </div>
                    <div>
                        <input type="text" class="form-control mapping-code-input" name="mapping_code[]" value="${subCode}" ${isChecked ? '' : 'disabled'} required>
                    </div>
                    <div>
                        <input type="text" class="form-control mapping-desc-input" name="mapping_desc[]" placeholder="Deskripsi..." ${isChecked ? '' : 'disabled'} required>
                    </div>
                    <div>
                        <input type="number" step="any" class="form-control mapping-num-input item-weight" name="mapping_weight[]" value="${currentWeight}" data-param-id="${paramId}" ${isChecked ? '' : 'disabled'} required>
                    </div>
                    <div>
                        <input type="number" step="any" class="form-control mapping-num-input item-value" name="mapping_value[]" value="1" data-param-id="${paramId}" ${isChecked ? '' : 'disabled'} required>
                    </div>
                    <div>
                        <div class="mapping-total-box item-total-box">${defaultTotal}</div>
                        <input type="hidden" class="item-total" name="mapping_total[]" value="${defaultTotal}" ${isChecked ? '' : 'disabled'}>
                        <input type="hidden" class="mapping-param-id" name="mapping_param_id[]" value="${paramId}" ${isChecked ? '' : 'disabled'}>
                        <input type="hidden" class="mapping-param-name" name="mapping_param_name[]" value="${escapeHtml(paramName)}" ${isChecked ? '' : 'disabled'}>
                    </div>
                    <div>
                        <button type="button" class="btn-del-subparam-row" title="Hapus Sub Parameter">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                `;
                list.appendChild(rowDiv);
                recalculateGroupTotal(paramId);
            }
        }
    });

    // 6. Real-time Calculation on Weight / Value Changes
    mappingGroupsContainer.addEventListener('input', function (e) {
        if (e.target.classList.contains('item-weight')) {
            var paramId = e.target.getAttribute('data-param-id');
            var newWeight = parseFloat(e.target.value) || 0;
            var groupCard = document.getElementById('paramGroup_' + paramId);
            if (groupCard) {
                // Sinkronkan seluruh item-weight pada grup parameter yang sama
                var weightInputs = groupCard.querySelectorAll('.item-weight');
                weightInputs.forEach(function (inp) {
                    if (inp !== e.target) {
                        inp.value = e.target.value;
                    }
                });
                // Update total skor tiap baris sub parameter (Bobot x Nilai)
                var rows = groupCard.querySelectorAll('.mapping-subparam-grid-row');
                rows.forEach(function (row) {
                    var w = parseFloat(row.querySelector('.item-weight').value) || 0;
                    var v = parseFloat(row.querySelector('.item-value').value) || 0;
                    var tot = Math.round(w * v * 100) / 100;
                    var totBox = row.querySelector('.item-total-box');
                    var totInput = row.querySelector('.item-total');
                    if (totBox) totBox.textContent = tot;
                    if (totInput) totInput.value = tot;
                });
            }
            if (paramId) {
                recalculateGroupTotal(paramId);
            }
        } else if (e.target.classList.contains('item-value')) {
            var row = e.target.closest('.mapping-subparam-grid-row');
            if (row) {
                var w = parseFloat(row.querySelector('.item-weight').value) || 0;
                var v = parseFloat(row.querySelector('.item-value').value) || 0;
                var tot = Math.round(w * v * 100) / 100;
                var totBox = row.querySelector('.item-total-box');
                var totInput = row.querySelector('.item-total');
                if (totBox) totBox.textContent = tot;
                if (totInput) totInput.value = tot;
                
                var paramId = e.target.getAttribute('data-param-id');
                if (paramId) {
                    recalculateGroupTotal(paramId);
                }
            }
        }
    });

    // Expand / Collapse Semua Button
    var isAllExpanded = false;
    document.getElementById('btnToggleAllAccordions').addEventListener('click', function () {
        isAllExpanded = !isAllExpanded;
        var icon = document.getElementById('expandAllIcon');
        var text = document.getElementById('expandAllText');

        document.querySelectorAll('.mapping-group-body').forEach(function (collapseEl) {
            var bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
            if (!bsCollapse) {
                bsCollapse = new bootstrap.Collapse(collapseEl, { toggle: false });
            }
            if (isAllExpanded) {
                bsCollapse.show();
            } else {
                bsCollapse.hide();
            }
        });

        document.querySelectorAll('.mapping-group-chevron').forEach(function (chev) {
            if (isAllExpanded) {
                chev.classList.remove('fa-chevron-down');
                chev.classList.add('fa-chevron-up');
            } else {
                chev.classList.remove('fa-chevron-up');
                chev.classList.add('fa-chevron-down');
            }
        });

        if (isAllExpanded) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            text.textContent = 'Tutup Semua';
        } else {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
            text.textContent = 'Expand Semua';
        }
    });

    // Parameter Search Filter inside Mapping modal
    document.getElementById('mappingSearchParam').addEventListener('input', function () {
        var query = this.value.trim().toLowerCase();
        document.querySelectorAll('.mapping-group-card').forEach(function (card) {
            var paramName = card.getAttribute('data-param-name') || '';
            var subRows = card.querySelectorAll('.mapping-subparam-grid-row');
            var hasMatchingSub = false;

            subRows.forEach(function (row) {
                var desc = row.getAttribute('data-sub-desc') || '';
                var descInput = row.querySelector('.mapping-desc-input');
                if (descInput) desc += ' ' + descInput.value.toLowerCase();
                if (!query || desc.indexOf(query) !== -1) {
                    row.style.display = '';
                    hasMatchingSub = true;
                } else {
                    row.style.display = 'none';
                }
            });

            if (!query || paramName.indexOf(query) !== -1 || hasMatchingSub) {
                card.style.display = '';
                if (query && hasMatchingSub) {
                    var collapseEl = card.querySelector('.mapping-group-body');
                    if (collapseEl && !collapseEl.classList.contains('show')) {
                        var bsCollapse = new bootstrap.Collapse(collapseEl, { toggle: false });
                        bsCollapse.show();
                    }
                }
            } else {
                card.style.display = 'none';
            }
        });
    });

    // ============================================================
    // 2. Drag & Drop File Upload & List Handling
    // ============================================================
    var dropZone = document.getElementById('dropZoneArea');
    var fileInput = document.getElementById('modalMultiAttachmentInput');
    var filesList = document.getElementById('uploadedFilesList');
    var filesCount = document.getElementById('uploadedFilesCount');
    var attNameInput = document.getElementById('attachmentName');
    var attPathInput = document.getElementById('attachmentPath');

    dropZone.addEventListener('click', function () {
        fileInput.click();
    });

    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.style.borderColor = '#2563eb';
        dropZone.style.background = '#eff6ff';
    });

    dropZone.addEventListener('dragleave', function (e) {
        e.preventDefault();
        dropZone.style.borderColor = '#93c5fd';
        dropZone.style.background = '#f8fafc';
    });

    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.style.borderColor = '#93c5fd';
        dropZone.style.background = '#f8fafc';
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleUploadedFiles(e.dataTransfer.files);
        }
    });

    fileInput.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            handleUploadedFiles(this.files);
        }
    });

    function handleUploadedFiles(files) {
        for (var i = 0; i < files.length; i++) {
            (function (file) {
                var ext = file.name.split('.').pop().toLowerCase();
                var badgeClass = 'badge-pdf';
                var badgeText = ext.toUpperCase();
                if (ext === 'xlsx' || ext === 'xls') badgeClass = 'badge-xlsx';
                else if (ext === 'doc' || ext === 'docx') badgeClass = 'badge-doc';

                var sizeKb = Math.round(file.size / 1024) + ' KB';
                var now = new Date();
                var dateStr = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) + ' ' + 
                              now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');

                var itemDiv = document.createElement('div');
                itemDiv.className = 'mapping-file-item';
                itemDiv.setAttribute('data-filename', file.name);
                itemDiv.innerHTML = `
                    <div class="mapping-file-left">
                        <span class="mapping-file-badge ${badgeClass}">${badgeText}</span>
                        <div>
                            <div class="mapping-file-name">${escapeHtml(file.name)}</div>
                            <div class="mapping-file-meta">${sizeKb}  •  ${dateStr}</div>
                        </div>
                    </div>
                    <button type="button" class="btn-remove-file-item" title="Hapus file">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                `;
                filesList.appendChild(itemDiv);
                updateFilesCount();

                // Upload to server
                var formData = new FormData();
                formData.append('file', file);
                formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

                fetch('<?= site_url('scoring/mapping/upload') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    if (json.rcode === '00' && json.result) {
                        attNameInput.value = json.result.attachment_name;
                        attPathInput.value = json.result.attachment_path;
                    }
                })
                .catch(function () {});
            })(files[i]);
        }
    }

    function updateFilesCount() {
        var count = filesList.querySelectorAll('.mapping-file-item').length;
        if (filesCount) filesCount.textContent = count;
        if (count === 0) {
            attNameInput.value = '';
            attPathInput.value = '';
        }
    }

    filesList.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-file-item');
        if (btn) {
            var item = btn.closest('.mapping-file-item');
            if (item) {
                item.remove();
                updateFilesCount();
            }
        }
    });

    document.getElementById('btnRemoveAllFiles').addEventListener('click', function () {
        filesList.innerHTML = '';
        updateFilesCount();
    });

    // Form submission validation with total bobot = 100 requirement
    form.addEventListener('submit', function (e) {
        var pSelect = document.getElementById('modalProductSelect');
        if (!pSelect.value) {
            e.preventDefault();
            showAlert('Peringatan', 'Silakan pilih Produk Kredit terlebih dahulu!');
            if (typeof $ !== 'undefined' && $('#modalProductSelect').hasClass('select2-hidden-accessible')) {
                $('#modalProductSelect').select2('open');
            } else {
                pSelect.focus();
            }
            return;
        }

        var checkedParams = document.querySelectorAll('.mapping-group-check:checked');
        if (checkedParams.length === 0) {
            e.preventDefault();
            showAlert('Peringatan', 'Minimal harus ada 1 Parameter yang dipilih dan aktif!');
            return;
        }

        var overallTotalBobot = calculateOverallTotalBobot();
        if (Math.abs(overallTotalBobot - 100) > 0.001) {
            e.preventDefault();
            var selisih = Math.round(Math.abs(100 - overallTotalBobot) * 100) / 100;
            var keterangan = overallTotalBobot < 100 ? 'Kurang ' + selisih : 'Lebih ' + selisih;
            showAlert(
                'Validasi Bobot Gagal',
                'Nilai bobot parameter harus tepat 100, tidak boleh kurang atau lebih!\n\nSaat ini total bobot adalah ' + overallTotalBobot + ' (' + keterangan + '). Silakan sesuaikan nilai bobot parameter hingga mencapai tepat 100 sebelum menyimpan mapping.'
            );
            var topBadge = document.getElementById('mappingTotalBobotBadge');
            if (topBadge) {
                topBadge.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }
    });

    // Save global passing score setting
    $('#btnSaveGlobalPassingScore').on('click', function () {
        var score = parseFloat($('#globalPassingScore').val());
        if (isNaN(score) || score < 0) {
            if (typeof App !== 'undefined' && App.swalError) {
                App.swalError('Batas skor tidak valid', 'Masukkan angka batas skor layak yang valid.');
            } else {
                Swal.fire({ icon: 'warning', title: 'Batas skor tidak valid', text: 'Masukkan angka batas skor layak yang valid.' });
            }
            return;
        }
        var applyAll = $('#chkApplyAllPassingScore').is(':checked');
        var confirmText = applyAll
            ? 'Nilai ini akan menjadi default global DAN diterapkan ke seluruh mapping produk yang sudah ada.'
            : 'Nilai ini akan menjadi default global untuk mapping produk baru. Mapping yang sudah ada tidak diubah.';
        App.confirmSave({
            title: 'Simpan Batas Skor Layak',
            text: confirmText
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($('#btnSaveGlobalPassingScore'), 'Menyimpan...');
                $('#globalPassingScoreForm').submit();
            }
        });
    });

    // Reset modal on "Buat Mapping Baru" button click
    $('#btnTambahMappingTabel').on('click', function () {
        var form = document.getElementById('mappingForm');
        form.action = '<?= site_url('scoring/mapping/store') ?>';
        document.getElementById('mappingModalLabel').textContent = 'Konfigurasi Mapping Produk & Parameter';
        var subtitle = document.getElementById('mappingModalSubtitle');
        if (subtitle) subtitle.textContent = 'Pilih kode produk dari master katalog dan tentukan parameter scoring.';
        document.getElementById('btnSubmitMapping').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Mapping';

        // Reset version & product
        document.getElementById('modalVersionName').value = 'Versi 1.0 - Kebijakan 2026';
        if (document.getElementById('modalPassingScore')) {
            document.getElementById('modalPassingScore').value = <?= json_encode($defaultPassingScore) ?>;
            syncPassingScoreInfo(<?= json_encode($defaultPassingScore) ?>);
        }
        var pSelect = document.getElementById('modalProductSelect');
        if (pSelect) {
            $(pSelect).val('0526').trigger('change');
            syncProductInfoPanel(pSelect);
        }

        // Reset attachments
        document.getElementById('attachmentName').value = '';
        document.getElementById('attachmentPath').value = '';
        document.getElementById('uploadedFilesList').innerHTML = '';
        updateFilesCount();

        // Reset parameters container
        var container = document.getElementById('mappingGroupsContainer');
        container.innerHTML = '';
        renderEmptyMappingState();
        updateMappingState();
    });

    // Detail modal handler (Delegated for DataTables compatibility)
    $(document).on('click', '.btn-view-mapping', function () {
        var mId = $(this).attr('data-id');
        var container = document.getElementById('detailMappingContent');
        container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="text-muted small mt-2">Memuat detail mapping...</p></div>';
        detailMappingModal.show();

        fetch('<?= site_url('scoring/mapping') ?>/' + mId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (json.rcode === '00' && json.result) {
                var data = json.result;
                document.getElementById('detailModalTitle').textContent = data.product_code + ' - ' + data.product_name;
                document.getElementById('detailModalSubtitle').textContent = 'Versi: ' + (data.version_name || 'Default');

                var html = `
                    <div class="row g-2 mb-3 p-3 bg-light rounded-3">
                        <div class="col-md-6">
                            <span class="text-muted small">Kode & Nama Produk:</span>
                            <div class="fw-bold text-dark">[${escapeHtml(data.product_code)}] ${escapeHtml(data.product_name)}</div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small">Versi Kebijakan:</span>
                            <div><span class="badge bg-success-subtle text-success border">${escapeHtml(data.version_name)}</span></div>
                        </div>
                        <div class="col-md-6 mt-2">
                            <span class="text-muted small">Batas Skor Kelayakan:</span>
                            <div><span class="badge bg-primary-subtle text-primary border fw-bold" style="font-size: 12px;"><i class="fa-solid fa-award me-1"></i>&ge; ${escapeHtml(String(data.passing_score || '350.00'))} (LAYAK)</span></div>
                        </div>
                        ${data.attachment_name ? `
                        <div class="col-md-6 mt-2">
                            <span class="text-muted small">Dokumen Lampiran:</span>
                            <div><a href="${escapeHtml(data.attachment_path)}" target="_blank" class="badge bg-info-subtle text-info border text-decoration-none"><i class="fa-solid fa-paperclip me-1"></i>${escapeHtml(data.attachment_name)}</a></div>
                        </div>` : ''}
                    </div>
                    <div class="table-responsive rounded-3 border">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light" style="font-size: 11.5px;">
                                <tr>
                                    <th>PARAMETER</th>
                                    <th>KODE</th>
                                    <th>DESKRIPSI</th>
                                    <th class="text-end">BOBOT</th>
                                    <th class="text-end">NILAI</th>
                                    <th class="text-end">JUMLAH</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                var items = data.items || [];
                items.forEach(function (it) {
                    html += `
                        <tr>
                            <td><span class="badge bg-light text-primary border">${escapeHtml(it.parameter_name)}</span></td>
                            <td><code>${escapeHtml(it.code)}</code></td>
                            <td class="fw-semibold text-dark">${escapeHtml(it.description)}</td>
                            <td class="text-end">${escapeHtml(String(it.weight))}</td>
                            <td class="text-end">${escapeHtml(String(it.value))}</td>
                            <td class="text-end fw-bold text-success">${escapeHtml(String(it.total))}</td>
                        </tr>
                    `;
                });
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger">Gagal memuat detail mapping.</div>';
            }
        })
        .catch(function () {
            container.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan koneksi.</div>';
        });
    });

    // Edit modal handler (Delegated for DataTables compatibility)
    $(document).on('click', '.btn-edit-mapping', function () {
        var mId = $(this).attr('data-id');
        var form = document.getElementById('mappingForm');
        form.action = '<?= site_url('scoring/mapping') ?>/' + mId + '/update';

        document.getElementById('mappingModalLabel').textContent = 'Edit Mapping Produk & Parameter';
        var subtitle = document.getElementById('mappingModalSubtitle');
        if (subtitle) subtitle.textContent = 'Perbarui parameter scoring dan nilai bobot untuk produk kredit.';
        document.getElementById('btnSubmitMapping').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan';

        // Clear existing groups while loading
        var container = document.getElementById('mappingGroupsContainer');
        container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="text-muted small mt-2">Memuat konfigurasi parameter mapping...</p></div>';

        mappingModal.show();

        fetch('<?= site_url('scoring/mapping') ?>/' + mId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (json.rcode === '00' && json.result) {
                var data = json.result;

                // 1. Set Product Select & sync info
                var pSelect = document.getElementById('modalProductSelect');
                if (pSelect) {
                    $(pSelect).val(String(data.product_id)).trigger('change');
                    syncProductInfoPanel(pSelect);
                }

                // 2. Set Version Name & Passing Score
                document.getElementById('modalVersionName').value = data.version_name || '';
                if (document.getElementById('modalPassingScore')) {
                    var scoreVal = formatPassingScore(data.passing_score);
                    document.getElementById('modalPassingScore').value = scoreVal;
                    syncPassingScoreInfo(scoreVal);
                }

                // 3. Set Attachment
                var filesList = document.getElementById('uploadedFilesList');
                filesList.innerHTML = '';
                if (data.attachment_name) {
                    document.getElementById('attachmentName').value = data.attachment_name;
                    document.getElementById('attachmentPath').value = data.attachment_path || '';
                    var ext = (data.attachment_name || '').split('.').pop().toLowerCase();
                    var badgeClass = 'badge-pdf';
                    if (ext === 'xlsx' || ext === 'xls') badgeClass = 'badge-xlsx';
                    else if (ext === 'doc' || ext === 'docx') badgeClass = 'badge-doc';
                    var itemDiv = document.createElement('div');
                    itemDiv.className = 'mapping-file-item';
                    itemDiv.setAttribute('data-filename', data.attachment_name);
                    itemDiv.innerHTML = `
                        <div class="mapping-file-left">
                            <span class="mapping-file-badge ${badgeClass}">${escapeHtml(ext.toUpperCase())}</span>
                            <div>
                                <div class="mapping-file-name">${escapeHtml(data.attachment_name)}</div>
                                <div class="mapping-file-meta">Dokumen Tersimpan</div>
                            </div>
                        </div>
                        <button type="button" class="btn-remove-file-item" title="Hapus file">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    `;
                    filesList.appendChild(itemDiv);
                } else {
                    document.getElementById('attachmentName').value = '';
                    document.getElementById('attachmentPath').value = '';
                }
                updateFilesCount();

                // 4. Group items by parameter
                var paramGroupsMap = {};
                var paramGroupsOrder = [];
                (data.items || []).forEach(function (it) {
                    var key = it.parameter_id ? 'id_' + it.parameter_id : 'name_' + it.parameter_name;
                    if (!paramGroupsMap[key]) {
                        paramGroupsMap[key] = {
                            id: it.parameter_id || ('cust_' + Math.random().toString(36).substring(2, 7)),
                            name: it.parameter_name,
                            sub_parameters: []
                        };
                        paramGroupsOrder.push(key);
                    }
                    paramGroupsMap[key].sub_parameters.push({
                        code: it.code,
                        description: it.description,
                        weight: parseFloat(it.weight) || 0,
                        value: parseFloat(it.value) || 0,
                        total: parseFloat(it.total) || 0
                    });
                });

                container.innerHTML = '';
                if (paramGroupsOrder.length === 0) {
                    renderEmptyMappingState();
                } else {
                    paramGroupsOrder.forEach(function (key, idx) {
                        var pGroup = paramGroupsMap[key];
                        var cardHtml = createParamGroupCardHtml(pGroup, idx + 1);
                        container.insertAdjacentHTML('beforeend', cardHtml);
                    });
                }

                updateMappingState();
                paramGroupsOrder.forEach(function (key) {
                    var pGroup = paramGroupsMap[key];
                    recalculateGroupTotal(pGroup.id);
                });
                updateOverallTotalBobotTracker();
            } else {
                showAlert('Error', 'Gagal memuat data mapping: ' + (json.message || 'Data tidak ditemukan'));
            }
        })
        .catch(function () {
            showAlert('Error', 'Terjadi kesalahan koneksi saat memuat mapping.');
        });
    });

    // 1. Inisialisasi DataTables (Custom Layout, Pagination & Filter Persis Master Produk)
    var dataTable = null;
    if ($.fn.DataTable) {
        dataTable = $('#mappingsTable').DataTable({
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
                zeroRecords: '<div class="empty-state py-5 text-center"><i class="fa-solid fa-diagram-project fa-2x text-muted mb-2 opacity-50"></i><p class="mb-0 text-muted">Tidak ada data mapping yang sesuai.</p></div>',
                emptyTable: '<div class="empty-state py-5 text-center"><i class="fa-solid fa-boxes-stacked fa-2x text-muted mb-2 opacity-50"></i><p class="mb-0 text-muted">Belum ada data mapping.</p></div>',
                paginate: {
                    first:    '<i class="fa-solid fa-angles-left"></i>',
                    previous: '<i class="fa-solid fa-angle-left"></i>',
                    next:     '<i class="fa-solid fa-angle-right"></i>',
                    last:     '<i class="fa-solid fa-angles-right"></i>'
                }
            },
            columnDefs: [
                { orderable: false, targets: [0, 5, 6, 7] } // NO, PARAMS, LAMPIRAN, AKSI
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

    // 2. Delegated Delete Handler (DataTables Safe)
    $(document).on('click', '.btn-delete-mapping', function () {
        var id = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        var deleteForm = document.getElementById('deleteMappingForm');
        deleteForm.action = '<?= site_url('scoring/mapping') ?>/' + id + '/delete';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Mapping?',
                text: 'Apakah Anda yakin ingin menghapus mapping "' + name + '"?',
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
            if (confirm('Apakah Anda yakin ingin menghapus mapping "' + name + '"?')) {
                deleteForm.submit();
            }
        }
    });

    // 3. DataTables Filter Integration
    function applyFilters() {
        var searchVal = ($('#filterSearchMapping').val() || '').trim();
        var prodVal = ($('#filterProductSelect').val() || '').trim();

        if (dataTable) {
            if (prodVal) {
                dataTable.column(1).search(prodVal);
            } else {
                dataTable.column(1).search('');
            }
            dataTable.search(searchVal).draw();
        } else {
            document.querySelectorAll('#mappingsTable tbody tr.mapping-row').forEach(function (tr) {
                var rowText = tr.getAttribute('data-text') || '';
                var rowCode = tr.getAttribute('data-code') || '';
                var matchSearch = !searchVal || rowText.indexOf(searchVal.toLowerCase()) !== -1;
                var matchProd = !prodVal || rowCode.indexOf(prodVal.toLowerCase()) !== -1;
                tr.style.display = (matchSearch && matchProd) ? '' : 'none';
            });
        }
    }

    $('#btnApplyFilter').on('click', applyFilters);
    $('#filterSearchMapping').on('keyup', function (e) {
        if (e.key === 'Enter') applyFilters();
    });
    $('#filterProductSelect').on('change', applyFilters);

    $('#btnResetFilter').on('click', function () {
        $('#filterSearchMapping').val('');
        $('#filterProductSelect').val('').trigger('change');
        if (dataTable) {
            dataTable.search('').columns().search('').draw();
        } else {
            applyFilters();
        }
    });

    function showAlert(title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: title, text: text });
        } else {
            alert(text);
        }
    }

    function escapeHtml(text) {
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, function (m) { return map[m]; });
    }
});
</script>

<?= view('partials/shell_end') ?>
