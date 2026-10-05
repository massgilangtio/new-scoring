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

<!-- BEGIN KPI -->
<div class="row mb-3">
    <div class="col-xl-4 col-md-6">
        <div class="card card-borderless rounded-3 overflow-hidden bg-blue" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="fw-bold text-white small mb-1 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:shop-bold-duotone" class="fs-6"></iconify-icon> Total Cabang
                </div>
                <div class="fw-bold fs-2 text-white"><?= esc((string) $totalBranches) ?></div>
                <div class="fw-semibold text-white text-opacity-75 small mb-0">Semua cabang terdaftar</div>
            </div>
            <div class="position-absolute top-0 end-0 mt-n5 ps-5 w-25">
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle ms-n5 position-absolute top-0 start-0"></div>
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle mt-n5 position-absolute top-0 start-0"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mt-3 mt-md-0">
        <div class="card card-borderless rounded-3 overflow-hidden bg-teal" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="fw-bold text-white small mb-1 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:check-circle-bold-duotone" class="fs-6"></iconify-icon> Aktif
                </div>
                <div class="fw-bold fs-2 text-white"><?= esc((string) $activeBranches) ?></div>
                <div class="fw-semibold text-white text-opacity-75 small mb-0">Cabang berstatus aktif</div>
            </div>
            <div class="position-absolute top-0 end-0 mt-n5 ps-5 w-25">
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle ms-n5 position-absolute top-0 start-0"></div>
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle mt-n5 position-absolute top-0 start-0"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-12 mt-3 mt-xl-0">
        <div class="card card-borderless rounded-3 overflow-hidden bg-red" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="fw-bold text-white small mb-1 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:close-circle-bold-duotone" class="fs-6"></iconify-icon> Nonaktif
                </div>
                <div class="fw-bold fs-2 text-white"><?= esc((string) max(0, $totalBranches - $activeBranches)) ?></div>
                <div class="fw-semibold text-white text-opacity-75 small mb-0">Cabang nonaktif</div>
            </div>
            <div class="position-absolute top-0 end-0 mt-n5 ps-5 w-25">
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle ms-n5 position-absolute top-0 start-0"></div>
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle mt-n5 position-absolute top-0 start-0"></div>
            </div>
        </div>
    </div>
</div>
<!-- END KPI -->

<!-- BEGIN table card -->
<div class="card card-borderless">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:buildings-bold-duotone" class="me-1"></iconify-icon>
            Daftar Cabang
        </h4>
        <div class="card-header-btn">
            <button type="button" class="btn btn-theme btn-sm" data-bs-toggle="modal" data-bs-target="#createBranchModal">
                <i class="fa fa-plus me-1"></i> Tambah Cabang
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="branchesTable" class="table table-hover table-striped align-middle mb-0">
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
                            <button type="button"
                                    class="btn btn-default btn-xs btn-icon btn-edit-branch"
                                    data-id="<?= esc($branch['id']) ?>"
                                    data-code="<?= esc($branch['code']) ?>"
                                    data-name="<?= esc($branch['name']) ?>"
                                    data-is-active="<?= ! empty($branch['is_active']) ? '1' : '0' ?>"
                                    data-bs-toggle="modal" data-bs-target="#editBranchModal"
                                    title="Edit Cabang">
                                <i class="fa fa-pen"></i>
                            </button>
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

    $('#createBranchForm').on('submit', function () {
        App.btnLoading($('#btnCreateBranch'), 'Menyimpan...');
    });

    $('#editBranchForm').on('submit', function () {
        App.btnLoading($('#btnEditBranch'), 'Memperbarui...');
    });
});
</script>

<?= view('partials/shell_end') ?>
