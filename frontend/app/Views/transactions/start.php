<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Pengajuan Baru']) ?>

<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= site_url('transactions') ?>">Pengajuan Scoring</a></li>
        <li class="breadcrumb-item active">Pengajuan Baru</li>
    </ol>
</nav>

<!-- Stepper -->
<div class="d-flex align-items-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--secondary);color:#fff;font-size:14px;">1</span>
        <span class="fw-bold" style="color:var(--primary);">Pilih Produk & Debitur</span>
    </div>
    <div style="flex:1;height:2px;background:var(--border);"></div>
    <div class="d-flex align-items-center gap-2 text-muted">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--border);color:var(--text-muted);font-size:14px;">2</span>
        <span class="fw-semibold" style="font-size:13px;">Input Scoring</span>
    </div>
    <div style="flex:1;height:2px;background:var(--border);"></div>
    <div class="d-flex align-items-center gap-2 text-muted">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--border);color:var(--text-muted);font-size:14px;">3</span>
        <span class="fw-semibold" style="font-size:13px;">Konfirmasi</span>
    </div>
</div>

<!-- Form Card -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-circle-plus me-2 text-primary"></i>Langkah 1 — Pilih Produk & Debitur</h5>
    </div>
    <div class="card-body">
        <form id="startForm" method="post" action="<?= site_url('transactions') ?>">
            <?= csrf_field() ?>
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="product_id">Produk Kredit <span class="required">*</span></label>
                    <select id="product_id" name="product_id" class="select2" data-placeholder="Pilih produk kredit..." required>
                        <?php foreach ($products as $product) : ?>
                            <option value="<?= esc($product['id']) ?>">
                                <?= esc($product['name']) ?> (Versi <?= esc($product['version_no']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><i class="fa-solid fa-circle-info me-1"></i>Pilih produk kredit yang sesuai dengan jenis pengajuan.</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="debtor_id">Debitur <span class="required">*</span></label>
                    <select id="debtor_id" name="debtor_id" class="select2" data-placeholder="Pilih atau cari debitur..." required>
                        <?php foreach ($debtors as $debtor) : ?>
                            <option value="<?= esc($debtor['id']) ?>">
                                <?= esc($debtor['full_name']) ?> — <?= esc($debtor['nik']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><i class="fa-solid fa-circle-info me-1"></i>Cari berdasarkan nama atau NIK debitur.</div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-4 pt-3 border-top">
                <a href="<?= site_url('transactions') ?>" class="btn btn-light">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
                <button type="submit" id="btnStart" class="btn btn-primary">
                    Lanjut ke Input Scoring <i class="fa-solid fa-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    App.initSelect2('#product_id', { placeholder: 'Pilih produk kredit...' });
    App.initSelect2('#debtor_id', { placeholder: 'Pilih atau cari debitur...' });

    $('#startForm').on('submit', function () {
        App.btnLoading($('#btnStart'), 'Memproses...');
    });
});
</script>

<?= view('partials/shell_end') ?>
