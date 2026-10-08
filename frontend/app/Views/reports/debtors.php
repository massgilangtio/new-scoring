<?php
$profile  = $profile ?? null;
$history  = $history ?? [];
$debtors  = $debtors ?? [];
$selected = $selected ?? 0;
$hasDebtor = ! empty($history['debtor']);
helper('access');
$canViewScoreDetails = can_view_score_details(is_array($profile) ? $profile : []);
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Riwayat Debitur']) ?>

<?= view('partials/reports_subnav', ['active' => 'debtors']) ?>

<div class="card card-borderless mb-3 filter-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:filter-bold-duotone" class="me-1"></iconify-icon>
            Filter Debitur
        </h4>
        <div class="card-header-btn">
            <button type="submit" class="btn btn-primary btn-xs" form="debtorFilterForm">
                <i class="fa fa-search me-1"></i><span class="btn-label-full"> Tampilkan Riwayat</span>
            </button>
            <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
        </div>
    </div>
    <div class="card-body filter-card-body">
        <form method="get" action="<?= site_url('reports/debtors') ?>" id="debtorFilterForm">
            <div class="row g-3 align-items-end">
                <div class="col-12">
                    <label class="form-label" for="debtor_filter">Pilih Debitur</label>
                    <select id="debtor_filter" name="id" class="select2" data-placeholder="Cari nama atau NIK debitur...">
                        <option value=""></option>
                        <?php foreach ($debtors as $debtor) : ?>
                            <option value="<?= esc($debtor['id']) ?>" <?= (int) $selected === (int) $debtor['id'] ? 'selected' : '' ?>>
                                <?= esc($debtor['full_name']) ?> — <?= esc($debtor['nik']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (! empty($history['error'])) : ?>
    <div class="alert alert-danger"><i class="fa fa-circle-exclamation me-2"></i><?= esc($history['error']) ?></div>
<?php endif; ?>

<?php if ($hasDebtor) : ?>
<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:user-bold-duotone" class="me-1"></iconify-icon>
            <?= esc($history['debtor']['full_name']) ?>
        </h4>
        <div class="d-flex align-items-center gap-1">
            <?= view('partials/export_header_btn', ['mode' => 'print']) ?>
            <span class="badge bg-white bg-opacity-15 text-white" id="tblCountBadge">0 pengajuan</span>
            <?= view('partials/card_widget_btn') ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="debtorHistoryTable" class="table table-hover table-striped align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>Nomor Pengajuan</th>
                        <th>Produk</th>
                        <?php if ($canViewScoreDetails) : ?>
                        <th class="text-center">Skor</th>
                        <th>Hasil</th>
                        <?php endif; ?>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function () {
    App.initSelect2('#debtor_filter', { placeholder: 'Cari nama atau NIK debitur...', allowClear: true });

    <?php if ($hasDebtor) : ?>
    var escHtml = function (v) {
        return $('<div>').text(v == null ? '' : String(v)).html();
    };

    App.initDT('#debtorHistoryTable', {
        serverSide: true,
        processing: true,
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: '<?= site_url('reports/debtors/' . (int) $selected . '/datatables') ?>',
            type: 'GET',
            dataSrc: function (json) {
                $('#tblCountBadge').text((json.recordsFiltered || 0) + ' pengajuan');
                return json.data || [];
            }
        },
        columns: [
            {
                data: 'transaction_no',
                render: function (data) {
                    var safe = escHtml(data);
                    return '<div class="d-inline-flex align-items-center gap-1">' +
                        '<span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace">' + safe + '</span>' +
                        '<button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="' + safe + '" title="Salin Nomor"><i class="fa fa-copy"></i></button></div>';
                }
            },
            { data: 'product_name', className: 'fw-semibold', render: function (d) { return escHtml(d); } },
<?php if ($canViewScoreDetails) : ?>
            {
                data: 'total_score',
                className: 'text-center fw-bold text-primary',
                render: function (d) { return d == null || d === '' ? '-' : escHtml(d); }
            },
            { data: 'result_label', render: function (d) { return d ? escHtml(d) : '-'; } },
<?php endif; ?>
            {
                data: 'status',
                render: function (d) {
                    var safe = escHtml(d || '-');
                    return '<span class="badge badge-' + safe + '">' + safe + '</span>';
                }
            }
        ]
    });
    <?php endif; ?>
});
</script>

<?= view('partials/shell_end') ?>
