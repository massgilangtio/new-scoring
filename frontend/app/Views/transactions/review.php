<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Konfirmasi Scoring']) ?>

<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= site_url('transactions') ?>">Pengajuan Scoring</a></li>
        <li class="breadcrumb-item active"><?= esc($item['transaction_no']) ?></li>
    </ol>
</nav>

<!-- Stepper -->
<div class="d-flex align-items-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-2 text-muted">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--success-bg);color:var(--success);font-size:12px;"><i class="fa-solid fa-check"></i></span>
        <span class="fw-semibold" style="font-size:13px;color:var(--success);">Pilih Produk & Debitur</span>
    </div>
    <div style="flex:1;height:2px;background:var(--secondary);"></div>
    <div class="d-flex align-items-center gap-2 text-muted">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--success-bg);color:var(--success);font-size:12px;"><i class="fa-solid fa-check"></i></span>
        <span class="fw-semibold" style="font-size:13px;color:var(--success);">Input Scoring</span>
    </div>
    <div style="flex:1;height:2px;background:var(--secondary);"></div>
    <div class="d-flex align-items-center gap-2">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--secondary);color:#fff;font-size:14px;">3</span>
        <span class="fw-bold" style="color:var(--primary);">Konfirmasi</span>
    </div>
</div>

<div class="row g-4">
    <!-- Preview / Score Card -->
    <div class="col-12 col-lg-8">
        <?php if (! empty($preview)) : ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa-solid fa-star-half-stroke me-2 text-primary"></i>Hasil Scoring Sementara</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded" style="background:var(--surface-2);border:1px solid var(--border);">
                    <div class="text-center" style="flex:0 0 auto;">
                        <div class="fw-black" style="font-size:3rem;color:var(--primary);line-height:1;"><?= esc((string) $preview['total_score']) ?></div>
                        <div class="text-muted" style="font-size:12px;">Total Skor</div>
                    </div>
                    <div style="width:1px;height:60px;background:var(--border);"></div>
                    <div>
                        <div class="fw-bold" style="font-size:1.1rem;color:var(--primary);"><?= esc($preview['result_label']) ?></div>
                        <div class="text-muted" style="font-size:13px;"><?= esc($item['transaction_no']) ?> &mdash; <?= esc($item['debtor']['full_name'] ?? '') ?></div>
                    </div>
                </div>

                <!-- Parameter breakdown -->
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Weight</th>
                                <th class="text-center">Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview['lines'] as $line) : ?>
                            <tr>
                                <td><?= esc($line['parameter_name']) ?></td>
                                <td class="text-center"><?= esc($line['value'] ?? '-') ?></td>
                                <td class="text-center"><?= esc($line['weight'] ?? '-') ?></td>
                                <td class="text-center fw-bold"><?= esc($line['line_score']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:var(--surface-2);">
                                <td colspan="3" class="fw-bold text-end">Total</td>
                                <td class="text-center fw-bold" style="color:var(--secondary);"><?= esc((string) $preview['total_score']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php elseif (! empty($item['snapshot'])) : ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa-solid fa-chart-bar me-2 text-primary"></i>Skor Tersimpan</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:var(--surface-2);border:1px solid var(--border);">
                    <div class="fw-black text-center" style="font-size:3rem;color:var(--primary);line-height:1;"><?= esc((string) $item['snapshot']['total_score']) ?></div>
                    <div style="width:1px;height:60px;background:var(--border);"></div>
                    <div>
                        <div class="fw-bold" style="font-size:1.1rem;color:var(--primary);"><?= esc($item['snapshot']['result_label']) ?></div>
                        <div class="text-muted" style="font-size:13px;">Skor hasil scoring</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navigation buttons -->
        <div class="d-flex align-items-center justify-content-between">
            <a href="<?= site_url('transactions/' . $item['id']) ?>" class="btn btn-light">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Input
            </a>
            <?php if (! empty($item['editable']) && ! empty($preview)) : ?>
            <form id="submitForm" method="post" action="<?= site_url('transactions/' . $item['id'] . '/submit') ?>">
                <?= csrf_field() ?>
                <button type="button" id="btnSubmit" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane me-1"></i> Submit & Kunci Pengajuan
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Side Info Card -->
    <div class="col-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Informasi Pengajuan</h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Nomor</td>
                            <td class="fw-bold" style="font-size:12px;"><?= esc($item['transaction_no']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Debitur</td>
                            <td class="fw-bold" style="font-size:12px;"><?= esc($item['debtor']['full_name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Produk</td>
                            <td class="fw-bold" style="font-size:12px;"><?= esc($item['product']['name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Status</td>
                            <td>
                                <span class="badge badge-<?= esc($item['status']) ?>"><?= esc($item['status']) ?></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (in_array($item['status'], ['approved', 'rejected'], true)) : ?>
        <div class="card">
            <div class="card-body p-3">
                <p class="text-muted mb-2" style="font-size:13px;">Buat pengajuan baru berdasarkan data yang sama.</p>
                <form id="dupForm" method="post" action="<?= site_url('transactions/' . $item['id'] . '/duplicate') ?>">
                    <?= csrf_field() ?>
                    <button type="button" id="btnDuplicate" class="btn btn-outline-primary w-100">
                        <i class="fa-solid fa-copy me-1"></i> Duplikasi Transaksi
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function () {
    // Submit confirmation
    $('#btnSubmit').on('click', function () {
        App.confirmSave({
            title: 'Submit Pengajuan',
            text: 'Setelah disubmit, pengajuan akan dikunci dan tidak dapat diubah lagi. Lanjutkan?',
            confirmButtonText: '<i class="fa-solid fa-paper-plane me-1"></i> Ya, Submit'
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($('#btnSubmit'), 'Mengirim...');
                $('#submitForm').submit();
            }
        });
    });

    // Duplicate confirmation
    $('#btnDuplicate').on('click', function () {
        App.confirm({
            title: 'Duplikasi Transaksi',
            text: 'Akan dibuat pengajuan baru dengan data yang sama. Lanjutkan?',
            confirmButtonText: '<i class="fa-solid fa-copy me-1"></i> Ya, Duplikasi'
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($('#btnDuplicate'), 'Menduplikasi...');
                $('#dupForm').submit();
            }
        });
    });
});
</script>

<?= view('partials/shell_end') ?>
