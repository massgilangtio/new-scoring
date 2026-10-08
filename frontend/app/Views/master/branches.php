<?php
$profile = $profile ?? [];
$branches = $branches ?? [];
$totalBranches = count($branches);
$activeBranches = 0;
foreach ($branches as $b) {
    if (! empty($b['is_active'])) {
        $activeBranches++;
    }
}
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Master Cabang']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?= view('partials/kpi_solid', ['items' => [
    ['label' => 'Total Cabang', 'value' => $totalBranches, 'sub' => 'Semua cabang terdaftar', 'tone' => 'blue', 'icon' => 'solar:shop-bold-duotone'],
    ['label' => 'Aktif', 'value' => $activeBranches, 'sub' => 'Cabang berstatus aktif', 'tone' => 'teal', 'icon' => 'solar:check-circle-bold-duotone'],
    ['label' => 'Nonaktif', 'value' => max(0, $totalBranches - $activeBranches), 'sub' => 'Cabang nonaktif', 'tone' => 'red', 'icon' => 'solar:close-circle-bold-duotone'],
]]) ?>

<!-- BEGIN table card -->
<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:buildings-bold-duotone" class="me-1"></iconify-icon>
            Daftar Cabang
        </h4>
        <div class="card-header-btn d-flex align-items-center gap-2">
            <form method="post" action="<?= site_url('master/branches/sync') ?>" class="d-inline m-0" id="syncBranchForm">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-warning btn-xs text-dark fw-semibold" id="btnSyncBranch" title="Tarik dan perbarui data cabang dari Core Banking Gateway">
                    <i class="fa fa-rotate me-1"></i><span class="btn-label-full"> Sinkronkan dari Core Gateway</span>
                </button>
            </form>
            <button type="button" class="btn btn-theme btn-xs" data-bs-toggle="modal" data-bs-target="#createBranchModal">
                <i class="fa fa-plus me-1"></i><span class="btn-label-full"> Tambah Cabang</span>
            </button>
            <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
        </div>

    </div>
    <div class="card-body">
            <table id="branchesTable" class="table table-hover table-striped align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Kode</th>
                        <th>Nama Cabang</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($branches as $index => $branch) : ?>
                    <tr>
                        <td class="text-muted"><?= esc((string) ($index + 1)) ?></td>
                        <td>
                            <div class="d-inline-flex align-items-center gap-1">
                                <span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace"><?= esc($branch['code']) ?></span>
                                <?php if (! empty($branch['code'])) : ?>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($branch['code']) ?>" title="Salin Kode Cabang">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="fw-semibold"><?= esc($branch['name']) ?></td>
                        <td class="text-center">
                            <?php if (! empty($branch['is_active'])) : ?>
                                <span class="badge bg-success bg-opacity-15 text-success py-6px badge-active">Aktif</span>
                            <?php else : ?>
                                <span class="badge bg-secondary bg-opacity-15 text-secondary py-6px badge-inactive">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php
                            ob_start();
                            ?>
                                    <li>
                                        <button type="button"
                                                class="dropdown-action-item btn-edit-branch"
                                                data-id="<?= esc($branch['id']) ?>"
                                                data-code="<?= esc($branch['code']) ?>"
                                                data-name="<?= esc($branch['name']) ?>"
                                                data-is-active="<?= ! empty($branch['is_active']) ? '1' : '0' ?>"
                                                data-bs-toggle="modal" data-bs-target="#editBranchModal">
                                            <span class="action-icon-circle action-icon-blue"><i class="fa-solid fa-pen"></i></span>
                                            <div class="action-text-group">
                                                <span class="action-title">Edit Cabang</span>
                                                <span class="action-desc">Ubah kode, nama, atau status</span>
                                            </div>
                                        </button>
                                    </li>
                            <?php
                            echo view('partials/action_dropdown_btn', ['menuHtml' => ob_get_clean()]);
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($branches)) : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada data cabang. Klik "Tambah Cabang" untuk menambahkan.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
    </div>
</div>
<!-- END table card -->

<!-- MODAL: Create Branch -->
<div class="modal fade" id="createBranchModal" tabindex="-1" aria-labelledby="createBranchLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createBranchLabel">
                    <iconify-icon icon="solar:shop-bold-duotone" class="me-1 text-primary"></iconify-icon>
                    Tambah Cabang Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="createBranchForm" method="post" action="<?= site_url('master/branches') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="branch_code">Kode Cabang <span class="text-danger">*</span></label>
                            <input id="branch_code" name="code" type="text" class="form-control" required placeholder="Contoh: KCU-MDN">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="branch_name">Nama Cabang <span class="text-danger">*</span></label>
                            <input id="branch_name" name="name" type="text" class="form-control" required placeholder="Nama lengkap cabang">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="branch_is_active">
                                <label class="form-check-label" for="branch_is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnCreateBranch" class="btn btn-theme">
                        <i class="fa fa-floppy-disk me-1"></i> Simpan Cabang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Edit Branch -->
<div class="modal fade" id="editBranchModal" tabindex="-1" aria-labelledby="editBranchLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBranchLabel">
                    <iconify-icon icon="solar:pen-bold-duotone" class="me-1 text-primary"></iconify-icon>
                    Edit Cabang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="editBranchForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="edit_branch_code">Kode Cabang <span class="text-danger">*</span></label>
                            <input id="edit_branch_code" name="code" type="text" class="form-control" required placeholder="Kode cabang">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="edit_branch_name">Nama Cabang <span class="text-danger">*</span></label>
                            <input id="edit_branch_name" name="name" type="text" class="form-control" required placeholder="Nama cabang">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_branch_is_active">
                                <label class="form-check-label" for="edit_branch_is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnEditBranch" class="btn btn-theme">
                        <i class="fa fa-floppy-disk me-1"></i> Perbarui Cabang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    App.initDT('#branchesTable', {
        order: [[2, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 3, 4] },
            { searchable: false, targets: [0, 3, 4] }
        ]
    });

    $('#editBranchModal').on('shown.bs.modal', function (e) {
        var $btn = $(e.relatedTarget);
        if (!$btn.hasClass('btn-edit-branch')) return;
        $('#editBranchForm').attr('action', '<?= site_url('master/branches/') ?>' + $btn.data('id'));
        $('#edit_branch_code').val($btn.data('code'));
        $('#edit_branch_name').val($btn.data('name'));
        $('#edit_branch_is_active').prop('checked', $btn.data('is-active') === 1 || $btn.data('is-active') === '1');
    });

    $('#syncBranchForm').on('submit', function () {
        App.btnLoading($('#btnSyncBranch'), 'Menyinkronkan...');
    });

    $('#createBranchForm').on('submit', function () {
        App.btnLoading($('#btnCreateBranch'), 'Menyimpan...');
    });

    $('#editBranchForm').on('submit', function () {
        App.btnLoading($('#btnEditBranch'), 'Memperbarui...');
    });
});
</script>

<?= view('partials/shell_end') ?>
