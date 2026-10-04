<?php

/** @var array $profile */
/** @var array $items */ ?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Perubahan Parameter']) ?>

<!-- Subnav -->
<ul class="subnav mb-4">
    <li><a href="<?= site_url('reports/scoring') ?>"><i class="fa-solid fa-chart-bar"></i> Scoring</a></li>
    <li><a href="<?= site_url('reports/debtors') ?>"><i class="fa-solid fa-users"></i> Riwayat Debitur</a></li>
    <li><a href="<?= site_url('reports/products') ?>"><i class="fa-solid fa-box-archive"></i> Riwayat Produk</a></li>
    <li><a href="<?= site_url('reports/changes') ?>" class="active"><i class="fa-solid fa-sliders"></i> Perubahan Parameter</a></li>
</ul>

<!-- Page Header -->
<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div class="page-header-text">
        <h2 class="page-title mb-1">
            <i class="fa-solid fa-sliders me-2 text-primary"></i>Perubahan Parameter Scoring
            <span class="badge bg-primary-subtle text-primary fs-6 align-middle ms-2"><?= count($items ?? []) ?> Catatan</span>
        </h2>
        <p class="text-muted mb-0">Log audit historis perubahan konfigurasi parameter, bobot, opsi nilai, dan threshold scoring</p>
    </div>
</div>

<!-- Main Card Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 fw-bold">
            <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Riwayat Modifikasi Parameter
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="changesTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 170px;">Waktu Perubahan</th>
                        <th style="width: 140px;">Aksi</th>
                        <th style="width: 180px;">Role Pelaksana</th>
                        <th>Objek Konfigurasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($items)) : ?>
                        <?php foreach ($items as $item) : ?>
                            <?php
                            $action = strtolower($item['action'] ?? '');
                            if (stripos($action, 'create') !== false || stripos($action, 'tambah') !== false) {
                                $actionBadge = 'bg-success-subtle text-success border border-success-subtle';
                            } elseif (stripos($action, 'update') !== false || stripos($action, 'edit') !== false || stripos($action, 'ubah') !== false) {
                                $actionBadge = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                            } elseif (stripos($action, 'delete') !== false || stripos($action, 'hapus') !== false) {
                                $actionBadge = 'bg-danger-subtle text-danger border border-danger-subtle';
                            } elseif (stripos($action, 'activate') !== false || stripos($action, 'aktif') !== false) {
                                $actionBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                            } else {
                                $actionBadge = 'bg-light text-dark border';
                            }
                            ?>
                            <tr>
                                <td>
                                    <div class="small fw-semibold text-dark">
                                        <i class="fa-regular fa-clock me-1 text-muted"></i>
                                        <?= esc($item['occurred_at']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $actionBadge ?> px-2 py-1 font-monospace">
                                        <?= esc($item['action']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                        <i class="fa-solid fa-user-shield me-1"></i>
                                        <?= esc($item['actor_role_name'] ?? 'System') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-dark border fw-medium"><?= esc($item['object_type']) ?></span>
                                        <code class="text-primary fw-semibold">ID #<?= esc($item['object_id']) ?></code>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-code-compare fs-1 text-muted mb-3 opacity-50"></i>
                                    <h6 class="fw-bold">Belum Ada Perubahan</h6>
                                    <p class="text-muted small">Belum tercatat adanya modifikasi parameter model scoring pada sistem.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        <?php if (! empty($items)) : ?>
            App.initDT('#changesTable', {
                order: [
                    [0, 'desc']
                ],
                pageLength: 25
            });
        <?php endif; ?>
    });
</script>

<?= view('partials/shell_end') ?>