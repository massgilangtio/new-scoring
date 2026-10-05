<?php
$permissions = $permissions ?? [];
$totalPerms = count($permissions);
$activePerms = 0;
foreach ($permissions as $p) {
    if (! empty($p['is_active'])) {
        $activePerms++;
    }
}
$inactivePerms = max(0, $totalPerms - $activePerms);
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Hak Akses']) ?>

<ul class="nav nav-pills access-subnav gap-2 mb-3">
    <li class="nav-item"><a class="nav-link" href="<?= site_url('access/users') ?>"><i class="fa fa-users me-1"></i>Pengguna</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= site_url('access/roles') ?>"><i class="fa fa-user-shield me-1"></i>Role</a></li>
    <li class="nav-item"><a class="nav-link active" href="<?= site_url('access/permissions') ?>"><i class="fa fa-key me-1"></i>Hak Akses</a></li>
</ul>

<div class="row mb-3">
    <div class="col-xl-4 col-md-4">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-indigo bg-gradient-to-purple overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Total Permission</div>
                <div class="h2 mb-4"><?= esc((string) $totalPerms) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:100%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Katalog hak akses</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:key-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mt-3 mt-md-0">
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-teal bg-gradient-to-cyan overflow-hidden" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="mb-2 fw-bold text-white">Aktif</div>
                <div class="h2 mb-4"><?= esc((string) $activePerms) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:<?= $totalPerms > 0 ? (int) round($activePerms / $totalPerms * 100) : 0 ?>%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Permission aktif</div>
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
                <div class="h2 mb-4"><?= esc((string) $inactivePerms) ?></div>
                <div class="progress h-5px bg-black mb-2"><div class="progress-bar bg-white bg-opacity-100 rounded-end" style="width:<?= $totalPerms > 0 ? (int) round($inactivePerms / $totalPerms * 100) : 0 ?>%;"></div></div>
                <div class="small fw-semibold text-white text-opacity-75 mb-n1">Permission nonaktif</div>
            </div>
            <div class="position-absolute w-100px h-100px bottom-0 end-0 d-flex align-items-center justify-content-center m-n3">
                <iconify-icon icon="solar:close-circle-bold-duotone" class="text-black text-opacity-30" style="font-size:150px"></iconify-icon>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info d-flex align-items-start gap-2 mb-3">
    <i class="fa fa-circle-info mt-1"></i>
    <div class="small mb-0">
        <strong>Catatan:</strong> Katalog ini bersifat terpusat. Penugasan permission ke role dikonfigurasi melalui menu Role.
    </div>
</div>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:shield-keyhole-bold-duotone" class="me-1"></iconify-icon>
            Daftar Hak Akses
        </h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0 datatable-permissions" id="permissionsTable">
                <thead>
                    <tr>
                        <th style="width: 60px;" class="text-center">No</th>
                        <th style="width: 280px;">Kode Permission</th>
                        <th>Nama Hak Akses</th>
                        <th style="width: 140px;" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($permissions)) : ?>
                        <?php $no = 1; foreach ($permissions as $permission) : ?>
                            <tr>
                                <td class="text-center text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace"><?= esc($permission['code']) ?></span>
                                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($permission['code']) ?>" title="Salin Kode Permission">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="fw-semibold"><?= esc($permission['name']) ?></td>
                                <td class="text-center">
                                    <?php if (! empty($permission['is_active'])) : ?>
                                        <span class="badge bg-success bg-opacity-15 text-success py-6px">Aktif</span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary bg-opacity-15 text-secondary py-6px">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada data permission.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#permissionsTable tbody tr td[colspan]').length === 0) {
        App.initDT('#permissionsTable', {
            pageLength: 25,
            order: [[1, 'asc']],
            columnDefs: [
                { orderable: false, targets: [0, 3] },
                { searchable: false, targets: [0, 3] }
            ]
        });
    }
});
</script>

<?= view('partials/shell_end') ?>
