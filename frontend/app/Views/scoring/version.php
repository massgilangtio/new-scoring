<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Versi ' . $version['version_no']]) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?php
$editable = ($version['status'] ?? '') === 'draft';
$totalWeight = 0;
if (! empty($version['parameters'])) {
    foreach ($version['parameters'] as $p) {
        $totalWeight += (float) ($p['weight'] ?? 0);
    }
}
$isWeightValid = abs($totalWeight - 100.0) < 0.001;
?>

<!-- Page Header -->
<div class="page-header d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= site_url('/') ?>"><i class="fa-solid fa-house me-1"></i>Dasbor</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('scoring/products') ?>">Konfigurasi Scoring</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('scoring/products/' . ($version['product_id'] ?? 1) . '/versions') ?>">Daftar Versi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Versi <?= esc($version['version_no']) ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h2 class="page-title mb-0">
                <i class="fa-solid fa-sliders me-2 text-primary"></i>Model Scoring — Versi <?= esc($version['version_no']) ?>
            </h2>
            <?php
            $status = strtolower($version['status'] ?? '');
            switch ($status) {
                case 'active':
                    $statusBadge = 'bg-success text-white';
                    break;
                case 'draft':
                    $statusBadge = 'bg-warning text-dark';
                    break;
                case 'archived':
                    $statusBadge = 'bg-secondary text-white';
                    break;
                default:
                    $statusBadge = 'bg-light text-dark';
                    break;
            }
            ?>
            <span class="badge <?= $statusBadge ?> px-3 py-2 fs-6 rounded-pill text-uppercase fw-bold">
                <i class="fa-solid <?= $status === 'active' ? 'fa-circle-check' : ($status === 'draft' ? 'fa-pen-to-square' : 'fa-box-archive') ?> me-1"></i>
                <?= esc($version['status']) ?>
            </span>
        </div>
        <p class="text-muted mb-0 mt-1">Skor parameter dihitung dari value × weight. Total bobot (weight) parameter wajib bernilai 100% untuk aktivasi.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= site_url('scoring/products/' . ($version['product_id'] ?? 1) . '/versions') ?>" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i>Kembali
        </a>
        <?php if ($version['status'] !== 'active') : ?>
            <form id="activateForm" method="post" action="<?= site_url('scoring/versions/' . $version['id'] . '/activate') ?>" class="d-inline">
                <?= csrf_field() ?>
                <button type="button" class="btn btn-success" id="btnActivateVersion" <?= (! $isWeightValid) ? 'disabled title="Total weight harus 100%"' : '' ?>>
                    <i class="fa-solid fa-bolt me-1"></i>Aktifkan Versi Ini
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 <?= $isWeightValid ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' ?>">
                    <i class="fa-solid fa-scale-balanced fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Bobot (Weight)</div>
                    <h3 class="mb-0 fw-bold <?= $isWeightValid ? 'text-success' : 'text-warning-emphasis' ?>">
                        <?= number_format($totalWeight, 1) ?>%
                    </h3>
                    <small class="<?= $isWeightValid ? 'text-success' : 'text-danger' ?>">
                        <?= $isWeightValid ? '<i class="fa-solid fa-check me-1"></i>Valid (100%)' : '<i class="fa-solid fa-triangle-exclamation me-1"></i>Harus 100%' ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-primary-subtle text-primary">
                    <i class="fa-solid fa-list-ol fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Jumlah Parameter</div>
                    <h3 class="mb-0 fw-bold text-dark"><?= count($version['parameters'] ?? []) ?></h3>
                    <small class="text-muted">Kriteria penilaian kredit</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-info-subtle text-info-emphasis">
                    <i class="fa-solid fa-chart-line fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Threshold Hasil</div>
                    <h3 class="mb-0 fw-bold text-dark"><?= count($version['thresholds'] ?? []) ?></h3>
                    <small class="text-muted">Rentang hasil kelayakan</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-secondary-subtle text-secondary">
                    <i class="fa-solid fa-keyboard fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Dynamic Fields</div>
                    <h3 class="mb-0 fw-bold text-dark"><?= count($version['dynamic_fields'] ?? []) ?></h3>
                    <small class="text-muted">Atribut formulir custom</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Simulator & Preview Section -->
<?php if (! empty($version['parameters'])) : ?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 fw-bold text-primary">
            <i class="fa-solid fa-calculator me-2"></i>Simulator Perhitungan Skor
        </h5>
        <span class="badge bg-light text-muted border">Uji Coba Formula</span>
    </div>
    <div class="card-body">
        <?php if (! empty($preview)) : ?>
            <div class="alert alert-success border-success-subtle shadow-sm mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 pb-3 border-bottom border-success-subtle mb-3">
                    <div>
                        <span class="text-uppercase small fw-bold text-success tracking-wide">Hasil Simulasi Skor</span>
                        <h2 class="mb-0 text-success fw-bold">
                            Total Skor: <?= esc($preview['total_score']) ?>
                            <span class="badge bg-success text-white fs-6 ms-2 align-middle"><?= esc($preview['result_label']) ?></span>
                        </h2>
                    </div>
                    <div class="text-muted small">
                        <i class="fa-regular fa-clock me-1"></i>Kalkulasi Server Terverifikasi
                    </div>
                </div>
                <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-receipt me-1 text-success"></i>Breakdown Perhitungan:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered bg-white mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Parameter</th>
                                <th class="text-center" style="width: 120px;">Nilai (Value)</th>
                                <th class="text-center" style="width: 120px;">Bobot (Weight)</th>
                                <th class="text-end" style="width: 150px;">Skor Garis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview['lines'] as $line) : ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?= esc($line['parameter_name']) ?></td>
                                    <td class="text-center font-monospace"><?= esc($line['value']) ?></td>
                                    <td class="text-center font-monospace"><?= esc($line['weight']) ?>%</td>
                                    <td class="text-end font-monospace fw-bold text-primary"><?= esc($line['line_score']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('scoring/versions/' . $version['id'] . '/calculate') ?>">
            <?= csrf_field() ?>
            <div class="row g-3 mb-3">
                <?php foreach ($version['parameters'] as $parameter) : ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold small" for="option-<?= esc($parameter['id']) ?>">
                            <?= esc($parameter['name']) ?>
                            <span class="badge bg-secondary-subtle text-secondary border ms-1"><?= esc($parameter['weight']) ?>%</span>
                        </label>
                        <select class="form-select select2-simple" id="option-<?= esc($parameter['id']) ?>" name="option[<?= esc($parameter['id']) ?>]" required>
                            <option value="">-- Pilih Nilai --</option>
                            <?php foreach ($parameter['options'] as $option) : ?>
                                <option value="<?= esc($option['id']) ?>">
                                    <?= esc($option['label']) ?> (Nilai: <?= esc($option['value']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-primary px-4" type="submit">
                    <i class="fa-solid fa-play me-1"></i> Hitung Simulasi
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Tabs Navigasi Parameter, Threshold & Dynamic Fields -->
<ul class="nav nav-tabs nav-fill mb-3" id="versionTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold py-3" id="params-tab" data-bs-toggle="tab" data-bs-target="#params-pane" type="button" role="tab" aria-selected="true">
            <i class="fa-solid fa-sliders me-2 text-primary"></i>Parameter &amp; Nilai
            <span class="badge bg-primary ms-1"><?= count($version['parameters'] ?? []) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold py-3" id="thresholds-tab" data-bs-toggle="tab" data-bs-target="#thresholds-pane" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-chart-line me-2 text-info"></i>Threshold Hasil
            <span class="badge bg-info text-white ms-1"><?= count($version['thresholds'] ?? []) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold py-3" id="fields-tab" data-bs-toggle="tab" data-bs-target="#fields-pane" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-keyboard me-2 text-secondary"></i>Dynamic Fields
            <span class="badge bg-secondary ms-1"><?= count($version['dynamic_fields'] ?? []) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="versionTabContent">
    <!-- TAB 1: PARAMETER & NILAI -->
    <div class="tab-pane fade show active" id="params-pane" role="tabpanel" aria-labelledby="params-tab" tabindex="0">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <div>
                    <h5 class="card-title mb-0 fw-bold">Kriteria Parameter Scoring</h5>
                    <p class="text-muted small mb-0">Total weight saat ini: <strong class="<?= $isWeightValid ? 'text-success' : 'text-danger' ?>"><?= $totalWeight ?>%</strong></p>
                </div>
                <?php if ($editable) : ?>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addParameterModal">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Parameter
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-3">
                <?php if (! empty($version['parameters'])) : ?>
                    <div class="row g-3">
                        <?php foreach ($version['parameters'] as $parameter) : ?>
                            <div class="col-12 col-lg-6">
                                <div class="card border h-100 shadow-none">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-bold text-dark fs-6"><?= esc($parameter['name']) ?></span>
                                            <span class="badge bg-primary text-white ms-2 font-monospace">Weight: <?= esc($parameter['weight']) ?>%</span>
                                        </div>
                                        <?php if ($editable) : ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 btn-add-option" 
                                                    data-param-id="<?= esc($parameter['id']) ?>" 
                                                    data-param-name="<?= esc($parameter['name']) ?>">
                                                <i class="fa-solid fa-plus me-1"></i> Nilai
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Label Opsi Nilai</th>
                                                        <th class="text-end" style="width: 100px;">Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (! empty($parameter['options'])) : ?>
                                                        <?php foreach ($parameter['options'] as $option) : ?>
                                                            <tr>
                                                                <td class="text-dark"><?= esc($option['label']) ?></td>
                                                                <td class="text-end font-monospace fw-bold text-primary"><?= esc($option['value']) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <tr>
                                                            <td colspan="2" class="text-center text-muted py-2 small">Belum ada pilihan nilai untuk parameter ini.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="empty-state py-5 text-center">
                        <i class="fa-solid fa-sliders fs-1 text-muted mb-3 opacity-50"></i>
                        <h6 class="fw-bold">Belum Ada Parameter</h6>
                        <p class="text-muted small">Tambahkan kriteria parameter scoring untuk versi ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TAB 2: THRESHOLD HASIL -->
    <div class="tab-pane fade" id="thresholds-pane" role="tabpanel" aria-labelledby="thresholds-tab" tabindex="0">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <div>
                    <h5 class="card-title mb-0 fw-bold">Threshold &amp; Kelayakan Hasil</h5>
                    <p class="text-muted small mb-0">Rentang skor kumulatif untuk menentukan status rekomendasi kredit</p>
                </div>
                <?php if ($editable) : ?>
                    <button type="button" class="btn btn-info text-white btn-sm" data-bs-toggle="modal" data-bs-target="#addThresholdModal">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Threshold
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 70px;" class="text-center">No</th>
                                <th>Label Hasil</th>
                                <th class="text-center" style="width: 180px;">Skor Minimum</th>
                                <th class="text-center" style="width: 180px;">Skor Maksimum</th>
                                <th class="text-center" style="width: 200px;">Rentang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($version['thresholds'])) : ?>
                                <?php $no = 1; foreach ($version['thresholds'] as $threshold) : ?>
                                    <tr>
                                        <td class="text-center text-muted fw-semibold"><?= $no++ ?></td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1">
                                                <?= esc($threshold['result_label']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center font-monospace fw-semibold"><?= esc($threshold['min_score']) ?></td>
                                        <td class="text-center font-monospace fw-semibold"><?= esc($threshold['max_score'] ?? '∞ (Tak Terbatas)') ?></td>
                                        <td class="text-center">
                                            <code class="text-dark bg-light px-2 py-1 rounded border">
                                                <?= esc($threshold['min_score']) ?> s/d <?= esc($threshold['max_score'] ?? 'Tak Terbatas') ?>
                                            </code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-chart-pie fs-1 text-muted mb-3 opacity-50"></i>
                                            <h6 class="fw-bold">Belum Ada Threshold</h6>
                                            <p class="text-muted small">Tentukan batas nilai skor minimum dan maksimum untuk hasil scoring.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: DYNAMIC FIELDS -->
    <div class="tab-pane fade" id="fields-pane" role="tabpanel" aria-labelledby="fields-tab" tabindex="0">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <div>
                    <h5 class="card-title mb-0 fw-bold">Dynamic Form Fields</h5>
                    <p class="text-muted small mb-0">Input tambahan formulir pengajuan kredit khusus versi ini</p>
                </div>
                <?php if ($editable) : ?>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addFieldModal">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Field
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 70px;" class="text-center">No</th>
                                <th>Kunci Field (Key)</th>
                                <th>Label Tampilan</th>
                                <th>Tipe Input</th>
                                <th class="text-center">Wajib (Required)</th>
                                <th style="width: 140px;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($version['dynamic_fields'])) : ?>
                                <?php $no = 1; foreach ($version['dynamic_fields'] as $field) : ?>
                                    <tr>
                                        <td class="text-center text-muted fw-semibold"><?= $no++ ?></td>
                                        <td><code class="text-dark bg-light px-2 py-1 rounded border"><?= esc($field['field_key']) ?></code></td>
                                        <td class="fw-semibold text-dark"><?= esc($field['label']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary border font-monospace">
                                                <?= esc($field['field_type']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (! empty($field['is_required'])) : ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Wajib</span>
                                            <?php else : ?>
                                                <span class="badge bg-light text-muted border">Opsional</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($editable && in_array($field['field_type'], ['dropdown', 'radio', 'checkbox'], true)) : ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-add-field-option" 
                                                        data-field-id="<?= esc($field['id']) ?>" 
                                                        data-field-label="<?= esc($field['label']) ?>">
                                                    <i class="fa-solid fa-plus me-1"></i> Opsi
                                                </button>
                                            <?php else : ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-keyboard fs-1 text-muted mb-3 opacity-50"></i>
                                            <h6 class="fw-bold">Belum Ada Dynamic Field</h6>
                                            <p class="text-muted small">Field tambahan formulir belum dikonfigurasikan.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODALS SECTION
     ============================================================ -->
<?php if ($editable) : ?>

<!-- Modal: Tambah Parameter -->
<div class="modal fade" id="addParameterModal" tabindex="-1" aria-labelledby="addParameterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('scoring/versions/' . $version['id'] . '/parameters') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addParameterModalLabel">
                    <i class="fa-solid fa-plus-circle me-2 text-primary"></i>Tambah Parameter Scoring
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="param_name">Nama Parameter <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="param_name" name="name" placeholder="Misal: Rasio Penghasilan Terhadap Angsuran" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="param_weight">Bobot Parameter (%) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control" id="param_weight" name="weight" placeholder="Misal: 25.0" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Pastikan seluruh bobot parameter berjumlah tepat 100%.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="param_order">Urutan Tampilan</label>
                    <input type="number" class="form-control" id="param_order" name="display_order" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Parameter</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Tambah Nilai Parameter (Option) -->
<div class="modal fade" id="addOptionModal" tabindex="-1" aria-labelledby="addOptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" id="addOptionForm" action="" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="version_id" value="<?= esc($version['id']) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addOptionModalLabel">
                    <i class="fa-solid fa-plus-circle me-2 text-primary"></i>Tambah Pilihan Nilai
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Parameter: <strong id="modalParamName" class="text-dark"></strong></p>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="option_label">Label Pilihan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="option_label" name="label" placeholder="Misal: > 50% atau Sangat Baik" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="option_value">Nilai Angka (Value) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="option_value" name="value" placeholder="Misal: 100 atau 4.5" required>
                    <div class="form-text">Nilai ini dikalikan dengan bobot parameter saat perhitungan skor.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="option_order">Urutan Tampilan</label>
                    <input type="number" class="form-control" id="option_order" name="display_order" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Nilai</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Tambah Threshold -->
<div class="modal fade" id="addThresholdModal" tabindex="-1" aria-labelledby="addThresholdModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('scoring/versions/' . $version['id'] . '/thresholds') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addThresholdModalLabel">
                    <i class="fa-solid fa-chart-line me-2 text-info"></i>Tambah Threshold Hasil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="result_label">Label Rekomendasi Hasil <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="result_label" name="result_label" placeholder="Misal: APPROVE, REVIEW, atau REJECT" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold" for="min_score">Skor Minimum <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="min_score" name="min_score" placeholder="0" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold" for="max_score">Skor Maksimum</label>
                        <input type="number" step="0.01" class="form-control" id="max_score" name="max_score" placeholder="Kosong = Tak Terbatas">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="threshold_order">Urutan Tampilan</label>
                    <input type="number" class="form-control" id="threshold_order" name="display_order" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-info text-white"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Threshold</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Tambah Dynamic Field -->
<div class="modal fade" id="addFieldModal" tabindex="-1" aria-labelledby="addFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('scoring/versions/' . $version['id'] . '/fields') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addFieldModalLabel">
                    <i class="fa-solid fa-keyboard me-2 text-secondary"></i>Tambah Dynamic Field
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="field_key">Kunci Field (Key) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" id="field_key" name="field_key" placeholder="misal: luas_tanah" pattern="[a-z0-9_]{1,40}" required>
                    <div class="form-text">Hanya huruf kecil, angka, dan underscore (maks 40 karakter).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="field_label">Label Tampilan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="field_label" name="label" placeholder="Misal: Luas Tanah Agunan (m2)" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="field_type">Tipe Input <span class="text-danger">*</span></label>
                    <select class="form-select" id="field_type" name="field_type" required>
                        <?php foreach (['text' => 'Teks Singkat', 'number' => 'Angka / Numerik', 'date' => 'Tanggal', 'dropdown' => 'Pilihan Dropdown', 'textarea' => 'Teks Panjang', 'radio' => 'Radio Button', 'checkbox' => 'Kotak Centang'] as $tVal => $tName) : ?>
                            <option value="<?= esc($tVal) ?>"><?= esc($tName) ?> (<?= esc($tVal) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="is_required" name="is_required" value="1">
                    <label class="form-check-label fw-semibold" for="is_required">
                        Field Wajib Diisi (Mandatory)
                    </label>
                </div>
                <input type="hidden" name="is_active" value="1">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="field_order">Urutan Tampilan</label>
                    <input type="number" class="form-control" id="field_order" name="display_order" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Field</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Tambah Opsi Dynamic Field -->
<div class="modal fade" id="addFieldOptionModal" tabindex="-1" aria-labelledby="addFieldOptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" id="addFieldOptionForm" action="" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="version_id" value="<?= esc($version['id']) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addFieldOptionModalLabel">
                    <i class="fa-solid fa-plus-circle me-2 text-secondary"></i>Tambah Opsi Field
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Field: <strong id="modalFieldLabel" class="text-dark"></strong></p>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="field_option_label">Label Opsi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="field_option_label" name="label" placeholder="Misal: Sertifikat Hak Milik (SHM)" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="field_option_value">Nilai Opsi (Value) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" id="field_option_value" name="value" placeholder="Misal: shm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="field_option_order">Urutan Tampilan</label>
                    <input type="number" class="form-control" id="field_option_order" name="display_order" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Opsi</button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<script>
$(document).ready(function () {
    // Select2 untuk dropdown simulator
    if ($.fn.select2) {
        $('.select2-simple').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    // Modal Trigger: Tambah Nilai Parameter
    $('.btn-add-option').on('click', function () {
        var paramId = $(this).data('param-id');
        var paramName = $(this).data('param-name');
        $('#modalParamName').text(paramName);
        $('#addOptionForm').attr('action', '<?= site_url('scoring/parameters') ?>/' + paramId + '/options');
        var modal = new bootstrap.Modal(document.getElementById('addOptionModal'));
        modal.show();
    });

    // Modal Trigger: Tambah Opsi Field
    $('.btn-add-field-option').on('click', function () {
        var fieldId = $(this).data('field-id');
        var fieldLabel = $(this).data('field-label');
        $('#modalFieldLabel').text(fieldLabel);
        $('#addFieldOptionForm').attr('action', '<?= site_url('scoring/fields') ?>/' + fieldId + '/options');
        var modal = new bootstrap.Modal(document.getElementById('addFieldOptionModal'));
        modal.show();
    });

    // Konfirmasi Aktivasi Versi
    $('#btnActivateVersion').on('click', function (e) {
        e.preventDefault();
        App.confirm({
            title: 'Aktifkan Versi Model Scoring?',
            text: 'Versi aktif saat ini akan digantikan oleh Versi <?= esc($version['version_no']) ?>. Lanjutkan?',
            icon: 'question',
            confirmButtonText: '<i class="fa-solid fa-bolt me-1"></i> Ya, Aktifkan Versi',
            customClass: { confirmButton: 'btn btn-success px-4', cancelButton: 'btn btn-light px-4 me-2' }
        }).then(function (result) {
            if (result.isConfirmed) {
                $('#activateForm').submit();
            }
        });
    });
});
</script>

<?= view('partials/shell_end') ?>
