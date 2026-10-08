<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Scoring Ulang']) ?>

<?php
$items = $items ?? [];
$totalRescore = count($items);
$waitingApproval = 0;
foreach ($items as $it) {
    if (($it['status'] ?? '') === 'waiting_approval') {
        $waitingApproval++;
    }
}
$otherCount = max(0, $totalRescore - $waitingApproval);
?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?= view('partials/kpi_solid', ['items' => [
    ['label' => 'Total Permintaan', 'value' => $totalRescore, 'sub' => 'Semua scoring ulang', 'tone' => 'blue', 'icon' => 'solar:refresh-bold-duotone'],
    ['label' => 'Menunggu Approval', 'value' => $waitingApproval, 'sub' => 'Perlu diputuskan', 'tone' => 'orange', 'icon' => 'solar:hourglass-bold-duotone'],
    ['label' => 'Lainnya', 'value' => $otherCount, 'sub' => 'Approved / selesai', 'tone' => 'teal', 'icon' => 'solar:check-circle-bold-duotone'],
]]) ?>

<?php if (in_array('scoring.submit', $profile['permissions'] ?? [], true)) : ?>
<div class="card card-borderless mb-3">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:plain-2-bold-duotone" class="me-1"></iconify-icon>
            Ajukan Scoring Ulang
        </h4>
        <div class="card-header-btn">
            <button type="button" id="btnRescore" class="btn btn-theme btn-xs" form="rescoreForm">
                <i class="fa fa-paper-plane me-1"></i><span class="btn-label-full"> Kirim Permintaan</span>
            </button>
            <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
        </div>
    </div>
    <div class="card-body">
        <form id="rescoreForm" method="post" action="<?= site_url('rescore') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="rescore_debtor_id">Debitur <span class="text-danger">*</span></label>
                    <select id="rescore_debtor_id" name="debtor_id" class="select2"
                            data-placeholder="Pilih debitur..." required>
                        <?php foreach ($debtors as $debtor) : ?>
                            <option value="<?= esc($debtor['id']) ?>">
                                <?= esc($debtor['full_name']) ?> — <?= esc($debtor['nik']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="rescore_product_id">Produk <span class="text-danger">*</span></label>
                    <select id="rescore_product_id" name="product_id" class="select2"
                            data-placeholder="Pilih produk..." required>
                        <?php foreach ($products as $product) : ?>
                            <option value="<?= esc($product['id']) ?>"><?= esc($product['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="rescore_reason">Alasan <span class="text-danger">*</span></label>
                    <input id="rescore_reason" name="reason" type="text" class="form-control"
                           placeholder="Alasan pengajuan scoring ulang" required>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:clipboard-list-bold-duotone" class="me-1"></iconify-icon>
            Daftar Permintaan
        </h4>
        <?= view('partials/card_widget_btn') ?>
    </div>
    <div class="card-body">
            <table id="rescoreTable" class="table table-hover table-striped align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>Debitur</th>
                        <th>Produk</th>
                        <th>Alasan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) : ?>
                    <tr>
                        <td class="fw-semibold"><?= esc($item['debtor_name']) ?></td>
                        <td><?= esc($item['product_name']) ?></td>
                        <td class="text-muted small"><?= esc($item['reason']) ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?= esc($item['status']) ?>"><?= esc($item['status']) ?></span>
                        </td>
                        <td class="text-center">
                            <?php if (($item['status'] ?? '') === 'waiting_approval' && in_array('scoring.approve', $profile['permissions'] ?? [], true)) : ?>
                            <?php
                            ob_start();
                            ?>
                                    <li>
                                        <form class="approve-form m-0 p-0" method="post" action="<?= site_url('rescore/' . $item['id'] . '/approve') ?>">
                                            <?= csrf_field() ?>
                                            <button type="button" class="dropdown-action-item btn-approve">
                                                <span class="action-icon-circle action-icon-green"><i class="fa-solid fa-circle-check"></i></span>
                                                <div class="action-text-group">
                                                    <span class="action-title">Setujui</span>
                                                    <span class="action-desc">Setujui permintaan scoring ulang</span>
                                                </div>
                                            </button>
                                        </form>
                                    </li>
                            <?php
                            echo view('partials/action_dropdown_btn', ['menuHtml' => ob_get_clean()]);
                            ?>
                            <?php else : ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada permintaan scoring ulang.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#rescore_debtor_id').length) {
        App.initSelect2('#rescore_debtor_id', { placeholder: 'Pilih debitur...' });
        App.initSelect2('#rescore_product_id', { placeholder: 'Pilih produk...' });
    }

    if ($('#rescoreTable tbody tr td[colspan]').length === 0) {
        App.initDT('#rescoreTable', {
            order: [[3, 'desc']],
            columnDefs: [{ orderable: false, targets: [4] }]
        });
    }

    $('#btnRescore').on('click', function () {
        App.confirm({
            title: 'Ajukan Scoring Ulang',
            text: 'Permintaan scoring ulang akan dikirim untuk diproses. Lanjutkan?',
            confirmButtonText: '<i class="fa-solid fa-paper-plane me-1"></i> Ya, Kirim'
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($('#btnRescore'), 'Mengirim...');
                $('#rescoreForm').submit();
            }
        });
    });

    $(document).on('click', '.btn-approve', function () {
        var $btn = $(this);
        App.confirmApprove({
            title: 'Setujui Scoring Ulang',
            text: 'Permintaan scoring ulang ini akan disetujui. Lanjutkan?'
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
