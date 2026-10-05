<?php
$roles = $roles ?? [];
$permissions = $permissions ?? [];
$totalRoles = count($roles);
$totalPermCatalog = count($permissions);
$assignedCount = 0;
foreach ($roles as $role) {
    $assignedCount += count($role['permissions'] ?? []);
}
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Manajemen Role']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<ul class="nav nav-pills access-subnav gap-2 mb-3">
    <li class="nav-item"><a class="nav-link" href="<?= site_url('access/users') ?>"><i class="fa fa-users me-1"></i>Pengguna</a></li>
    <li class="nav-item"><a class="nav-link active" href="<?= site_url('access/roles') ?>"><i class="fa fa-user-shield me-1"></i>Role</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= site_url('access/permissions') ?>"><i class="fa fa-key me-1"></i>Hak Akses</a></li>
</ul>

<div class="row mb-3">
    <div class="col-xl-4 col-md-4">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-blue bg-gradient-to-indigo overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Total Role</div>
                <div class="h2 mb-4"><?= esc((string) $totalRoles) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:100%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Role terdaftar</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:shield-user-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mt-3 mt-md-0">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-teal bg-gradient-to-green overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Katalog Permission</div>
                <div class="h2 mb-4"><?= esc((string) $totalPermCatalog) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:100%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Permission tersedia</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:key-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mt-3 mt-md-0">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-orange bg-gradient-to-pink overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Assignment</div>
                <div class="h2 mb-4"><?= esc((string) $assignedCount) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:70%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Permission terikat ke role</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:link-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-theme btn-sm" data-bs-toggle="modal" data-bs-target="#createRoleModal">
        <i class="fa fa-plus me-1"></i> Tambah Role
    </button>
</div>

<?php if (! empty($roles)) : ?>
<div class="row g-3">
    <?php foreach ($roles as $role) : ?>
    <div class="col-12 col-lg-6">
        <div class="card card-borderless h-100 role-perm-card">
            <div class="card-header bg-gray-900" data-bs-theme="dark">
                <div>
                    <h4 class="card-header-title text-white mb-0"><?= esc($role['name']) ?></h4>
                    <div class="d-inline-flex align-items-center gap-1 mt-1">
                        <span class="badge bg-white bg-opacity-15 text-white font-monospace"><?= esc($role['code']) ?></span>
                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($role['code']) ?>" title="Salin Kode Role">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                </div>
                <span class="badge bg-theme"><?= count($role['permissions'] ?? []) ?> permission</span>
            </div>
            <div class="card-body">
                <form method="post" action="<?= site_url('access/roles/' . $role['id'] . '/permissions') ?>"
                      class="role-perm-form" id="roleForm-<?= esc($role['id']) ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2">
                        <?php foreach ($permissions as $permission) : ?>
                        <div class="col-12 col-sm-6">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="permission_ids[]"
                                       value="<?= esc($permission['id']) ?>"
                                       id="perm-<?= esc($role['id']) ?>-<?= esc($permission['id']) ?>"
                                       <?= in_array($permission['code'], $role['permissions'] ?? [], true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="perm-<?= esc($role['id']) ?>-<?= esc($permission['id']) ?>">
                                    <span class="fw-semibold d-block"><?= esc($permission['name']) ?></span>
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <small class="text-muted font-monospace"><?= esc($permission['code']) ?></small>
                                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($permission['code']) ?>" title="Salin Kode Permission" onclick="event.preventDefault();">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-end">
                <button type="button" class="btn btn-theme btn-sm btn-save-role" data-role-id="<?= esc($role['id']) ?>">
                    <i class="fa fa-floppy-disk me-1"></i> Simpan Hak Akses
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else : ?>
<div class="card card-borderless">
    <div class="card-body text-center text-muted py-5">Belum ada role. Klik "Tambah Role" untuk membuat role pertama.</div>
</div>
<?php endif; ?>

<div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createRoleLabel">
                    <iconify-icon icon="solar:shield-user-bold-duotone" class="me-1 text-primary"></iconify-icon>
                    Tambah Role Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="createRoleForm" method="post" action="<?= site_url('access/roles') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="role_code">Kode Role <span class="text-danger">*</span></label>
                            <input id="role_code" name="code" type="text" class="form-control" pattern="[a-z0-9_]{2,50}" required placeholder="Contoh: staff_ao">
                            <div class="form-text">Lowercase, huruf, angka, underscore.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="role_name">Nama Role <span class="text-danger">*</span></label>
                            <input id="role_name" name="name" type="text" class="form-control" required placeholder="Contoh: Staff AO">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnCreateRole" class="btn btn-theme">
                        <i class="fa fa-floppy-disk me-1"></i> Simpan Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $(document).on('click', '.btn-save-role', function () {
        var roleId = $(this).data('role-id') || $(this).attr('data-role-id');
        var $btn = $(this);
        App.confirmSave({
            title: 'Simpan Hak Akses',
            text: 'Perubahan hak akses role ini akan disimpan. Lanjutkan?'
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($btn, 'Menyimpan...');
                $('#roleForm-' + roleId).submit();
            }
        });
    });

    $('#createRoleForm').on('submit', function () {
        App.btnLoading($('#btnCreateRole'), 'Menyimpan...');
    });
});
</script>

<?= view('partials/shell_end') ?>
