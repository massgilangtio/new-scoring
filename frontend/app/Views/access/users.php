<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Manajemen User']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-users me-2 text-primary"></i>Manajemen User</h2>
        <p class="text-muted mb-0">Kelola data pengguna dan hak akses sistem</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="fa-solid fa-plus me-1"></i> Tambah User Baru
        </button>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Cabang</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user) : ?>
                    <tr>
                        <td class="text-muted"><?= esc((string) ($index + 1)) ?></td>
                        <td class="fw-semibold"><?= esc($user['username']) ?></td>
                        <td><?= esc($user['full_name']) ?></td>
                        <td>
                            <span class="badge" style="background:var(--info-bg);color:#075985;">
                                <?= esc($user['role_name'] ?? '-') ?>
                            </span>
                        </td>
                        <td><?= esc($user['branch_name'] ?? '-') ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?= ! empty($user['is_active']) ? 'active' : 'inactive' ?>">
                                <?= ! empty($user['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary btn-edit-user"
                                    data-id="<?= esc($user['id']) ?>"
                                    data-full-name="<?= esc($user['full_name']) ?>"
                                    data-role-id="<?= esc($user['role_id'] ?? '') ?>"
                                    data-branch-id="<?= esc($user['branch_id'] ?? '') ?>"
                                    data-is-active="<?= ! empty($user['is_active']) ? '1' : '0' ?>"
                                    data-bs-toggle="modal" data-bs-target="#editUserModal"
                                    title="Edit User">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)) : ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fa-regular fa-user"></i>
                                <h6>Belum Ada User</h6>
                                <p>Klik "Tambah User Baru" untuk menambahkan pengguna pertama.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: Create User
     ============================================================ -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">
                    <i class="fa-solid fa-user-plus me-2 text-primary"></i>Tambah User Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="createUserForm" method="post" action="<?= site_url('access/users') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_username">Username <span class="required">*</span></label>
                            <input id="new_username" name="username" type="text" class="form-control" required
                                   placeholder="Contoh: john.doe">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_full_name">Nama Lengkap <span class="required">*</span></label>
                            <input id="new_full_name" name="full_name" type="text" class="form-control" required
                                   placeholder="Nama lengkap pengguna">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="new_password">Password Awal <span class="required">*</span></label>
                            <input id="new_password" name="password" type="password" class="form-control"
                                   minlength="12" required placeholder="Minimal 12 karakter">
                            <div class="form-text">Password minimal 12 karakter. Pengguna dapat mengubah setelah login.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_role_id">Role <span class="required">*</span></label>
                            <select id="new_role_id" name="role_id" class="select2" data-placeholder="Pilih role..." required>
                                <option value=""></option>
                                <?php foreach ($roles as $role) : ?>
                                    <?php if (! empty($role['is_active'])) : ?>
                                        <option value="<?= esc($role['id']) ?>"><?= esc($role['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_branch_id">Cabang <span class="required">*</span></label>
                            <select id="new_branch_id" name="branch_id" class="select2" data-placeholder="Pilih cabang..." required>
                                <option value=""></option>
                                <?php foreach ($branches as $branch) : ?>
                                    <option value="<?= esc($branch['id']) ?>"><?= esc($branch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="new_is_active">
                                <label class="form-check-label" for="new_is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnCreateUser" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: Edit User
     ============================================================ -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="editUserForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="edit_full_name">Nama Lengkap <span class="required">*</span></label>
                            <input id="edit_full_name" name="full_name" type="text" class="form-control" required
                                   placeholder="Nama lengkap pengguna">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="edit_role_id">Role <span class="required">*</span></label>
                            <select id="edit_role_id" name="role_id" class="select2" data-placeholder="Pilih role..." required>
                                <option value=""></option>
                                <?php foreach ($roles as $role) : ?>
                                    <option value="<?= esc($role['id']) ?>"><?= esc($role['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="edit_branch_id">Cabang <span class="required">*</span></label>
                            <select id="edit_branch_id" name="branch_id" class="select2" data-placeholder="Pilih cabang..." required>
                                <option value=""></option>
                                <?php foreach ($branches as $branch) : ?>
                                    <option value="<?= esc($branch['id']) ?>"><?= esc($branch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit_password">Password Baru <span class="text-muted fw-normal">(opsional)</span></label>
                            <input id="edit_password" name="password" type="password" class="form-control"
                                   placeholder="Kosongkan jika tidak ingin mengubah password">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                                <label class="form-check-label" for="edit_is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnEditUser" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Perbarui User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // Init DataTable
    App.initDT('#usersTable', {
        order: [[2, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 5, 6] },
            { searchable: false, targets: [0, 5, 6] }
        ]
    });

    // Init Select2 in Create Modal
    var $createModal = $('#createUserModal');
    $createModal.on('shown.bs.modal', function () {
        App.initSelect2InModal('#new_role_id', '#createUserModal', { placeholder: 'Pilih role...' });
        App.initSelect2InModal('#new_branch_id', '#createUserModal', { placeholder: 'Pilih cabang...' });
    });

    $createModal.on('hidden.bs.modal', function () {
        $('#createUserForm')[0].reset();
        $('#new_role_id, #new_branch_id').val('').trigger('change.select2');
    });

    // Init Select2 in Edit Modal & populate data
    var $editModal = $('#editUserModal');
    $editModal.on('shown.bs.modal', function (e) {
        App.initSelect2InModal('#edit_role_id', '#editUserModal', { placeholder: 'Pilih role...' });
        App.initSelect2InModal('#edit_branch_id', '#editUserModal', { placeholder: 'Pilih cabang...' });
        var $btn = $(e.relatedTarget);
        if (!$btn.hasClass('btn-edit-user')) return;
        var userId = $btn.data('id');
        $('#editUserForm').attr('action', '<?= site_url('access/users/') ?>' + userId);
        $('#edit_full_name').val($btn.data('full-name'));
        $('#edit_role_id').val($btn.data('role-id')).trigger('change.select2');
        $('#edit_branch_id').val($btn.data('branch-id')).trigger('change.select2');
        $('#edit_is_active').prop('checked', $btn.data('is-active') === 1 || $btn.data('is-active') === '1');
        $('#edit_password').val('');
    });

    $editModal.on('hidden.bs.modal', function () {
        $('#editUserForm')[0].reset();
    });

    // Create Form Submit
    $('#createUserForm').on('submit', function () {
        App.btnLoading($('#btnCreateUser'), 'Menyimpan...');
    });

    // Edit Form Submit
    $('#editUserForm').on('submit', function () {
        App.btnLoading($('#btnEditUser'), 'Memperbarui...');
    });
});
</script>

<?= view('partials/shell_end') ?>
