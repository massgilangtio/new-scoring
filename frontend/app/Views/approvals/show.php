<?php
$profile = $profile ?? [];
$item = $item ?? [];
$candidates = $candidates ?? [];
$permissions = $profile['permissions'] ?? [];
helper('access');
$canViewScoreDetails = can_view_score_details($profile);
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
            <?= view('partials/card_widget_btn') ?>
        </div>
    </div>
    <div class="card-body">
        <div class="text-muted mb-3">
            <span class="fw-semibold text-dark"><?= esc($item['debtor']['full_name'] ?? '-') ?></span>
            &mdash; <?= esc($item['product']['name'] ?? '-') ?>
        </div>

        <?php if (! empty($item['duplicate_reason'])) : ?>
        <div class="alert alert-warning border border-warning border-opacity-50 d-flex align-items-start gap-3 mb-3 p-3 rounded-3 shadow-xs">
            <div class="fs-2 text-warning flex-shrink-0 mt-n1"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-warning text-dark font-monospace fw-bold">Pengajuan Ulang / Duplikat</span>
                    <span class="small text-muted">Debitur &amp; Produk telah terdaftar sebelumnya</span>
                </div>
                <div class="small fw-semibold text-dark mb-1">Alasan Penginputan Ulang (Reviewer):</div>
                <div class="p-2 bg-white bg-opacity-75 rounded border border-warning border-opacity-25 fst-italic text-secondary small">
                    "<?= esc($item['duplicate_reason']) ?>"
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (! empty($item['snapshot']) && $canViewScoreDetails) : ?>
        <?php
        $formatInt = static function ($val) {
            if ($val === null || $val === '' || $val === '-') {
                return '-';
            }
            return (string) (int) round((float) $val);
        };
        ?>
        <div class="card card-borderless rounded-3 overflow-hidden bg-teal mb-0" data-bs-theme="dark">
            <div class="card-body position-relative z-3 py-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="text-white text-opacity-75 small fw-semibold mb-1">Skor Kredit</div>
                        <div class="display-6 fw-bold text-white mb-0"><?= esc($formatInt($item['snapshot']['total_score'] ?? '-')) ?></div>
                    </div>
                    <div class="col">
                        <div class="fw-bold text-white fs-5"><?= esc($item['snapshot']['result_label'] ?? '-') ?></div>
                        <div class="text-white text-opacity-75 small">Hasil Scoring</div>
                    </div>
                </div>
            </div>
            <div class="position-absolute top-0 end-0 mt-n5 ps-5 w-25 d-none d-md-block">
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle ms-n5 position-absolute top-0 start-0"></div>
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle mt-n5 position-absolute top-0 start-0"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$snapshotLines = $item['snapshot']['lines'] ?? [];
if (! empty($snapshotLines)) :
    if (! isset($formatInt)) {
        $formatInt = static function ($val) {
            if ($val === null || $val === '' || $val === '-') {
                return '-';
            }
            return (string) (int) round((float) $val);
        };
    }
?>
<div class="card card-borderless mb-3">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:list-check-bold-duotone" class="me-1"></iconify-icon>
            Rincian Parameter Terpilih
        </h4>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white bg-opacity-15 text-white">View only</span>
            <?= view('partials/card_widget_btn') ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Parameter</th>
                        <th>Pilihan</th>
                        <?php if ($canViewScoreDetails) : ?>
                        <th class="text-end">Bobot</th>
                        <th class="text-end">Nilai</th>
                        <th class="text-end">Skor</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($snapshotLines as $idx => $line) : ?>
                    <tr>
                        <td class="text-muted"><?= esc((string) ($idx + 1)) ?></td>
                        <td class="fw-semibold"><?= esc($line['parameter_name'] ?? '-') ?></td>
                        <td><?= esc($line['option_label'] ?? '-') ?></td>
                        <?php if ($canViewScoreDetails) : ?>
                        <td class="text-end font-monospace"><?= esc($formatInt($line['weight'] ?? '-')) ?></td>
                        <td class="text-end font-monospace"><?= esc($formatInt($line['value'] ?? '-')) ?></td>
                        <td class="text-end font-monospace fw-bold"><?= esc($formatInt($line['line_score'] ?? '-')) ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if ($canViewScoreDetails) : ?>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="5" class="fw-bold text-end">Total Skor</td>
                        <td class="text-end fw-bold text-primary font-monospace fs-6"><?= esc($formatInt($item['snapshot']['total_score'] ?? '-')) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
        <div class="px-3 py-2 border-top small text-muted">
            <i class="fa-solid fa-lock me-1"></i> Data parameter terkunci (hanya tampilan). Keputusan approval di bawah.
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (in_array('scoring.assign', $permissions, true) && in_array($item['status'], ['waiting_for_approver_assignment', 'submitted'], true)) : ?>
<div class="card card-borderless mb-3">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0">
            <iconify-icon icon="solar:user-plus-bold-duotone" class="me-1"></iconify-icon>
            <?= $item['status'] === 'submitted' ? 'Penugasan Ulang Approver' : 'Tugaskan Approver' ?>
        </h4>
        <?= view('partials/card_widget_btn') ?>
    </div>
    <div class="card-body">
        <form id="assignForm" method="post" action="<?= site_url('approvals/' . $item['id'] . '/assign') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="approver_id">Approver <span class="text-danger">*</span></label>
                    <select id="approver_id" name="approver_id" class="select2" data-placeholder="Pilih nama pegawai Approver..." required>
                        <?php foreach ($candidates as $candidate) : ?>
                            <?php
                            $candName = (string) ($candidate['full_name'] ?? '');
                            $candRole = (string) ($candidate['role_name'] ?? 'Approver');
                            $candLabel = (string) ($candidate['label'] ?? ($candName . ' — ' . $candRole));
                            ?>
                            <option value="<?= esc($candidate['id']) ?>"><?= esc($candLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Format: Nama Pegawai — Role</div>
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
        <?= view('partials/card_widget_btn') ?>
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
        App.initSelect2('#approver_id', { placeholder: 'Pilih nama pegawai Approver...' });
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
