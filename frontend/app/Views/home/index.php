<?php
$permissions = $profile['permissions'] ?? [];
$can = static function (string $code) use ($permissions): bool {
    return in_array($code, $permissions, true);
};
$summary = $dashboard['summary'] ?? ['total' => 0, 'in_progress' => 0, 'approved' => 0, 'rejected' => 0];
$changes = $dashboard['changes'] ?? [];
$trend = $dashboard['trend'] ?? [];
$products = $dashboard['products'] ?? [];
$recent = $dashboard['recent'] ?? [];
$notes = $notes ?? [];
$query = trim((string) ($_GET['q'] ?? ''));
if ($query !== '') {
    $needle = mb_strtolower($query);
    $recent = array_values(array_filter($recent, static function (array $item) use ($needle): bool {
        $haystack = mb_strtolower($item['transaction_no'] . ' ' . $item['debtor_name'] . ' ' . $item['product_name']);

        return str_contains($haystack, $needle);
    }));
}
$stages = [
    'draft' => 'Pengisian',
    'submitted' => 'Menunggu keputusan',
    'waiting_for_approver_assignment' => 'Belum ditugaskan',
    'approved' => 'Selesai',
    'returned' => 'Perlu perbaikan',
    'rejected' => 'Ditolak',
];
$statusBadge = [
    'draft' => 'bg-secondary bg-opacity-15 text-secondary',
    'submitted' => 'bg-info bg-opacity-15 text-info',
    'waiting_for_approver_assignment' => 'bg-info bg-opacity-15 text-info',
    'approved' => 'bg-success bg-opacity-15 text-success',
    'returned' => 'bg-warning bg-opacity-15 text-warning',
    'rejected' => 'bg-danger bg-opacity-15 text-danger',
];
$monthNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$period = (string) ($dashboard['period'] ?? '');
$periodOptions = ['' => 'Semua periode'];
$cursor = new DateTimeImmutable('first day of this month');
for ($i = 0; $i < 6; $i++) {
    $periodOptions[$cursor->format('Y-m')] = $monthNames[(int) $cursor->format('n')] . ' ' . $cursor->format('Y');
    $cursor = $cursor->modify('-1 month');
}
$changeText = static function ($value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $number = (int) $value;
    $sign = $number >= 0 ? '+' : '';

    return $sign . $number . '% dibanding bulan lalu';
};
$ago = static function (string $value): string {
    $stamp = strtotime($value);
    if ($stamp === false) {
        return '';
    }
    $diff = max(0, time() - $stamp);
    if ($diff < 3600) {
        return max(1, (int) floor($diff / 60)) . ' menit lalu';
    }
    if ($diff < 86400) {
        return (int) floor($diff / 3600) . ' jam lalu';
    }

    return (int) floor($diff / 86400) . ' hari lalu';
};
$cards = [
    ['key' => 'total', 'label' => 'Total Pengajuan', 'bg' => 'bg-blue', 'icon' => 'solar:document-bold-duotone', 'href' => site_url('transactions')],
    ['key' => 'in_progress', 'label' => 'Dalam Proses', 'bg' => 'bg-orange', 'icon' => 'solar:clock-circle-bold-duotone', 'href' => site_url('approvals')],
    ['key' => 'approved', 'label' => 'Disetujui', 'bg' => 'bg-teal', 'icon' => 'solar:check-circle-bold-duotone', 'href' => site_url('reports/scoring')],
    ['key' => 'rejected', 'label' => 'Ditolak', 'bg' => 'bg-red', 'icon' => 'solar:close-circle-bold-duotone', 'href' => site_url('reports/scoring')],
];
$trendMax = 1;
foreach ($trend as $point) {
    $trendMax = max($trendMax, (int) $point['applications'], (int) $point['approved']);
}
$line = [];
$barSlots = count($trend);
foreach ($trend as $index => $point) {
    $x = $barSlots === 1 ? 160 : 28 + ($index * (280 / max(1, $barSlots - 1)));
    $y = 128 - ((int) $point['applications'] / $trendMax) * 100;
    $line[] = round($x, 1) . ',' . round($y, 1);
}
$productColors = ['bg-blue', 'bg-teal', 'bg-orange', 'bg-red', 'bg-indigo'];
$productBar = ['#0c83f0', '#00acac', '#f59c1a', '#ff5b57', '#8753de'];
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Dasbor', 'isDashboard' => true]) ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>

<!-- BEGIN row — KPI (Color Admin index pattern) -->
<div class="row mb-3">
    <?php foreach ($cards as $i => $card) : ?>
        <?php
        $delta = $changes[$card['key']] ?? null;
        $mt = $i === 0 ? '' : ($i < 2 ? ' mt-3 mt-md-0' : ' mt-3 mt-xl-0');
        ?>
        <div class="col-xl-3 col-md-6<?= $mt ?>">
            <div class="card card-borderless rounded-3 overflow-hidden <?= esc($card['bg']) ?>" data-bs-theme="dark">
                <div class="card-body position-relative z-3">
                    <div class="fw-bold text-white small mb-1 d-flex align-items-center gap-2">
                        <iconify-icon icon="<?= esc($card['icon']) ?>" class="fs-6"></iconify-icon>
                        <?= esc($card['label']) ?>
                    </div>
                    <div class="fw-bold fs-2 text-white"><?= esc((string) ($summary[$card['key']] ?? 0)) ?></div>
                    <div class="fw-semibold text-white text-opacity-75 small mb-3 text-ellipsis"><?= esc($changeText($delta)) ?></div>
                    <div class="text-end mb-n3 mx-n3">
                        <a href="<?= esc($card['href']) ?>" class="text-decoration-none link-white fw-semibold small d-block px-3 py-2 bg-black bg-opacity-50">
                            Lihat detail <i class="fa fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <div class="position-absolute top-0 end-0 mt-n5 ps-5 w-25">
                    <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle ms-n5 position-absolute top-0 start-0"></div>
                    <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle mt-n5 position-absolute top-0 start-0"></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<!-- END row -->

<!-- BEGIN row — main + side -->
<div class="row g-3">
    <!-- BEGIN col-8 -->
    <div class="col-xl-8 d-flex flex-column gap-3">
        <!-- Analytics -->
        <div class="card card-borderless">
            <div class="card-header bg-gray-900" data-bs-theme="dark">
                <h4 class="card-header-title text-white d-flex align-items-center gap-3 flex-wrap mb-0">
                    <span>Analitik Scoring</span>
                    <form method="get" action="<?= site_url('/') ?>" class="d-inline">
                        <?php if ($query !== '') : ?><input type="hidden" name="q" value="<?= esc($query) ?>"><?php endif; ?>
                        <select id="dashPeriod" name="period" class="form-select form-select-sm h-25px py-0 w-auto border-0 shadow-none my-n1 bg-dark text-white" onchange="this.form.submit()">
                            <?php foreach ($periodOptions as $value => $label) : ?>
                                <option value="<?= esc($value) ?>" <?= $period === (string) $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </h4>
                <div class="card-header-btn">
                    <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="card-expand"><i class="fa fa-expand"></i></a>
                    <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="card-collapse"><i class="fa fa-minus"></i></a>
                </div>
            </div>
            <div class="card-body pb-0">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <div class="rounded bg-primary bg-opacity-15 p-3 position-relative">
                            <div class="small fw-semibold">Total</div>
                            <div class="fw-bold fs-4"><?= esc((string) ($summary['total'] ?? 0)) ?></div>
                            <div class="small fw-semibold text-muted">Pengajuan</div>
                            <div class="w-50px h-50px fs-2 d-flex align-items-center justify-content-center position-absolute top-0 end-0 text-primary">
                                <iconify-icon icon="solar:document-bold-duotone"></iconify-icon>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="rounded bg-warning bg-opacity-15 p-3 position-relative">
                            <div class="small fw-semibold">Proses</div>
                            <div class="fw-bold fs-4"><?= esc((string) ($summary['in_progress'] ?? 0)) ?></div>
                            <div class="small fw-semibold text-muted">Dalam antrean</div>
                            <div class="w-50px h-50px fs-2 d-flex align-items-center justify-content-center position-absolute top-0 end-0 text-warning">
                                <iconify-icon icon="solar:clock-circle-bold-duotone"></iconify-icon>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="rounded bg-success bg-opacity-15 p-3 position-relative">
                            <div class="small fw-semibold">Disetujui</div>
                            <div class="fw-bold fs-4"><?= esc((string) ($summary['approved'] ?? 0)) ?></div>
                            <div class="small fw-semibold text-muted">Lolos scoring</div>
                            <div class="w-50px h-50px fs-2 d-flex align-items-center justify-content-center position-absolute top-0 end-0 text-success">
                                <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="rounded bg-danger bg-opacity-15 p-3 position-relative">
                            <div class="small fw-semibold">Ditolak</div>
                            <div class="fw-bold fs-4"><?= esc((string) ($summary['rejected'] ?? 0)) ?></div>
                            <div class="small fw-semibold text-muted">Tidak lolos</div>
                            <div class="w-50px h-50px fs-2 d-flex align-items-center justify-content-center position-absolute top-0 end-0 text-danger">
                                <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 mt-3 mb-1 small text-muted fw-semibold">
                    <span><i class="fa fa-minus text-primary me-1"></i> Pengajuan</span>
                    <span><i class="fa fa-square text-success me-1" style="font-size:8px;vertical-align:middle;"></i> Disetujui</span>
                    <span class="ms-auto">6 bulan terakhir</span>
                </div>
            </div>
            <div class="px-3 pb-3">
                <svg class="w-100 ca-dash-chart" viewBox="0 0 340 168" role="img" aria-label="Tren pengajuan enam bulan">
                    <?php if ($trend === []) : ?>
                        <text x="170" y="84" text-anchor="middle" fill="#adb5bd" font-size="12">Belum ada data tren</text>
                    <?php endif; ?>
                    <?php foreach ($trend as $index => $point) : ?>
                        <?php
                        $x = $barSlots === 1 ? 150 : 18 + ($index * (300 / max(1, $barSlots - 1)));
                        $barHeight = ((int) $point['approved'] / $trendMax) * 100;
                        ?>
                        <rect x="<?= esc((string) round($x - 7, 1)) ?>" y="<?= esc((string) round(128 - $barHeight, 1)) ?>" width="14" height="<?= esc((string) round(max(0, $barHeight), 1)) ?>" rx="4" fill="#00acac"></rect>
                        <text x="<?= esc((string) round($x, 1)) ?>" y="148" text-anchor="middle" fill="#6c757d" font-size="11"><?= esc($point['label']) ?></text>
                    <?php endforeach; ?>
                    <?php if ($line !== []) : ?>
                        <polyline points="<?= esc(implode(' ', $line)) ?>" fill="none" stroke="#0c83f0" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                    <?php endif; ?>
                </svg>
            </div>
        </div>

        <!-- Recent tabs + table (Color Admin nav-tabs-inverse) -->
        <div class="card card-borderless dash-table">
            <ul class="nav nav-tabs nav-tabs-inverse nav-justified rounded-top border-0" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link py-10px border-0 active is-on d-flex align-items-center justify-content-center gap-2 w-100" data-dash-tab="all">
                        <iconify-icon icon="solar:clipboard-list-bold-duotone" class="fs-4 text-primary"></iconify-icon>
                        <span class="d-none d-md-inline">Terbaru</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link py-10px border-0 d-flex align-items-center justify-content-center gap-2 w-100" data-dash-tab="pending">
                        <iconify-icon icon="solar:hourglass-bold-duotone" class="fs-4 text-primary"></iconify-icon>
                        <span class="d-none d-md-inline">Menunggu</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link py-10px border-0 d-flex align-items-center justify-content-center gap-2 w-100" data-dash-tab="returned">
                        <iconify-icon icon="solar:restart-bold-duotone" class="fs-4 text-primary"></iconify-icon>
                        <span class="d-none d-md-inline">Dikembalikan</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link py-10px border-0 d-flex align-items-center justify-content-center gap-2 w-100" data-dash-tab="approved">
                        <iconify-icon icon="solar:check-circle-bold-duotone" class="fs-4 text-primary"></iconify-icon>
                        <span class="d-none d-md-inline">Disetujui</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link py-10px border-0 d-flex align-items-center justify-content-center gap-2 w-100" data-dash-tab="rejected">
                        <iconify-icon icon="solar:close-circle-bold-duotone" class="fs-4 text-primary"></iconify-icon>
                        <span class="d-none d-md-inline">Ditolak</span>
                    </button>
                </li>
            </ul>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-card mb-0 text-nowrap">
                        <thead class="table-py-10px">
                            <tr>
                                <th>Pengajuan</th>
                                <th>Debitur</th>
                                <th>Produk</th>
                                <th>Status</th>
                                <th>Tahap</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="table-py-15px">
                            <?php if ($recent === []) : ?>
                                <tr><td colspan="6" class="text-muted px-3 py-4">Belum ada pengajuan.</td></tr>
                            <?php endif; ?>
                            <?php foreach (array_slice($recent, 0, 5) as $item) : ?>
                                <?php
                                $status = (string) $item['status'];
                                $tab = 'all';
                                if (in_array($status, ['submitted', 'waiting_for_approver_assignment'], true)) {
                                    $tab = 'pending';
                                } elseif (in_array($status, ['returned', 'approved', 'rejected'], true)) {
                                    $tab = $status;
                                }
                                $href = site_url('transactions/' . $item['id']);
                                if (! in_array($status, ['draft', 'returned'], true)) {
                                    $href = site_url(($can('scoring.approve') || $can('scoring.assign')) ? 'approvals/' . $item['id'] : 'transactions/' . $item['id'] . '/review');
                                }
                                $badgeClass = $statusBadge[$status] ?? 'bg-secondary bg-opacity-15 text-secondary';
                                ?>
                                <tr data-status="<?= esc($tab) ?>">
                                    <td>
                                        <div class="fw-semibold"><?= esc(date('d M Y H:i', strtotime((string) $item['created_at']))) ?></div>
                                        <div class="fw-semibold fs-11px text-body text-opacity-50"><?= esc($item['transaction_no']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= esc($item['debtor_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= esc($item['product_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= esc($badgeClass) ?> py-6px badge-<?= esc($status) ?>"><?= esc($item['status_label']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small"><?= esc($stages[$status] ?? $item['status_label']) ?></div>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= esc($href) ?>" class="btn btn-default btn-xs btn-icon" aria-label="Lihat pengajuan">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2 border-top text-center">
                    <a href="<?= site_url('reports/scoring') ?>" class="fw-semibold text-decoration-none small">Lihat semua pengajuan</a>
                </div>
            </div>
        </div>
    </div>
    <!-- END col-8 -->

    <!-- BEGIN col-4 -->
    <div class="col-xl-4 d-flex flex-column gap-3">
        <!-- Product distribution -->
        <div class="card card-borderless">
            <div class="card-header bg-gray-900" data-bs-theme="dark">
                <h4 class="card-header-title text-white mb-0">Distribusi Produk</h4>
                <div class="card-header-btn">
                    <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="card-collapse"><i class="fa fa-minus"></i></a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($products === []) : ?>
                    <p class="text-muted mb-0 small">Belum ada pengajuan pada periode ini.</p>
                <?php else : ?>
                    <?php foreach ($products as $index => $product) : ?>
                        <?php $share = max(0, min(100, (int) $product['share'])); ?>
                        <div class="<?= $index > 0 ? 'mt-3' : '' ?>">
                            <div class="d-flex justify-content-between small fw-semibold mb-1">
                                <span><?= esc($product['name']) ?></span>
                                <span class="text-muted"><?= esc((string) $product['count']) ?> · <?= esc((string) $share) ?>%</span>
                            </div>
                            <div class="progress h-5px rounded-pill">
                                <div class="progress-bar <?= esc($productColors[$index % count($productColors)]) ?>" style="width: <?= esc((string) $share) ?>%; background-color: <?= esc($productBar[$index % count($productBar)]) ?>;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notifications -->
        <div class="card card-borderless">
            <div class="card-header bg-gray-900" data-bs-theme="dark">
                <h4 class="card-header-title text-white mb-0">Notifikasi</h4>
                <a href="<?= site_url('notifications') ?>" class="text-white text-opacity-50 small fw-semibold text-decoration-none">Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if ($notes === []) : ?>
                    <p class="text-muted small px-3 py-3 mb-0">Belum ada notifikasi.</p>
                <?php else : ?>
                    <div class="h-280px" data-scrollbar="true" data-wheel-propagation="true">
                        <?php foreach (array_slice($notes, 0, 8) as $note) : ?>
                            <div class="d-flex align-items-start gap-2 px-3 py-2 border-bottom">
                                <div class="rounded-circle bg-primary bg-opacity-15 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;">
                                    <iconify-icon icon="solar:bell-bing-bold-duotone"></iconify-icon>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="fw-bold small text-truncate"><?= esc($note['title']) ?></div>
                                    <div class="fw-semibold small text-muted"><?= esc($ago((string) ($note['created_at'] ?? ''))) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="card card-borderless">
            <div class="card-header bg-gray-900" data-bs-theme="dark">
                <h4 class="card-header-title text-white mb-0">Quick Action</h4>
            </div>
            <div class="list-group list-group-flush">
                <?php if ($can('scoring.submit')) : ?>
                    <a href="<?= site_url('transactions/new') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:file-add-bold-duotone" class="fs-4 text-primary"></iconify-icon>
                        <span class="flex-1">
                            <span class="d-block fw-bold small">Pengajuan Scoring</span>
                            <span class="d-block text-muted" style="font-size:11px;">Buat pengajuan baru</span>
                        </span>
                        <i class="fa fa-chevron-right text-muted"></i>
                    </a>
                <?php endif; ?>
                <a href="<?= site_url($can('scoring.approve') || $can('scoring.assign') ? 'approvals' : 'transactions') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:clipboard-check-bold-duotone" class="fs-4 text-teal"></iconify-icon>
                    <span class="flex-1">
                        <span class="d-block fw-bold small">Cek Status</span>
                        <span class="d-block text-muted" style="font-size:11px;">Lihat status pengajuan</span>
                    </span>
                    <i class="fa fa-chevron-right text-muted"></i>
                </a>
                <a href="<?= site_url('reports/scoring') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:chart-bold-duotone" class="fs-4 text-info"></iconify-icon>
                    <span class="flex-1">
                        <span class="d-block fw-bold small">Laporan Scoring</span>
                        <span class="d-block text-muted" style="font-size:11px;">Download laporan</span>
                    </span>
                    <i class="fa fa-chevron-right text-muted"></i>
                </a>
            </div>
        </div>

        <!-- Related -->
        <div class="card card-borderless">
            <div class="card-header">
                <h4 class="card-header-title mb-0">Link Terkait</h4>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= site_url('reports/scoring') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:book-bold-duotone" class="fs-4 text-secondary"></iconify-icon>
                    <span class="flex-1">
                        <span class="d-block fw-bold small">User Guide</span>
                        <span class="d-block text-muted" style="font-size:11px;">Panduan penggunaan</span>
                    </span>
                </a>
                <a href="<?= site_url('notifications') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:question-circle-bold-duotone" class="fs-4 text-secondary"></iconify-icon>
                    <span class="flex-1">
                        <span class="d-block fw-bold small">FAQ</span>
                        <span class="d-block text-muted" style="font-size:11px;">Pertanyaan umum</span>
                    </span>
                </a>
            </div>
        </div>
    </div>
    <!-- END col-4 -->
</div>
<!-- END row -->

<script>
    document.querySelectorAll('[data-dash-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-dash-tab]').forEach(function (item) {
                item.classList.remove('is-on', 'active');
            });
            button.classList.add('is-on', 'active');
            var tab = button.getAttribute('data-dash-tab');
            document.querySelectorAll('.dash-table tbody tr[data-status]').forEach(function (row) {
                row.hidden = tab !== 'all' && row.getAttribute('data-status') !== tab;
            });
        });
    });
</script>
<?= view('partials/shell_end') ?>
