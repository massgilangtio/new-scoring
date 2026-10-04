<?php
/**
 * View variables injected by Reports::products()
 *
 * @var array       $profile   Authenticated user profile data
 * @var array       $products  List of credit products for the dropdown
 * @var array|null  $history   Product history data (audit logs & versions), or null if none selected
 * @var int         $selected  Currently selected product ID from the query string
 */

$prod      = $history['product'] ?? null;
$isActive  = $prod['is_active'] ?? true;
$auditLogs = $history['audit_logs'] ?? [];
$versions  = $history['versions'] ?? [];
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Riwayat Produk']) ?>

<!-- Subnav -->
<ul class="subnav mb-4">
    <li><a href="<?= site_url('reports/scoring') ?>"><i class="fa-solid fa-chart-bar"></i> Scoring</a></li>
    <li><a href="<?= site_url('reports/debtors') ?>"><i class="fa-solid fa-users"></i> Riwayat Debitur</a></li>
    <li><a href="<?= site_url('reports/products') ?>" class="active"><i class="fa-solid fa-box-archive"></i> Riwayat Produk</a></li>
    <li><a href="<?= site_url('reports/changes') ?>"><i class="fa-solid fa-sliders"></i> Perubahan Parameter</a></li>
</ul>

<!-- 1. Header Banner -->
<div class="module-banner-wrap position-relative mb-4">
    <img class="module-banner-img"
         src="<?= base_url('assets/images/mockup-background-header-riwayatproduk.png') ?>?v=4"
         alt="Riwayat Produk Banner">
    <?php if ($prod) : ?>
    <div class="rp-banner-overlay-card">
        <div class="rp-banner-card-icon">
            <i class="fa-solid fa-box-open"></i>
        </div>
        <div class="rp-banner-card-info">
            <span class="rp-banner-card-label">Produk Terpilih</span>
            <h4 class="rp-banner-card-code mb-0"><?= esc($prod['code']) ?></h4>
            <div class="rp-banner-card-name text-truncate" title="<?= esc($prod['name']) ?>"><?= esc(strtoupper($prod['name'])) ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- 2. Filter Card -->
<div class="filter-card mb-4">
    <div class="filter-card-header">
        <div class="filter-header-left">
            <div class="filter-icon-badge">
                <i class="fa-solid fa-filter"></i>
            </div>
            <div>
                <h6 class="filter-title">Filter Produk</h6>
                <p class="filter-subtitle">Pilih produk kredit untuk melihat riwayat perubahan dan versi model scoring.</p>
            </div>
        </div>
    </div>
    <div class="filter-card-body">
        <form method="get" action="<?= site_url('reports/products') ?>">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-8">
                    <label class="filter-form-label" for="product_filter">Produk Kredit</label>
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-box-open filter-input-icon text-primary"></i>
                        <select id="product_filter" name="id" class="form-select filter-select2" data-placeholder="Pilih atau cari produk..." style="padding-left:42px !important;">
                            <option value=""></option>
                            <?php foreach ($products as $product) : ?>
                                <option value="<?= esc($product['id']) ?>" <?= (int) $selected === (int) $product['id'] ? 'selected' : '' ?>>
                                    <?= esc($product['code']) ?> — <?= esc($product['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <button type="submit" class="btn btn-primary-gradient w-100">
                        <i class="fa-solid fa-magnifying-glass me-2"></i> Tampilkan Riwayat
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Error Alert -->
<?php if (! empty($history['error'])) : ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
        <i class="fa-solid fa-circle-exclamation fs-5 flex-shrink-0"></i>
        <div><?= esc($history['error']) ?></div>
    </div>
<?php endif; ?>

<!-- ===== HASIL DATA: TWO-COLUMN SIDE-BY-SIDE LAYOUT (MOCKUP STYLE) ===== -->
<?php if (! empty($history['product'])) : ?>
<div class="row g-4 align-items-start mb-4">

    <!-- ===== SISI KIRI: RIWAYAT PERUBAHAN DATA PRODUK ===== -->
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm border-0 h-100">
            <!-- Card Header -->
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rp-card-icon rp-icon-purple">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h6 class="fw-bold mb-0 text-dark" style="font-size:15px;">Riwayat Perubahan Data Produk</h6>
                                <span class="badge rp-badge-product"><?= esc($prod['name']) ?></span>
                            </div>
                            <p class="text-muted small mb-0 mt-1">Daftar log perubahan data pada produk kredit yang dipilih</p>
                        </div>
                    </div>
                    <div class="rp-stat-pill rp-stat-pill-green">
                        <div class="rp-stat-icon"><i class="fa-regular fa-file-lines"></i></div>
                        <div>
                            <div class="rp-stat-label">Total Perubahan</div>
                            <div class="rp-stat-value" id="rpStatPerubahanVal"><?= count($auditLogs) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <?php if (! empty($auditLogs)) : ?>
                <!-- Toolbar: Page Length, Search & Filter Button (Mockup Style) -->
                <div class="px-3 py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white">
                    <!-- Kiri: Page Length Selector -->
                    <div class="d-flex align-items-center gap-2 text-muted small fw-medium">
                        <span>Tampilkan</span>
                        <select id="auditLogPageLength" class="form-select form-select-sm rp-length-select">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span>data</span>
                    </div>

                    <!-- Kanan: Search Bar & Filter Toggle Button -->
                    <div class="d-flex align-items-center gap-2">
                        <div class="rp-search-box">
                            <i class="fa-solid fa-magnifying-glass rp-search-icon"></i>
                            <input type="text" id="auditLogSearchInput" class="form-control form-control-sm rp-search-input" placeholder="Cari data...">
                            <button type="button" class="btn btn-sm p-0 rp-search-clear" id="auditLogSearchClear" title="Bersihkan pencarian">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm rp-btn-filter" id="btnToggleAuditFilter" data-bs-toggle="collapse" data-bs-target="#auditFilterPanel" aria-expanded="false" title="Filter Lanjutan">
                            <i class="fa-solid fa-sliders"></i>
                        </button>
                    </div>
                </div>

                <!-- Collapsible Advanced Filter Panel -->
                <div class="collapse border-bottom bg-light px-3 py-2" id="auditFilterPanel">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-sm-4">
                            <label class="form-label small text-muted mb-1 fw-semibold">Filter Aksi</label>
                            <select id="filterAuditAction" class="form-select form-select-sm">
                                <option value="">Semua Aksi</option>
                                <option value="Tambah Produk">Tambah Produk</option>
                                <option value="Ubah Produk">Ubah Produk</option>
                                <option value="Hapus Produk">Hapus Produk</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label small text-muted mb-1 fw-semibold">Filter Role Pelaksana</label>
                            <select id="filterAuditRole" class="form-select form-select-sm">
                                <option value="">Semua Role</option>
                                <option value="Admin IT">Admin IT</option>
                                <option value="System">System</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-4 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-light border text-danger w-100 mt-sm-4" id="btnResetAuditFilters">
                                <i class="fa-solid fa-rotate-left me-1"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <?php
                $fieldLabels = [
                    'code'            => 'Kode Produk',
                    'name'            => 'Nama Produk',
                    'business_unit'   => 'Status Branch',
                    'interest_rate'   => 'Suku Bunga',
                    'product_type_id' => 'Jenis Produk',
                    'is_active'       => 'Status Aktif',
                ];
                ?>
                <table id="auditLogTable" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                        <tr class="table-light">
                            <th style="min-width:165px;">WAKTU PERUBAHAN</th>
                            <th style="min-width:130px;">AKSI</th>
                            <th style="min-width:115px;">ROLE PELAKSANA</th>
                            <th style="min-width:200px;">DETAIL PERUBAHAN</th>
                            <th class="text-center" style="width:35px;"><i class="fa-solid fa-ellipsis-vertical text-muted"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($auditLogs as $log) :
                        $action = strtolower($log['action'] ?? '');
                        if (str_contains($action, 'created')) {
                            $actionLabel = 'Tambah Produk';
                            $actionClass = 'rp-badge-action-green';
                            $actionIcon  = 'fa-plus';
                        } elseif (str_contains($action, 'updated')) {
                            $actionLabel = 'Ubah Produk';
                            $actionClass = 'rp-badge-action-yellow';
                            $actionIcon  = 'fa-pen';
                        } elseif (str_contains($action, 'deleted')) {
                            $actionLabel = 'Hapus Produk';
                            $actionClass = 'rp-badge-action-red';
                            $actionIcon  = 'fa-trash';
                        } else {
                            $actionLabel = $log['action'];
                            $actionClass = 'rp-badge-role';
                            $actionIcon  = 'fa-circle-info';
                        }

                        $before = $log['before_data'] ?? [];
                        $after  = $log['after_data'] ?? [];
                        $diffs  = [];

                        if ($before && $after) {
                            foreach ($after as $key => $val) {
                                $oldVal = $before[$key] ?? null;
                                if ((string) $oldVal !== (string) $val) {
                                    $label = $fieldLabels[$key] ?? $key;
                                    if ($key === 'is_active') {
                                        $oldDisplay = $oldVal ? 'Aktif' : 'Non-aktif';
                                        $newDisplay = $val ? 'Aktif' : 'Non-aktif';
                                    } elseif ($key === 'business_unit') {
                                        $oldDisplay = (string) $oldVal === '0' ? 'Konvensional' : 'Syariah';
                                        $newDisplay = (string) $val === '0' ? 'Konvensional' : 'Syariah';
                                    } else {
                                        $oldDisplay = ($oldVal === null || $oldVal === '') ? '-' : $oldVal;
                                        $newDisplay = ($val === null || $val === '') ? '-' : $val;
                                    }
                                    $diffs[] = compact('label', 'key', 'oldDisplay', 'newDisplay');
                                }
                            }
                        } elseif ($after) {
                            foreach ($after as $key => $val) {
                                $label = $fieldLabels[$key] ?? $key;
                                if ($key === 'is_active') {
                                    $newDisplay = $val ? 'Aktif' : 'Non-aktif';
                                } elseif ($key === 'business_unit') {
                                    $newDisplay = (string) $val === '0' ? 'Konvensional' : 'Syariah';
                                } else {
                                    $newDisplay = ($val === null || $val === '') ? '-' : $val;
                                }
                                $diffs[] = ['label' => $label, 'key' => $key, 'oldDisplay' => null, 'newDisplay' => $newDisplay];
                            }
                        }
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-regular fa-clock text-muted" style="font-size:14px;flex-shrink:0;"></i>
                                <div>
                                    <div class="fw-semibold text-dark" style="font-size:12px;"><?= esc($log['occurred_at'] ?? '-') ?></div>
                                    <div class="text-muted" style="font-size:11px;">(+07:00 WIB)</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge rounded-pill <?= $actionClass ?>">
                                <i class="fa-solid <?= $actionIcon ?> me-1"></i><?= esc($actionLabel) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge rounded-pill rp-badge-role">
                                <i class="fa-solid fa-user-shield me-1 text-muted"></i><?= esc($log['actor_role_name'] ?? 'Admin IT') ?>
                            </span>
                        </td>
                        <td>
                            <?php if (! empty($diffs)) : ?>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <?php foreach ($diffs as $diff) : ?>
                                <span class="rp-diff-chip">
                                    <span class="rp-diff-key"><?= esc($diff['label']) ?>:</span>
                                    <?php if ($diff['oldDisplay'] !== null) : ?>
                                        <span class="rp-diff-val-old"><?= esc($diff['oldDisplay']) ?></span>
                                        <i class="fa-solid fa-arrow-right rp-diff-arrow"></i>
                                    <?php endif; ?>
                                    <span class="rp-diff-val-new <?= $diff['key'] === 'code' ? 'rp-diff-blue' : '' ?>"><?= esc($diff['newDisplay']) ?></span>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php else : ?>
                            <span class="text-muted small fst-italic">Tidak ada detail perubahan tercatat.</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-link text-muted p-0" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Menu Opsi">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2" style="font-size:12px;min-width:180px;border-radius:10px;z-index:1070;">
                                    <li>
                                        <span class="dropdown-header text-muted fw-semibold" style="font-size:11px;">Opsi Log Audit</span>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" onclick="navigator.clipboard.writeText('<?= esc($log['occurred_at'] ?? '') ?>'); if(window.App && App.toastInfo) App.toastInfo('Waktu disalin ke clipboard');">
                                            <i class="fa-regular fa-copy text-primary" style="font-size:13px;"></i> Salin Waktu Log
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" onclick="if(window.App && App.alertInfo) App.alertInfo('Detail Log', 'Aksi: <?= esc($actionLabel) ?><br>Waktu: <?= esc($log['occurred_at'] ?? '-') ?><br>Role: <?= esc($log['actor_role_name'] ?? 'Admin IT') ?>');">
                                            <i class="fa-solid fa-circle-info text-info" style="font-size:13px;"></i> Lihat Ringkasan
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-clock-rotate-left fs-1 text-muted mb-3 d-block opacity-50"></i>
                    <h6 class="fw-bold">Belum Ada Riwayat Perubahan</h6>
                    <p class="text-muted small">Belum ada perubahan data yang tercatat untuk produk ini.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /col kiri -->

    <!-- ===== SISI KANAN: RIWAYAT VERSI MODEL SCORING ===== -->
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm border-0 h-100">
            <!-- Card Header -->
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rp-card-icon rp-icon-blue">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h6 class="fw-bold mb-0 text-dark" style="font-size:15px;">Riwayat Versi Model Scoring</h6>
                                <span class="badge rp-badge-code"><?= esc($prod['code'] ?? '') ?></span>
                            </div>
                            <p class="text-muted small mb-0 mt-1">Daftar versi konfigurasi model scoring untuk produk ini</p>
                        </div>
                    </div>
                    <div class="rp-stat-pill rp-stat-pill-purple">
                        <div class="rp-stat-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <div>
                            <div class="rp-stat-label">Total Versi</div>
                            <div class="rp-stat-value"><?= count($versions) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <?php if (! empty($versions)) : ?>
                <table id="productHistoryTable" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                        <tr class="table-light">
                            <th class="text-center" style="width:45px;">NO</th>
                            <th>NOMOR VERSI</th>
                            <th>STATUS VERSI</th>
                            <th>DIAKTIFKAN PADA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($versions as $version) : ?>
                        <tr>
                            <td class="text-center text-muted fw-semibold"><?= $no++ ?></td>
                            <td>
                                <span class="fw-bold text-dark font-monospace">Versi <?= esc($version['version_no']) ?></span>
                            </td>
                            <td>
                                <?php
                                $vStatus = strtolower($version['status'] ?? '');
                                switch ($vStatus) {
                                    case 'active':
                                        $vc = 'bg-success-subtle text-success border border-success-subtle';
                                        $vi = 'fa-circle-check';
                                        break;
                                    case 'draft':
                                        $vc = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                        $vi = 'fa-file-lines';
                                        break;
                                    default:
                                        $vc = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
                                        $vi = 'fa-box-archive';
                                }
                                ?>
                                <span class="badge <?= $vc ?> px-2 py-1 text-capitalize">
                                    <i class="fa-solid <?= $vi ?> me-1"></i><?= esc($version['status']) ?>
                                </span>
                            </td>
                            <td style="font-size:12px;">
                                <i class="fa-regular fa-calendar-check me-1 text-muted"></i>
                                <?= esc($version['activated_at'] ?? 'Belum pernah diaktifkan') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <div class="text-center py-5">
                    <div class="rp-empty-doc-wrapper mb-3">
                        <i class="fa-regular fa-file-lines text-primary" style="font-size:32px;"></i>
                        <span class="rp-empty-clock-badge">
                            <i class="fa-solid fa-clock text-white" style="font-size:11px;"></i>
                        </span>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Belum Ada Riwayat Versi</h6>
                    <p class="text-muted small mb-0">Produk ini belum memiliki riwayat konfigurasi versi model scoring.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /col kanan -->

</div><!-- /row -->

<?php elseif ($selected > 0 && empty($history['error'])) : ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body text-center py-5">
        <i class="fa-solid fa-inbox fs-1 text-muted opacity-50 mb-3 d-block"></i>
        <h6 class="fw-bold">Data Riwayat Tidak Ditemukan</h6>
        <p class="text-muted small">Tidak ada informasi riwayat yang tersedia untuk produk yang dipilih.</p>
    </div>
</div>
<?php endif; ?>

<!-- ===== CUSTOM STYLES FOR RIWAYAT PRODUK ===== -->
<style>
/* 1. Banner Card Mengambang (Produk Terpilih) */
.rp-banner-overlay-card {
    position: absolute;
    right: 24px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 14px;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 12px 30px -6px rgba(0, 0, 0, 0.12), 0 4px 10px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.85);
    max-width: 320px;
    z-index: 5;
}

@media (max-width: 768px) {
    .rp-banner-overlay-card {
        display: none;
    }
}

.rp-banner-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0284c7;
    font-size: 20px;
    flex-shrink: 0;
}

.rp-banner-card-info {
    min-width: 0;
}

.rp-banner-card-label {
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
    display: block;
    line-height: 1.2;
}

.rp-banner-card-code {
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.5px;
    font-size: 18px;
}

.rp-banner-card-name {
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    max-width: 190px;
    line-height: 1.3;
}

/* 2. Card Header Icons & Badges */
.rp-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

.rp-icon-purple {
    background: #f3e8ff;
    color: #7c3aed;
}

.rp-icon-blue {
    background: #e0f2fe;
    color: #0284c7;
}

.rp-badge-product {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #cbd5e1 !important;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
}

.rp-badge-code {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe !important;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
}

/* 3. Stat Pills */
.rp-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    border-radius: 12px;
    padding: 6px 14px;
    flex-shrink: 0;
}

.rp-stat-pill-green {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
}

.rp-stat-pill-green .rp-stat-icon {
    color: #10b981;
    font-size: 22px;
    display: flex;
    align-items: center;
}

.rp-stat-pill-green .rp-stat-value {
    font-size: 22px;
    font-weight: 800;
    color: #047857;
    line-height: 1;
}

.rp-stat-pill-purple {
    background: #f5f3ff;
    border: 1px solid #ddd6fe;
}

.rp-stat-pill-purple .rp-stat-icon {
    color: #7c3aed;
    font-size: 22px;
    display: flex;
    align-items: center;
}

.rp-stat-pill-purple .rp-stat-value {
    font-size: 22px;
    font-weight: 800;
    color: #5b21b6;
    line-height: 1;
}

.rp-stat-label {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    line-height: 1.2;
}

/* 4. Action & Role Badges */
.rp-badge-action-green {
    background: #d1fae5;
    color: #047857;
    border: 1px solid #a7f3d0;
    font-size: 11px;
    padding: 4px 10px;
    font-weight: 600;
}

.rp-badge-action-yellow {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
    font-size: 11px;
    padding: 4px 10px;
    font-weight: 600;
}

.rp-badge-action-red {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
    font-size: 11px;
    padding: 4px 10px;
    font-weight: 600;
}

.rp-badge-role {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    font-size: 11px;
    padding: 4px 10px;
    font-weight: 500;
}

/* 5. Diff Chips */
.rp-diff-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 3px 8px;
    font-size: 12px;
}

.rp-diff-key {
    font-weight: 600;
    color: #475569;
}

.rp-diff-val-old {
    background: #fee2e2;
    color: #dc2626;
    border-radius: 4px;
    padding: 1px 6px;
    text-decoration: line-through;
    font-weight: 500;
}

.rp-diff-val-new {
    background: #dcfce7;
    color: #16a34a;
    border-radius: 4px;
    padding: 1px 6px;
    font-weight: 600;
}

.rp-diff-val-new.rp-diff-blue {
    background: #e0f2fe;
    color: #0284c7;
}

.rp-diff-arrow {
    color: #94a3b8;
    font-size: 10px;
}

/* 6. Empty State Versi Styling */
.rp-empty-doc-wrapper {
    position: relative;
    display: inline-block;
    width: 64px;
    height: 64px;
    background: #eff6ff;
    border-radius: 16px;
    line-height: 64px;
    text-align: center;
}

.rp-empty-clock-badge {
    position: absolute;
    bottom: -4px;
    right: -4px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #0284c7;
    border: 2px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 7. DataTables Toolbar & Controls (Mockup Style) */
.rp-length-select {
    width: auto !important;
    min-width: 68px;
    border-radius: 8px !important;
    border: 1px solid #e2e8f0 !important;
    font-size: 12px !important;
    background-color: #fff !important;
    padding: 4px 24px 4px 10px !important;
    cursor: pointer;
}

.rp-search-box {
    position: relative;
    width: 200px;
}

.rp-search-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #94a3b8;
    pointer-events: none;
}

.rp-search-input {
    padding-left: 32px !important;
    padding-right: 28px !important;
    font-size: 12px !important;
    border-radius: 8px !important;
    border: 1px solid #e2e8f0 !important;
    background-color: #ffffff !important;
    height: 33px;
    transition: all 0.2s ease;
}

.rp-search-input:focus {
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
}

.rp-search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    cursor: pointer;
    font-size: 13px;
    line-height: 1;
    display: none;
    border: none;
    background: transparent;
}

.rp-search-clear:hover {
    color: #dc2626;
}

.rp-btn-filter {
    height: 33px;
    border-radius: 8px !important;
    border: 1px solid #e2e8f0 !important;
    color: #475569 !important;
    padding: 0 11px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff !important;
    transition: all 0.15s ease;
}

.rp-btn-filter:hover, .rp-btn-filter[aria-expanded="true"] {
    background: #f1f5f9 !important;
    color: #0284c7 !important;
    border-color: #0284c7 !important;
}

/* 8. DataTables Custom Pagination & Info */
.dataTables_wrapper .dataTables_info {
    font-size: 12px !important;
    color: #64748b !important;
    padding-top: 0 !important;
    font-weight: 500;
}

.dataTables_wrapper .dataTables_paginate {
    padding-top: 0 !important;
}

.dataTables_wrapper .pagination {
    margin-bottom: 0 !important;
    gap: 4px;
}

.dataTables_wrapper .page-item .page-link {
    border-radius: 8px !important;
    font-size: 12px !important;
    font-weight: 600;
    color: #475569 !important;
    border: 1px solid #e2e8f0 !important;
    padding: 5px 11px !important;
    transition: all 0.15s ease-in-out;
}

.dataTables_wrapper .page-item.active .page-link {
    background-color: #0284c7 !important;
    border-color: #0284c7 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3) !important;
}

.dataTables_wrapper .page-item.disabled .page-link {
    color: #cbd5e1 !important;
    background-color: #f8fafc !important;
    border-color: #e2e8f0 !important;
    cursor: not-allowed;
}

.dataTables_wrapper .page-item .page-link:hover:not(.active) {
    background-color: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
    color: #0f172a !important;
}

/* Table header text styling */
#auditLogTable thead th, #productHistoryTable thead th {
    font-size: 11px !important;
    letter-spacing: 0.4px;
    font-weight: 700 !important;
    color: #475569 !important;
    padding-top: 12px !important;
    padding-bottom: 12px !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
</style>

<!-- ===== SCRIPTS ===== -->
<script>
$(document).ready(function () {
    // 1. Inisialisasi Select2 untuk Filter Produk Utama
    App.initSelect2('#product_filter', {
        placeholder: 'Pilih atau cari produk...',
        allowClear: true
    });

    <?php if (! empty($auditLogs)) : ?>
    // 2. DataTables untuk Riwayat Perubahan Data Produk (Audit Log)
    var auditTable = $('#auditLogTable').DataTable({
        dom: "<'table-responsive'tr><'px-3 py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2'ip>",
        order: [[0, 'desc']],
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        language: {
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
            infoEmpty: 'Menampilkan 0 data',
            infoFiltered: '(disaring dari _MAX_ total data)',
            zeroRecords: '<div class="text-center py-5 text-muted small"><i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block opacity-50 text-secondary"></i>Tidak ada data perubahan yang sesuai dengan filter pencarian.</div>',
            paginate: {
                previous: '<i class="fa-solid fa-chevron-left"></i>',
                next: '<i class="fa-solid fa-chevron-right"></i>'
            }
        },
        columnDefs: [
            { orderable: false, targets: [3, 4] }
        ]
    });

    // Custom Page Length Handler
    $('#auditLogPageLength').on('change', function () {
        var len = parseInt($(this).val(), 10);
        auditTable.page.len(len).draw();
    });

    // Custom Search Input Handler (Realtime)
    $('#auditLogSearchInput').on('keyup input', function () {
        var query = $(this).val();
        auditTable.search(query).draw();
        if (query.length > 0) {
            $('#auditLogSearchClear').show();
        } else {
            $('#auditLogSearchClear').hide();
        }
    });

    // Clear Search Button Handler
    $('#auditLogSearchClear').on('click', function () {
        $('#auditLogSearchInput').val('').trigger('input').focus();
    });

    // Filter Aksi Handler
    $('#filterAuditAction').on('change', function () {
        var val = $(this).val();
        auditTable.column(1).search(val ? val : '', true, false).draw();
    });

    // Filter Role Handler
    $('#filterAuditRole').on('change', function () {
        var val = $(this).val();
        auditTable.column(2).search(val ? val : '', true, false).draw();
    });

    // Reset All Filters Handler
    $('#btnResetAuditFilters').on('click', function () {
        $('#filterAuditAction').val('');
        $('#filterAuditRole').val('');
        $('#auditLogSearchInput').val('').trigger('input');
        auditTable.search('').columns().search('').draw();
    });
    <?php endif; ?>

    <?php if (! empty($versions)) : ?>
    // 3. DataTables untuk Riwayat Versi Model Scoring
    $('#productHistoryTable').DataTable({
        dom: "<'table-responsive'tr><'px-3 py-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-2'ip>",
        order: [[1, 'desc']],
        pageLength: 5,
        responsive: true,
        autoWidth: false,
        language: {
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ versi',
            infoEmpty: 'Menampilkan 0 versi',
            infoFiltered: '(disaring dari _MAX_ total versi)',
            zeroRecords: '<div class="text-center py-4 text-muted small">Tidak ada versi scoring yang sesuai.</div>',
            paginate: {
                previous: '<i class="fa-solid fa-chevron-left"></i>',
                next: '<i class="fa-solid fa-chevron-right"></i>'
            }
        }
    });
    <?php endif; ?>
});
</script>

<?= view('partials/shell_end') ?>
