<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Konfigurasi Scoring']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-sliders me-2 text-primary"></i>Konfigurasi Scoring</h2>
        <p class="text-muted mb-0">Kelola konfigurasi dan versi scoring per produk kredit</p>
    </div>
</div>

<!-- Settings Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-gear me-2 text-primary"></i>Pengaturan Umum</h5>
    </div>
    <div class="card-body">
        <form id="settingForm" method="post" action="<?= site_url('scoring/duplicate-setting') ?>">
            <?= csrf_field() ?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="enabled" value="1" id="chkDuplicate"
                       <?= ! empty($duplicateEnabled) ? 'checked' : '' ?>>
                <label class="form-check-label" for="chkDuplicate">
                    <strong>Izinkan duplikasi transaksi</strong>
                    <span class="text-muted d-block" style="font-size:12px;">Pengguna dapat menduplikasi transaksi yang sudah disetujui atau ditolak.</span>
                </label>
            </div>
            <button type="button" id="btnSaveSetting" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Pengaturan
            </button>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-code-branch me-2 text-primary"></i>Daftar Produk & Versi</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="scoringProductsTable" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Produk</th>
                        <th class="text-center" style="width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) : ?>
                        <?php if (! empty($product['is_active'])) : ?>
                        <tr>
                            <td><span class="product-code"><?= esc($product['code']) ?></span></td>
                            <td class="fw-semibold"><?= esc($product['name']) ?></td>
                            <td class="text-center">
                                <a href="<?= site_url('scoring/products/' . $product['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-code-branch me-1"></i> Lihat Versi
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (empty(array_filter($products, fn($p) => ! empty($p['is_active'])))) : ?>
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">
                                <i class="fa-solid fa-sliders"></i>
                                <h6>Belum Ada Produk Aktif</h6>
                                <p>Aktifkan produk terlebih dahulu dari menu Master Produk.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    App.initDT('#scoringProductsTable', {
        order: [[1, 'asc']],
        columnDefs: [{ orderable: false, targets: [2] }]
    });

    $('#btnSaveSetting').on('click', function () {
        App.confirmSave({
            title: 'Simpan Pengaturan',
            text: 'Pengaturan sistem akan diperbarui. Lanjutkan?'
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($('#btnSaveSetting'), 'Menyimpan...');
                $('#settingForm').submit();
            }
        });
    });
});
</script>

<?= view('partials/shell_end') ?>
