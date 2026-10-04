<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Hak Akses']) ?>

<!-- Page Header -->
<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= site_url('/') ?>"><i class="fa-solid fa-house me-1"></i>Dasbor</a></li>
                <li class="breadcrumb-item">Manajemen Akses</li>
                <li class="breadcrumb-item active" aria-current="page">Hak Akses</li>
            </ol>
        </nav>
        <h2 class="page-title mb-1">
            <i class="fa-solid fa-key me-2 text-primary"></i>Katalog Hak Akses
            <span class="badge bg-primary-subtle text-primary fs-6 align-middle ms-2"><?= count($permissions) ?> Permission</span>
        </h2>
        <p class="text-muted mb-0">Daftar katalog hak akses sistem. Penugasan hak akses ke role dikonfigurasi melalui menu Role.</p>
    </div>
</div>

<!-- Subnav Navigasi Tab -->
<ul class="subnav nav nav-pills gap-2 mb-4">
    <li class="nav-item">
        <a class="nav-link" href="<?= site_url('access/users') ?>">
            <i class="fa-solid fa-users me-1"></i>Pengguna
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?= site_url('access/roles') ?>">
            <i class="fa-solid fa-user-shield me-1"></i>Role &amp; Otoritas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="<?= site_url('access/permissions') ?>">
            <i class="fa-solid fa-key me-1"></i>Katalog Hak Akses
        </a>
    </li>
</ul>

<!-- Alert / Info Banner -->
<div class="alert alert-info d-flex align-items-center gap-2 mb-4">
    <i class="fa-solid fa-circle-info fs-5 flex-shrink-0"></i>
    <div>
        <strong>Catatan Kebijakan:</strong> Katalog hak akses ini bersifat terpusat. Permission penugasan approver dievaluasi sesuai alur dokumen dan matriks wewenang kredit.
    </div>
</div>

<!-- Main Card Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 fw-bold">
            <i class="fa-solid fa-list-check me-2 text-primary"></i>Daftar Hak Akses
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable-permissions" id="permissionsTable">
                <thead class="table-light">
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
                                <td class="text-center text-muted fw-semibold"><?= $no++ ?></td>
                                <td>
                                    <code class="px-2 py-1 rounded bg-light text-primary border font-monospace fw-semibold">
                                        <?= esc($permission['code']) ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><?= esc($permission['name']) ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if (! empty($permission['is_active'])) : ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="fa-solid fa-circle-check me-1"></i>Aktif
                                        </span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                            <i class="fa-solid fa-circle-xmark me-1"></i>Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-shield-halved fs-1 text-muted mb-3 opacity-50"></i>
                                    <h6 class="fw-bold">Belum Ada Hak Akses</h6>
                                    <p class="text-muted small">Tidak ada data permission yang terdaftar dalam sistem.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= view('partials/shell_end') ?>

<script>
$(document).ready(function () {
    if ($.fn.DataTable && $('#permissionsTable tbody tr td[colspan]').length === 0) {
        $('#permissionsTable').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Cari permission...",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ permission",
                infoEmpty: "Menampilkan 0 data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                zeroRecords: "Tidak ada permission yang cocok",
                paginate: {
                    first: '<i class="fa-solid fa-angles-left"></i>',
                    last: '<i class="fa-solid fa-angles-right"></i>',
                    next: '<i class="fa-solid fa-angle-right"></i>',
                    previous: '<i class="fa-solid fa-angle-left"></i>'
                }
            },
            columnDefs: [
                { orderable: false, targets: 0 }
            ],
            order: [[1, 'asc']]
        });
    }
});
</script>
