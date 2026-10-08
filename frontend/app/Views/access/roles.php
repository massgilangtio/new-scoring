<?php
$profile = $profile ?? [];
$roles = $roles ?? [];
$totalRoles = count($roles);
$totalRoles = count($roles);
$linkedJgCount = 0;
$totalPermsInRoles = 0;
foreach ($roles as $role) {
    if (! empty($role['job_groups']) || (! empty($role['job_group_map']) && $role['job_group_map'] !== '-')) {
        $linkedJgCount++;
    }
    $totalPermsInRoles = max($totalPermsInRoles, count($role['permission_ids'] ?? $role['permissions'] ?? []));
}
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Manajemen Role']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?= view('partials/access_subnav', ['active' => 'roles']) ?>

<div class="row g-2 mb-3 access-kpi">
    <div class="col-4">
        <div class="card card-borderless rounded-3 overflow-hidden bg-indigo h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:shield-user-bold-duotone"></iconify-icon> Total Role</div>
                <div class="kpi-value"><?= esc((string) $totalRoles) ?></div>
                <p class="kpi-sub">Role otorisasi aktif</p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card card-borderless rounded-3 overflow-hidden bg-teal h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:link-bold-duotone"></iconify-icon> Kelompok Terhubung</div>
                <div class="kpi-value"><?= esc((string) $linkedJgCount) ?></div>
                <p class="kpi-sub">Dipakai kelompok jabatan</p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card card-borderless rounded-3 overflow-hidden bg-blue h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Total Hak Akses</div>
                <div class="kpi-value"><?= esc((string) $totalPermsInRoles) ?></div>
                <p class="kpi-sub">Otoritas permission</p>
            </div>
        </div>
    </div>
</div>

<div class="alert access-note d-flex align-items-start gap-2 mb-3">
    <i class="fa fa-circle-info mt-1 text-primary"></i>
    <div class="small mb-0">
        Kolom <strong>Kelompok Jabatan (Role Maps)</strong> menunjukkan kelompok jabatan HRIS yang terhubung ke role ini.
        Misalnya role <strong>SUPERADMIN</strong> terhubung ke Kelompok Jabatan <strong>999 (Divisi Teknologi Informasi)</strong> sehingga pegawai divisi tersebut otomatis memperoleh wewenang SUPER ADMINISTRATOR.
    </div>
</div>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <i class="fa fa-list me-1"></i> List Roles
        </h4>
        <div class="card-header-btn">
            <button type="button" class="btn btn-success btn-xs" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                <i class="fa fa-plus"></i><span class="btn-label-full ms-1">Tambah Data</span>
            </button>
            <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
        </div>
    </div>
    <div class="card-body">
        <table id="rolesTable" class="table table-hover table-striped w-100 mb-0">
            <thead>
                <tr>
                    <th style="width:45px;" class="text-center align-top">No.</th>
                    <th style="width:140px;" class="text-center align-top">Role ID</th>
                    <th style="width:220px;" class="text-start align-top">Nama Role</th>
                    <th class="align-top">Kelompok Jabatan (Role Maps)</th>
                    <th style="width:160px;" class="text-center align-top">Hak Akses</th>
                    <th style="width:80px;" class="text-center align-top">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $index => $role) :
                    $jgs = $role['job_groups'] ?? [];
                    $permCount = count($role['permission_ids'] ?? $role['permissions'] ?? []);
                    ?>
                <tr>
                    <td class="text-center text-muted align-top"><?= esc((string) ($index + 1)) ?></td>
                    <td class="text-center align-top">
                        <div class="d-inline-flex align-items-center gap-1 justify-content-center">
                            <span class="badge access-badge access-badge-code font-monospace"><?= esc($role['code'] ?? '-') ?></span>
                            <?php if (! empty($role['code']) && $role['code'] !== '-') : ?>
                                <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($role['code']) ?>" title="Salin Role ID">
                                    <i class="fa fa-copy"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="fw-semibold text-start align-top"><?= esc($role['name'] ?? '-') ?></td>
                    <td class="align-top">
                        <?php if (! empty($jgs)) : ?>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <?php foreach ($jgs as $jg) : ?>
                                    <span class="badge bg-secondary font-monospace px-2 py-1">
                                        <i class="fa fa-briefcase me-1 text-warning"></i><?= esc($jg['code'] ?? '-') ?>
                                    </span>
                                    <span class="small text-dark fw-medium me-2"><?= esc($jg['name'] ?? '') ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (! empty($role['job_group_map']) && $role['job_group_map'] !== '-') : ?>
                            <span class="badge bg-secondary font-monospace px-2 py-1"><?= esc($role['job_group_map']) ?></span>
                        <?php else : ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                <i class="fa fa-triangle-exclamation me-1"></i>Belum Dipetakan
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center align-top">
                        <?php if ($permCount > 0) : ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                <i class="fa fa-shield me-1"></i><?= esc((string) $permCount) ?> Hak Akses
                            </span>
                        <?php else : ?>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-1">
                                0 Hak Akses
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center align-top">
                        <?php
                        ob_start();
                        ?>
                                <li>
                                    <a href="<?= site_url('access/roles/' . (int) ($role['id'] ?? 0)) ?>" class="dropdown-action-item">
                                        <span class="action-icon-circle action-icon-blue"><i class="fa-solid fa-pen-to-square"></i></span>
                                        <div class="action-text-group">
                                            <span class="action-title">Edit Role</span>
                                            <span class="action-desc">Ubah nama &amp; permission role</span>
                                        </div>
                                    </a>
                                </li>
                        <?php
                        echo view('partials/action_dropdown_btn', ['menuHtml' => ob_get_clean()]);
                        ?>
                    </td>
                </tr>

                <?php endforeach; ?>
                <?php if (empty($roles)) : ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada role. Klik "Tambah Data" untuk membuat role pertama.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

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
                            <label class="form-label" for="role_code">Role ID <span class="text-danger">*</span></label>
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
                        <i class="fa fa-floppy-disk me-1"></i> Simpan &amp; Atur Permission
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#rolesTable tbody tr td[colspan]').length === 0) {
        var dt = App.initDT('#rolesTable', {
            order: [[2, 'asc']],
            columnDefs: [
                { orderable: false, targets: [0, 4] },
                { searchable: false, targets: [0, 4] }
            ]
        });
        if (dt) {
            dt.on('order.dt search.dt draw.dt', function () {
                dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                    cell.innerHTML = '<span class="text-muted">' + (i + 1) + '</span>';
                });
            }).draw(false);
        }
    }

    $('#createRoleForm').on('submit', function () {
        App.btnLoading($('#btnCreateRole'), 'Menyimpan...');
    });
});
</script>

<?= view('partials/shell_end') ?>
