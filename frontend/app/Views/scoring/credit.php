<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Scoring Kredit']) ?>

<?php if (! empty($message)) : ?>
<div data-swal="success" data-swal-message="<?= esc($message) ?>" hidden></div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<div id="creditWizard" data-form-wizard data-wizard-start="1">
<?= view('partials/form_wizard_nav', [
    'current' => 1,
    'steps'   => [
        ['label' => 'Pilih Debitur'],
        ['label' => 'Pilih Produk'],
        ['label' => 'Penilaian Scoring'],
        ['label' => 'Ringkasan / Konfirmasi'],
    ],
]) ?>

<form id="creditScoringForm">
    <?= csrf_field() ?>

    <div class="row g-3">
        <!-- 1. Section: Pilih CIS ID / Debitur & Edit Data Debitur -->
        <div class="col-12" data-wizard-pane="1">
            <div class="card card-borderless overflow-hidden">
                <div class="card-header bg-gray-900" data-bs-theme="dark">
                    <h4 class="card-header-title text-white mb-0">
                        <iconify-icon icon="solar:user-check-bold-duotone" class="me-1"></iconify-icon>
                        Pilih Debitur &amp; Periksa Profil
                    </h4>
                    <span class="badge bg-white bg-opacity-15 text-white">Data master (read-only)</span>
                </div>
                <div class="card-body">
                    <!-- Dropdown Pilih Debitur -->
                    <div class="mb-4">
                        <label for="debtorSelect" class="form-label fw-semibold text-dark">
                            Pilih CIS ID / Nama Debitur <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="debtorSelect" style="width: 100%;" required>
                            <option value="">-- Cari berdasarkan CIS ID, NIK, atau Nama Debitur --</option>
                            <?php foreach ($debtors as $d) : ?>
                                <option value="<?= esc($d['id']) ?>" 
                                        data-debtor='<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                    [CIS: <?= esc($d['cis_id']) ?>] <?= esc($d['nik']) ?> - <?= esc($d['full_name']) ?> (<?= esc($d['branch_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted">Pilih debitur dari daftar master. Profil ditampilkan read-only; ubah data lewat Master Debitur.</div>
                    </div>

                    <!-- Panel Detail Data Debitur (read-only) -->
                    <div id="debtorDetailPanel" class="d-none">
                        <div class="p-3 bg-light rounded-3 mb-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0" id="dispDebtorName">-</h6>
                                    <div class="small text-muted">
                                        CIS ID: <strong id="dispCisId">-</strong> &bull; CIF ID: <strong id="dispCifId">-</strong>
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                <i class="fa-solid fa-check-circle me-1"></i> Debitur Aktif
                            </span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label small fw-semibold text-dark">NIK</label>
                                <input type="text" class="form-control form-control-sm" id="debNik" name="nik" readonly tabindex="-1">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label small fw-semibold text-dark">Nama Lengkap Sesuai KTP</label>
                                <input type="text" class="form-control form-control-sm" id="debFullName" name="full_name" readonly tabindex="-1">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label small fw-semibold text-dark">Nomor Telepon / WhatsApp</label>
                                <input type="text" class="form-control form-control-sm" id="debPhone" name="phone" readonly tabindex="-1">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label small fw-semibold text-dark">Jenis Kelamin</label>
                                <select class="form-select form-select-sm" id="debGender" name="gender" disabled>
                                    <option value="">Pilih</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label small fw-semibold text-dark">Agama</label>
                                <select class="form-select form-select-sm" id="debReligion" name="religion" disabled>
                                    <option value="">Pilih</option>
                                    <option value="Islam">Islam</option>
                                    <option value="Protestan">Protestan</option>
                                    <option value="Katolik">Katolik</option>
                                    <option value="Buddha">Buddha</option>
                                    <option value="Hindu">Hindu</option>
                                    <option value="Konghucu">Konghucu</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label small fw-semibold text-dark">Tanggal Lahir</label>
                                <input type="date" class="form-control form-control-sm" id="debBirthDate" name="birth_date" readonly tabindex="-1">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label small fw-semibold text-dark">Tempat Lahir</label>
                                <input type="text" class="form-control form-control-sm" id="debBirthPlace" name="birth_place" readonly tabindex="-1">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label small fw-semibold text-dark">NPWP</label>
                                <input type="text" class="form-control form-control-sm" id="debNpwp" name="npwp" readonly tabindex="-1">
                            </div>
                            <div class="col-md-6 col-lg-8">
                                <label class="form-label small fw-semibold text-dark">Alamat Lengkap</label>
                                <input type="text" class="form-control form-control-sm" id="debAddress" name="address" readonly tabindex="-1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-actions">
                <span></span>
                <button type="button" class="btn btn-theme" data-wizard-next id="btnCreditNext1">
                    Berikutnya <i class="fa fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        <!-- 2. Section: Pilih Kode Produk yang Memiliki Mapping Parameter -->
        <div class="col-12 d-none" data-wizard-pane="2">
            <div class="card card-borderless overflow-hidden">
                <div class="card-header bg-gray-900" data-bs-theme="dark">
                    <h4 class="card-header-title text-white mb-0">
                        <iconify-icon icon="solar:box-bold-duotone" class="me-1"></iconify-icon>
                        Pilih Kode Produk Kredit
                    </h4>
                    <span class="badge bg-white bg-opacity-15 text-white">Produk dengan mapping aktif</span>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label for="productSelect" class="form-label fw-semibold text-dark">
                            Kode Produk Kredit <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="productSelect" style="width: 100%;" required>
                            <option value="">-- Pilih Kode Produk Kredit --</option>
                            <?php foreach ($products as $pr) : ?>
                                <option value="<?= esc($pr['product_id']) ?>" data-mapping-id="<?= esc($pr['mapping_id']) ?>">
                                    <?= esc($pr['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted">Pilih produk kredit untuk memuat parameter penilaian scoring yang telah dimapping.</div>
                    </div>
                </div>
            </div>
            <div class="wizard-actions">
                <button type="button" class="btn btn-default" data-wizard-prev>
                    <i class="fa fa-arrow-left me-1"></i> Sebelumnya
                </button>
                <button type="button" class="btn btn-theme" data-wizard-next id="btnCreditNext2">
                    Berikutnya <i class="fa fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        <!-- 3. Section: Penilaian Parameter Hasil Mapping (Beserta Kode & Deskripsi) -->
        <div class="col-12 d-none" data-wizard-pane="3" id="scoringSection">
            <div class="card card-borderless overflow-hidden">
                <div class="card-header bg-gray-900" data-bs-theme="dark">
                    <h4 class="card-header-title text-white mb-0">
                        <iconify-icon icon="solar:checklist-minimalistic-bold-duotone" class="me-1"></iconify-icon>
                        Penilaian Parameter Scoring
                    </h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-white bg-opacity-15 text-white" id="topCutoffBadge">
                            <i class="fa fa-award me-1"></i>Batas: &ge; 350.00
                        </span>
                        <span class="badge bg-theme text-white" id="totalScoreBadge">0.00</span>
                        <span class="badge bg-danger text-white fw-bold" id="topEligibilityBadge">
                            <i class="fa fa-circle-xmark me-1"></i> TIDAK LAYAK
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4 small">
                        Pilih opsi nilai untuk setiap parameter yang ter-mapping pada produk ini:
                    </p>

                    <!-- Dynamic Parameter Cards Container -->
                    <div id="parameterContainer" class="d-flex flex-column gap-3">
                        <!-- Populated dynamically via AJAX -->
                    </div>

                    <!-- Notes Input -->
                    <div class="mt-4 pt-3 border-top">
                        <label for="scoringNotes" class="form-label fw-semibold text-dark">
                            Catatan Analisa Scoring (Opsional)
                        </label>
                        <textarea class="form-control" id="scoringNotes" name="notes" rows="2" 
                                  placeholder="Tambahkan catatan khusus analisa scoring debitur ini jika ada..."></textarea>
                    </div>
                    <input type="hidden" id="cutoffPassingScore" value="350.00">
                </div>
            </div>
            <div class="wizard-actions">
                <button type="button" class="btn btn-default" data-wizard-prev>
                    <i class="fa fa-arrow-left me-1"></i> Sebelumnya
                </button>
                <button type="button" class="btn btn-theme" data-wizard-next id="btnCreditNext3">
                    Berikutnya <i class="fa fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        <!-- 4. Ringkasan data scoring -->
        <div class="col-12 d-none" data-wizard-pane="4">
            <div class="card card-borderless overflow-hidden">
                <div class="card-header bg-gray-900" data-bs-theme="dark">
                    <h4 class="card-header-title text-white mb-0">
                        <iconify-icon icon="solar:clipboard-check-bold-duotone" class="me-1"></iconify-icon>
                        Ringkasan Data Scoring
                    </h4>
                    <span class="badge bg-white bg-opacity-15 text-white">Periksa sebelum simpan</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <div class="text-muted small fw-semibold text-uppercase mb-2">Debitur</div>
                                <div class="fw-bold text-dark" id="sumDebtorName">-</div>
                                <div class="small text-muted mt-1">
                                    CIS: <strong id="sumCisId">-</strong> &bull; NIK: <strong id="sumNik">-</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <div class="text-muted small fw-semibold text-uppercase mb-2">Produk</div>
                                <div class="fw-bold text-dark" id="sumProductName">-</div>
                                <div class="small text-muted mt-1">
                                    Batas layak: <span class="badge bg-primary-subtle text-primary border" id="cutoffPassingScoreDisplay">&ge; 350.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="fw-semibold text-dark mb-2">Rincian Penilaian Parameter</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0" id="sumParamsTable">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Pilihan</th>
                                        <th class="text-end">Skor</th>
                                    </tr>
                                </thead>
                                <tbody id="sumParamsBody">
                                    <tr><td colspan="3" class="text-muted text-center">Belum ada penilaian</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-4 border">
                        <div class="row g-3 align-items-center">
                            <div class="col-12 col-md-4 text-center border-md-end">
                                <div class="text-muted small fw-semibold text-uppercase">Total Skor</div>
                                <div class="display-6 fw-bold text-primary mb-1" id="finalScoreDisplay">0.00</div>
                                <div id="eligibilityStatusContainer">
                                    <span class="badge bg-danger text-white px-3 py-2 rounded-pill fw-bold fs-6" id="eligibilityBadge">
                                        <i class="fa-solid fa-circle-xmark me-1"></i> TIDAK LAYAK
                                    </span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="text-muted small fw-semibold text-uppercase mb-1">Catatan</div>
                                <div class="small text-dark" id="sumNotes">-</div>
                            </div>
                            <div class="col-12 col-md-4 text-end">
                                <button type="button" class="btn btn-theme btn-lg w-100 py-3" id="btnSaveScoring" data-wizard-finish>
                                    <i class="fa fa-floppy-disk me-2"></i> Simpan Scoring Kredit
                                </button>
                                <div class="text-muted small mt-2 text-center" style="font-size: 11px;">
                                    Status kelayakan &amp; detail penilaian disimpan ke sistem
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-actions">
                <button type="button" class="btn btn-default" data-wizard-prev>
                    <i class="fa fa-arrow-left me-1"></i> Sebelumnya
                </button>
                <span class="text-muted small">Pastikan ringkasan benar sebelum menyimpan</span>
            </div>
        </div>
    </div>
</form>
</div>

<!-- Modal: Pilih Supervisi / Pimpinan Unit (Pimunit) -->
<div class="modal fade" id="supervisorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow" style="background-color: #ffffff !important;">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="solar:shield-user-bold-duotone" class="me-1 text-primary"></iconify-icon>
                    Pilih Supervisi / Pimpinan Unit (Pimunit)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    Scoring kredit ini akan dikirimkan untuk proses review dan persetujuan (approval) oleh Pimpinan Unit / Supervisi yang Anda pilih:
                </p>

                <div class="mb-3">
                    <label for="supervisorSelect" class="form-label fw-semibold text-dark">
                        Nama Supervisi / Pimunit <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="supervisorSelect" style="width: 100%;" required>
                        <option value="">-- Pilih Pejabat Supervisi / Pimunit --</option>
                        <?php foreach ($supervisors as $sup) : ?>
                            <option value="<?= esc($sup['id']) ?>">
                                <?= esc($sup['full_name']) ?> (<?= esc($sup['role_name']) ?> - <?= esc($sup['branch_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="alert alert-info py-2 px-3 small rounded-3 mb-0">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    Supervisi yang dipilih akan menerima notifikasi in-app untuk meninjau dan memutuskan approval pengajuan ini.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-theme" id="btnConfirmSendSupervisor">
                    <i class="fa fa-paper-plane me-1"></i> Kirim ke Supervisi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    let currentParameters = [];
    let currentDebtorId = null;
    let currentProductId = null;
    let currentMappingId = null;

    // Initialize Select2
    if ($.fn.select2) {
        $('#debtorSelect').select2({
            placeholder: '-- Cari berdasarkan CIS ID, NIK, atau Nama Debitur --',
            allowClear: true
        });

        $('#productSelect').select2({
            placeholder: '-- Pilih Kode Produk Kredit --',
            allowClear: true
        });

        $('#supervisorSelect').select2({
            placeholder: '-- Pilih Pejabat Supervisi / Pimunit --',
            dropdownParent: $('#supervisorModal')
        });
    }

    // When Debitur is chosen
    $('#debtorSelect').on('change', function () {
        const debtorId = $(this).val();
        currentDebtorId = debtorId;

        if (!debtorId) {
            $('#debtorDetailPanel').addClass('d-none');
            return;
        }

        const debtorData = $(this).find(':selected').data('debtor');
        if (debtorData) {
            $('#dispDebtorName').text(debtorData.full_name);
            $('#dispCisId').text(debtorData.cis_id || '-');
            $('#dispCifId').text(debtorData.cif_id || '-');

            $('#debNik').val(debtorData.nik || '');
            $('#debFullName').val(debtorData.full_name || '');
            $('#debPhone').val(debtorData.phone || '');
            $('#debGender').val(debtorData.gender || '');
            $('#debReligion').val(debtorData.religion || '');
            $('#debBirthDate').val(debtorData.birth_date || '');
            $('#debBirthPlace').val(debtorData.birth_place || '');
            $('#debNpwp').val(debtorData.npwp || '');
            $('#debAddress').val(debtorData.address || '');

            $('#debtorDetailPanel').removeClass('d-none');
        }
    });

    // Wizard step guards
    $('#creditWizard').on('wizard:beforeNext', function (e, step) {
        if (step === 1 && !currentDebtorId) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Debitur Belum Dipilih', text: 'Silakan pilih debitur terlebih dahulu.', confirmButtonColor: '#0c2b6b' });
            return;
        }
        if (step === 2 && !currentProductId) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Produk Belum Dipilih', text: 'Silakan pilih kode produk kredit terlebih dahulu.', confirmButtonColor: '#0c2b6b' });
            return;
        }
        if (step === 3) {
            const totalParams = currentParameters.length;
            const selectedParams = $('.param-radio:checked').length;
            if (!totalParams || selectedParams < totalParams) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Penilaian Belum Lengkap',
                    text: 'Harap lengkapi pilihan untuk seluruh parameter yang tersedia.',
                    confirmButtonColor: '#0c2b6b'
                });
            }
        }
    });

    function fillScoringSummary() {
        $('#sumDebtorName').text($('#debFullName').val() || $('#dispDebtorName').text() || '-');
        $('#sumCisId').text($('#dispCisId').text() || '-');
        $('#sumNik').text($('#debNik').val() || '-');
        const prodLabel = $('#productSelect option:selected').text().trim() || '-';
        $('#sumProductName').text(prodLabel);
        const notes = ($('#scoringNotes').val() || '').trim();
        $('#sumNotes').text(notes || '(Tidak ada catatan)');

        const rows = [];
        $('.param-radio:checked').each(function () {
            const name = $(this).data('param-name') || '-';
            const code = $(this).data('code') || '';
            const desc = $(this).data('desc') || '';
            const total = parseFloat($(this).data('total')) || 0;
            rows.push(
                '<tr>' +
                '<td class="fw-semibold">' + $('<div>').text(name).html() + '</td>' +
                '<td><span class="badge bg-secondary-subtle text-secondary me-1">' + $('<div>').text(String(code)).html() + '</span>' +
                $('<div>').text(desc).html() + '</td>' +
                '<td class="text-end fw-bold">' + total.toFixed(2) + '</td>' +
                '</tr>'
            );
        });
        $('#sumParamsBody').html(rows.length ? rows.join('') : '<tr><td colspan="3" class="text-muted text-center">Belum ada penilaian</td></tr>');
        calculateTotalScore();
    }

    $('#creditWizard').on('wizard:step', function (e, step) {
        if (step === 4) {
            fillScoringSummary();
        }
    });

    // When Product is chosen -> Load mapped parameters
    $('#productSelect').on('change', function () {
        const productId = $(this).val();
        currentProductId = productId;
        currentMappingId = $(this).find(':selected').data('mapping-id');

        if (!productId) {
            $('#parameterContainer').empty();
            return;
        }

        $('#parameterContainer').html('<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary mb-2"></i><p class="text-muted small">Memuat parameter mapping produk...</p></div>');

        $.get('<?= site_url('scoring/credit/mapping-items/') ?>' + productId, function (res) {
            if (res.rcode === '00' && res.result && res.result.parameters) {
                currentParameters = res.result.parameters;
                currentMappingId = res.result.mapping_id;
                // Load passing_score from product mapping (read-only on transaction)
                const cutoffVal = res.result.passing_score
                    ? parseFloat(res.result.passing_score).toFixed(2)
                    : '350.00';
                $('#cutoffPassingScore').val(cutoffVal);
                $('#cutoffPassingScoreDisplay').html('&ge; ' + cutoffVal);
                renderParameters(res.result.parameters);
            } else {
                $('#parameterContainer').html('<div class="alert alert-warning">Tidak ada parameter mapping aktif untuk produk ini.</div>');
            }
        }).fail(function () {
            $('#parameterContainer').html('<div class="alert alert-danger">Gagal memuat parameter mapping.</div>');
        });
    });

    // Render parameters and sub-parameters
    function renderParameters(parameters) {
        const $cont = $('#parameterContainer');
        $cont.empty();

        if (parameters.length === 0) {
            $cont.html('<div class="alert alert-warning">Produk ini belum memiliki mapping parameter.</div>');
            return;
        }

        parameters.forEach((param, pIdx) => {
            let optionsHtml = '';
            param.sub_parameters.forEach((sub, sIdx) => {
                const isFirst = (sIdx === 0) ? 'checked' : '';
                optionsHtml += `
                    <div class="col-md-6 col-lg-4">
                        <label class="param-choice-card border rounded-3 p-3 w-100 d-block cursor-pointer position-relative ${sIdx === 0 ? 'border-primary bg-primary-subtle' : 'bg-white'}">
                            <div class="form-check">
                                <input class="form-check-input param-radio" type="radio" 
                                       name="param_${pIdx}" 
                                       id="opt_${pIdx}_${sIdx}" 
                                       value="${sIdx}" 
                                       data-weight="${sub.weight}" 
                                       data-value="${sub.value}" 
                                       data-total="${sub.total}" 
                                       data-param-name="${param.name}"
                                       data-code="${sub.code}"
                                       data-desc="${sub.description}"
                                       ${isFirst}>
                                <label class="form-check-label fw-bold text-dark w-100" for="opt_${pIdx}_${sIdx}">
                                    <span class="badge bg-secondary-subtle text-secondary me-1">${sub.code}</span>
                                    ${sub.description}
                                </label>
                            </div>
                            <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center text-muted" style="font-size: 12px;">
                                <span>Bobot: <strong>${parseFloat(sub.weight)}</strong> &bull; Nilai: <strong>${parseFloat(sub.value)}</strong></span>
                                <span class="badge bg-primary text-white">Skor: ${parseFloat(sub.total)}</span>
                            </div>
                        </label>
                    </div>
                `;
            });

            const cardHtml = `
                <div class="card border rounded-3 overflow-hidden shadow-none mb-2">
                    <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                        <div class="fw-bold text-dark">
                            <i class="fa-solid fa-circle-dot text-primary me-2"></i> ${param.name}
                        </div>
                        <span class="badge bg-white text-muted border px-2 py-1 small">Wajib Dipilih</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            ${optionsHtml}
                        </div>
                    </div>
                </div>
            `;
            $cont.append(cardHtml);
        });

        calculateTotalScore();
    }

    // Listen to parameter selection change
    $(document).on('change', '.param-radio', function () {
        const $parent = $(this).closest('.card-body');
        $parent.find('.param-choice-card').removeClass('border-primary bg-primary-subtle').addClass('bg-white');
        $(this).closest('.param-choice-card').addClass('border-primary bg-primary-subtle').removeClass('bg-white');
        calculateTotalScore();
    });

    // Calculate total score & live eligibility status (LAYAK jika >= cutoff, TIDAK LAYAK jika < cutoff)
    function calculateTotalScore() {
        let total = 0;
        $('.param-radio:checked').each(function () {
            total += (parseFloat($(this).data('total')) || 0);
        });
        const formatted = total.toFixed(2);
        $('#totalScoreBadge').text(formatted);
        $('#finalScoreDisplay').text(formatted);

        const cutoff = parseFloat($('#cutoffPassingScore').val()) || 350.0;
        $('#topCutoffBadge').html(`<i class="fa-solid fa-award me-1"></i>Batas: &ge; ${cutoff.toFixed(2)}`);

        const isLayak = total >= cutoff;
        if (isLayak) {
            $('#eligibilityBadge').removeClass('bg-danger').addClass('bg-success')
                .html('<i class="fa-solid fa-circle-check me-1"></i> LAYAK');
            $('#topEligibilityBadge').removeClass('bg-danger').addClass('bg-success')
                .html('<i class="fa-solid fa-circle-check me-1"></i> LAYAK');
        } else {
            $('#eligibilityBadge').removeClass('bg-success').addClass('bg-danger')
                .html('<i class="fa-solid fa-circle-xmark me-1"></i> TIDAK LAYAK');
            $('#topEligibilityBadge').removeClass('bg-success').addClass('bg-danger')
                .html('<i class="fa-solid fa-circle-xmark me-1"></i> TIDAK LAYAK');
        }

        return total;
    }

    // Collect all data to save (passing_score resolved server-side from product mapping)
    function collectData(sendToSupervisor, supervisorId) {
        const details = [];
        $('.param-radio:checked').each(function () {
            details.push({
                parameter_name: $(this).data('param-name'),
                sub_parameter_code: $(this).data('code'),
                sub_parameter_desc: $(this).data('desc'),
                weight: parseFloat($(this).data('weight')) || 0,
                value: parseFloat($(this).data('value')) || 0,
                total: parseFloat($(this).data('total')) || 0
            });
        });

        const total = calculateTotalScore();

        return {
            debtor_id: parseInt(currentDebtorId),
            debtor_data: {
                nik: $('#debNik').val(),
                full_name: $('#debFullName').val(),
                phone: $('#debPhone').val(),
                gender: $('#debGender').val(),
                religion: $('#debReligion').val(),
                birth_date: $('#debBirthDate').val(),
                birth_place: $('#debBirthPlace').val(),
                npwp: $('#debNpwp').val(),
                address: $('#debAddress').val()
            },
            product_id: parseInt(currentProductId),
            mapping_id: currentMappingId ? parseInt(currentMappingId) : null,
            total_score: total,
            details: details,
            send_to_supervisor: !!sendToSupervisor,
            supervisor_id: supervisorId ? parseInt(supervisorId) : null,
            notes: $('#scoringNotes').val()
        };
    }

    // Save Execution AJAX
    function executeSave(sendToSupervisor, supervisorId) {
        const payload = collectData(sendToSupervisor, supervisorId);

        Swal.fire({
            title: 'Menyimpan Scoring...',
            text: 'Harap tunggu beberapa saat.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '<?= site_url('scoring/credit/save') ?>',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
            },
            success: function (res) {
                if (res.rcode === '00') {
                    $('#supervisorModal').modal('hide');
                    const isLayak = (res.result.eligibility_status === 'LAYAK');
                    const badgeClass = isLayak ? 'bg-success text-white' : 'bg-danger text-white';
                    const badgeIcon = isLayak ? 'fa-circle-check' : 'fa-circle-xmark';
                    Swal.fire({
                        icon: isLayak ? 'success' : 'warning',
                        title: 'Berhasil Disimpan!',
                        html: `
                            <p class="mb-2">${res.message}</p>
                            <div class="p-3 bg-light rounded-3 text-start small">
                                <div><strong>Nomor Scoring:</strong> ${res.result.scoring_no}</div>
                                <div><strong>Total Skor:</strong> <span class="text-primary fw-bold">${res.result.total_score}</span> (Batas Minimal: &ge; ${res.result.passing_score})</div>
                                <div class="mt-1"><strong>Hasil Kelayakan:</strong> <span class="badge ${badgeClass} fs-6 px-2 py-1"><i class="fa-solid ${badgeIcon} me-1"></i>${res.result.eligibility_status}</span></div>
                                <div class="mt-1"><strong>Status:</strong> <span class="badge ${sendToSupervisor ? 'bg-warning text-dark' : 'bg-secondary text-white'}">${res.result.status}</span></div>
                            </div>
                        `,
                        confirmButtonColor: '#0c2b6b',
                        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Selesai'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: res.message || 'Terjadi kesalahan sistem.',
                        confirmButtonColor: '#0c2b6b'
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Jaringan',
                    text: 'Tidak dapat menghubungi server.',
                    confirmButtonColor: '#0c2b6b'
                });
            }
        });
    }

    // Click "Simpan Scoring Kredit"
    $('#btnSaveScoring').on('click', function () {
        if (!currentDebtorId) {
            Swal.fire({
                icon: 'warning',
                title: 'Debitur Belum Dipilih',
                text: 'Silakan pilih debitur terlebih dahulu.',
                confirmButtonColor: '#0c2b6b'
            });
            return;
        }

        if (!currentProductId) {
            Swal.fire({
                icon: 'warning',
                title: 'Produk Belum Dipilih',
                text: 'Silakan pilih kode produk kredit terlebih dahulu.',
                confirmButtonColor: '#0c2b6b'
            });
            return;
        }

        const totalParams = currentParameters.length;
        const selectedParams = $('.param-radio:checked').length;
        if (totalParams > 0 && selectedParams < totalParams) {
            Swal.fire({
                icon: 'warning',
                title: 'Penilaian Belum Lengkap',
                text: 'Harap lengkapi pilihan untuk seluruh parameter yang tersedia.',
                confirmButtonColor: '#0c2b6b'
            });
            return;
        }

        const total = calculateTotalScore();
        const cutoff = parseFloat($('#cutoffPassingScore').val()) || 350.0;
        const isLayak = total >= cutoff;
        const statusHtml = isLayak
            ? '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>LAYAK</span>'
            : '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i>TIDAK LAYAK</span>';

        // Show prompt: “Total Skor: X (Status). Apakah scoring kredit ini akan dikirim ke Supervisi?”
        Swal.fire({
            title: 'Konfirmasi Simpan',
            html: `<div class="mb-2">Total Skor: <strong>${total.toFixed(2)}</strong> (Batas: &ge; ${cutoff.toFixed(2)}) &bull; Status: ${statusHtml}</div><div>Apakah scoring kredit ini akan dikirim ke Supervisi?</div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0c2b6b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-paper-plane me-1"></i> Ya, Kirim ke Supervisi',
            cancelButtonText: '<i class="fa-solid fa-floppy-disk me-1"></i> Tidak (Simpan Saja)',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // If Ya: show Supervisi / Pimpinan Unit selection modal
                setTimeout(function () {
                    $('#supervisorModal').modal('show');
                }, 250);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // If Tidak: save without sending to supervisor
                executeSave(false, null);
            }
        });
    });

    // Confirm sending to supervisor from modal
    $('#btnConfirmSendSupervisor').on('click', function () {
        const supervisorId = $('#supervisorSelect').val();
        if (!supervisorId) {
            Swal.fire({
                icon: 'warning',
                title: 'Supervisi Belum Dipilih',
                text: 'Silakan pilih salah satu Pejabat Supervisi / Pimunit yang dituju.',
                confirmButtonColor: '#0c2b6b'
            });
            return;
        }

        executeSave(true, supervisorId);
    });
});
</script>

<?= view('partials/shell_end') ?>
