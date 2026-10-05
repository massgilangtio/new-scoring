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

<div class="row mb-3">
    <div class="col-xl-4 col-md-4">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-info bg-gradient-to-blue overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Total Pengajuan</div>
                <div class="h2 mb-4"><?= esc((string) $totalTx) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:100%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Semua pengajuan scoring</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:document-text-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mt-3 mt-md-0">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-orange bg-gradient-to-pink overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Dalam Proses</div>
                <div class="h2 mb-4"><?= esc((string) ($draftCount + $pendingCount)) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:<?= $totalTx > 0 ? (int) round(($draftCount + $pendingCount) / $totalTx * 100) : 0 ?>%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Draft / menunggu approval</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:hourglass-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mt-3 mt-md-0">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-teal bg-gradient-to-green overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Selesai</div>
                <div class="h2 mb-4"><?= esc((string) $doneCount) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:<?= $totalTx > 0 ? (int) round($doneCount / $totalTx * 100) : 0 ?>%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Approved / rejected</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:check-circle-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
</div>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:document-text-bold-duotone" class="me-1"></iconify-icon>
            Daftar Pengajuan Scoring
        </h4>
        <div class="card-header-btn">
            <a href="<?= site_url('scoring/credit') ?>" class="btn btn-theme btn-sm">
                <i class="fa fa-plus me-1"></i> Pengajuan Baru
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="transactionsTable" class="table table-hover table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Nomor Pengajuan</th>
                        <th>Nama Debitur</th>
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
                            <td><?= esc($item['product_name']) ?></td>
                            <td><?= esc(! empty($item['created_at']) ? date('d M Y', strtotime($item['created_at'])) : '-') ?></td>
                            <td>
                                <span class="badge badge-<?= esc($status) ?>">
                                    <?= esc($item['status_label'] ?? $status) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="<?= site_url('transactions/' . $item['id']) ?>"
                                       class="btn btn-default btn-xs btn-icon"
                                       title="Buka Detail">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <?php if (in_array($status, ['approved', 'rejected'], true)) : ?>
                                    <form method="post" action="<?= site_url('transactions/' . $item['id'] . '/duplicate') ?>" class="d-inline rescore-from-tx-form">
                                        <?= csrf_field() ?>
                                        <button type="button" class="btn btn-theme btn-xs btn-icon btn-rescore-tx" title="Scoring Ulang">
                                            <i class="fa fa-arrows-rotate"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)) : ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Belum ada pengajuan. Klik "Pengajuan Baru" untuk memulai.
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
    if ($('#transactionsTable tbody tr td[colspan]').length === 0) {
        App.initDT('#transactionsTable', {
            order: [[4, 'desc']],
            columnDefs: [
                { orderable: false, targets: [6] },
                { searchable: false, targets: [0, 6] }
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
