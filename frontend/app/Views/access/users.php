<?php
$users = $users ?? [];
$roles = $roles ?? [];
$branches = $branches ?? [];
$totalUsers = count($users);
$activeUsers = 0;
foreach ($users as $u) {
    if (! empty($u['is_active'])) {
        $activeUsers++;
    }
}
$inactiveUsers = max(0, $totalUsers - $activeUsers);
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Manajemen User']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<ul class="nav nav-pills access-subnav gap-2 mb-3">
    <li class="nav-item"><a class="nav-link active" href="<?= site_url('access/users') ?>"><i class="fa fa-users me-1"></i>Pengguna</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= site_url('access/roles') ?>"><i class="fa fa-user-shield me-1"></i>Role</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= site_url('access/permissions') ?>"><i class="fa fa-key me-1"></i>Hak Akses</a></li>
</ul>

<div class="row mb-3">
    <div class="col-xl-4 col-md-4">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-info bg-gradient-to-blue overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Total User</div>
                <div class="h2 mb-4"><?= esc((string) $totalUsers) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:100%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Semua pengguna terdaftar</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mt-3 mt-md-0">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-teal bg-gradient-to-green overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Aktif</div>
                <div class="h2 mb-4"><?= esc((string) $activeUsers) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:<?= $totalUsers > 0 ? (int) round($activeUsers / $totalUsers * 100) : 0 ?>%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">User berstatus aktif</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:check-circle-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mt-3 mt-md-0">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-orange bg-gradient-to-pink overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Nonaktif</div>
                <div class="h2 mb-4"><?= esc((string) $inactiveUsers) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:<?= $totalUsers > 0 ? (int) round($inactiveUsers / $totalUsers * 100) : 0 ?>%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">User nonaktif</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:close-circle-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
</div>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="me-1"></iconify-icon>
            Daftar User
        </h4>
        <div class="card-header-btn">
            <button type="button" class="btn btn-theme btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fa fa-plus me-1"></i> Tambah User Baru
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover table-striped align-middle mb-0">
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
                        <td>
                            <div class="d-inline-flex align-items-center gap-1">
                                <span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace"><?= esc($user['username']) ?></span>
                                <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($user['username']) ?>" title="Salin Username">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td class="fw-semibold"><?= esc($user['full_name']) ?></td>
                        <td><span class="badge bg-info bg-opacity-15 text-info py-6px"><?= esc($user['role_name'] ?? '-') ?></span></td>
                        <td><?= esc($user['branch_name'] ?? '-') ?></td>
                        <td class="text-center">
                            <?php if (! empty($user['is_active'])) : ?>
                                <span class="badge bg-success bg-opacity-15 text-success py-6px badge-active">Aktif</span>
                            <?php else : ?>
                                <span class="badge bg-secondary bg-opacity-15 text-secondary py-6px badge-inactive">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-default btn-xs btn-icon btn-edit-user"
                                    data-id="<?= esc($user['id']) ?>"
                                    data-full-name="<?= esc($user['full_name']) ?>"
                                    data-role-id="<?= esc($user['role_id'] ?? '') ?>"
                                    data-branch-id="<?= esc($user['branch_id'] ?? '') ?>"
                                    data-is-active="<?= ! empty($user['is_active']) ? '1' : '0' ?>"
                                    data-bs-toggle="modal" data-bs-target="#editUserModal"
                                    title="Edit User">
                                <i class="fa fa-pen"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)) : ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada user. Klik "Tambah User Baru" untuk menambahkan.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">
                    <iconify-icon icon="solar:user-plus-bold-duotone" class="me-1 text-primary"></iconify-icon>
                    Tambah User Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="createUserForm" method="post" action="<?= site_url('access/users') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_username">Username <span class="text-danger">*</span></label>
                            <input id="new_username" name="username" type="text" class="form-control" required placeholder="Contoh: john.doe">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_full_name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input id="new_full_name" name="full_name" type="text" class="form-control" required placeholder="Nama lengkap pengguna">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="new_password">Password Awal <span class="text-danger">*</span></label>
                            <input id="new_password" name="password" type="password" class="form-control" minlength="12" required placeholder="Minimal 12 karakter">
                            <div class="form-text">Password minimal 12 karakter.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_role_id">Role <span class="text-danger">*</span></label>
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
                            <label class="form-label" for="new_branch_id">Cabang <span class="text-danger">*</span></label>
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
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnCreateUser" class="btn btn-theme">
                        <i class="fa fa-floppy-disk me-1"></i> Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    <iconify-icon icon="solar:pen-bold-duotone" class="me-1 text-primary"></iconify-icon>
                    Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="editUserForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="edit_full_name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input id="edit_full_name" name="full_name" type="text" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="edit_role_id">Role <span class="text-danger">*</span></label>
                            <select id="edit_role_id" name="role_id" class="select2" data-placeholder="Pilih role..." required>
                                <option value=""></option>
                                <?php foreach ($roles as $role) : ?>
                                    <option value="<?= esc($role['id']) ?>"><?= esc($role['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="edit_branch_id">Cabang <span class="text-danger">*</span></label>
                            <select id="edit_branch_id" name="branch_id" class="select2" data-placeholder="Pilih cabang..." required>
                                <option value=""></option>
                                <?php foreach ($branches as $branch) : ?>
                                    <option value="<?= esc($branch['id']) ?>"><?= esc($branch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit_password">Password Baru <span class="text-muted fw-normal">(opsional)</span></label>
                            <input id="edit_password" name="password" type="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
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
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnEditUser" class="btn btn-theme">
                        <i class="fa fa-floppy-disk me-1"></i> Perbarui User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#usersTable tbody tr td[colspan]').length === 0) {
        App.initDT('#usersTable', {
            order: [[2, 'asc']],
            columnDefs: [
                { orderable: false, targets: [0, 5, 6] },
                { searchable: false, targets: [0, 5, 6] }
            ]
        });
    }

    var $createModal = $('#createUserModal');
    $createModal.on('shown.bs.modal', function () {
        App.initSelect2InModal('#new_role_id', '#createUserModal', { placeholder: 'Pilih role...' });
        App.initSelect2InModal('#new_branch_id', '#createUserModal', { placeholder: 'Pilih cabang...' });
    });
    $createModal.on('hidden.bs.modal', function () {
        $('#createUserForm')[0].reset();
        $('#new_role_id, #new_branch_id').val('').trigger('change.select2');
    });

    var $editModal = $('#editUserModal');
    $editModal.on('shown.bs.modal', function (e) {
        App.initSelect2InModal('#edit_role_id', '#editUserModal', { placeholder: 'Pilih role...' });
        App.initSelect2InModal('#edit_branch_id', '#editUserModal', { placeholder: 'Pilih cabang...' });
        var $btn = $(e.relatedTarget);
        if (!$btn.hasClass('btn-edit-user')) return;
        var userId = $btn.data('id');
        $('#editUserForm').attr('action', '<?= site_url('access/users/') ?>' + userId);
        $('#edit_full_name').val($btn.attr('data-full-name') || $btn.data('fullName') || '');
        $('#edit_role_id').val($btn.attr('data-role-id') || $btn.data('roleId') || '').trigger('change.select2');
        $('#edit_branch_id').val($btn.attr('data-branch-id') || $btn.data('branchId') || '').trigger('change.select2');
        var isActive = $btn.attr('data-is-active') || $btn.data('isActive');
        $('#edit_is_active').prop('checked', isActive === 1 || isActive === '1');
        $('#edit_password').val('');
    });
    $editModal.on('hidden.bs.modal', function () {
        $('#editUserForm')[0].reset();
    });

    $('#createUserForm').on('submit', function () {
        App.btnLoading($('#btnCreateUser'), 'Menyimpan...');
    });
    $('#editUserForm').on('submit', function () {
        App.btnLoading($('#btnEditUser'), 'Memperbarui...');
    });
});
</script>

<?= view('partials/shell_end') ?>
