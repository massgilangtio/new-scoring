<?php
$profile = $profile ?? [];
$branches = $branches ?? [];
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Master Cabang']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-header-text">
        <h2 class="page-title mb-1"><i class="fa-solid fa-building me-2 text-primary"></i>Master Cabang</h2>
        <p class="text-muted mb-0">Kelola data cabang yang terdaftar dalam sistem</p>
    </div>
    <div class="page-actions w-100 w-sm-auto text-end">
        <button type="button" class="btn btn-primary w-100 w-sm-auto" data-bs-toggle="modal" data-bs-target="#createBranchModal">
            <i class="fa-solid fa-plus me-1"></i> Tambah Cabang
        </button>
    </div>
</div>

<!-- Branches Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="branchesTable" class="table table-hover align-middle mb-0">
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
                            <span class="product-code"><?= esc($branch['code']) ?></span>
                        </td>
                        <td><?= esc($branch['name']) ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?= ! empty($branch['is_active']) ? 'active' : 'inactive' ?>">
                                <?= ! empty($branch['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary btn-edit-branch"
                                    data-id="<?= esc($branch['id']) ?>"
                                    data-code="<?= esc($branch['code']) ?>"
                                    data-name="<?= esc($branch['name']) ?>"
                                    data-is-active="<?= ! empty($branch['is_active']) ? '1' : '0' ?>"
                                    data-bs-toggle="modal" data-bs-target="#editBranchModal"
                                    title="Edit Cabang">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($branches)) : ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fa-regular fa-building"></i>
                                <h6>Belum Ada Data Cabang</h6>
                                <p>Klik "Tambah Cabang" untuk menambahkan cabang pertama.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: Create Branch -->
<div class="modal fade" id="createBranchModal" tabindex="-1" aria-labelledby="createBranchLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createBranchLabel">
                    <i class="fa-solid fa-building-circle-check me-2 text-primary"></i>Tambah Cabang Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="createBranchForm" method="post" action="<?= site_url('master/branches') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="branch_code">Kode Cabang <span class="required">*</span></label>
                            <input id="branch_code" name="code" type="text" class="form-control" required
                                   placeholder="Contoh: KCU-MDN">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="branch_name">Nama Cabang <span class="required">*</span></label>
                            <input id="branch_name" name="name" type="text" class="form-control" required
                                   placeholder="Nama lengkap cabang">
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
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnCreateBranch" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Cabang
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
                    <i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Edit Cabang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="editBranchForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="edit_branch_code">Kode Cabang <span class="required">*</span></label>
                            <input id="edit_branch_code" name="code" type="text" class="form-control" required placeholder="Kode cabang">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="edit_branch_name">Nama Cabang <span class="required">*</span></label>
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
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnEditBranch" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Perbarui Cabang
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

    // Populate Edit Modal
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
