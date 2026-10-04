<?php
$profile = $profile ?? [];
$item = $item ?? [];
$candidates = $candidates ?? [];
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Keputusan Approval']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?php $permissions = $profile['permissions'] ?? []; ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= site_url('approvals') ?>">Approval</a></li>
        <li class="breadcrumb-item active"><?= esc($item['transaction_no']) ?></li>
    </ol>
</nav>

<!-- Header Info -->
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width:48px;height:48px;background:#e8f0ff;flex-shrink:0;">
                        <i class="fa-solid fa-file-invoice-dollar" style="color:var(--secondary);font-size:20px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="mb-1"><?= esc($item['transaction_no']) ?></h3>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="text-muted"><?= esc($item['debtor']['full_name'] ?? '') ?></span>
                            <span class="text-muted">—</span>
                            <span class="text-muted"><?= esc($item['product']['name'] ?? '') ?></span>
                            <span class="badge badge-<?= esc($item['status']) ?>"><?= esc($item['status']) ?></span>
                        </div>
                    </div>
                </div>

                <?php if (! empty($item['snapshot'])) : ?>
                <div class="d-flex align-items-center gap-3 mt-3 p-3 rounded" style="background:var(--surface-2);border:1px solid var(--border);">
                    <div class="text-center">
                        <div class="fw-black" style="font-size:2.5rem;color:var(--primary);line-height:1;"><?= esc((string) $item['snapshot']['total_score']) ?></div>
                        <div class="text-muted" style="font-size:11px;">Skor</div>
                    </div>
                    <div style="width:1px;height:50px;background:var(--border);"></div>
                    <div>
                        <div class="fw-bold" style="color:var(--primary);"><?= esc($item['snapshot']['result_label']) ?></div>
                        <div class="text-muted" style="font-size:13px;">Hasil Scoring</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Form: Assign Approver -->
<?php if (in_array('scoring.assign', $permissions, true) && in_array($item['status'], ['waiting_for_approver_assignment', 'submitted'], true)) : ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa-solid fa-user-plus me-2 text-primary"></i>
            <?= $item['status'] === 'submitted' ? 'Penugasan Ulang Approver' : 'Tugaskan Approver' ?>
        </h5>
    </div>
    <div class="card-body">
        <form id="assignForm" method="post" action="<?= site_url('approvals/' . $item['id'] . '/assign') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="approver_id">Approver <span class="required">*</span></label>
                    <select id="approver_id" name="approver_id" class="select2" data-placeholder="Pilih approver..." required>
                        <?php foreach ($candidates as $candidate) : ?>
                            <option value="<?= esc($candidate['id']) ?>"><?= esc($candidate['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="reason">
                        Alasan <?= $item['status'] === 'submitted' ? '<span class="required">*</span>' : '<span class="text-muted fw-normal">(opsional)</span>' ?>
                    </label>
                    <input id="reason" name="reason" type="text" class="form-control"
                           placeholder="Masukkan alasan penugasan..."
                           <?= $item['status'] === 'submitted' ? 'required' : '' ?>>
                </div>
            </div>
            <div class="mt-3">
                <button type="button" id="btnAssign" class="btn btn-primary">
                    <i class="fa-solid fa-user-check me-1"></i> Simpan Penugasan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Form: Decision -->
<?php if (
    in_array('scoring.approve', $permissions, true) &&
    ($item['status'] ?? '') === 'submitted' &&
    (int) ($item['assigned_approver_id'] ?? 0) === (int) ($profile['id'] ?? 0)
) : ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-gavel me-2 text-primary"></i>Berikan Keputusan</h5>
    </div>
    <div class="card-body">
        <form id="decideForm" method="post" action="<?= site_url('approvals/' . $item['id'] . '/decide') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="decision">Keputusan <span class="required">*</span></label>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="form-check form-check-inline p-0 me-0">
                            <label class="d-flex align-items-center gap-2 px-3 py-2 rounded border" style="cursor:pointer;" id="lblApproved">
                                <input class="decision-radio" type="radio" name="decision" value="approved" style="width:auto;margin:0;">
                                <i class="fa-solid fa-circle-check" style="color:var(--success);"></i>
                                <span>Setujui</span>
                            </label>
                        </div>
                        <div class="form-check form-check-inline p-0 me-0">
                            <label class="d-flex align-items-center gap-2 px-3 py-2 rounded border" style="cursor:pointer;" id="lblRejected">
                                <input class="decision-radio" type="radio" name="decision" value="rejected" style="width:auto;margin:0;">
                                <i class="fa-solid fa-circle-xmark" style="color:var(--danger);"></i>
                                <span>Tolak</span>
                            </label>
                        </div>
                        <div class="form-check form-check-inline p-0 me-0">
                            <label class="d-flex align-items-center gap-2 px-3 py-2 rounded border" style="cursor:pointer;" id="lblReturned">
                                <input class="decision-radio" type="radio" name="decision" value="returned" style="width:auto;margin:0;">
                                <i class="fa-solid fa-rotate-left" style="color:var(--warning);"></i>
                                <span>Kembalikan</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="note">Catatan <span class="required">*</span></label>
                    <input id="note" name="note" type="text" class="form-control"
                           placeholder="Masukkan catatan keputusan..." required>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="button" id="btnDecide" class="btn btn-primary">
                    <i class="fa-solid fa-gavel me-1"></i> Simpan Keputusan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function () {
    App.initSelect2('#approver_id', { placeholder: 'Pilih approver...' });

    // Assign confirmation
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

    // Decision confirmation
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
