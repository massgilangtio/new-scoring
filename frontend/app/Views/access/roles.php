<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Manajemen Role']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-user-shield me-2 text-primary"></i>Manajemen Role</h2>
        <p class="text-muted mb-0">Kelola role dan hak akses pengguna. Hak akses mengikuti permission pada role, bukan nama role.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
            <i class="fa-solid fa-plus me-1"></i> Tambah Role
        </button>
    </div>
</div>

<!-- Roles Grid -->
<?php if (! empty($roles)) : ?>
<div class="row g-3">
    <?php foreach ($roles as $role) : ?>
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h5 class="mb-0"><?= esc($role['name']) ?></h5>
                    <small class="text-muted"><?= esc($role['code']) ?></small>
                </div>
                <span class="badge bg-primary"><?= count($role['permissions'] ?? []) ?> permission</span>
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
                                <label class="form-check-label" for="perm-<?= esc($role['id']) ?>-<?= esc($permission['id']) ?>"
                                       style="font-size:12px;">
                                    <span class="fw-semibold" style="color:var(--primary);"><?= esc($permission['name']) ?></span>
                                    <br>
                                    <small class="text-muted font-monospace"><?= esc($permission['code']) ?></small>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="button" class="btn btn-primary btn-save-role" data-role-id="<?= esc($role['id']) ?>">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Hak Akses
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else : ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state py-4">
            <i class="fa-solid fa-user-shield"></i>
            <h6>Belum Ada Role</h6>
            <p>Klik "Tambah Role" untuk membuat role pertama.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL: Create Role -->
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createRoleLabel">
                    <i class="fa-solid fa-user-plus me-2 text-primary"></i>Tambah Role Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="createRoleForm" method="post" action="<?= site_url('access/roles') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="role_code">Kode Role <span class="required">*</span></label>
                            <input id="role_code" name="code" type="text" class="form-control"
                                   pattern="[a-z0-9_]{2,50}" required
                                   placeholder="Contoh: staff_ao">
                            <div class="form-text">Lowercase, hanya huruf, angka, dan underscore.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="role_name">Nama Role <span class="required">*</span></label>
                            <input id="role_name" name="name" type="text" class="form-control" required
                                   placeholder="Contoh: Staff AO">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnCreateRole" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // Save permissions with confirmation
    $(document).on('click', '.btn-save-role', function () {
        var roleId = $(this).data('role-id');
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
