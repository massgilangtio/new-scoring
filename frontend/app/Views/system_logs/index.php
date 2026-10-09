<?php
$total = (int) ($total ?? 0);
$stats = $stats ?? [];
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Log Sistem & Error']) ?>

<?= view('partials/kpi_solid', ['items' => [
    ['label' => 'Total Log Permintaan', 'value' => (int) ($stats['total'] ?? $total), 'sub' => 'Seluruh riwayat HTTP API', 'tone' => 'indigo', 'icon' => 'solar:server-square-bold-duotone', 'id' => 'kpiTotal'],
    ['label' => 'Sukses (2xx)', 'value' => (int) ($stats['success'] ?? 0), 'sub' => 'Permintaan berhasil diproses', 'tone' => 'teal', 'icon' => 'solar:check-circle-bold-duotone', 'id' => 'kpiSuccess'],
    ['label' => 'Client Error (4xx)', 'value' => (int) ($stats['client_error'] ?? 0), 'sub' => 'Validasi, 401 unauth, 404', 'tone' => 'orange', 'icon' => 'solar:danger-triangle-bold-duotone', 'id' => 'kpiClientError'],
    ['label' => 'Bug & Fatal Error (5xx)', 'value' => (int) ($stats['server_error'] ?? 0), 'sub' => 'Exception server & crash log', 'tone' => 'red', 'icon' => 'solar:bug-bold-duotone', 'id' => 'kpiServerError'],
]]) ?>

<div class="card card-borderless mb-3 filter-card shadow-sm">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0 d-flex align-items-center">
            <iconify-icon icon="solar:filter-bold-duotone" class="me-2 fs-5 text-warning"></iconify-icon>
            Filter &amp; Pencarian Log Sistem &amp; Error
        </h4>
        <div class="d-flex align-items-center gap-1">
            <div class="btn-group btn-group-xs me-2 d-none d-lg-inline-flex" role="group">
                <button type="button" class="btn btn-outline-light btn-xs btn-quick-date" data-range="today">Hari Ini</button>
                <button type="button" class="btn btn-outline-light btn-xs btn-quick-date" data-range="yesterday">Kemarin</button>
                <button type="button" class="btn btn-outline-light btn-xs btn-quick-date" data-range="7days">7 Hari</button>
                <button type="button" class="btn btn-outline-light btn-xs btn-quick-date" data-range="30days">30 Hari</button>
                <button type="button" class="btn btn-outline-light btn-xs btn-quick-date active" data-range="all">Semua</button>
            </div>
            <?= view('partials/filter_header_btn') ?>
        </div>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body filter-card-body p-3 bg-light bg-opacity-50">
            <div class="row g-2 mb-2">
                <div class="col-12 col-md-6 col-lg-5">
                    <label class="form-label small fw-bold mb-1" for="filterSearch">
                        <i class="fa fa-search me-1 text-primary"></i> Cari Cepat (Endpoint, Method, User/NPP, IP, Pesan Error)
                    </label>
                    <div class="input-group input-group-sm flex-nowrap">
                        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Ketik kata kunci pencarian...">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-3">
                    <label class="form-label small fw-bold mb-1" for="filterDateFrom">
                        <i class="fa fa-calendar-alt me-1 text-teal"></i> Tanggal Mulai
                    </label>
                    <input type="date" id="filterDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-lg-3">
                    <label class="form-label small fw-bold mb-1" for="filterDateTo">
                        <i class="fa fa-calendar-check me-1 text-teal"></i> Tanggal Akhir
                    </label>
                    <input type="date" id="filterDateTo" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-lg-1 d-flex align-items-end">
                    <button type="button" id="btnFilterApply" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">
                        <i class="fa fa-filter me-1"></i> Terapkan
                    </button>
                </div>
            </div>

            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-bold mb-1" for="filterLevel">
                        <i class="fa fa-layer-group me-1 text-indigo"></i> Status / Kategori
                    </label>
                    <select id="filterLevel" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        <option value="ERROR">🔥 Bug &amp; Fatal Error (5xx)</option>
                        <option value="WARNING">⚠️ Client Error (4xx)</option>
                        <option value="SUCCESS">✅ Sukses (2xx)</option>
                        <option value="INFO">ℹ️ Info / Redirect (3xx)</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-bold mb-1" for="filterMethod">
                        <i class="fa fa-code-branch me-1 text-success"></i> HTTP Method
                    </label>
                    <select id="filterMethod" class="form-select form-select-sm">
                        <option value="">Semua Method</option>
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="PATCH">PATCH</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-bold mb-1" for="filterStatus">
                        <i class="fa fa-tag me-1 text-warning"></i> HTTP Status Code
                    </label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">Semua Status Code</option>
                        <option value="200">200 OK</option>
                        <option value="201">201 Created</option>
                        <option value="400">400 Bad Request</option>
                        <option value="401">401 Unauthorized</option>
                        <option value="403">403 Forbidden</option>
                        <option value="404">404 Not Found</option>
                        <option value="422">422 Unprocessable Entity</option>
                        <option value="500">500 Internal Server Error</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2 d-flex gap-1">
                    <button type="button" id="btnFilterReset" class="btn btn-default btn-sm w-100 fw-semibold" title="Reset filter">
                        <i class="fa fa-undo me-1"></i> Reset
                    </button>
                </div>
                <div class="col-12 col-md-1 d-flex">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" id="btnOpenClearModal" title="Pembersihan Log Berkala">
                        <i class="fa fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-borderless table-card shadow-sm">
    <div class="card-header bg-gray-900" data-bs-theme="dark">
        <h4 class="card-header-title text-white mb-0 d-flex align-items-center">
            <iconify-icon icon="solar:history-bold-duotone" class="me-2 fs-5 text-teal"></iconify-icon>
            Riwayat Log Permintaan, Respon, Error &amp; Bug
        </h4>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 px-2 py-1 small d-none d-md-inline" id="badgeBugAlert">
                <i class="fa fa-bug me-1"></i> Traceback Otomatis Aktif
            </span>
            <span class="badge bg-white bg-opacity-15 text-white px-2 py-1" id="tblCountBadge"><?= esc((string) $total) ?> entri</span>
            <?= view('partials/card_widget_btn') ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive d-none d-md-block">
            <table id="systemLogTable" class="table table-hover table-striped align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width: 155px;">Waktu (WIB)</th>
                        <th style="width: 130px;">Status &amp; Level</th>
                        <th style="width: 80px;">Method</th>
                        <th>Endpoint / Path API</th>
                        <th style="width: 140px;">Pengguna</th>
                        <th style="width: 120px;">IP &amp; Latency</th>
                        <th>Keterangan / Error</th>
                        <th class="text-center" style="width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div id="systemLogMobileList" class="d-md-none p-3">
            <div class="text-center py-4 text-muted small"><i class="fa fa-spinner fa-spin me-1"></i> Memuat data log...</div>
        </div>
    </div>
</div>

<!-- Modal Detail Log -->
<div class="modal fade" id="modalLogDetail" tabindex="-1" aria-labelledby="modalLogDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-gray-900 text-white" data-bs-theme="dark">
                <div class="d-flex align-items-center gap-2">
                    <span id="modalStatusBadge" class="badge bg-danger fs-6 px-2 py-1">500</span>
                    <span id="modalMethodBadge" class="badge bg-dark fs-6 px-2 py-1">POST</span>
                    <h5 class="modal-title mb-0 font-monospace text-truncate" id="modalLogTitle" style="max-width: 600px;">/api/v1/...</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs nav-tabs-v2 px-3 pt-2 bg-light border-bottom" id="logDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold small" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
                            <i class="fa fa-info-circle me-1 text-primary"></i> Ringkasan &amp; Header
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small" id="tab-request-btn" data-bs-toggle="tab" data-bs-target="#tab-request" type="button" role="tab">
                            <i class="fa fa-arrow-circle-right me-1 text-indigo"></i> Request Payload
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small" id="tab-response-btn" data-bs-toggle="tab" data-bs-target="#tab-response" type="button" role="tab">
                            <i class="fa fa-arrow-circle-left me-1 text-teal"></i> Response Data
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small text-danger" id="tab-traceback-btn" data-bs-toggle="tab" data-bs-target="#tab-traceback" type="button" role="tab">
                            <i class="fa fa-bug me-1"></i> Bug &amp; Traceback <span class="badge bg-danger ms-1 d-none" id="tracebackBadge">!</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-3" id="logDetailTabContent">
                    <!-- Tab 1: Overview -->
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card h-100 border">
                                    <div class="card-header bg-light py-2 fw-bold small text-muted">
                                        <i class="fa fa-network-wired me-1"></i> Detail Permintaan
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-striped mb-0 small">
                                            <tbody>
                                                <tr><td class="fw-bold w-30">ID Log</td><td id="m_id" class="font-monospace">-</td></tr>
                                                <tr><td class="fw-bold">Waktu (WIB)</td><td id="m_created_at" class="font-monospace">-</td></tr>
                                                <tr><td class="fw-bold">HTTP Method</td><td id="m_method">-</td></tr>
                                                <tr><td class="fw-bold">Path / Endpoint</td><td id="m_path" class="font-monospace text-break">-</td></tr>
                                                <tr><td class="fw-bold">Status Code</td><td id="m_status_code">-</td></tr>
                                                <tr><td class="fw-bold">Waktu Eksekusi</td><td id="m_latency">-</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border">
                                    <div class="card-header bg-light py-2 fw-bold small text-muted">
                                        <i class="fa fa-user-shield me-1"></i> Klien &amp; Pengguna
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-striped mb-0 small">
                                            <tbody>
                                                <tr><td class="fw-bold w-30">Pengguna / NPP</td><td id="m_username">-</td></tr>
                                                <tr><td class="fw-bold">User ID</td><td id="m_user_id" class="font-monospace">-</td></tr>
                                                <tr><td class="fw-bold">IP Klien</td><td id="m_client_ip" class="font-monospace">-</td></tr>
                                                <tr><td class="fw-bold">User Agent</td><td id="m_user_agent" class="small text-break">-</td></tr>
                                                <tr><td class="fw-bold">Pesan Error</td><td id="m_error_message" class="text-danger fw-semibold">-</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Request Payload -->
                    <div class="tab-pane fade" id="tab-request" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-muted"><i class="fa fa-code me-1"></i> Query Parameters &amp; JSON Request Body</span>
                            <button type="button" class="btn btn-outline-secondary btn-xs btn-copy-json" data-target="m_request_body">
                                <i class="fa fa-copy me-1"></i> Salin JSON
                            </button>
                        </div>
                        <div class="mb-2" id="wrap_query_params">
                            <label class="form-label small fw-bold text-muted">Query String:</label>
                            <pre class="bg-light p-2 rounded small font-monospace mb-0" id="m_query_params">-</pre>
                        </div>
                        <div>
                            <label class="form-label small fw-bold text-muted">Request Body (Data Sensitif Ter-masking):</label>
                            <pre class="bg-dark text-light p-3 rounded small font-monospace overflow-auto" style="max-height: 400px;" id="m_request_body">Tidak ada body</pre>
                        </div>
                    </div>

                    <!-- Tab 3: Response Data -->
                    <div class="tab-pane fade" id="tab-response" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-muted"><i class="fa fa-reply me-1"></i> Data Respons dari Layanan</span>
                            <button type="button" class="btn btn-outline-secondary btn-xs btn-copy-json" data-target="m_response_body">
                                <i class="fa fa-copy me-1"></i> Salin JSON
                            </button>
                        </div>
                        <pre class="bg-dark text-light p-3 rounded small font-monospace overflow-auto" style="max-height: 450px;" id="m_response_body">Tidak ada data respons</pre>
                    </div>

                    <!-- Tab 4: Bug & Traceback -->
                    <div class="tab-pane fade" id="tab-traceback" role="tabpanel">
                        <div class="alert alert-danger d-flex align-items-center justify-content-between mb-3" id="alertTracebackInfo">
                            <div>
                                <i class="fa fa-bug fa-lg me-2"></i>
                                <strong>Penyebab Bug / Exception:</strong>
                                <span id="m_tb_error_msg" class="ms-1 font-monospace">-</span>
                            </div>
                            <button type="button" class="btn btn-danger btn-xs" id="btnCopyTraceback">
                                <i class="fa fa-copy me-1"></i> Salin Stack Trace
                            </button>
                        </div>
                        <label class="form-label small fw-bold text-danger">Python Execution Stack Trace:</label>
                        <pre class="bg-dark text-danger-emphasis bg-opacity-95 p-3 rounded small font-monospace overflow-auto border border-danger border-opacity-50" style="max-height: 450px; background-color: #1a1415 !important; color: #ff8b94 !important;" id="m_traceback">Tidak ada traceback untuk request ini.</pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Pembersihan Log -->
<div class="modal fade" id="modalClearLogs" tabindex="-1" aria-labelledby="modalClearLogsLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalClearLogsLabel"><i class="fa fa-trash-alt me-2"></i> Bersihkan Log Sistem</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Pilih rentang waktu log yang ingin dibersihkan dari basis data untuk menghemat ruang penyimpanan:</p>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Pilihan Pembersihan:</label>
                    <select id="selectClearDays" class="form-select">
                        <option value="90">Hapus log yang lebih lama dari 90 hari</option>
                        <option value="60">Hapus log yang lebih lama dari 60 hari</option>
                        <option value="30" selected>Hapus log yang lebih lama dari 30 hari (Rekomendasi)</option>
                        <option value="7">Hapus log yang lebih lama dari 7 hari</option>
                        <option value="0">Hapus SELURUH riwayat log (0 hari)</option>
                    </select>
                </div>
                <div class="alert alert-warning small mb-0">
                    <i class="fa fa-exclamation-triangle me-1"></i> Tindakan ini tidak dapat dibatalkan. Log yang dihapus akan hilang permanen dari database.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnConfirmClearLogs">
                    <i class="fa fa-trash-alt me-1"></i> Bersihkan Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    var cachedRows = {};

    function pad(n) { return n < 10 ? '0' + n : n; }

    function formatWib(isoStr) {
        if (!isoStr) return '-';
        var d = new Date(isoStr);
        if (isNaN(d.getTime())) return isoStr;
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' +
               pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }

    function escHtml(str) {
        if (str == null) return '';
        return $('<div>').text(str).html();
    }

    function getStatusBadge(code, level) {
        var cls = 'bg-secondary';
        var icon = '';
        var codeNum = parseInt(code, 10);

        if (codeNum >= 500 || level === 'ERROR') {
            cls = 'bg-danger';
            icon = '<i class="fa fa-bug me-1"></i>';
        } else if (codeNum >= 400 || level === 'WARNING') {
            cls = 'bg-warning text-dark';
            icon = '<i class="fa fa-exclamation-triangle me-1"></i>';
        } else if (codeNum >= 200 || level === 'SUCCESS') {
            cls = 'bg-success';
            icon = '<i class="fa fa-check me-1"></i>';
        } else {
            cls = 'bg-info text-dark';
            icon = '<i class="fa fa-info me-1"></i>';
        }
        return '<span class="badge ' + cls + ' font-monospace px-2 py-1">' + icon + codeNum + '</span>';
    }

    function getMethodBadge(method) {
        var m = (method || 'GET').toUpperCase();
        var cls = 'bg-primary';
        if (m === 'POST') cls = 'bg-teal text-white';
        else if (m === 'PUT') cls = 'bg-warning text-dark';
        else if (m === 'PATCH') cls = 'bg-indigo text-white';
        else if (m === 'DELETE') cls = 'bg-danger text-white';
        return '<span class="badge ' + cls + ' font-monospace px-2 py-1">' + m + '</span>';
    }

    var dataTable = App.initDT('#systemLogTable', {
        serverSide: true,
        processing: true,
        searching: false,
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: '<?= site_url('system-logs/datatables') ?>',
            type: 'GET',
            data: function (d) {
                d.filterSearch = $('#filterSearch').val();
                d.filterDateFrom = $('#filterDateFrom').val();
                d.filterDateTo = $('#filterDateTo').val();
                d.filterLevel = $('#filterLevel').val();
                d.filterMethod = $('#filterMethod').val();
                d.filterStatus = $('#filterStatus').val();
            },
            dataSrc: function (json) {
                var stats = json.stats || {};
                $('#kpiTotal').text(stats.total != null ? stats.total : (json.recordsTotal || 0));
                if (stats.success != null) $('#kpiSuccess').text(stats.success);
                if (stats.client_error != null) $('#kpiClientError').text(stats.client_error);
                if (stats.server_error != null) $('#kpiServerError').text(stats.server_error);
                $('#tblCountBadge').text((json.recordsFiltered || 0) + ' entri');

                var data = json.data || [];
                data.forEach(function (row) {
                    cachedRows[row.id] = row;
                });
                renderMobileList(data);
                return data;
            }
        },
        columns: [
            // 0. Waktu
            {
                data: 'created_at',
                className: 'small text-nowrap',
                render: function (d) {
                    return '<div class="fw-bold text-dark font-monospace">' + formatWib(d) + '</div>';
                }
            },
            // 1. Status & Level
            {
                data: null,
                render: function (row) {
                    return getStatusBadge(row.status_code, row.level);
                }
            },
            // 2. Method
            {
                data: 'method',
                render: function (m) {
                    return getMethodBadge(m);
                }
            },
            // 3. Path API
            {
                data: null,
                render: function (row) {
                    var pathHtml = '<span class="fw-bold font-monospace text-dark">' + escHtml(row.path) + '</span>';
                    if (row.query_params) {
                        pathHtml += '<div class="small text-muted font-monospace text-truncate" style="max-width:320px;" title="' + escHtml(row.query_params) + '">?' + escHtml(row.query_params) + '</div>';
                    }
                    return pathHtml;
                }
            },
            // 4. Pengguna
            {
                data: null,
                render: function (row) {
                    if (row.username) {
                        return '<div class="small fw-bold text-dark"><i class="fa fa-user me-1 text-primary"></i>' + escHtml(row.username) + '</div>' +
                               '<div class="small text-muted font-monospace">ID: ' + (row.user_id || '-') + '</div>';
                    }
                    return '<span class="badge bg-light text-muted border">Tamu / Sistem</span>';
                }
            },
            // 5. IP & Latency
            {
                data: null,
                className: 'small font-monospace',
                render: function (row) {
                    var ms = row.execution_time_ms != null ? parseFloat(row.execution_time_ms).toFixed(1) + ' ms' : '-';
                    var ip = row.client_ip || '-';
                    var latencyBadge = '<span class="badge bg-light text-dark border">' + ms + '</span>';
                    return '<div class="text-truncate" style="max-width:120px;" title="' + escHtml(ip) + '">' + escHtml(ip) + '</div><div>' + latencyBadge + '</div>';
                }
            },
            // 6. Keterangan / Error
            {
                data: null,
                render: function (row) {
                    if (row.error_message) {
                        var bugIcon = row.has_traceback ? '<span class="badge bg-danger text-white me-1"><i class="fa fa-bug"></i> Bug</span>' : '';
                        return '<div class="text-danger small fw-semibold text-truncate" style="max-width:240px;" title="' + escHtml(row.error_message) + '">' +
                               bugIcon + escHtml(row.error_message) + '</div>';
                    }
                    return '<span class="small text-success"><i class="fa fa-check-circle me-1"></i> Sukses</span>';
                }
            },
            // 7. Aksi
            {
                data: 'id',
                className: 'text-center',
                orderable: false,
                render: function (id) {
                    return '<button type="button" class="btn btn-outline-primary btn-xs btn-view-log shadow-sm" data-id="' + id + '" title="Lihat Detail Log">' +
                           '<i class="fa fa-eye me-1"></i> Detail</button>';
                }
            }
        ]
    });

    function renderMobileList(items) {
        var $list = $('#systemLogMobileList').empty();
        if (!items || items.length === 0) {
            $list.html('<div class="text-center py-4 text-muted small">Tidak ada catatan log ditemukan</div>');
            return;
        }

        items.forEach(function (row) {
            var card = $(
                '<div class="card border mb-2 shadow-sm rounded-3">' +
                '  <div class="card-body p-3">' +
                '    <div class="d-flex justify-content-between align-items-center mb-2">' +
                '      <div>' + getStatusBadge(row.status_code, row.level) + ' ' + getMethodBadge(row.method) + '</div>' +
                '      <div class="small text-muted font-monospace">' + formatWib(row.created_at) + '</div>' +
                '    </div>' +
                '    <div class="fw-bold font-monospace small text-dark mb-1 text-break">' + escHtml(row.path) + '</div>' +
                (row.error_message ? '<div class="small text-danger fw-semibold mb-2 text-break"><i class="fa fa-exclamation-triangle me-1"></i>' + escHtml(row.error_message) + '</div>' : '') +
                '    <div class="d-flex justify-content-between align-items-center pt-2 border-top">' +
                '      <div class="small text-muted">' + (row.username ? '<i class="fa fa-user me-1"></i>' + escHtml(row.username) : 'Tamu') + ' · ' + (row.execution_time_ms || 0) + 'ms</div>' +
                '      <button type="button" class="btn btn-primary btn-xs btn-view-log" data-id="' + row.id + '"><i class="fa fa-eye me-1"></i> Detail</button>' +
                '    </div>' +
                '  </div>' +
                '</div>'
            );
            $list.append(card);
        });
    }

    function reload() {
        if (dataTable) dataTable.ajax.reload();
    }

    // Detail Button click handler
    $(document).on('click', '.btn-view-log', function () {
        var id = $(this).data('id');
        var cached = cachedRows[id];

        // Reset Modal Tabs
        $('#tab-overview-btn').tab('show');

        // Loading state
        $('#modalLogTitle').text('Memuat ID #' + id + '...');
        $('#modalLogDetail').modal('show');

        $.getJSON('<?= site_url('system-logs/detail') ?>/' + id, function (res) {
            if (res.rcode !== '00') {
                alert(res.message || 'Gagal memuat detail log');
                return;
            }
            var d = res.result;

            // Header Info
            $('#modalLogTitle').text(d.path);
            $('#modalStatusBadge').text(d.status_code).removeClass('bg-success bg-warning bg-danger bg-info');
            if (d.status_code >= 500) $('#modalStatusBadge').addClass('bg-danger');
            else if (d.status_code >= 400) $('#modalStatusBadge').addClass('bg-warning text-dark');
            else if (d.status_code >= 200) $('#modalStatusBadge').addClass('bg-success');
            else $('#modalStatusBadge').addClass('bg-info text-dark');

            $('#modalMethodBadge').text(d.method);

            // Tab 1: Overview
            $('#m_id').text('#' + d.id);
            $('#m_created_at').text(formatWib(d.created_at));
            $('#m_method').html(getMethodBadge(d.method));
            $('#m_path').text(d.path);
            $('#m_status_code').html(getStatusBadge(d.status_code, d.level));
            $('#m_latency').text(d.execution_time_ms + ' ms');

            $('#m_username').text(d.username || 'Tamu / Tanpa Login');
            $('#m_user_id').text(d.user_id ? '#' + d.user_id : '-');
            $('#m_client_ip').text(d.client_ip || '-');
            $('#m_user_agent').text(d.user_agent || '-');
            $('#m_error_message').text(d.error_message || 'Tidak ada error (Sukses)');

            // Tab 2: Request Payload
            if (d.query_params) {
                $('#wrap_query_params').show();
                $('#m_query_params').text(d.query_params);
            } else {
                $('#wrap_query_params').hide();
            }

            if (d.request_body) {
                $('#m_request_body').text(typeof d.request_body === 'object' ? JSON.stringify(d.request_body, null, 2) : d.request_body);
            } else {
                $('#m_request_body').text('Tidak ada request body (GET / tanpa payload)');
            }

            // Tab 3: Response Body
            if (d.response_body) {
                $('#m_response_body').text(typeof d.response_body === 'object' ? JSON.stringify(d.response_body, null, 2) : d.response_body);
            } else {
                $('#m_response_body').text('Tidak ada response body tersimpan');
            }

            // Tab 4: Traceback & Bug
            if (d.traceback) {
                $('#tracebackBadge').removeClass('d-none');
                $('#alertTracebackInfo').show();
                $('#m_tb_error_msg').text(d.error_message || 'Unhandled Exception');
                $('#m_traceback').text(d.traceback);
            } else {
                $('#tracebackBadge').addClass('d-none');
                $('#alertTracebackInfo').hide();
                $('#m_traceback').text('Tidak ada stack trace exception untuk permintaan ini.');
            }
        }).fail(function () {
            alert('Terjadi kesalahan saat memanggil API detail log');
        });
    });

    // Copy JSON / Traceback buttons
    $('.btn-copy-json').on('click', function () {
        var targetId = $(this).data('target');
        var text = $('#' + targetId).text();
        navigator.clipboard.writeText(text).then(function () {
            alert('Berhasil disalin ke papan klip!');
        });
    });

    $('#btnCopyTraceback').on('click', function () {
        var text = $('#m_traceback').text();
        navigator.clipboard.writeText(text).then(function () {
            alert('Stack trace bug berhasil disalin ke papan klip!');
        });
    });

    // Quick Date Range buttons
    $('.btn-quick-date').on('click', function () {
        $('.btn-quick-date').removeClass('active');
        $(this).addClass('active');
        var range = $(this).data('range');
        var now = new Date();
        var formatYmd = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };

        if (range === 'today') {
            var todayStr = formatYmd(now);
            $('#filterDateFrom').val(todayStr);
            $('#filterDateTo').val(todayStr);
        } else if (range === 'yesterday') {
            var yest = new Date();
            yest.setDate(yest.getDate() - 1);
            var yestStr = formatYmd(yest);
            $('#filterDateFrom').val(yestStr);
            $('#filterDateTo').val(yestStr);
        } else if (range === '7days') {
            var past7 = new Date();
            past7.setDate(past7.getDate() - 6);
            $('#filterDateFrom').val(formatYmd(past7));
            $('#filterDateTo').val(formatYmd(now));
        } else if (range === '30days') {
            var past30 = new Date();
            past30.setDate(past30.getDate() - 29);
            $('#filterDateFrom').val(formatYmd(past30));
            $('#filterDateTo').val(formatYmd(now));
        } else {
            $('#filterDateFrom').val('');
            $('#filterDateTo').val('');
        }
        reload();
    });

    $('#btnFilterApply').on('click', reload);

    $('#btnFilterReset').on('click', function () {
        $('#filterSearch').val('');
        $('#filterDateFrom').val('');
        $('#filterDateTo').val('');
        $('#filterLevel').val('');
        $('#filterMethod').val('');
        $('#filterStatus').val('');
        $('.btn-quick-date').removeClass('active');
        $('.btn-quick-date[data-range="all"]').addClass('active');
        reload();
    });

    $('#filterSearch').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            reload();
        }
    });

    $('#filterLevel, #filterMethod, #filterStatus, #filterDateFrom, #filterDateTo').on('change', reload);

    // Clear logs modal & action
    $('#btnOpenClearModal').on('click', function () {
        $('#modalClearLogs').modal('show');
    });

    $('#btnConfirmClearLogs').on('click', function () {
        var days = $('#selectClearDays').val();
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Membersihkan...');

        $.post('<?= site_url('system-logs/clear') ?>', { days: days }, function (res) {
            $('#modalClearLogs').modal('hide');
            $btn.prop('disabled', false).html('<i class="fa fa-trash-alt me-1"></i> Bersihkan Sekarang');
            alert(res.message || 'Log berhasil dibersihkan');
            reload();
        }).fail(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-trash-alt me-1"></i> Bersihkan Sekarang');
            alert('Gagal membersihkan log');
        });
    });
});
</script>

<?= view('partials/shell_end') ?>
