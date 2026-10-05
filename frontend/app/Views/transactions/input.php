<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Input Scoring']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?= view('partials/form_wizard_nav', [
    'current' => 2,
    'steps'   => [
        ['label' => 'Produk & Debitur', 'href' => site_url('transactions')],
        ['label' => 'Input Scoring'],
        ['label' => 'Ringkasan', 'href' => site_url('transactions/' . $item['id'] . '/review')],
    ],
]) ?>

<div class="card card-borderless mb-3">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:document-text-bold-duotone" class="me-1"></iconify-icon>
            <?= esc($item['transaction_no']) ?>
        </h4>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($item['transaction_no']) ?>" title="Salin Nomor">
                <i class="fa fa-copy"></i>
            </button>
            <span class="badge badge-<?= esc($item['status']) ?>"><?= esc($item['status']) ?></span>
        </div>
    </div>
    <div class="card-body py-3">
        <div class="text-muted small">
            <span class="fw-semibold text-dark"><?= esc($item['debtor']['full_name'] ?? '-') ?></span>
            &mdash; <?= esc($item['product']['name'] ?? '-') ?>
        </div>
        <?php if (! empty($item['duplicated_from_id'])) : ?>
        <div class="alert alert-info py-2 px-3 small mb-0 mt-2">
            <i class="fa fa-info-circle me-1"></i>
            Scoring ulang dari transaksi #<?= esc((string) $item['duplicated_from_id']) ?>.
            Debitur &amp; produk sama; <strong>parameter dikosongkan</strong> — silakan isi ulang.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (! empty($item['editable'])) : ?>
<?php
$selected = [];
foreach ($item['answers'] as $answer) {
    $selected[$answer['parameter_id']] = $answer['option_id'];
}
?>
<div class="card card-borderless">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:clipboard-list-bold-duotone" class="me-1"></iconify-icon>
            Langkah 2 — Input Parameter Scoring
        </h4>
        <span class="badge bg-theme"><?= count($item['version']['parameters']) ?> Parameter</span>
    </div>
    <div class="card-body">
        <form id="inputForm" method="post" action="<?= site_url('transactions/' . $item['id'] . '/answers') ?>">
            <?= csrf_field() ?>

            <?php if (! empty($item['version']['parameters'])) : ?>
            <h6 class="fw-bold mb-3">
                <i class="fa fa-sliders me-2 text-primary"></i>Parameter Penilaian
            </h6>
            <div class="row g-3 mb-4">
                <?php foreach ($item['version']['parameters'] as $parameter) : ?>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="option-<?= esc($parameter['id']) ?>">
                        <?= esc($parameter['name']) ?> <span class="text-danger">*</span>
                        <small class="text-muted fw-normal">(weight: <?= esc($parameter['weight']) ?>)</small>
                    </label>
                    <select id="option-<?= esc($parameter['id']) ?>"
                            name="option[<?= esc($parameter['id']) ?>]"
                            class="select2" required
                            data-placeholder="Pilih nilai...">
                        <option value=""></option>
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

            <?php
            $dynamicFields = array_filter($item['version']['dynamic_fields'] ?? [], function ($f) {
                return ! empty($f['is_active']) && ! in_array($f['field_type'], ['dropdown', 'radio', 'checkbox'], true);
            });
            ?>
            <?php if (! empty($dynamicFields)) : ?>
            <h6 class="fw-bold mb-3 mt-2">
                <i class="fa fa-pen-to-square me-2 text-primary"></i>Data Tambahan
            </h6>
            <div class="row g-3 mb-4">
                <?php foreach ($dynamicFields as $field) : ?>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="field-<?= esc($field['id']) ?>">
                        <?= esc($field['label']) ?>
                        <?php if (! empty($field['is_required'])) : ?><span class="text-danger">*</span><?php endif; ?>
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
                <a href="<?= site_url('transactions/' . $item['id'] . '/review') ?>" class="btn btn-default">
                    <i class="fa fa-arrow-left me-1"></i> Kembali
                </a>
                <button type="submit" id="btnInput" class="btn btn-theme">
                    Lanjut ke Konfirmasi <i class="fa fa-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
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
<div class="card card-borderless">
    <div class="card-body text-center py-5">
        <iconify-icon icon="solar:lock-bold-duotone" class="text-muted mb-3" style="font-size:64px"></iconify-icon>
        <h5 class="fw-bold">Pengajuan Sudah Dikunci</h5>
        <p class="text-muted mb-3">Pengajuan ini tidak dapat diubah karena sudah dalam proses.</p>
        <a href="<?= site_url('transactions/' . $item['id'] . '/review') ?>" class="btn btn-theme">
            <i class="fa fa-eye me-1"></i> Lihat Hasil Scoring
        </a>
    </div>
</div>
<?php endif; ?>

<?= view('partials/shell_end') ?>
