<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Approval']) ?>

<?php
$inbox = $inbox ?? [];
$waiting = $waiting ?? [];
$inboxCount = count($inbox);
$waitingCount = count($waiting);
?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-xl-6 col-md-6">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-info bg-gradient-to-blue overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Menunggu Keputusan</div>
                <div class="h2 mb-4"><?= esc((string) $inboxCount) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:100%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Antrian keputusan Anda</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:inbox-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
    <div class="col-xl-6 col-md-6 mt-3 mt-md-0">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-orange bg-gradient-to-pink overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Menunggu Penugasan</div>
                <div class="h2 mb-4"><?= esc((string) $waitingCount) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:100%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Perlu assign approver</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:user-plus-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
</div>

<div class="card card-borderless table-card mb-3">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:inbox-bold-duotone" class="me-1"></iconify-icon>
            Menunggu Keputusan Saya
        </h4>
        <span class="badge bg-white bg-opacity-15 text-white"><?= esc((string) $inboxCount) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="inboxTable" class="table table-hover table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nomor Pengajuan</th>
                        <th>Debitur</th>
                        <th>Produk</th>
                        <th class="text-center" style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inbox as $item) : ?>
                    <tr>
                        <td>
                            <div class="d-inline-flex align-items-center gap-1">
                                <span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace"><?= esc($item['transaction_no']) ?></span>
                                <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($item['transaction_no']) ?>" title="Salin Nomor">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td class="fw-semibold"><?= esc($item['debtor_name']) ?></td>
                        <td><?= esc($item['product_name']) ?></td>
                        <td class="text-center">
                            <a href="<?= site_url('approvals/' . $item['id']) ?>" class="btn btn-theme btn-sm">
                                <i class="fa fa-eye me-1"></i> Buka
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($inbox)) : ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Tidak ada pengajuan yang menunggu keputusan Anda.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:user-plus-bold-duotone" class="me-1"></iconify-icon>
            Menunggu Penugasan Approver
        </h4>
        <span class="badge bg-white bg-opacity-15 text-white"><?= esc((string) $waitingCount) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="waitingTable" class="table table-hover table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nomor Pengajuan</th>
                        <th>Debitur</th>
                        <th>Produk</th>
                        <th class="text-center" style="width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($waiting as $item) : ?>
                    <tr>
                        <td>
                            <div class="d-inline-flex align-items-center gap-1">
                                <span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace"><?= esc($item['transaction_no']) ?></span>
                                <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($item['transaction_no']) ?>" title="Salin Nomor">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td class="fw-semibold"><?= esc($item['debtor_name']) ?></td>
                        <td><?= esc($item['product_name']) ?></td>
                        <td class="text-center">
                            <a href="<?= site_url('approvals/' . $item['id']) ?>" class="btn btn-default btn-sm">
                                <i class="fa fa-user-plus me-1"></i> Tugaskan
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($waiting)) : ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Tidak ada pengajuan yang menunggu penugasan approver.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#inboxTable tbody tr td[colspan]').length === 0) {
        App.initDT('#inboxTable', {
            searching: <?= $inboxCount > 0 ? 'true' : 'false' ?>,
            paging:    <?= $inboxCount > 10 ? 'true' : 'false' ?>,
            columnDefs: [{ orderable: false, targets: [3] }]
        });
    }

    if ($('#waitingTable tbody tr td[colspan]').length === 0) {
        App.initDT('#waitingTable', {
            searching: <?= $waitingCount > 0 ? 'true' : 'false' ?>,
            paging:    <?= $waitingCount > 10 ? 'true' : 'false' ?>,
            columnDefs: [{ orderable: false, targets: [3] }]
        });
    }
});
</script>

<?= view('partials/shell_end') ?>
