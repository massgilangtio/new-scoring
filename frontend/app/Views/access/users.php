<?php
helper('access');
$userHris = $userHris ?? [];
$users = $users ?? [];
$displayItems = ! empty($userHris) ? $userHris : $users;

$jobGroups = $jobGroups ?? [];
$branches = $branches ?? [];
$totalUsers = count($displayItems);

$unitKerjaSet = [];
$kelJabatanSet = [];
foreach ($displayItems as $item) {
    if (! empty($item['id_unit_kerja'])) {
        $unitKerjaSet[$item['id_unit_kerja']] = true;
    }
    if (! empty($item['id_kel_jabatan'])) {
        $kelJabatanSet[$item['id_kel_jabatan']] = true;
    }
}
$totalUnitKerja = count($unitKerjaSet);
$totalKelJabatan = count($kelJabatanSet);
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Manajemen User & Pegawai HRIS']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?= view('partials/access_subnav', ['active' => 'users']) ?>

<!-- BEGIN KPI -->
<div class="row g-2 mb-3 access-kpi">
    <div class="col-4">
        <div class="card card-borderless rounded-3 overflow-hidden bg-blue h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:users-group-rounded-bold-duotone"></iconify-icon> Total Pegawai</div>
                <div class="kpi-value"><?= esc((string) $totalUsers) ?></div>
                <p class="kpi-sub">Data Pegawai HRIS (tbl_userhris)</p>
            </div>
            <div class="position-absolute top-0 end-0 mt-n5 ps-5 w-25 d-none d-md-block">
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle ms-n5 position-absolute top-0 start-0"></div>
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle mt-n5 position-absolute top-0 start-0"></div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card card-borderless rounded-3 overflow-hidden bg-teal h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:buildings-bold-duotone"></iconify-icon> Unit Kerja</div>
                <div class="kpi-value"><?= esc((string) max(1, $totalUnitKerja)) ?></div>
                <p class="kpi-sub">Divisi / Cabang Terdaftar</p>
            </div>
            <div class="position-absolute top-0 end-0 mt-n5 ps-5 w-25 d-none d-md-block">
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle ms-n5 position-absolute top-0 start-0"></div>
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle mt-n5 position-absolute top-0 start-0"></div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card card-borderless rounded-3 overflow-hidden bg-purple h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="kpi-label"><iconify-icon icon="solar:shield-user-bold-duotone"></iconify-icon> Kelompok Jabatan</div>
                <div class="kpi-value"><?= esc((string) max(1, $totalKelJabatan)) ?></div>
                <p class="kpi-sub">Struktur Jabatan Pegawai</p>
            </div>
            <div class="position-absolute top-0 end-0 mt-n5 ps-5 w-25 d-none d-md-block">
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle ms-n5 position-absolute top-0 start-0"></div>
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle mt-n5 position-absolute top-0 start-0"></div>
            </div>
        </div>
    </div>
</div>
<!-- END KPI -->

<!-- BEGIN table card -->
<div class="card card-borderless table-card">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="me-1"></iconify-icon>
            Daftar Pengguna HRIS (tbl_userhris)
        </h4>
        <div class="card-header-btn">
            <button type="button" class="btn btn-warning btn-xs me-1 text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#syncHrisModal">
                <i class="fa fa-cloud-arrow-down me-1"></i>Tarik Pegawai HRIS
            </button>
            <button type="button" class="btn btn-theme btn-xs" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fa fa-plus"></i><span class="btn-label-full ms-1">Tambah User</span>
            </button>
            <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover table-striped align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>User ID / NPP</th>
                        <th>Nama Pegawai</th>
                        <th>NRIK</th>
                        <th>Jabatan</th>
                        <th>Kelompok Jabatan</th>
                        <th>Unit Kerja</th>
                        <th class="text-center">Branch ID</th>
                        <th>Kontak Pegawai</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width:80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($displayItems as $index => $item) :
                        $userid = (string) ($item['userid'] ?? $item['username'] ?? '');
                        $npp = (string) ($item['npp'] ?? $item['username'] ?? '');
                        $nama = (string) ($item['nama'] ?? $item['full_name'] ?? '');
                        $nrik = (string) ($item['nrik'] ?? '-');
                        $nmJabatan = (string) ($item['nm_jabatan'] ?? $item['jabatan'] ?? '-');
                        $idJabatan = (string) ($item['id_jabatan'] ?? '');
                        $namaKelJabatan = (string) ($item['nama_kel_jabatan'] ?? $item['job_group_name'] ?? '-');
                        $idKelJabatan = (string) ($item['id_kel_jabatan'] ?? $item['job_group_code'] ?? '');
                        $nmUnitKerja = (string) ($item['nm_unit_kerja'] ?? $item['unitKerjaName'] ?? '-');
                        $idUnitKerja = (string) ($item['id_unit_kerja'] ?? '');
                        $branchid = (string) ($item['branchid'] ?? $item['branchCode'] ?? $item['branch_name'] ?? '-');
                        $userEmail = (string) ($item['user_email'] ?? $item['email'] ?? '-');
                        $noHp = (string) ($item['no_hp'] ?? '-');
                        $stsauth = isset($item['stsauth']) ? (int) $item['stsauth'] : 0;
                        $stsbest = isset($item['stsbest']) ? (int) $item['stsbest'] : 0;
                        $tone = 'tone-' . ((abs(crc32($npp !== '' ? $npp : (string) $index)) % 6) + 1);
                        ?>
                    <tr>
                        <td class="text-muted"><?= esc((string) ($index + 1)) ?></td>
                        <td>
                            <div class="fw-bold font-monospace text-primary"><?= esc($npp) ?></div>
                            <div class="small text-muted font-monospace"><?= esc($userid) ?></div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="user-avatar <?= esc($tone) ?>"><?= esc(access_initials($nama !== '' ? $nama : $npp)) ?></span>
                                <div class="min-w-0">
                                    <div class="user-meta-name text-truncate fw-semibold"><?= esc($nama !== '' ? $nama : '-') ?></div>
                                    <div class="small text-muted font-monospace"><?= esc($npp) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="font-monospace small text-nowrap"><?= esc($nrik) ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-truncate" style="max-width:220px;" title="<?= esc($nmJabatan) ?>"><?= esc($nmJabatan) ?></div>
                            <?php if ($idJabatan !== '') : ?>
                            <div class="small text-muted font-monospace">ID: <?= esc($idJabatan) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge access-badge access-badge-role"><?= esc($namaKelJabatan) ?></span>
                            <?php if ($idKelJabatan !== '') : ?>
                            <div class="small text-muted font-monospace mt-1">ID: <?= esc($idKelJabatan) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold text-truncate" style="max-width:200px;" title="<?= esc($nmUnitKerja) ?>"><?= esc($nmUnitKerja) ?></div>
                            <?php if ($idUnitKerja !== '') : ?>
                            <div class="small text-muted font-monospace">Unit: <?= esc($idUnitKerja) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary font-monospace"><?= esc($branchid) ?></span>
                        </td>
                        <td>
                            <div class="small"><i class="fa fa-envelope text-muted me-1"></i><?= esc($userEmail) ?></div>
                            <div class="small text-muted"><i class="fa fa-phone text-muted me-1"></i><?= esc($noHp) ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-teal-subtle text-teal border border-teal-subtle">Aktif</span>
                            <div class="small text-muted mt-1">Auth: <?= esc((string) $stsauth) ?></div>
                        </td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-default btn-xs btn-detail-hris"
                                    data-userid="<?= esc($userid) ?>"
                                    data-npp="<?= esc($npp) ?>"
                                    data-nrik="<?= esc($nrik) ?>"
                                    data-nama="<?= esc($nama) ?>"
                                    data-email="<?= esc($userEmail) ?>"
                                    data-nohp="<?= esc($noHp) ?>"
                                    data-unitkerja="<?= esc($nmUnitKerja) ?> (<?= esc($idUnitKerja) ?>)"
                                    data-branchid="<?= esc($branchid) ?>"
                                    data-jabatan="<?= esc($nmJabatan) ?> (<?= esc($idJabatan) ?>)"
                                    data-keljabatan="<?= esc($namaKelJabatan) ?> (<?= esc($idKelJabatan) ?>)"
                                    data-stsauth="<?= esc((string) $stsauth) ?>"
                                    data-stsbest="<?= esc((string) $stsbest) ?>"
                                    data-created="<?= esc((string) ($item['created_at'] ?? '-')) ?>"
                                    data-updated="<?= esc((string) ($item['updated_at'] ?? '-')) ?>"
                                    title="Lihat Detail Kolom tbl_userhris">
                                <i class="fa fa-eye text-primary"></i> Detail
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($displayItems)) : ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">Belum ada data pegawai HRIS. Klik "Tarik Pegawai HRIS" untuk mengambil data.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- END table card -->

<!-- MODAL SYNC HRIS -->
<div class="modal fade" id="syncHrisModal" tabindex="-1" aria-labelledby="syncHrisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gray-900 text-white">
                <h5 class="modal-title text-white" id="syncHrisModalLabel">
                    <i class="fa fa-cloud-arrow-down me-2 text-warning"></i>Tarik Data Pegawai dari Live HRIS Gateway
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="post" action="<?= site_url('access/users/sync-hris') ?>" id="syncHrisForm">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Memanggil endpoint live gateway <code>/hris/inqMasterPegawaiByKondisi</code> (reqid: <code>HR006</code>) untuk mengambil data pegawai dan menyimpannya ke tabel <code>tbl_userhris</code>.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="sync_userid">User ID / NPP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace" id="sync_userid" name="userid" value="1776" required placeholder="Contoh: 1776">
                        <div class="form-text">Contoh testing: <strong>1776</strong>, <strong>4259</strong>, atau <strong>2870</strong>.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="sync_kondisi">Kondisi (Opsional)</label>
                        <input type="text" class="form-control font-monospace" id="sync_kondisi" name="kondisi" value="" placeholder="Default: kosong">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSyncSubmit" class="btn btn-warning fw-bold text-dark">
                        <i class="fa fa-bolt me-1"></i> Tarik &amp; Simpan ke tbl_userhris
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DETAIL USERHRIS -->
<div class="modal fade" id="detailHrisModal" tabindex="-1" aria-labelledby="detailHrisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gray-900 text-white">
                <h5 class="modal-title text-white" id="detailHrisModalLabel">
                    <i class="fa fa-id-card me-2 text-info"></i>Rincian Kolom tbl_userhris
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <tbody>
                        <tr>
                            <th class="w-35 bg-light">userid (Primary Key)</th>
                            <td id="dt_userid" class="font-monospace fw-bold text-primary"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">npp</th>
                            <td id="dt_npp" class="font-monospace fw-bold"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">nrik</th>
                            <td id="dt_nrik" class="font-monospace"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">nama</th>
                            <td id="dt_nama" class="fw-bold"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">user_email</th>
                            <td id="dt_email"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">no_hp</th>
                            <td id="dt_nohp"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">nm_unit_kerja (id_unit_kerja)</th>
                            <td id="dt_unitkerja"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">branchid</th>
                            <td id="dt_branchid" class="font-monospace"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">nm_jabatan (id_jabatan)</th>
                            <td id="dt_jabatan"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">nama_kel_jabatan (id_kel_jabatan)</th>
                            <td id="dt_keljabatan"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">stsauth / stsbest</th>
                            <td>
                                <span class="badge bg-secondary me-1">stsauth: <span id="dt_stsauth"></span></span>
                                <span class="badge bg-secondary">stsbest: <span id="dt_stsbest"></span></span>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">created_at / updated_at</th>
                            <td class="small text-muted font-monospace">
                                Dibuat: <span id="dt_created"></span> | Diperbarui: <span id="dt_updated"></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CREATE USER -->
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
                            <label class="form-label" for="new_full_name">Nama Pegawai <span class="text-danger">*</span></label>
                            <input id="new_full_name" name="full_name" type="text" class="form-control" required placeholder="Nama lengkap pegawai">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="new_password">Password Awal <span class="text-danger">*</span></label>
                            <input id="new_password" name="password" type="password" class="form-control" minlength="12" required placeholder="Minimal 12 karakter">
                            <div class="form-text">Password minimal 12 karakter.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_job_group_id">Kelompok Jabatan <span class="text-danger">*</span></label>
                            <select id="new_job_group_id" name="job_group_id" class="select2" data-placeholder="Pilih kelompok jabatan..." required>
                                <option value=""></option>
                                <?php foreach ($jobGroups as $group) : ?>
                                    <?php if (! empty($group['is_active'])) : ?>
                                        <option value="<?= esc($group['id']) ?>">
                                            <?= esc($group['name']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_branch_id">Cabang / Unit Kantor <span class="text-danger">*</span></label>
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

<script>
$(document).ready(function () {
    if ($('#usersTable tbody tr td[colspan]').length === 0) {
        var usersDt = $('#usersTable').DataTable({
            order: [[1, 'asc']],
            responsive: false,
            scrollX: true,
            columnDefs: [
                { orderable: false, targets: [0, 9, 10] },
                { searchable: false, targets: [0, 9, 10] }
            ]
        });
        if (usersDt) {
            usersDt.on('order.dt search.dt draw.dt', function () {
                usersDt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                    cell.innerHTML = '<span class="text-muted">' + (i + 1) + '</span>';
                });
            }).draw(false);
        }
    }

    // Detail Button Click handler
    $(document).on('click', '.btn-detail-hris', function () {
        var $btn = $(this);
        $('#dt_userid').text($btn.data('userid') || '-');
        $('#dt_npp').text($btn.data('npp') || '-');
        $('#dt_nrik').text($btn.data('nrik') || '-');
        $('#dt_nama').text($btn.data('nama') || '-');
        $('#dt_email').text($btn.data('email') || '-');
        $('#dt_nohp').text($btn.data('nohp') || '-');
        $('#dt_unitkerja').text($btn.data('unitkerja') || '-');
        $('#dt_branchid').text($btn.data('branchid') || '-');
        $('#dt_jabatan').text($btn.data('jabatan') || '-');
        $('#dt_keljabatan').text($btn.data('keljabatan') || '-');
        $('#dt_stsauth').text($btn.data('stsauth') !== undefined ? $btn.data('stsauth') : '0');
        $('#dt_stsbest').text($btn.data('stsbest') !== undefined ? $btn.data('stsbest') : '0');
        $('#dt_created').text($btn.data('created') || '-');
        $('#dt_updated').text($btn.data('updated') || '-');
        $('#detailHrisModal').modal('show');
    });

    $('#syncHrisForm').on('submit', function () {
        App.btnLoading($('#btnSyncSubmit'), 'Menarik data...');
    });
    $('#createUserForm').on('submit', function () {
        App.btnLoading($('#btnCreateUser'), 'Menyimpan...');
    });
});
</script>

<?= view('partials/shell_end') ?>
