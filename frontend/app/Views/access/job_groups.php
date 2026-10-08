<?php
$profile = $profile ?? [];
$jobGroups = $jobGroups ?? [];
$roles = $roles ?? [];
$total = count($jobGroups);
$activeCount = 0;
$mappedCount = 0;
$totalPegawaiCovered = 0;
foreach ($jobGroups as $g) {
    if (! empty($g['is_active'])) {
        $activeCount++;
    }
    if (! empty($g['role_id'])) {
        $mappedCount++;
    }
    $totalPegawaiCovered += (int) ($g['total_pegawai'] ?? 0);
}
$unmappedCount = $total - $mappedCount;
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Kelompok Jabatan']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?= view('partials/access_subnav', ['active' => 'job_groups']) ?>

<div class="row g-2 mb-3 access-kpi">
    <div class="col-md-3 col-6">
        <div class="card card-borderless rounded-3 overflow-hidden bg-indigo h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:case-round-bold-duotone"></iconify-icon> Total Kelompok</div>
                <div class="kpi-value"><?= esc((string) $total) ?></div>
                <p class="kpi-sub">Data dari tbl_userhris</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-borderless rounded-3 overflow-hidden bg-teal h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:users-group-rounded-bold-duotone"></iconify-icon> Total Pegawai</div>
                <div class="kpi-value"><?= number_format($totalPegawaiCovered, 0, ',', '.') ?></div>
                <p class="kpi-sub">Pegawai aktif tercover</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-borderless rounded-3 overflow-hidden bg-blue h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:shield-check-bold-duotone"></iconify-icon> Role Terpetakan</div>
                <div class="kpi-value"><?= esc((string) $mappedCount) ?></div>
                <p class="kpi-sub"><?= esc((string) $unmappedCount) ?> belum dipetakan</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-borderless rounded-3 overflow-hidden bg-orange h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label text-white"><iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Status Aktif</div>
                <div class="kpi-value text-white"><?= esc((string) $activeCount) ?></div>
                <p class="kpi-sub text-white-50">Dapat dipakai user</p>
            </div>
        </div>
    </div>
</div>

<div class="alert access-note d-flex align-items-start gap-2 mb-3">
    <i class="fa fa-circle-info mt-1 text-primary"></i>
    <div class="small mb-0">
        <strong>Kelompok Jabatan (<code>tbl_kel_jabatan</code>)</strong> digenerate secara otomatis dari data riil pegawai HRIS (<code>tbl_userhris</code>).
        Setiap kelompok dapat dipetakan ke <strong>Role Otorisasi</strong> agar pengguna yang masuk dalam kelompok tersebut secara otomatis mendapatkan wewenang sesuai tugasnya.
    </div>
</div>

<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <i class="fa fa-briefcase me-1 text-theme"></i> Master Kelompok Jabatan HRIS
        </h4>
        <div class="card-header-btn d-flex align-items-center gap-1">
            <form id="syncJobGroupForm" method="post" action="<?= site_url('access/job-groups/sync') ?>" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" id="btnSyncJobGroups" class="btn btn-primary btn-xs">
                    <i class="fa fa-rotate me-1"></i><span class="btn-label-full">Sinkronkan dari HRIS</span>
                </button>
            </form>
            <button type="button" class="btn btn-success btn-xs ms-1" data-bs-toggle="modal" data-bs-target="#createJobGroupModal">
                <i class="fa fa-plus me-1"></i><span class="btn-label-full">Tambah Kelompok</span>
            </button>
            <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
        </div>
    </div>
    <div class="card-body">
        <table id="jobGroupsTable" class="table table-hover table-striped align-middle w-100 mb-0">
            <thead>
                <tr>
                    <th style="width:45px;" class="text-center">No.</th>
                    <th style="width:130px;">ID Kelompok</th>
                    <th>Nama Kelompok Jabatan</th>
                    <th style="width:130px;" class="text-center">Total Pegawai</th>
                    <th>Role Otorisasi</th>
                    <th class="text-center" style="width:90px;">Status</th>
                    <th class="text-center" style="width:70px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobGroups as $index => $group) : ?>
                <tr>
                    <td class="text-center text-muted"><?= esc((string) ($index + 1)) ?></td>
                    <td>
                        <?php $jgCode = (string) ($group['id_kel_jabatan'] ?? $group['code'] ?? '-'); ?>
                        <div class="d-inline-flex align-items-center gap-1">
                            <span class="badge bg-secondary font-monospace fw-bold px-2 py-1">
                                <?= esc($jgCode) ?>
                            </span>
                            <?php if ($jgCode !== '' && $jgCode !== '-') : ?>
                                <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($jgCode) ?>" title="Salin ID Kelompok">
                                    <i class="fa fa-copy"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="fw-semibold">
                        <?= esc($group['nama_kel_jabatan'] ?? $group['name'] ?? '-') ?>
                    </td>
                    <td class="text-center">
                        <?php $jml = (int) ($group['total_pegawai'] ?? 0); ?>
                        <?php if ($jml > 0) : ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1">
                                <i class="fa fa-users me-1"></i><?= number_format($jml, 0, ',', '.') ?> Pegawai
                            </span>
                        <?php else : ?>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-1">
                                0 Pegawai
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (! empty($group['role_id'])) : ?>
                            <span class="badge bg-teal text-white px-2 py-1">
                                <i class="fa fa-shield me-1"></i><?= esc($group['role_name'] ?? 'Role ID: ' . $group['role_id']) ?>
                            </span>
                            <?php if (! empty($group['role_code'])) : ?>
                                <span class="small text-muted font-monospace ms-1">(<?= esc($group['role_code']) ?>)</span>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                <i class="fa fa-triangle-exclamation me-1"></i>Belum Dipetakan
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if (! empty($group['is_active'])) : ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                        <?php else : ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php
                        ob_start();
                        ?>
                                <li>
                                    <button type="button"
                                            class="dropdown-action-item btn-edit-job-group"
                                            data-id="<?= esc($group['id']) ?>"
                                            data-id-kel-jabatan="<?= esc($group['id_kel_jabatan'] ?? $group['code'] ?? '') ?>"
                                            data-nama-kel-jabatan="<?= esc($group['nama_kel_jabatan'] ?? $group['name'] ?? '') ?>"
                                            data-role-id="<?= esc($group['role_id'] ?? '') ?>"
                                            data-is-active="<?= ! empty($group['is_active']) ? '1' : '0' ?>"
                                            data-bs-toggle="modal" data-bs-target="#editJobGroupModal">
                                        <span class="action-icon-circle action-icon-blue"><i class="fa-solid fa-pen-to-square"></i></span>
                                        <div class="action-text-group">
                                            <span class="action-title">Petakan Role</span>
                                            <span class="action-desc">Ubah nama &amp; mapping role</span>
                                        </div>
                                    </button>
                                </li>
                        <?php
                        echo view('partials/action_dropdown_btn', ['menuHtml' => ob_get_clean()]);
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($jobGroups)) : ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Belum ada kelompok jabatan. Klik tombol "Sinkronkan dari HRIS" untuk memuat data.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kelompok Jabatan -->
<div class="modal fade" id="createJobGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-briefcase me-1 text-primary"></i> Tambah Kelompok Jabatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createJobGroupForm" method="post" action="<?= site_url('access/job-groups') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="jg_id_kel_jabatan">ID Kelompok <span class="text-danger">*</span></label>
                            <input id="jg_id_kel_jabatan" name="id_kel_jabatan" type="text" class="form-control font-monospace" required placeholder="Contoh: 15 / AO_01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="jg_nama_kel_jabatan">Nama Kelompok <span class="text-danger">*</span></label>
                            <input id="jg_nama_kel_jabatan" name="nama_kel_jabatan" type="text" class="form-control" required placeholder="Contoh: Account Officer">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="jg_role_id">Role Otorisasi</label>
                            <select id="jg_role_id" name="role_id" class="select2" data-placeholder="Pilih role otorisasi (opsional)...">
                                <option value="">-- Belum Dipetakan (Pilih Nanti) --</option>
                                <?php foreach ($roles as $role) : ?>
                                    <?php if (! empty($role['is_active'])) : ?>
                                    <option value="<?= esc($role['id']) ?>"><?= esc($role['name']) ?> (<?= esc($role['code']) ?>)</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">User dengan kelompok ini dapat dihubungkan ke role otorisasi terkait.</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="jg_is_active">
                                <label class="form-check-label" for="jg_is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnCreateJobGroup" class="btn btn-theme"><i class="fa fa-floppy-disk me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kelompok Jabatan / Pemetaan Role -->
<div class="modal fade" id="editJobGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-pen me-1 text-primary"></i> Kelola Kelompok Jabatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editJobGroupForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="edit_jg_id_kel_jabatan">ID Kelompok</label>
                            <input id="edit_jg_id_kel_jabatan" type="text" class="form-control font-monospace bg-light" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit_jg_nama_kel_jabatan">Nama Kelompok <span class="text-danger">*</span></label>
                            <input id="edit_jg_nama_kel_jabatan" name="nama_kel_jabatan" type="text" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit_jg_role_id">Role Otorisasi</label>
                            <select id="edit_jg_role_id" name="role_id" class="select2" data-placeholder="Pilih role otorisasi...">
                                <option value="">-- Belum Dipetakan --</option>
                                <?php foreach ($roles as $role) : ?>
                                    <option value="<?= esc($role['id']) ?>"><?= esc($role['name']) ?> (<?= esc($role['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Pengguna yang memiliki kelompok ini akan disinkronkan ke role terpilih.</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_jg_is_active">
                                <label class="form-check-label" for="edit_jg_is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnEditJobGroup" class="btn btn-theme"><i class="fa fa-floppy-disk me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#jobGroupsTable tbody tr td[colspan]').length === 0) {
        var dt = App.initDT('#jobGroupsTable', {
            order: [[3, 'desc'], [1, 'asc']],
            columnDefs: [
                { orderable: false, targets: [0, 6] },
                { searchable: false, targets: [0, 6] }
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

    $('#createJobGroupModal').on('shown.bs.modal', function () {
        App.initSelect2InModal('#jg_role_id', '#createJobGroupModal', { placeholder: 'Pilih role otorisasi (opsional)...' });
    });
    $('#createJobGroupModal').on('hidden.bs.modal', function () {
        $('#createJobGroupForm')[0].reset();
        $('#jg_role_id').val('').trigger('change.select2');
    });

    $('#editJobGroupModal').on('shown.bs.modal', function (e) {
        App.initSelect2InModal('#edit_jg_role_id', '#editJobGroupModal', { placeholder: 'Pilih role otorisasi...' });
        var $btn = $(e.relatedTarget);
        if (!$btn.hasClass('btn-edit-job-group')) return;
        $('#editJobGroupForm').attr('action', '<?= site_url('access/job-groups/') ?>' + $btn.data('id'));
        $('#edit_jg_id_kel_jabatan').val($btn.attr('data-id-kel-jabatan') || '');
        $('#edit_jg_nama_kel_jabatan').val($btn.attr('data-nama-kel-jabatan') || '');
        $('#edit_jg_role_id').val($btn.attr('data-role-id') || '').trigger('change.select2');
        var active = $btn.attr('data-is-active');
        $('#edit_jg_is_active').prop('checked', active === '1' || active === 1);
    });

    $('#syncJobGroupForm').on('submit', function () {
        App.btnLoading($('#btnSyncJobGroups'), 'Sinkronisasi...');
    });
    $('#createJobGroupForm').on('submit', function () { App.btnLoading($('#btnCreateJobGroup'), 'Menyimpan...'); });
    $('#editJobGroupForm').on('submit', function () { App.btnLoading($('#btnEditJobGroup'), 'Menyimpan...'); });
});
</script>

<?= view('partials/shell_end') ?>
