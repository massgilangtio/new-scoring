<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Input Scoring']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= site_url('transactions') ?>">Pengajuan Scoring</a></li>
        <li class="breadcrumb-item active"><?= esc($item['transaction_no']) ?></li>
    </ol>
</nav>

<!-- Stepper -->
<div class="d-flex align-items-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-2 text-muted">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--success-bg);color:var(--success);font-size:14px;"><i class="fa-solid fa-check" style="font-size:12px;"></i></span>
        <span class="fw-semibold" style="font-size:13px;color:var(--success);">Pilih Produk & Debitur</span>
    </div>
    <div style="flex:1;height:2px;background:var(--secondary);"></div>
    <div class="d-flex align-items-center gap-2">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--secondary);color:#fff;font-size:14px;">2</span>
        <span class="fw-bold" style="color:var(--primary);">Input Scoring</span>
    </div>
    <div style="flex:1;height:2px;background:var(--border);"></div>
    <div class="d-flex align-items-center gap-2 text-muted">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--border);color:var(--text-muted);font-size:14px;">3</span>
        <span class="fw-semibold" style="font-size:13px;">Konfirmasi</span>
    </div>
</div>

<!-- Info Card -->
<div class="d-flex align-items-center gap-3 p-3 mb-4 rounded" style="background:var(--info-bg);border:1px solid #7dd3fc;">
    <i class="fa-solid fa-file-invoice-dollar fa-lg" style="color:var(--info);"></i>
    <div>
        <div class="fw-bold" style="color:var(--primary);"><?= esc($item['transaction_no']) ?></div>
        <div class="text-muted" style="font-size:13px;">
            <?= esc($item['debtor']['full_name'] ?? '') ?> &mdash;
            <?= esc($item['product']['name'] ?? '') ?> &mdash;
            <span class="badge badge-<?= esc($item['status']) ?>"><?= esc($item['status']) ?></span>
        </div>
    </div>
</div>

<?php if (! empty($item['editable'])) : ?>
<?php
$selected = [];
foreach ($item['answers'] as $answer) {
    $selected[$answer['parameter_id']] = $answer['option_id'];
}
?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-clipboard-list me-2 text-primary"></i>Langkah 2 — Input Parameter Scoring</h5>
        <span class="badge bg-primary"><?= count($item['version']['parameters']) ?> Parameter</span>
    </div>
    <div class="card-body">
        <form id="inputForm" method="post" action="<?= site_url('transactions/' . $item['id'] . '/answers') ?>">
            <?= csrf_field() ?>

            <!-- Scoring Parameters -->
            <?php if (! empty($item['version']['parameters'])) : ?>
            <h6 class="fw-bold mb-3" style="color:var(--primary);">
                <i class="fa-solid fa-sliders me-2"></i>Parameter Penilaian
            </h6>
            <div class="row g-3 mb-4">
                <?php foreach ($item['version']['parameters'] as $parameter) : ?>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="option-<?= esc($parameter['id']) ?>">
                        <?= esc($parameter['name']) ?> <span class="required">*</span>
                        <small class="text-muted fw-normal">(weight: <?= esc($parameter['weight']) ?>)</small>
                    </label>
                    <select id="option-<?= esc($parameter['id']) ?>"
                            name="option[<?= esc($parameter['id']) ?>]"
                            class="select2" required
                            data-placeholder="Pilih nilai...">
                        <?php foreach ($parameter['options'] as $option) : ?>
                            <option value="<?= esc($option['id']) ?>"
                                <?= (int) ($selected[$parameter['id']] ?? 0) === (int) $option['id'] ? 'selected' : '' ?>>
                                <?= esc($option['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Dynamic Fields -->
            <?php
            $dynamicFields = array_filter($item['version']['dynamic_fields'] ?? [], function ($f) {
                return ! empty($f['is_active']) && ! in_array($f['field_type'], ['dropdown', 'radio', 'checkbox'], true);
            });
            ?>
            <?php if (! empty($dynamicFields)) : ?>
            <h6 class="fw-bold mb-3 mt-2" style="color:var(--primary);">
                <i class="fa-solid fa-pen-to-square me-2"></i>Data Tambahan
            </h6>
            <div class="row g-3 mb-4">
                <?php foreach ($dynamicFields as $field) : ?>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="field-<?= esc($field['id']) ?>">
                        <?= esc($field['label']) ?>
                        <?php if (! empty($field['is_required'])) : ?><span class="required">*</span><?php endif; ?>
                    </label>
                    <?php if ($field['field_type'] === 'textarea') : ?>
                        <textarea id="field-<?= esc($field['id']) ?>"
                                  name="field[<?= esc($field['id']) ?>]"
                                  class="form-control"
                                  rows="3"
                                  <?= ! empty($field['is_required']) ? 'required' : '' ?>></textarea>
                    <?php else : ?>
                        <input id="field-<?= esc($field['id']) ?>"
                               name="field[<?= esc($field['id']) ?>]"
                               type="<?= esc($field['field_type'] === 'number' ? 'number' : ($field['field_type'] === 'date' ? 'date' : 'text')) ?>"
                               class="form-control"
                               <?= ! empty($field['is_required']) ? 'required' : '' ?>>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                <a href="<?= site_url('transactions/' . $item['id'] . '/review') ?>" class="btn btn-light">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
                <button type="submit" id="btnInput" class="btn btn-primary">
                    Lanjut ke Konfirmasi <i class="fa-solid fa-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    // Initialize Select2 for all parameter selects
    $('#inputForm select').each(function () {
        var $el = $(this);
        var label = $el.closest('.col-12').find('.form-label').text().trim().replace(' *', '').replace(/\(.*?\)/, '').trim();
        App.initSelect2($el, { placeholder: 'Pilih ' + label + '...' });
    });

    $('#inputForm').on('submit', function () {
        App.btnLoading($('#btnInput'), 'Menyimpan...');
    });
});
</script>

<?php else : ?>
<!-- Not Editable -->
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fa-solid fa-lock fa-3x text-muted mb-3"></i>
        <h5 class="fw-bold" style="color:var(--primary);">Pengajuan Sudah Dikunci</h5>
        <p class="text-muted">Pengajuan ini tidak dapat diubah karena sudah dalam proses.</p>
        <a href="<?= site_url('transactions/' . $item['id'] . '/review') ?>" class="btn btn-primary mt-2">
            <i class="fa-solid fa-eye me-1"></i> Lihat Hasil Scoring
        </a>
    </div>
</div>
<?php endif; ?>

<?= view('partials/shell_end') ?>
