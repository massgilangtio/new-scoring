<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Scoring Ulang']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-rotate me-2 text-primary"></i>Permintaan Scoring Ulang</h2>
        <p class="text-muted mb-0">Ajukan atau kelola permintaan scoring ulang</p>
    </div>
</div>

<!-- Submit Form (only if has permission) -->
<?php if (in_array('scoring.submit', $profile['permissions'] ?? [], true)) : ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-paper-plane me-2 text-primary"></i>Ajukan Scoring Ulang</h5>
    </div>
    <div class="card-body">
        <form id="rescoreForm" method="post" action="<?= site_url('rescore') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="rescore_debtor_id">Debitur <span class="required">*</span></label>
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
                    <label class="form-label" for="rescore_product_id">Produk <span class="required">*</span></label>
                    <select id="rescore_product_id" name="product_id" class="select2"
                            data-placeholder="Pilih produk..." required>
                        <?php foreach ($products as $product) : ?>
                            <option value="<?= esc($product['id']) ?>"><?= esc($product['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="rescore_reason">Alasan <span class="required">*</span></label>
                    <input id="rescore_reason" name="reason" type="text" class="form-control"
                           placeholder="Alasan pengajuan scoring ulang" required>
                </div>
            </div>
            <div class="mt-3">
                <button type="button" id="btnRescore" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane me-1"></i> Kirim Permintaan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Rescore List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-list me-2 text-primary"></i>Daftar Permintaan</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="rescoreTable" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Debitur</th>
                        <th>Produk</th>
                        <th>Alasan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) : ?>
                    <tr>
                        <td><?= esc($item['debtor_name']) ?></td>
                        <td><?= esc($item['product_name']) ?></td>
                        <td class="text-muted" style="font-size:13px;"><?= esc($item['reason']) ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?= esc($item['status']) ?>"><?= esc($item['status']) ?></span>
                        </td>
                        <td class="text-center">
                            <?php if (($item['status'] ?? '') === 'waiting_approval' && in_array('scoring.approve', $profile['permissions'] ?? [], true)) : ?>
                            <form class="approve-form" method="post" action="<?= site_url('rescore/' . $item['id'] . '/approve') ?>">
                                <?= csrf_field() ?>
                                <button type="button" class="btn btn-sm btn-success btn-approve">
                                    <i class="fa-solid fa-circle-check me-1"></i> Setujui
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fa-regular fa-folder-open"></i>
                                <h6>Belum Ada Permintaan</h6>
                                <p>Belum ada permintaan scoring ulang yang diajukan.</p>
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
    App.initSelect2('#rescore_debtor_id', { placeholder: 'Pilih debitur...' });
    App.initSelect2('#rescore_product_id', { placeholder: 'Pilih produk...' });

    App.initDT('#rescoreTable', {
        order: [[3, 'desc']],
        columnDefs: [{ orderable: false, targets: [4] }]
    });

    // Submit rescore confirmation
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

    // Approve rescore confirmation
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
