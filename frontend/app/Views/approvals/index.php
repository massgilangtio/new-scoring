<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Approval']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-circle-check me-2 text-primary"></i>Antrian Approval</h2>
        <p class="text-muted mb-0">Pengajuan yang menunggu keputusan dan penugasan approver</p>
    </div>
</div>

<!-- Inbox Section -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-inbox me-2" style="color:var(--secondary);"></i>Menunggu Keputusan Saya</h5>
        <span class="badge bg-primary"><?= count($inbox) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="inboxTable" class="table table-hover align-middle mb-0">
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
                        <td class="fw-semibold"><?= esc($item['transaction_no']) ?></td>
                        <td><?= esc($item['debtor_name']) ?></td>
                        <td><?= esc($item['product_name']) ?></td>
                        <td class="text-center">
                            <a href="<?= site_url('approvals/' . $item['id']) ?>"
                               class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-eye me-1"></i> Buka
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($inbox)) : ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="fa-regular fa-circle-check"></i>
                                <h6>Tidak Ada Antrian</h6>
                                <p>Tidak ada pengajuan yang menunggu keputusan Anda saat ini.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Waiting Assignment Section -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-user-plus me-2" style="color:var(--warning);"></i>Menunggu Penugasan Approver</h5>
        <span class="badge bg-warning"><?= count($waiting) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="waitingTable" class="table table-hover align-middle mb-0">
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
                        <td class="fw-semibold"><?= esc($item['transaction_no']) ?></td>
                        <td><?= esc($item['debtor_name']) ?></td>
                        <td><?= esc($item['product_name']) ?></td>
                        <td class="text-center">
                            <a href="<?= site_url('approvals/' . $item['id']) ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-user-plus me-1"></i> Tugaskan
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($waiting)) : ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="fa-regular fa-clock"></i>
                                <h6>Tidak Ada Antrian</h6>
                                <p>Tidak ada pengajuan yang menunggu penugasan approver.</p>
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
    App.initDT('#inboxTable', {
        searching: <?= count($inbox) > 0 ? 'true' : 'false' ?>,
        paging:    <?= count($inbox) > 10 ? 'true' : 'false' ?>,
        columnDefs: [{ orderable: false, targets: [3] }]
    });

    App.initDT('#waitingTable', {
        searching: <?= count($waiting) > 0 ? 'true' : 'false' ?>,
        paging:    <?= count($waiting) > 10 ? 'true' : 'false' ?>,
        columnDefs: [{ orderable: false, targets: [3] }]
    });
});
</script>

<?= view('partials/shell_end') ?>
