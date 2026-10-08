<?php
$stats = $stats ?? ['total' => 0, 'approved' => 0, 'rejected' => 0];
$statuses = $statuses ?? [];
$total = (int) ($stats['total'] ?? 0);
$approved = (int) ($stats['approved'] ?? 0);
$rejected = (int) ($stats['rejected'] ?? 0);
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Laporan Scoring']) ?>
<?php
helper('access');
$canViewScoreDetails = can_view_score_details($profile ?? []);
?>

<?= view('partials/reports_subnav', ['active' => 'scoring']) ?>

<?= view('partials/kpi_solid', ['items' => [
    ['label' => 'Total Laporan', 'value' => $total, 'sub' => 'Semua pengajuan tercatat', 'tone' => 'blue', 'icon' => 'solar:chart-bold-duotone', 'id' => 'kpiTotal'],
    ['label' => 'Disetujui', 'value' => $approved, 'sub' => 'Status approved', 'tone' => 'teal', 'icon' => 'solar:check-circle-bold-duotone', 'id' => 'kpiApproved'],
    ['label' => 'Ditolak', 'value' => $rejected, 'sub' => 'Status rejected', 'tone' => 'orange', 'icon' => 'solar:close-circle-bold-duotone', 'id' => 'kpiRejected'],
]]) ?>

<div class="card card-borderless mb-3 filter-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:filter-bold-duotone" class="me-1"></iconify-icon>
            Filter Laporan
        </h4>
        <?= view('partials/filter_header_btn') ?>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body filter-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="filterSearch">Cari</label>
                    <div class="input-group flex-nowrap">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" id="filterSearch" class="form-control" placeholder="No. pengajuan, debitur, NIK, produk...">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="filterStatus">Status</label>
                    <select id="filterStatus" class="select2" data-placeholder="Semua status">
                        <option value=""></option>
                        <?php foreach ($statuses as $st) : ?>
                            <option value="<?= esc($st) ?>"><?= esc($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-1">
                    <button type="button" id="btnFilterApply" class="btn btn-primary btn-xs">
                        <i class="fa fa-search me-1"></i> Terapkan
                    </button>
                    <button type="button" id="btnFilterReset" class="btn btn-default btn-xs">
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:document-text-bold-duotone" class="me-1"></iconify-icon>
            Riwayat Pengajuan Scoring
        </h4>
        <div class="d-flex align-items-center gap-1">
            <?= view('partials/export_header_btn', ['mode' => 'print']) ?>
            <span class="badge bg-white bg-opacity-15 text-white" id="tblCountBadge"><?= esc((string) $total) ?> data</span>
            <?= view('partials/card_widget_btn') ?>
        </div>
    </div>
    <div class="card-body">
            <table id="reportScoringTable" class="table table-hover table-striped align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>No. Pengajuan</th>
                        <th>Debitur</th>
                        <th>NIK</th>
                        <th>Produk</th>
                        <th>Cabang</th>
                        <th>Versi</th>
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

<script>
$(document).ready(function () {
    App.initSelect2('#filterStatus', { placeholder: 'Semua status', allowClear: true });

    var escHtml = function (v) {
        return $('<div>').text(v == null ? '' : String(v)).html();
    };

    var dataTable = App.initDT('#reportScoringTable', {
        serverSide: true,
        processing: true,
        searching: false,
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: '<?= site_url('reports/scoring/datatables') ?>',
            type: 'GET',
            data: function (d) {
                d.filterSearch = $('#filterSearch').val();
                d.filterStatus = $('#filterStatus').val();
            },
            dataSrc: function (json) {
                if (json.stats) {
                    var t = parseInt(json.stats.total || 0, 10);
                    var a = parseInt(json.stats.approved || 0, 10);
                    var r = parseInt(json.stats.rejected || 0, 10);
                    $('#kpiTotal').text(t);
                    $('#kpiApproved').text(a);
                    $('#kpiRejected').text(r);
                    $('#tblCountBadge').text(t + ' data');
                }
                return json.data || [];
            }
        },
        columns: [
            {
                data: 'transaction_no',
                render: function (data) {
                    var safe = escHtml(data);
                    if (!safe) return '<span class="text-muted">-</span>';
                    return '<div class="d-inline-flex align-items-center gap-1">' +
                        '<span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace">' + safe + '</span>' +
                        '<button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="' + safe + '" title="Salin Nomor"><i class="fa fa-copy"></i></button>' +
                        '</div>';
                }
            },
            { data: 'debtor_name', className: 'fw-semibold', render: function (d) { return escHtml(d); } },
            { data: 'nik', className: 'font-monospace small', render: function (d) { return escHtml(d); } },
            { data: 'product_name', render: function (d) { return escHtml(d); } },
            { data: 'branch_name', render: function (d) { return escHtml(d); } },
            { data: 'version_no', className: 'text-muted small', render: function (d) { return escHtml(d); } },
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

    function reload() {
        if (dataTable) dataTable.ajax.reload();
    }

    $('#btnFilterApply').on('click', reload);
    $('#btnFilterReset').on('click', function () {
        $('#filterSearch').val('');
        $('#filterStatus').val(null).trigger('change');
        reload();
    });
    $('#filterSearch').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); reload(); }
    });
    $('#filterStatus').on('change', reload);
});
</script>

<?= view('partials/shell_end') ?>
