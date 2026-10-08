<?php
helper('access');
$profile = $profile ?? [];
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
                        <th>User ID</th>
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
                            <div class="d-inline-flex align-items-center gap-1">
                                <span class="fw-bold font-monospace text-primary"><?= esc($userid !== '' ? $userid : $npp) ?></span>
                                <?php $valUserid = $userid !== '' ? $userid : $npp; ?>
                                <?php if ($valUserid !== '') : ?>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($valUserid) ?>" title="Salin User ID">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php if ($npp !== '' && $npp !== $userid) : ?>
                                <div class="small text-muted font-monospace d-flex align-items-center gap-1 mt-1">
                                    <span>NPP: <?= esc($npp) ?></span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($npp) ?>" title="Salin NPP">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="user-avatar <?= esc($tone) ?>"><?= esc(access_initials($nama !== '' ? $nama : ($userid !== '' ? $userid : 'U'))) ?></span>
                                <div class="min-w-0">
                                    <div class="user-meta-name text-truncate fw-semibold"><?= esc($nama !== '' ? $nama : '-') ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($nrik !== '' && $nrik !== '-') : ?>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span class="font-monospace small text-nowrap"><?= esc($nrik) ?></span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($nrik) ?>" title="Salin NRIK">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            <?php else : ?>
                                <span class="font-monospace small text-muted">-</span>
                            <?php endif; ?>
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
                            <?php if ($branchid !== '' && $branchid !== '-') : ?>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span class="badge bg-secondary font-monospace"><?= esc($branchid) ?></span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($branchid) ?>" title="Salin Branch ID">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            <?php else : ?>
                                <span class="badge bg-secondary font-monospace">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="small"><i class="fa fa-envelope text-muted me-1"></i><?= esc($userEmail) ?></div>
                            <div class="small text-muted d-flex align-items-center gap-1 mt-1">
                                <i class="fa fa-phone text-muted me-1"></i>
                                <span><?= esc($noHp) ?></span>
                                <?php if ($noHp !== '' && $noHp !== '-') : ?>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($noHp) ?>" title="Salin No HP">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-teal-subtle text-teal border border-teal-subtle">Aktif</span>
                            <div class="mt-1">
                                <?php if ($stsauth === 1) : ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="stsauth = 1 (Menggunakan MFA)">
                                    <i class="fa fa-shield-halved me-1"></i>Pakai MFA (1)
                                </span>
                                <?php else : ?>
                                <span class="badge bg-secondary-subtle text-muted border border-secondary-subtle" title="stsauth = 0 (Tidak menggunakan MFA)">
                                    <i class="fa fa-shield me-1"></i>Tanpa MFA (0)
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center text-nowrap">
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
                                    data-secretkey="<?= esc((string) ($item['secret_key'] ?? '')) ?>"
                                    data-created="<?= esc((string) ($item['created_at'] ?? '-')) ?>"
                                    data-updated="<?= esc((string) ($item['updated_at'] ?? '-')) ?>"
                                    title="Lihat Detail Kolom tbl_userhris">
                                <i class="fa fa-eye text-primary"></i> Detail
                            </button>
                            <?php if ($stsauth === 1) : ?>
                            <button type="button"
                                    class="btn btn-outline-danger btn-xs ms-1 btn-toggle-mfa"
                                    data-userid="<?= esc($userid) ?>"
                                    data-stsauth="0"
                                    data-nama="<?= esc($nama) ?>"
                                    title="Nonaktifkan MFA (Ubah stsauth jadi 0)">
                                <i class="fa fa-ban text-danger"></i> Nonaktifkan
                            </button>
                            <button type="button"
                                    class="btn btn-outline-warning btn-xs ms-1 btn-reset-mfa"
                                    data-userid="<?= esc($userid) ?>"
                                    data-nama="<?= esc($nama) ?>"
                                    title="Reset Secret Key agar pegawai bisa scan barcode QR baru">
                                <i class="fa fa-rotate-left text-warning"></i> Reset Key
                            </button>
                            <?php else : ?>
                            <button type="button"
                                    class="btn btn-outline-success btn-xs ms-1 btn-toggle-mfa"
                                    data-userid="<?= esc($userid) ?>"
                                    data-stsauth="1"
                                    data-nama="<?= esc($nama) ?>"
                                    title="Aktifkan MFA (Ubah stsauth jadi 1)">
                                <i class="fa fa-shield-halved text-success"></i> Aktifkan MFA
                            </button>
                            <?php endif; ?>
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
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fa fa-info-circle me-1"></i> Memanggil endpoint <code>/hris/inqMasterPegawaiByKondisi</code> (reqid: <code>HR006</code>).
                    </div>

                    <div class="card border mb-3">
                        <div class="card-body p-3 bg-light rounded">
                            <div class="fw-bold mb-1"><i class="fa fa-users text-warning me-1"></i> Tarik Semua Pegawai (Rekomendasi)</div>
                            <p class="text-muted small mb-2">
                                Mengirim payload default: <code>{"reqid": "HR006", "kondisi": ""}</code> untuk menarik seluruh data pegawai (~2.600+ pegawai).
                            </p>
                            <ul class="text-muted small ps-3 mb-3">
                                <li><strong>Pegawai baru:</strong> Di-insert ke <code>tbl_userhris</code></li>
                                <li><strong>Pegawai existing (NPP sama):</strong> Di-update otomatis tanpa menimpa secret key MFA</li>
                            </ul>
                            <button type="button" class="btn btn-warning text-dark fw-bold w-100" id="btnSyncAllNow">
                                <i class="fa fa-bolt me-1"></i> Tarik Semua Pegawai HRIS Sekarang
                            </button>
                        </div>
                    </div>

                    <div class="border rounded p-2">
                        <a class="text-decoration-none small fw-semibold text-secondary d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#syncFilterCollapse" role="button" aria-expanded="false">
                            <span><i class="fa fa-sliders me-1"></i> Opsi Filter Tertentu (Opsional)</span>
                            <i class="fa fa-chevron-down small"></i>
                        </a>
                        <div class="collapse mt-2" id="syncFilterCollapse">
                            <div class="mb-2">
                                <label class="form-label small mb-1" for="sync_userid">User ID / NPP Tertentu</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="sync_userid" name="userid" value="" placeholder="Kosongkan jika menarik semua (contoh: 1776)">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1" for="sync_kondisi">Kondisi (Opsional)</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="sync_kondisi" name="kondisi" value="" placeholder="Default: kosong">
                            </div>
                            <button type="submit" id="btnSyncSubmit" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fa fa-filter me-1"></i> Tarik dengan Filter di Atas
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
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
                            <td>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span id="dt_userid" class="font-monospace fw-bold text-primary"></span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" id="btnCopyDtUserid" data-clipboard="" title="Salin User ID">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">npp</th>
                            <td>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span id="dt_npp" class="font-monospace fw-bold"></span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" id="btnCopyDtNpp" data-clipboard="" title="Salin NPP">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">nrik</th>
                            <td>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span id="dt_nrik" class="font-monospace"></span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" id="btnCopyDtNrik" data-clipboard="" title="Salin NRIK">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </td>
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
                            <td>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span id="dt_nohp"></span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" id="btnCopyDtNohp" data-clipboard="" title="Salin No HP">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">nm_unit_kerja (id_unit_kerja)</th>
                            <td id="dt_unitkerja"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">branchid</th>
                            <td>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span id="dt_branchid" class="font-monospace"></span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" id="btnCopyDtBranchid" data-clipboard="" title="Salin Branch ID">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </td>
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
                            <th class="bg-light">stsauth (Status MFA) / stsbest</th>
                            <td>
                                <span id="dt_stsauth_container"></span>
                                <span class="badge bg-secondary ms-1">stsbest: <span id="dt_stsbest"></span></span>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">secret_key</th>
                            <td>
                                <code id="dt_secretkey" class="user-select-all font-monospace text-wrap small bg-light p-1 rounded border"></code>
                                <div id="dt_secretkey_empty" class="text-muted small fst-italic">Belum aktif / Kosong (Siap scan barcode baru)</div>
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
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-warning text-dark fw-bold btn-sm" id="btnDetailResetMfa">
                    <i class="fa fa-rotate-left text-warning me-1"></i> Reset Secret Key &amp; Scan Ulang
                </button>
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
        var uId = String($btn.data('userid') || '');
        var npp = String($btn.data('npp') || '');
        var nrik = String($btn.data('nrik') || '');
        var nohp = String($btn.data('nohp') || '');
        var brid = String($btn.data('branchid') || '');

        $('#dt_userid').text(uId || '-');
        $('#dt_npp').text(npp || '-');
        $('#dt_nrik').text(nrik || '-');
        $('#dt_nama').text($btn.data('nama') || '-');
        $('#dt_email').text($btn.data('email') || '-');
        $('#dt_nohp').text(nohp || '-');
        $('#dt_unitkerja').text($btn.data('unitkerja') || '-');
        $('#dt_branchid').text(brid || '-');

        $('#btnCopyDtUserid').attr('data-clipboard', uId).prop('hidden', !uId || uId === '-');
        $('#btnCopyDtNpp').attr('data-clipboard', npp).prop('hidden', !npp || npp === '-');
        $('#btnCopyDtNrik').attr('data-clipboard', nrik).prop('hidden', !nrik || nrik === '-');
        $('#btnCopyDtNohp').attr('data-clipboard', nohp).prop('hidden', !nohp || nohp === '-');
        $('#btnCopyDtBranchid').attr('data-clipboard', brid).prop('hidden', !brid || brid === '-');
        $('#dt_jabatan').text($btn.data('jabatan') || '-');
        $('#dt_keljabatan').text($btn.data('keljabatan') || '-');
        var stsauth = String($btn.data('stsauth') !== undefined ? $btn.data('stsauth') : '0');
        if (stsauth === '1') {
            $('#dt_stsauth_container').html('<span class="badge bg-primary text-white"><i class="fa fa-shield-halved me-1"></i>1 - Pakai MFA</span>');
        } else {
            $('#dt_stsauth_container').html('<span class="badge bg-secondary text-white"><i class="fa fa-shield me-1"></i>0 - Tidak Menggunakan MFA</span>');
        }
        $('#dt_stsbest').text($btn.data('stsbest') !== undefined ? $btn.data('stsbest') : '0');
        var secretKey = $btn.data('secretkey');
        if (secretKey) {
            $('#dt_secretkey').text(secretKey).show();
            $('#dt_secretkey_empty').hide();
        } else {
            $('#dt_secretkey').text('').hide();
            $('#dt_secretkey_empty').show();
        }
        $('#dt_created').text($btn.data('created') || '-');
        $('#dt_updated').text($btn.data('updated') || '-');
        $('#btnDetailResetMfa').data('userid', $btn.data('userid')).data('nama', $btn.data('nama'));
        $('#detailHrisModal').modal('show');
    });

    function triggerToggleMfa(userid, nama, targetStsauth) {
        var isEnable = parseInt(targetStsauth, 10) === 1;
        Swal.fire({
            title: isEnable ? 'Aktifkan MFA untuk Pegawai?' : 'Nonaktifkan MFA untuk Pegawai?',
            text: isEnable 
                ? 'Pegawai ' + (nama || userid) + ' akan diwajibkan menggunakan MFA (Google Authenticator) saat login (stsauth = 1).' 
                : 'Pegawai ' + (nama || userid) + ' dapat langsung login tanpa MFA (stsauth = 0).',
            icon: isEnable ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonColor: isEnable ? '#20c997' : '#ff5b57',
            cancelButtonColor: '#6c757d',
            confirmButtonText: isEnable ? 'Ya, Aktifkan MFA' : 'Ya, Nonaktifkan MFA',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) {
                var form = $('<form>', {
                    method: 'POST',
                    action: '<?= site_url('access/users/toggle-mfa') ?>'
                });
                form.append($('<input>', {
                    type: 'hidden',
                    name: '<?= csrf_token() ?>',
                    value: '<?= csrf_hash() ?>'
                }));
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'userid',
                    value: userid
                }));
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'stsauth',
                    value: targetStsauth
                }));
                $('body').append(form);
                form.submit();
            }
        });
    }

    $(document).on('click', '.btn-toggle-mfa', function () {
        var userid = $(this).data('userid');
        var nama = $(this).data('nama');
        var stsauth = $(this).data('stsauth');
        triggerToggleMfa(userid, nama, stsauth);
    });

    function triggerResetMfa(userid, nama) {
        Swal.fire({
            title: 'Reset Secret Key?',
            text: 'Secret Key untuk ' + (nama || userid) + ' akan dihapus. Pengguna dapat memindai barcode QR baru saat login berikutnya.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff5b57',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Reset Key',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) {
                var form = $('<form>', {
                    method: 'POST',
                    action: '<?= site_url('access/users/reset-mfa') ?>'
                });
                form.append($('<input>', {
                    type: 'hidden',
                    name: '<?= csrf_token() ?>',
                    value: '<?= csrf_hash() ?>'
                }));
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'userid',
                    value: userid
                }));
                $('body').append(form);
                form.submit();
            }
        });
    }

    $(document).on('click', '.btn-reset-mfa', function () {
        var userid = $(this).data('userid');
        var nama = $(this).data('nama');
        triggerResetMfa(userid, nama);
    });

    $('#btnDetailResetMfa').on('click', function () {
        var userid = $(this).data('userid');
        var nama = $(this).data('nama');
        $('#detailHrisModal').modal('hide');
        triggerResetMfa(userid, nama);
    });

    $('#btnSyncAllNow').on('click', function () {
        $('#sync_userid').val('');
        $('#sync_kondisi').val('');
        Swal.fire({
            title: 'Tarik Semua Pegawai HRIS?',
            text: 'Sistem akan memanggil live Gateway /hris/inqMasterPegawaiByKondisi dan meng-upsert ~2.600+ pegawai ke tbl_userhris. Proses memakan waktu beberapa detik.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<span class="text-dark fw-bold">Ya, Tarik Semua</span>',
            cancelButtonText: 'Batal'
        }).then(function (res) {
            if (res.isConfirmed) {
                $('#syncHrisModal').modal('hide');
                Swal.fire({
                    title: 'Sedang Menarik Data Pegawai...',
                    text: 'Mengunduh dan menyinkronkan data pegawai dari HRIS Gateway ke database. Mohon tunggu...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $('#syncHrisForm').submit();
            }
        });
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
