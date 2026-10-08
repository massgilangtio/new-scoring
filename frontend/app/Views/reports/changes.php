<?php
$total = (int) ($total ?? 0);
$actions = $actions ?? [];
$objectTypes = $object_types ?? [];
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Perubahan Parameter']) ?>

<?= view('partials/reports_subnav', ['active' => 'changes']) ?>

<div class="card card-borderless mb-3 filter-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:filter-bold-duotone" class="me-1"></iconify-icon>
            Filter Perubahan
        </h4>
        <?= view('partials/filter_header_btn') ?>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body filter-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="filterSearch">Cari</label>
                    <div class="input-group flex-nowrap">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Aksi, role, objek...">
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="filterAction">Aksi</label>
                    <select id="filterAction" class="select2" data-placeholder="Semua aksi">
                        <option value=""></option>
                        <?php foreach ($actions as $act) : ?>
                            <option value="<?= esc($act) ?>"><?= esc($act) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="filterObjectType">Tipe Objek</label>
                    <select id="filterObjectType" class="select2" data-placeholder="Semua tipe">
                        <option value=""></option>
                        <?php foreach ($objectTypes as $ot) : ?>
                            <option value="<?= esc($ot) ?>"><?= esc($ot) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-1">
                    <button type="button" id="btnFilterApply" class="btn btn-primary btn-xs"><i class="fa fa-search me-1"></i> Terapkan</button>
                    <button type="button" id="btnFilterReset" class="btn btn-default btn-xs">Reset</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:code-scan-bold-duotone" class="me-1"></iconify-icon>
            Riwayat Modifikasi Parameter
        </h4>
        <div class="d-flex align-items-center gap-1">
            <?= view('partials/export_header_btn', ['mode' => 'print']) ?>
            <span class="badge bg-white bg-opacity-15 text-white" id="tblCountBadge"><?= esc((string) $total) ?> catatan</span>
            <?= view('partials/card_widget_btn') ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="changesTable" class="table table-hover table-striped align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width:170px;">Waktu Perubahan</th>
                        <th style="width:180px;">Aksi</th>
                        <th style="width:180px;">Role Pelaksana</th>
                        <th>Objek Konfigurasi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    App.initSelect2('#filterAction', { placeholder: 'Semua aksi', allowClear: true });
    App.initSelect2('#filterObjectType', { placeholder: 'Semua tipe', allowClear: true });

    var escHtml = function (v) {
        return $('<div>').text(v == null ? '' : String(v)).html();
    };

    var actionBadgeClass = function (action) {
        var a = String(action || '').toLowerCase();
        if (a.indexOf('create') !== -1 || a.indexOf('tambah') !== -1) return 'bg-success-subtle text-success border border-success-subtle';
        if (a.indexOf('update') !== -1 || a.indexOf('edit') !== -1 || a.indexOf('ubah') !== -1) return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
        if (a.indexOf('delete') !== -1 || a.indexOf('hapus') !== -1) return 'bg-danger-subtle text-danger border border-danger-subtle';
        if (a.indexOf('activate') !== -1 || a.indexOf('aktif') !== -1) return 'bg-primary-subtle text-primary border border-primary-subtle';
        return 'bg-light text-dark border';
    };

    var dataTable = App.initDT('#changesTable', {
        serverSide: true,
        processing: true,
        searching: false,
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: '<?= site_url('reports/changes/datatables') ?>',
            type: 'GET',
            data: function (d) {
                d.filterSearch = $('#filterSearch').val();
                d.filterAction = $('#filterAction').val();
                d.filterObjectType = $('#filterObjectType').val();
            },
            dataSrc: function (json) {
                $('#tblCountBadge').text((json.recordsFiltered || 0) + ' catatan');
                return json.data || [];
            }
        },
        columns: [
            { data: 'occurred_at', className: 'small text-muted text-nowrap', render: function (d) { return escHtml(d); } },
            {
                data: 'action',
                render: function (d) {
                    var safe = escHtml(d);
                    return '<span class="badge ' + actionBadgeClass(d) + ' px-2 py-1 font-monospace">' + safe + '</span>';
                }
            },
            {
                data: 'actor_role_name',
                render: function (d) {
                    return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="fa fa-user-shield me-1"></i>' + escHtml(d || 'System') + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function (row) {
                    return '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                        '<span class="badge bg-primary bg-opacity-15 text-primary">' + escHtml(row.object_type) + '</span>' +
                        '<code class="text-primary fw-semibold">ID #' + escHtml(row.object_id) + '</code></div>';
                }
            }
        ]
    });

    function reload() { if (dataTable) dataTable.ajax.reload(); }
    $('#btnFilterApply').on('click', reload);
    $('#btnFilterReset').on('click', function () {
        $('#filterSearch').val('');
        $('#filterAction').val(null).trigger('change');
        $('#filterObjectType').val(null).trigger('change');
        reload();
    });
    $('#filterSearch').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); reload(); } });
    $('#filterAction, #filterObjectType').on('change', reload);
});
</script>

<?= view('partials/shell_end') ?>
