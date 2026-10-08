<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Versi Scoring']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= site_url('scoring/products') ?>">Parameter Scoring</a></li>
        <li class="breadcrumb-item active"><?= esc($product['name']) ?></li>
    </ol>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-code-branch me-2 text-primary"></i>Versi Scoring — <?= esc($product['name']) ?></h2>
        <p class="text-muted mb-0">Kelola versi konfigurasi scoring untuk produk ini</p>
    </div>
    <div class="page-actions">
        <form id="createVersionForm" method="post" action="<?= site_url('scoring/products/' . $product['id'] . '/versions') ?>">
            <?= csrf_field() ?>
            <button type="button" id="btnNewVersion" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> Buat Versi Draft
            </button>
        </form>
    </div>
</div>

<!-- Versions Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="versionsTable" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Versi</th>
                        <th class="text-center">Status</th>
                        <th>Diaktifkan</th>
                        <th class="text-center" style="width:180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) : ?>
                    <tr>
                        <td class="fw-bold"><?= esc($item['version_no']) ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?= esc($item['status']) ?>"><?= esc($item['status']) ?></span>
                        </td>
                        <td class="text-muted" style="font-size:13px;"><?= esc($item['activated_at'] ?? '-') ?></td>
                        <td class="text-center">
                            <?php
                            ob_start();
                            ?>
                                    <li>
                                        <a href="<?= site_url('scoring/versions/' . $item['id']) ?>" class="dropdown-action-item">
                                            <span class="action-icon-circle action-icon-green"><i class="fa-solid fa-eye"></i></span>
                                            <div class="action-text-group">
                                                <span class="action-title">Detail</span>
                                                <span class="action-desc">Lihat konfigurasi versi</span>
                                            </div>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <form class="form-copy m-0 p-0" method="post" action="<?= site_url('scoring/versions/' . $item['id'] . '/copy') ?>">
                                            <?= csrf_field() ?>
                                            <button type="button" class="dropdown-action-item btn-copy">
                                                <span class="action-icon-circle action-icon-orange"><i class="fa-solid fa-copy"></i></span>
                                                <div class="action-text-group">
                                                    <span class="action-title">Salin Draft</span>
                                                    <span class="action-desc">Salin ke versi draft baru</span>
                                                </div>
                                            </button>
                                        </form>
                                    </li>
                            <?php
                            echo view('partials/action_dropdown_btn', ['menuHtml' => ob_get_clean()]);
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="fa-regular fa-file-code"></i>
                                <h6>Belum Ada Versi</h6>
                                <p>Klik "Buat Versi Draft" untuk membuat versi pertama.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>Hanya satu versi yang aktif. Versi yang sudah aktif atau nonaktif tetap tersimpan dan tidak dapat diubah.</small>
    </div>
</div>

<script>
$(document).ready(function () {
    App.initDT('#versionsTable', {
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: [3] }]
    });

    // Create Version confirmation
    $('#btnNewVersion').on('click', function () {
        App.confirm({
            title: 'Buat Versi Draft',
            text: 'Versi draft baru akan dibuat berdasarkan konfigurasi terkini. Lanjutkan?',
            confirmButtonText: '<i class="fa-solid fa-plus me-1"></i> Ya, Buat'
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($('#btnNewVersion'), 'Membuat...');
                $('#createVersionForm').submit();
            }
        });
    });

    // Copy Version confirmation
    $(document).on('click', '.btn-copy', function () {
        var $btn = $(this);
        App.confirm({
            title: 'Salin ke Draft Baru',
            text: 'Versi ini akan disalin sebagai draft baru. Lanjutkan?',
            confirmButtonText: '<i class="fa-solid fa-copy me-1"></i> Ya, Salin'
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($btn, '');
                $btn.closest('form').submit();
            }
        });
    });
});
</script>

<?= view('partials/shell_end') ?>
