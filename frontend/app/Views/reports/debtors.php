<?php
$profile  = $profile ?? null;
$history  = $history ?? [];
$debtors  = $debtors ?? [];
$selected = $selected ?? 0;
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Riwayat Debitur']) ?>

<!-- Subnav -->
<ul class="subnav mb-4">
    <li><a href="<?= site_url('reports/scoring') ?>"><i class="fa-solid fa-chart-bar"></i> Scoring</a></li>
    <li><a href="<?= site_url('reports/debtors') ?>" class="active"><i class="fa-solid fa-users"></i> Riwayat Debitur</a></li>
    <li><a href="<?= site_url('reports/products') ?>"><i class="fa-solid fa-box-archive"></i> Riwayat Produk</a></li>
    <li><a href="<?= site_url('reports/changes') ?>"><i class="fa-solid fa-sliders"></i> Perubahan Parameter</a></li>
</ul>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-user-clock me-2 text-primary"></i>Riwayat Debitur</h2>
        <p class="text-muted mb-0">Lihat riwayat pengajuan scoring untuk debitur tertentu</p>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fa-solid fa-filter me-2 text-primary"></i>Filter Debitur</h6>
    </div>
    <div class="card-body">
        <form method="get" action="<?= site_url('reports/debtors') ?>">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-8">
                    <label class="form-label" for="debtor_filter">Pilih Debitur</label>
                    <select id="debtor_filter" name="id" class="select2" data-placeholder="Cari nama atau NIK debitur...">
                        <option value=""></option>
                        <?php foreach ($debtors as $debtor) : ?>
                            <option value="<?= esc($debtor['id']) ?>" <?= (int) $selected === (int) $debtor['id'] ? 'selected' : '' ?>>
                                <?= esc($debtor['full_name']) ?> — <?= esc($debtor['nik']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Tampilkan Riwayat
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<?php if (! empty($history['error'])) : ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= esc($history['error']) ?>
    </div>
<?php endif; ?>

<?php if (! empty($history['debtor'])) : ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fa-solid fa-user me-2 text-primary"></i>
                <?= esc($history['debtor']['full_name']) ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="debtorHistoryTable" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nomor Pengajuan</th>
                            <th>Produk</th>
                            <th class="text-center">Skor</th>
                            <th>Hasil</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history['items'] as $item) : ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($item['transaction_no']) ?></td>
                                <td><?= esc($item['product_name']) ?></td>
                                <td class="text-center fw-bold" style="color:var(--secondary);"><?= esc($item['total_score'] ?? '-') ?></td>
                                <td><?= esc($item['result_label'] ?? '-') ?></td>
                                <td>
                                    <span class="badge badge-<?= esc($item['status']) ?>"><?= esc($item['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($history['items'])) : ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fa-regular fa-clock"></i>
                                        <h6>Belum Ada Riwayat</h6>
                                        <p>Debitur ini belum memiliki riwayat pengajuan.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    $(document).ready(function() {
        App.initSelect2('#debtor_filter', {
            placeholder: 'Cari nama atau NIK debitur...',
            allowClear: true
        });

        <?php if (! empty($history['debtor'])) : ?>
            App.initDT('#debtorHistoryTable', {
                order: [
                    [0, 'desc']
                ]
            });
        <?php endif; ?>
    });
</script>

<?= view('partials/shell_end') ?>