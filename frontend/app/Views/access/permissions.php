<?php
helper('access');
$profile = $profile ?? [];
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
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Hak Akses / Module']) ?>

<?= view('partials/access_subnav', ['active' => 'permissions']) ?>

<div class="row g-2 mb-3 access-kpi">
    <div class="col-4">
        <div class="card card-borderless rounded-3 overflow-hidden bg-indigo h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:key-bold-duotone"></iconify-icon> Total Module</div>
                <div class="kpi-value"><?= esc((string) $totalPerms) ?></div>
                <p class="kpi-sub">Katalog permission</p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card card-borderless rounded-3 overflow-hidden bg-teal h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Aktif</div>
                <div class="kpi-value"><?= esc((string) $activePerms) ?></div>
                <p class="kpi-sub">Module aktif</p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card card-borderless rounded-3 overflow-hidden bg-orange h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon> Nonaktif</div>
                <div class="kpi-value"><?= esc((string) $inactivePerms) ?></div>
                <p class="kpi-sub">Nonaktif</p>
            </div>
        </div>
    </div>
</div>

<div class="alert access-note d-flex align-items-start gap-2 mb-3">
    <i class="fa fa-circle-info mt-1 text-primary"></i>
    <div class="small mb-0">
        <strong>Catatan:</strong> Katalog module bersifat terpusat (read-only). Mapping ke role dilakukan di menu
        <a href="<?= site_url('access/roles') ?>" class="fw-semibold">Role</a> → Edit.
    </div>
</div>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <i class="fa fa-list me-1"></i> List Module
        </h4>
        <?= view('partials/card_widget_btn') ?>
    </div>
    <div class="card-body">
        <table class="table table-hover table-striped align-middle w-100 mb-0" id="permissionsTable">
            <thead>
                <tr>
                    <th style="width:56px;">No.</th>
                    <th style="width:160px;">Module ID</th>
                    <th>Module Name</th>
                    <th style="width:140px;">Grup</th>
                    <th style="width:110px;" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($permissions)) : ?>
                    <?php $no = 1; foreach ($permissions as $permission) :
                        $module = access_perm_module((string) ($permission['code'] ?? ''));
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td>
                                <span class="badge access-badge access-badge-code font-monospace"><?= esc($permission['code']) ?></span>
                            </td>
                            <td class="fw-semibold"><?= esc($permission['name']) ?></td>
                            <td><span class="module-pill mod-<?= esc($module['tone']) ?>"><?= esc($module['label']) ?></span></td>
                            <td class="text-center">
                                <?php if (! empty($permission['is_active'])) : ?>
                                    <span class="badge access-badge access-badge-active">Aktif</span>
                                <?php else : ?>
                                    <span class="badge access-badge access-badge-inactive">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada data module.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#permissionsTable tbody tr td[colspan]').length === 0) {
        var dt = App.initDT('#permissionsTable', {
            pageLength: 25,
            order: [[1, 'asc']],
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
});
</script>

<?= view('partials/shell_end') ?>
