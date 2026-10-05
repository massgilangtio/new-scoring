<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Konfirmasi Scoring']) ?>

<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>

<?= view('partials/form_wizard_nav', [
    'current' => 3,
    'steps'   => [
        ['label' => 'Produk & Debitur', 'href' => site_url('transactions')],
        ['label' => 'Input Scoring', 'href' => ! empty($item['editable']) ? site_url('transactions/' . $item['id']) : 'javascript:;'],
        ['label' => 'Ringkasan'],
    ],
]) ?>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <?php if (! empty($preview)) : ?>
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-blue bg-gradient-to-indigo overflow-hidden mb-3" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="text-white text-opacity-75 small fw-semibold mb-1">Total Skor</div>
                        <div class="display-5 fw-bold text-white mb-0"><?= esc((string) $preview['total_score']) ?></div>
                    </div>
                    <div class="col">
                        <div class="fw-bold text-white fs-5"><?= esc($preview['result_label']) ?></div>
                        <div class="d-inline-flex align-items-center gap-1 text-white text-opacity-75 small">
                            <span class="font-monospace"><?= esc($item['transaction_no']) ?></span>
                            <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($item['transaction_no']) ?>" title="Salin Nomor">
                                <i class="fa fa-copy"></i>
                            </button>
                            <span>&mdash; <?= esc($item['debtor']['full_name'] ?? '') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:chart-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>

        <div class="card card-borderless mb-3">
            <div class="card-header bg-gray-900" data-bs-theme="dark">
                <h4 class="card-header-title text-white mb-0">
                    <iconify-icon icon="solar:list-bold-duotone" class="me-1"></iconify-icon>
                    Rincian Parameter
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
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
                                <td class="fw-semibold"><?= esc($line['parameter_name']) ?></td>
                                <td class="text-center"><?= esc($line['value'] ?? '-') ?></td>
                                <td class="text-center"><?= esc($line['weight'] ?? '-') ?></td>
                                <td class="text-center fw-bold"><?= esc($line['line_score']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="fw-bold text-end">Total</td>
                                <td class="text-center fw-bold text-primary"><?= esc((string) $preview['total_score']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php elseif (! empty($item['snapshot'])) : ?>
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-teal bg-gradient-to-green overflow-hidden mb-3" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="text-white text-opacity-75 small fw-semibold mb-1">Skor Tersimpan</div>
                        <div class="display-5 fw-bold text-white mb-0"><?= esc((string) $item['snapshot']['total_score']) ?></div>
                    </div>
                    <div class="col">
                        <div class="fw-bold text-white fs-5"><?= esc($item['snapshot']['result_label']) ?></div>
                        <div class="text-white text-opacity-75 small">Hasil scoring terkunci</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex align-items-center justify-content-between">
            <a href="<?= site_url('transactions/' . $item['id']) ?>" class="btn btn-default">
                <i class="fa fa-arrow-left me-1"></i> Kembali ke Input
            </a>
            <?php if (! empty($item['editable']) && ! empty($preview)) : ?>
            <form id="submitForm" method="post" action="<?= site_url('transactions/' . $item['id'] . '/submit') ?>">
                <?= csrf_field() ?>
                <button type="button" id="btnSubmit" class="btn btn-theme">
                    <i class="fa fa-paper-plane me-1"></i> Submit &amp; Kunci Pengajuan
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card card-borderless mb-3">
            <div class="card-header bg-gray-900" data-bs-theme="dark">
                <h4 class="card-header-title text-white mb-0">
                    <iconify-icon icon="solar:info-circle-bold-duotone" class="me-1"></iconify-icon>
                    Informasi Pengajuan
                </h4>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small">Nomor</td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace"><?= esc($item['transaction_no']) ?></span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($item['transaction_no']) ?>" title="Salin Nomor">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Debitur</td>
                            <td class="fw-semibold text-end small"><?= esc($item['debtor']['full_name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Produk</td>
                            <td class="fw-semibold text-end small"><?= esc($item['product']['name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Status</td>
                            <td class="text-end">
                                <span class="badge badge-<?= esc($item['status']) ?>"><?= esc($item['status']) ?></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (in_array($item['status'], ['approved', 'rejected'], true) && in_array('scoring.submit', $profile['permissions'] ?? [], true)) : ?>
        <div class="card card-borderless">
            <div class="card-body">
                <p class="text-muted small mb-3">Buat pengajuan baru (ID berbeda) dengan debitur &amp; produk yang sama. Parameter scoring dikosongkan agar bisa diisi ulang.</p>
                <form id="dupForm" method="post" action="<?= site_url('transactions/' . $item['id'] . '/duplicate') ?>">
                    <?= csrf_field() ?>
                    <button type="button" id="btnDuplicate" class="btn btn-theme w-100">
                        <i class="fa fa-arrows-rotate me-1"></i> Scoring Ulang
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function () {
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

    $('#btnDuplicate').on('click', function () {
        App.confirm({
            title: 'Scoring Ulang',
            text: 'Pengajuan baru akan dibuat (ID berbeda). Debitur & produk sama; jawaban parameter dikosongkan agar bisa diedit. Lanjutkan?',
            confirmButtonText: '<i class="fa-solid fa-arrows-rotate me-1"></i> Ya, Scoring Ulang'
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($('#btnDuplicate'), 'Membuat...');
                $('#dupForm').submit();
            }
        });
    });
});
</script>

<?= view('partials/shell_end') ?>
