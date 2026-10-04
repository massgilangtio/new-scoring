<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Pengajuan Scoring']) ?>

<!-- Flash messages via SweetAlert2 -->
<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-file-invoice me-2 text-primary"></i>Daftar Pengajuan Scoring</h2>
        <p class="text-muted mb-0">Kelola semua pengajuan scoring kredit</p>
    </div>
    <div class="page-actions">
        <a href="<?= site_url('scoring/credit') ?>" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Pengajuan Baru
        </a>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="transactionsTable" class="table table-hover align-middle mb-0">
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
                                <a href="<?= site_url('transactions/' . $item['id']) ?>" class="fw-semibold text-primary">
                                    <?= esc($item['transaction_no']) ?>
                                </a>
                            </td>
                            <td><?= esc($item['debtor_name']) ?></td>
                            <td><?= esc($item['product_name']) ?></td>
                            <td><?= esc(! empty($item['created_at']) ? date('d M Y', strtotime($item['created_at'])) : '-') ?></td>
                            <td>
                                <span class="badge badge-<?= esc($status) ?>">
                                    <?= esc($item['status_label'] ?? $status) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= site_url('transactions/' . $item['id']) ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Buka Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)) : ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <h6>Belum Ada Pengajuan</h6>
                                    <p>Anda belum memiliki pengajuan scoring. Klik "Pengajuan Baru" untuk memulai.</p>
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
    App.initDT('#transactionsTable', {
        order: [[4, 'desc']],
        columnDefs: [
            { orderable: false, targets: [6] },
            { searchable: false, targets: [0, 6] }
        ]
    });
});
</script>

<?= view('partials/shell_end') ?>
