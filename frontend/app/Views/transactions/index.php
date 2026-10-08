<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Pengajuan Scoring']) ?>

<?php
$items = $items ?? [];
$totalTx = count($items);
$statusCounts = [];
foreach ($items as $it) {
    $st = (string) ($it['status'] ?? 'unknown');
    $statusCounts[$st] = ($statusCounts[$st] ?? 0) + 1;
}
$draftCount = (int) (($statusCounts['draft'] ?? 0) + ($statusCounts['in_progress'] ?? 0));
$pendingCount = (int) (($statusCounts['submitted'] ?? 0) + ($statusCounts['pending'] ?? 0) + ($statusCounts['in_review'] ?? 0));
$doneCount = (int) (($statusCounts['approved'] ?? 0) + ($statusCounts['rejected'] ?? 0));
?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?= view('partials/kpi_solid', ['items' => [
    ['label' => 'Total Pengajuan', 'value' => $totalTx, 'sub' => 'Semua pengajuan scoring', 'tone' => 'blue', 'icon' => 'solar:document-text-bold-duotone'],
    ['label' => 'Dalam Proses', 'value' => $draftCount + $pendingCount, 'sub' => 'Draft / menunggu approval', 'tone' => 'orange', 'icon' => 'solar:hourglass-bold-duotone'],
    ['label' => 'Selesai', 'value' => $doneCount, 'sub' => 'Approved / rejected', 'tone' => 'teal', 'icon' => 'solar:check-circle-bold-duotone'],
]]) ?>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0 d-flex align-items-center">
            <iconify-icon icon="solar:document-text-bold-duotone" class="me-1"></iconify-icon>
            Daftar Pengajuan Scoring
            <?php
            $userBranchId = (string) ($profile['branchid'] ?? '001');
            $userBranchName = (string) ($profile['branch_name'] ?? '');
            if ($userBranchId !== '001') : ?>
                <span class="badge bg-theme text-theme-color ms-2"><i class="fa fa-building me-1"></i>Cabang: <?= esc($userBranchId) ?> - <?= esc($userBranchName) ?></span>
            <?php else : ?>
                <span class="badge bg-indigo-subtle text-indigo border ms-2"><i class="fa fa-globe me-1"></i>Kantor Pusat (Semua Cabang)</span>
            <?php endif; ?>
        </h4>
        <div class="card-header-btn">
            <a href="<?= site_url('scoring/credit') ?>" class="btn btn-theme btn-xs">
                <i class="fa fa-plus me-1"></i><span class="btn-label-full"> Pengajuan Baru</span>
            </a>
            <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
        </div>
    </div>
    <div class="card-body">
            <table id="transactionsTable" class="table table-hover table-striped align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Nomor Pengajuan</th>
                        <th>Nama Debitur</th>
                        <th>Cabang</th>
                        <th>Produk</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-center" style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item) : ?>
                        <?php $status = (string) $item['status']; ?>
                        <tr>
                            <td class="text-muted"><?= esc((string) ($index + 1)) ?></td>
                            <td>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="<?= site_url('transactions/' . $item['id']) ?>" class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace text-decoration-none">
                                        <?= esc($item['transaction_no']) ?>
                                    </a>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($item['transaction_no']) ?>" title="Salin Nomor Pengajuan">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="fw-semibold"><?= esc($item['debtor_name']) ?></td>
                            <td>
                                <div class="d-flex flex-column gap-0">
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace mb-1" style="width: fit-content;">
                                        <i class="fa fa-building me-1"></i><?= esc($item['branch_code'] ?: '-') ?>
                                    </span>
                                    <span class="small text-muted text-truncate" style="max-width:180px;" title="<?= esc($item['branch_name'] ?: '-') ?>">
                                        <?= esc($item['branch_name'] ?: '-') ?>
                                    </span>
                                </div>
                            </td>
                            <td><?= esc($item['product_name']) ?></td>
                            <td><?= esc(! empty($item['created_at']) ? date('d M Y', strtotime($item['created_at'])) : '-') ?></td>
                            <td>
                                <span class="badge badge-<?= esc($status) ?>">
                                    <?= esc($item['status_label'] ?? $status) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php
                                ob_start();
                                ?>
                                        <li>
                                            <a href="<?= site_url('transactions/' . $item['id']) ?>" class="dropdown-action-item">
                                                <span class="action-icon-circle action-icon-green"><i class="fa-solid fa-eye"></i></span>
                                                <div class="action-text-group">
                                                    <span class="action-title">Buka Detail</span>
                                                    <span class="action-desc">Lihat / lanjutkan pengajuan</span>
                                                </div>
                                            </a>
                                        </li>
                                        <?php if (in_array($status, ['approved', 'rejected'], true)) : ?>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <form method="post" action="<?= site_url('transactions/' . $item['id'] . '/duplicate') ?>" class="rescore-from-tx-form m-0 p-0">
                                                <?= csrf_field() ?>
                                                <button type="button" class="dropdown-action-item btn-rescore-tx">
                                                    <span class="action-icon-circle action-icon-orange"><i class="fa-solid fa-arrows-rotate"></i></span>
                                                    <div class="action-text-group">
                                                        <span class="action-title">Scoring Ulang</span>
                                                        <span class="action-desc">Buat pengajuan baru dari data ini</span>
                                                    </div>
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                <?php
                                echo view('partials/action_dropdown_btn', ['menuHtml' => ob_get_clean()]);
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)) : ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Belum ada pengajuan. Klik "Pengajuan Baru" untuk memulai.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#transactionsTable tbody tr td[colspan]').length === 0) {
        App.initDT('#transactionsTable', {
            order: [[5, 'desc']],
            columnDefs: [
                { orderable: false, targets: [7] },
                { searchable: false, targets: [0, 7] }
            ]
        });
    }

    $(document).on('click', '.btn-rescore-tx', function () {
        var $btn = $(this);
        App.confirm({
            title: 'Scoring Ulang',
            text: 'Pengajuan baru akan dibuat (ID berbeda). Debitur & produk sama; jawaban parameter dikosongkan agar bisa diedit. Lanjutkan?',
            confirmButtonText: '<i class="fa-solid fa-arrows-rotate me-1"></i> Ya, Scoring Ulang'
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($btn, '');
                $btn.closest('form').submit();
            }
        });
    });
});
</script>

<?= view('partials/shell_end') ?>
