<?php
$profile = $profile ?? [];
$item = $item ?? [];
$candidates = $candidates ?? [];
$permissions = $profile['permissions'] ?? [];
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Keputusan Approval']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

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
    <div class="card-body">
        <div class="text-muted mb-3">
            <span class="fw-semibold text-dark"><?= esc($item['debtor']['full_name'] ?? '-') ?></span>
            &mdash; <?= esc($item['product']['name'] ?? '-') ?>
        </div>

        <?php if (! empty($item['snapshot'])) : ?>
        <div class="card rounded-3 border-0 bg-gradient-135 bg-gradient-from-teal bg-gradient-to-green overflow-hidden mb-0" data-bs-theme="dark">
            <div class="card-body position-relative z-3 py-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="text-white text-opacity-75 small fw-semibold mb-1">Skor</div>
                        <div class="display-6 fw-bold text-white mb-0"><?= esc((string) $item['snapshot']['total_score']) ?></div>
                    </div>
                    <div class="col">
                        <div class="fw-bold text-white fs-5"><?= esc($item['snapshot']['result_label']) ?></div>
                        <div class="text-white text-opacity-75 small">Hasil Scoring</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (in_array('scoring.assign', $permissions, true) && in_array($item['status'], ['waiting_for_approver_assignment', 'submitted'], true)) : ?>
<div class="card card-borderless mb-3">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:user-plus-bold-duotone" class="me-1"></iconify-icon>
            <?= $item['status'] === 'submitted' ? 'Penugasan Ulang Approver' : 'Tugaskan Approver' ?>
        </h4>
    </div>
    <div class="card-body">
        <form id="assignForm" method="post" action="<?= site_url('approvals/' . $item['id'] . '/assign') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="approver_id">Approver <span class="text-danger">*</span></label>
                    <select id="approver_id" name="approver_id" class="select2" data-placeholder="Pilih approver..." required>
                        <?php foreach ($candidates as $candidate) : ?>
                            <option value="<?= esc($candidate['id']) ?>"><?= esc($candidate['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="reason">
                        Alasan <?= $item['status'] === 'submitted' ? '<span class="text-danger">*</span>' : '<span class="text-muted fw-normal">(opsional)</span>' ?>
                    </label>
                    <input id="reason" name="reason" type="text" class="form-control"
                           placeholder="Masukkan alasan penugasan..."
                           <?= $item['status'] === 'submitted' ? 'required' : '' ?>>
                </div>
            </div>
            <div class="mt-3">
                <button type="button" id="btnAssign" class="btn btn-theme">
                    <i class="fa fa-user-check me-1"></i> Simpan Penugasan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (
    in_array('scoring.approve', $permissions, true) &&
    ($item['status'] ?? '') === 'submitted' &&
    (int) ($item['assigned_approver_id'] ?? 0) === (int) ($profile['id'] ?? 0)
) : ?>
<div class="card card-borderless">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:hammer-bold-duotone" class="me-1"></iconify-icon>
            Berikan Keputusan
        </h4>
    </div>
    <div class="card-body">
        <form id="decideForm" method="post" action="<?= site_url('approvals/' . $item['id'] . '/decide') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Keputusan <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="form-check form-check-inline p-0 me-0">
                            <label class="d-flex align-items-center gap-2 px-3 py-2 rounded border decision-pill" style="cursor:pointer;" id="lblApproved">
                                <input class="decision-radio" type="radio" name="decision" value="approved" style="width:auto;margin:0;">
                                <i class="fa fa-circle-check text-success"></i>
                                <span>Setujui</span>
                            </label>
                        </div>
                        <div class="form-check form-check-inline p-0 me-0">
                            <label class="d-flex align-items-center gap-2 px-3 py-2 rounded border decision-pill" style="cursor:pointer;" id="lblRejected">
                                <input class="decision-radio" type="radio" name="decision" value="rejected" style="width:auto;margin:0;">
                                <i class="fa fa-circle-xmark text-danger"></i>
                                <span>Tolak</span>
                            </label>
                        </div>
                        <div class="form-check form-check-inline p-0 me-0">
                            <label class="d-flex align-items-center gap-2 px-3 py-2 rounded border decision-pill" style="cursor:pointer;" id="lblReturned">
                                <input class="decision-radio" type="radio" name="decision" value="returned" style="width:auto;margin:0;">
                                <i class="fa fa-rotate-left text-warning"></i>
                                <span>Kembalikan</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="note">Catatan <span class="text-danger">*</span></label>
                    <input id="note" name="note" type="text" class="form-control"
                           placeholder="Masukkan catatan keputusan..." required>
                </div>
            </div>
            <div class="mt-3">
                <button type="button" id="btnDecide" class="btn btn-theme">
                    <i class="fa fa-gavel me-1"></i> Simpan Keputusan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function () {
    if ($('#approver_id').length) {
        App.initSelect2('#approver_id', { placeholder: 'Pilih approver...' });
    }

    $('#btnAssign').on('click', function () {
        App.confirm({
            title: 'Tugaskan Approver',
            text: 'Pengajuan akan diteruskan ke approver yang dipilih. Lanjutkan?',
            confirmButtonText: '<i class="fa-solid fa-user-check me-1"></i> Ya, Tugaskan'
        }).then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($('#btnAssign'), 'Menyimpan...');
                $('#assignForm').submit();
            }
        });
    });

    $('#btnDecide').on('click', function () {
        var decision = $('input[name="decision"]:checked').val();
        var confirmFn;
        if (decision === 'approved') {
            confirmFn = App.confirmApprove;
        } else if (decision === 'rejected') {
            confirmFn = App.confirmReject;
        } else if (decision === 'returned') {
            confirmFn = App.confirmReturn;
        } else {
            App.alertWarning('Pilih Keputusan', 'Silakan pilih keputusan terlebih dahulu.');
            return;
        }

        confirmFn().then(function (result) {
            if (result.isConfirmed) {
                App.btnLoading($('#btnDecide'), 'Menyimpan...');
                $('#decideForm').submit();
            }
        });
    });
});
</script>

<?= view('partials/shell_end') ?>
