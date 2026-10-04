<?php
/** @var array $profile */
/** @var array $items */
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Laporan Scoring']) ?>

<!-- Subnav -->
<ul class="subnav mb-4">
    <li><a href="<?= site_url('reports/scoring') ?>" class="active"><i class="fa-solid fa-chart-bar"></i> Scoring</a></li>
    <li><a href="<?= site_url('reports/debtors') ?>"><i class="fa-solid fa-users"></i> Riwayat Debitur</a></li>
    <li><a href="<?= site_url('reports/products') ?>"><i class="fa-solid fa-box-archive"></i> Riwayat Produk</a></li>
    <li><a href="<?= site_url('reports/changes') ?>"><i class="fa-solid fa-sliders"></i> Perubahan Parameter</a></li>
</ul>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-chart-bar me-2 text-primary"></i>Laporan Scoring</h2>
        <p class="text-muted mb-0">Riwayat seluruh pengajuan scoring kredit</p>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="reportScoringTable" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No. Pengajuan</th>
                        <th>Debitur</th>
                        <th>NIK</th>
                        <th>Produk</th>
                        <th>Cabang</th>
                        <th>Versi</th>
                        <th class="text-center">Skor</th>
                        <th>Hasil</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) : ?>
                    <tr>
                        <td class="fw-semibold"><?= esc($item['transaction_no']) ?></td>
                        <td><?= esc($item['debtor_name']) ?></td>
                        <td class="font-monospace" style="font-size:12px;"><?= esc($item['nik']) ?></td>
                        <td><?= esc($item['product_name']) ?></td>
                        <td><?= esc($item['branch_name']) ?></td>
                        <td class="text-muted" style="font-size:12px;"><?= esc($item['version_no']) ?></td>
                        <td class="text-center fw-bold" style="color:var(--secondary);"><?= esc($item['total_score'] ?? '-') ?></td>
                        <td><?= esc($item['result_label'] ?? '-') ?></td>
                        <td>
                            <span class="badge badge-<?= esc($item['status']) ?>"><?= esc($item['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fa-regular fa-folder-open"></i>
                                <h6>Belum Ada Data Laporan</h6>
                                <p>Laporan akan tampil setelah ada pengajuan scoring yang diproses.</p>
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
$(document).ready(function () {
    App.initDT('#reportScoringTable', {
        order: [[0, 'desc']]
    });
});
</script>

<?= view('partials/shell_end') ?>
