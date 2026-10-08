<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Approval & Izin Pengajuan']) ?>

<?php
$inbox = $inbox ?? [];
$duplicates = $duplicates ?? [];
$inboxCount = count($inbox);
$duplicateCount = count($duplicates);
?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<?= view('partials/kpi_solid', ['items' => [
    ['label' => 'Menunggu Keputusan', 'value' => $inboxCount, 'sub' => 'Antrian scoring Anda', 'tone' => 'blue', 'icon' => 'solar:inbox-bold-duotone'],
    ['label' => 'Izin Pengajuan Ulang', 'value' => $duplicateCount, 'sub' => 'Persetujuan skor ganda', 'tone' => 'red', 'icon' => 'solar:shield-warning-bold-duotone'],
]]) ?>

<!-- Nav Tabs for Two-Gate Workflow -->
<ul class="nav nav-tabs nav-tabs-v2 px-1 mb-3" id="approvalTabs" role="tablist">
    <li class="nav-item me-2" role="presentation">
        <button class="nav-link <?= $duplicateCount > 0 && $inboxCount === 0 ? '' : 'active' ?> d-flex align-items-center gap-2 py-2 px-3 fw-semibold" id="tab-scoring-btn" data-bs-toggle="tab" data-bs-target="#tab-scoring" type="button" role="tab" aria-controls="tab-scoring" aria-selected="<?= $duplicateCount > 0 && $inboxCount === 0 ? 'false' : 'true' ?>">
            <iconify-icon icon="solar:inbox-bold-duotone" class="fs-5 text-primary"></iconify-icon>
            <span>Persetujuan Scoring</span>
            <span class="badge rounded-pill bg-primary bg-opacity-15 text-primary ms-1"><?= esc((string) $inboxCount) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $duplicateCount > 0 && $inboxCount === 0 ? 'active' : '' ?> d-flex align-items-center gap-2 py-2 px-3 fw-semibold position-relative" id="tab-duplicates-btn" data-bs-toggle="tab" data-bs-target="#tab-duplicates" type="button" role="tab" aria-controls="tab-duplicates" aria-selected="<?= $duplicateCount > 0 && $inboxCount === 0 ? 'true' : 'false' ?>">
            <iconify-icon icon="solar:shield-warning-bold-duotone" class="fs-5 text-danger"></iconify-icon>
            <span>Izin Pengajuan Ulang</span>
            <?php if ($duplicateCount > 0) : ?>
                <span class="badge rounded-pill bg-danger text-white ms-1"><?= esc((string) $duplicateCount) ?></span>
            <?php else : ?>
                <span class="badge rounded-pill bg-secondary bg-opacity-25 text-secondary ms-1">0</span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content" id="approvalTabsContent">
    <!-- TAB 1: REGULAR SCORING APPROVAL -->
    <div class="tab-pane fade <?= $duplicateCount > 0 && $inboxCount === 0 ? '' : 'show active' ?>" id="tab-scoring" role="tabpanel" aria-labelledby="tab-scoring-btn">
        <div class="card card-borderless table-card mb-3">
            <div class="card-header bg-gray-900" data-bs-theme="dark">
                <h4 class="card-header-title text-white mb-0">
                    <iconify-icon icon="solar:inbox-bold-duotone" class="me-1"></iconify-icon>
                    Menunggu Keputusan Saya
                </h4>
                <div class="card-header-btn">
                    <span class="badge bg-white bg-opacity-15 text-white"><?= esc((string) $inboxCount) ?></span>
                    <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
                </div>
            </div>
            <div class="card-body">
                <table id="inboxTable" class="table table-hover table-striped align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th>Nomor Pengajuan</th>
                            <th>Debitur</th>
                            <th>Produk</th>
                            <th class="text-center" style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inbox as $item) : ?>
                        <tr>
                            <td>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span class="badge bg-primary bg-opacity-15 text-primary py-6px font-monospace"><?= esc($item['transaction_no']) ?></span>
                                    <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($item['transaction_no']) ?>" title="Salin Nomor">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="fw-semibold"><?= esc($item['debtor_name']) ?></td>
                            <td><?= esc($item['product_name']) ?></td>
                            <td class="text-center">
                                <?php
                                ob_start();
                                ?>
                                        <li>
                                            <a href="<?= site_url('approvals/' . $item['id']) ?>" class="dropdown-action-item">
                                                <span class="action-icon-circle action-icon-green"><i class="fa-solid fa-eye"></i></span>
                                                <div class="action-text-group">
                                                    <span class="action-title">Buka</span>
                                                    <span class="action-desc">Lihat detail &amp; berikan keputusan</span>
                                                </div>
                                            </a>
                                        </li>
                                <?php
                                echo view('partials/action_dropdown_btn', ['menuHtml' => ob_get_clean()]);
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inbox)) : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Tidak ada pengajuan yang menunggu keputusan Anda.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: DUPLICATE PERMISSION GATE (GATE 1) -->
    <div class="tab-pane fade <?= $duplicateCount > 0 && $inboxCount === 0 ? 'show active' : '' ?>" id="tab-duplicates" role="tabpanel" aria-labelledby="tab-duplicates-btn">
        
        <!-- Informasi Gate Persetujuan Khusus di Atas (Full Width) -->
        <div class="alert alert-warning border border-warning border-opacity-50 bg-warning bg-opacity-10 d-flex align-items-start gap-3 p-3 mb-3 rounded-3 shadow-xs">
            <div class="fs-2 text-warning flex-shrink-0 mt-n1">
                <iconify-icon icon="solar:shield-warning-bold-duotone"></iconify-icon>
            </div>
            <div class="flex-grow-1">
                <h6 class="alert-heading fw-bold mb-1 text-dark">Gate 1: Persetujuan Izin Pengajuan Ulang (Duplicate Scoring)</h6>
                <div class="small text-secondary mb-0">
                    Daftar di bawah memuat transaksi scoring yang terdeteksi memiliki NIK dan Produk yang sudah pernah dinilai sebelumnya.
                    Jika Anda <strong>Setujui Izin</strong>, pengajuan akan masuk ke antrean reguler <strong>Persetujuan Scoring</strong>.
                    Jika <strong>Tolak Izin</strong>, pengajuan akan langsung dibatalkan (Rejected).
                </div>
            </div>
        </div>

        <div class="card card-borderless table-card">
            <div class="card-header bg-gray-900" data-bs-theme="dark">
                <h4 class="card-header-title text-white mb-0">
                    <iconify-icon icon="solar:shield-warning-bold-duotone" class="me-1 text-danger"></iconify-icon>
                    Daftar Permohonan Izin Pengajuan Ulang
                </h4>
                <div class="card-header-btn">
                    <span class="badge bg-danger text-white"><?= esc((string) $duplicateCount) ?></span>
                    <?= view('partials/card_widget_btn', ['wrap' => false]) ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="duplicateTable" class="table table-hover table-striped align-middle mb-0 w-100">
                        <thead>
                            <tr>
                                <th>Nomor Pengajuan</th>
                                <th>Debitur &amp; NIK</th>
                                <th>Produk Kredit</th>
                                <th>Alasan Pengajuan Ulang</th>
                                <th>Skor Sebelumnya</th>
                                <th>Pemohon &amp; Waktu</th>
                                <th class="text-center" style="width:170px;">Keputusan Izin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($duplicates as $dup) : ?>
                            <tr>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="badge bg-danger bg-opacity-15 text-danger py-6px font-monospace fw-bold"><?= esc($dup['transaction_no']) ?></span>
                                        <button type="button" class="btn btn-default btn-xs btn-icon btn-copy-inline" data-clipboard="<?= esc($dup['transaction_no']) ?>" title="Salin Nomor">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= esc($dup['debtor_name']) ?></div>
                                    <div class="small text-muted font-monospace"><i class="fa fa-id-card me-1"></i><?= esc($dup['nik'] ?? '-') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border"><?= esc($dup['product_name']) ?></span>
                                </td>
                                <td>
                                    <div class="p-2 bg-light rounded border border-warning border-opacity-50 small fst-italic text-dark" style="max-width:280px;">
                                        "<?= esc($dup['duplicate_reason']) ?>"
                                    </div>
                                </td>
                                <td>
                                    <?php if (! empty($dup['prior_score']) && $dup['prior_score'] !== '-') : ?>
                                        <div class="small fw-semibold text-dark">Skor: <?= esc((string) (int) round((float) $dup['prior_score'])) ?></div>
                                        <span class="badge bg-info-subtle text-info border small"><?= esc($dup['prior_eligibility'] ?? 'SEBELUMNYA') ?></span>
                                        <div class="text-muted" style="font-size:0.75rem;"><?= esc($dup['prior_date'] ?? '') ?></div>
                                    <?php else : ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= esc($dup['creator_name']) ?></div>
                                    <div class="text-muted small"><?= esc($dup['created_at']) ?></div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-theme btn-xs d-inline-flex align-items-center gap-1 btn-approve-duplicate"
                                                data-id="<?= esc((string) $dup['id']) ?>"
                                                data-no="<?= esc($dup['transaction_no']) ?>"
                                                data-debtor="<?= esc($dup['debtor_name']) ?>"
                                                title="Setujui Izin Pengajuan Ulang">
                                            <i class="fa fa-check"></i> Setujui
                                        </button>
                                        <button type="button" class="btn btn-danger btn-xs d-inline-flex align-items-center gap-1 btn-reject-duplicate"
                                                data-id="<?= esc((string) $dup['id']) ?>"
                                                data-no="<?= esc($dup['transaction_no']) ?>"
                                                data-debtor="<?= esc($dup['debtor_name']) ?>"
                                                title="Tolak Izin Pengajuan Ulang">
                                            <i class="fa fa-times"></i> Tolak
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($duplicates)) : ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Tidak ada permohonan izin pengajuan ulang yang menunggu keputusan.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Setujui Izin -->
<div class="modal fade" id="approveDuplicateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" id="approveDuplicateForm" action="" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-theme text-white">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:check-circle-bold" class="fs-4"></iconify-icon>
                    Setujui Izin Pengajuan Ulang
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Apakah Anda yakin menyetujui izin pengajuan ulang untuk transaksi ini?</p>
                <div class="p-3 bg-light rounded border mb-3">
                    <div class="row g-2 small">
                        <div class="col-4 text-muted">Nomor Transaksi:</div>
                        <div class="col-8 fw-bold font-monospace text-primary" id="apprModalNo">-</div>
                        <div class="col-4 text-muted">Debitur:</div>
                        <div class="col-8 fw-semibold text-dark" id="apprModalDebtor">-</div>
                    </div>
                </div>
                <div class="alert alert-info py-2 px-3 small mb-0">
                    <i class="fa fa-info-circle me-1"></i> Setelah disetujui, transaksi ini akan otomatis berpindah ke antrean <strong>Persetujuan Scoring</strong> reguler untuk keputusan kredit akhir.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-theme">Ya, Setujui Izin</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tolak Izin -->
<div class="modal fade" id="rejectDuplicateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" id="rejectDuplicateForm" action="" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:close-circle-bold" class="fs-4"></iconify-icon>
                    Tolak Izin Pengajuan Ulang
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Anda akan menolak izin pengajuan ulang untuk transaksi ini. Transaksi akan langsung berstatus <strong>Batal / Ditolak</strong>.</p>
                <div class="p-3 bg-light rounded border mb-3">
                    <div class="row g-2 small">
                        <div class="col-4 text-muted">Nomor Transaksi:</div>
                        <div class="col-8 fw-bold font-monospace text-danger" id="rejModalNo">-</div>
                        <div class="col-4 text-muted">Debitur:</div>
                        <div class="col-8 fw-semibold text-dark" id="rejModalDebtor">-</div>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold small">Alasan Penolakan Izin <span class="text-danger">*</span></label>
                    <textarea name="note" id="rejModalNote" class="form-control" rows="3" placeholder="Masukkan alasan penolakan izin..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Kembali</button>
                <button type="submit" class="btn btn-danger">Tolak &amp; Batalkan Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#inboxTable tbody tr td[colspan]').length === 0) {
        App.initDT('#inboxTable', {
            searching: <?= $inboxCount > 0 ? 'true' : 'false' ?>,
            paging:    <?= $inboxCount > 10 ? 'true' : 'false' ?>,
            columnDefs: [{ orderable: false, targets: [3] }]
        });
    }

    if ($('#duplicateTable tbody tr td[colspan]').length === 0) {
        App.initDT('#duplicateTable', {
            searching: <?= $duplicateCount > 0 ? 'true' : 'false' ?>,
            paging:    <?= $duplicateCount > 10 ? 'true' : 'false' ?>,
            responsive: false,
            columnDefs: [{ orderable: false, targets: [6] }]
        });
    }

    // Modal handlers (delegated)
    $(document).on('click', '.btn-approve-duplicate', function () {
        var id = $(this).data('id');
        var no = $(this).data('no');
        var debtor = $(this).data('debtor');

        $('#apprModalNo').text(no);
        $('#apprModalDebtor').text(debtor);
        $('#approveDuplicateForm').attr('action', '<?= site_url('approvals/duplicate/') ?>' + id + '/approve');
        $('#approveDuplicateModal').modal('show');
    });

    $(document).on('click', '.btn-reject-duplicate', function () {
        var id = $(this).data('id');
        var no = $(this).data('no');
        var debtor = $(this).data('debtor');

        $('#rejModalNo').text(no);
        $('#rejModalDebtor').text(debtor);
        $('#rejModalNote').val('');
        $('#rejectDuplicateForm').attr('action', '<?= site_url('approvals/duplicate/') ?>' + id + '/reject');
        $('#rejectDuplicateModal').modal('show');
    });

    // Check URL hash
    if (window.location.hash === '#tab-duplicates') {
        var triggerEl = document.querySelector('#tab-duplicates-btn');
        if (triggerEl) {
            var tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }
});
</script>

<?= view('partials/shell_end') ?>
