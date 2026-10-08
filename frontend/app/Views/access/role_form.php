<?php
helper('access');
$profile = $profile ?? [];
$role = $role ?? [];
$permissions = $permissions ?? [];
$roleId = (int) ($role['id'] ?? 0);
$selectedCodes = $role['permissions'] ?? [];
$selectedIds = array_map('intval', $role['permission_ids'] ?? []);
$permGroups = access_group_permissions($permissions);
$rawCodes = [];
if (! empty($role['job_group_codes']) && is_array($role['job_group_codes'])) {
    $rawCodes = $role['job_group_codes'];
} elseif (! empty($role['job_groups']) && is_array($role['job_groups'])) {
    $rawCodes = array_column($role['job_groups'], 'code');
} elseif (! empty($role['job_group_map']) && $role['job_group_map'] !== '-') {
    $rawCodes = array_map('trim', explode(',', $role['job_group_map']));
}
$uniqueCodes = array_values(array_unique(array_filter($rawCodes)));
$roleMaps = ! empty($uniqueCodes) ? implode(', ', $uniqueCodes) : '-';
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Edit Role']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?= view('partials/access_subnav', ['active' => 'roles']) ?>

<div class="card card-borderless role-form-card mb-3">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <i class="fa fa-bullhorn me-1"></i> Form Data Role
        </h4>
        <div class="card-header-btn">
            <a href="<?= site_url('access/roles') ?>" class="btn btn-default btn-xs">
                <i class="fa fa-arrow-left"></i><span class="btn-label-full ms-1">Kembali</span>
            </a>
            <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
        </div>
    </div>
    <div class="card-body">
        <form id="roleForm" method="post" action="<?= site_url('access/roles/' . $roleId) ?>">
            <?= csrf_field() ?>

            <div class="row g-3 mb-4 role-meta-form">
                <div class="col-md-4">
                    <label class="form-label" for="role_code">Role ID <span class="text-danger">*</span></label>
                    <input id="role_code" type="text" class="form-control font-monospace" value="<?= esc($role['code'] ?? '') ?>" readonly>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="role_name">Nama Role <span class="text-danger">*</span></label>
                    <input id="role_name" name="name" type="text" class="form-control" required value="<?= esc($role['name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="role_maps">Role Maps</label>
                    <input id="role_maps" type="text" class="form-control font-monospace bg-light" value="<?= esc($roleMaps) ?>" readonly title="Kelompok Jabatan (Role Maps) yang terhubung ke role ini">
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="role_is_active" <?= ! empty($role['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role_is_active">Role aktif</label>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <h5 class="mb-0 fw-bold">MODULE</h5>
                <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0" style="max-width:320px;">
                    <label class="form-label mb-0 small text-muted" for="moduleSearch">Search:</label>
                    <input type="search" id="moduleSearch" class="form-control form-control-sm" placeholder="Cari module / permission...">
                </div>
            </div>

            <div class="module-perm-panel mb-3">
                <div class="module-perm-toolbar px-3 py-2 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="small text-muted">
                        Centang module permission yang boleh diakses role ini.
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="toggleAllModules">
                        <label class="form-check-label small fw-semibold" for="toggleAllModules">Pilih semua</label>
                    </div>
                </div>
                <div class="module-perm-list" id="modulePermList">
                    <?php foreach ($permGroups as $groupKey => $group) :
                        $meta = $group['meta'];
                        ?>
                    <div class="module-group-label" data-group="<?= esc($groupKey) ?>">
                        <i class="<?= esc($meta['icon']) ?> me-1"></i><?= esc($meta['label']) ?>
                    </div>
                    <?php foreach ($group['items'] as $permission) :
                        $pid = (int) ($permission['id'] ?? 0);
                        $code = (string) ($permission['code'] ?? '');
                        $name = (string) ($permission['name'] ?? '');
                        $checked = in_array($pid, $selectedIds, true) || in_array($code, $selectedCodes, true);
                        $label = strtoupper($code) . ' - ' . strtoupper($name);
                        $searchBlob = strtolower($code . ' ' . $name . ' ' . $meta['label']);
                        ?>
                    <label class="module-perm-row" data-search="<?= esc($searchBlob) ?>">
                        <span class="module-perm-caption">
                            <span class="module-perm-code"><?= esc(strtoupper($code)) ?></span>
                            <span class="module-perm-sep">-</span>
                            <span class="module-perm-name"><?= esc($name) ?></span>
                        </span>
                        <input class="form-check-input module-perm-check" type="checkbox"
                               name="permission_ids[]" value="<?= esc((string) $pid) ?>"
                               <?= $checked ? 'checked' : '' ?>>
                    </label>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php if (empty($permissions)) : ?>
                    <div class="text-center text-muted py-4">Belum ada katalog permission.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-end gap-2">
                <a href="<?= site_url('access/roles') ?>" class="btn btn-default">Batal</a>
                <button type="submit" id="btnSaveRole" class="btn btn-theme">
                    <i class="fa fa-floppy-disk me-1"></i> Simpan Hak Akses
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    function syncMaps() {
        var total = $('.module-perm-check').length;
        var checked = $('.module-perm-check:checked').length;
        var $all = $('#toggleAllModules').get(0);
        if ($all) {
            $all.indeterminate = checked > 0 && checked < total;
            $('#toggleAllModules').prop('checked', checked === total && total > 0);
        }
    }

    function filterModules() {
        var q = String($('#moduleSearch').val() || '').toLowerCase().trim();
        $('#modulePermList .module-perm-row').each(function () {
            var hay = String($(this).data('search') || '');
            $(this).toggle(!q || hay.indexOf(q) !== -1);
        });
        $('#modulePermList .module-group-label').each(function () {
            var $label = $(this);
            var visible = false;
            $label.nextUntil('.module-group-label').each(function () {
                if ($(this).hasClass('module-perm-row') && $(this).is(':visible')) {
                    visible = true;
                    return false;
                }
            });
            $label.toggle(visible || !q);
        });
    }

    $('#moduleSearch').on('input', filterModules);
    $(document).on('change', '.module-perm-check', syncMaps);
    $('#toggleAllModules').on('change', function () {
        var on = $(this).is(':checked');
        $('.module-perm-row:visible .module-perm-check').prop('checked', on);
        syncMaps();
    });

    $('#roleForm').on('submit', function () {
        App.btnLoading($('#btnSaveRole'), 'Menyimpan...');
    });

    syncMaps();
});
</script>

<?= view('partials/shell_end') ?>
