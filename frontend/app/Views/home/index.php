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
        return '';
    }
    $number = (int) $value;
    $arrow = $number >= 0 ? '↑' : '↓';

    return $arrow . ' ' . abs($number) . '% dari bulan lalu';
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
    ['key' => 'total', 'label' => 'Total Pengajuan', 'tone' => 'blue', 'icon' => 'doc'],
    ['key' => 'in_progress', 'label' => 'Dalam Proses', 'tone' => 'amber', 'icon' => 'clock'],
    ['key' => 'approved', 'label' => 'Disetujui', 'tone' => 'green', 'icon' => 'check'],
    ['key' => 'rejected', 'label' => 'Ditolak', 'tone' => 'red', 'icon' => 'x'],
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
$donut = '';
$cursorShare = 0;
$donutColors = ['#1e60d5', '#22a35a', '#f5a524', '#f97316', '#94a3b8'];
foreach ($products as $index => $product) {
    $share = max(0, (int) $product['share']);
    $next = min(100, $cursorShare + $share);
    $donut .= $donutColors[$index % count($donutColors)] . ' ' . $cursorShare . '% ' . $next . '%, ';
    $cursorShare = $next;
}
if ($cursorShare < 100) {
    $donut .= '#e6eef8 ' . $cursorShare . '% 100%';
}
if ($donut === '') {
    $donut = '#e6eef8 0 100%';
}
?>
<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Dasbor', 'isDashboard' => true]) ?>
<?php if (! empty($error)) : ?>
<div data-swal="error" data-swal-title="Terjadi Kesalahan" data-swal-message="<?= esc($error) ?>" hidden></div>
<?php endif; ?>
<section class="dash">
    <article class="dash-hero">
        <div class="dash-hero-copy">
            <h2>Selamat Datang, <?= esc($profile['full_name'] ?? '') ?> <span aria-hidden="true">👋</span></h2>
            <p class="dash-hero-kicker">Di New Scoring Credit System</p>
            <p>Analisa kredit lebih cepat, akurat dan terpercaya dengan dukungan data dan teknologi terkini.</p>
            <ul>
                <li><strong>Cepat</strong><span>Proses lebih efisien</span></li>
                <li><strong>Akurat</strong><span>Hasil lebih valid</span></li>
                <li><strong>Terpercaya</strong><span>Mendukung keputusan</span></li>
            </ul>
        </div>
        <div class="dash-hero-art">
            <img src="<?= base_url('assets/images/logo-banksumut.png') ?>" alt="Bank Sumut">
            <p>Melayani<br>Masa Depan<br>Lebih Baik</p>
        </div>
    </article>

    <div class="dash-stats">
        <?php foreach ($cards as $card) : ?>
            <?php $delta = $changes[$card['key']] ?? null; ?>
            <article class="tone-<?= esc($card['tone']) ?>">
                <span class="dash-stat-icon" aria-hidden="true"></span>
                <p><?= esc($card['label']) ?></p>
                <strong><?= esc((string) ($summary[$card['key']] ?? 0)) ?></strong>
                <?php if ($delta !== null && $delta !== '') : ?>
                    <small class="<?= ((int) $delta) >= 0 ? 'is-up' : 'is-down' ?>"><?= esc($changeText($delta)) ?></small>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <form class="dash-period" method="get" action="<?= site_url('/') ?>">
            <?php if ($query !== '') : ?><input type="hidden" name="q" value="<?= esc($query) ?>"><?php endif; ?>
            <label for="dashPeriod">Periode</label>
            <select id="dashPeriod" name="period" onchange="this.form.submit()">
                <?php foreach ($periodOptions as $value => $label) : ?>
                    <option value="<?= esc($value) ?>" <?= $period === (string) $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="dash-grid">
        <section class="dash-card dash-trend">
            <header>
                <h3>Tren Pengajuan Scoring</h3>
                <span>6 bulan terakhir</span>
            </header>
            <p class="dash-legend"><i class="is-line"></i>Pengajuan <i class="is-bar"></i>Disetujui</p>
            <svg class="dash-chart" viewBox="0 0 340 168" role="img" aria-label="Tren pengajuan enam bulan">
                <?php foreach ($trend as $index => $point) : ?>
                    <?php
                    $x = $barSlots === 1 ? 150 : 18 + ($index * (300 / max(1, $barSlots - 1)));
                    $barHeight = ((int) $point['approved'] / $trendMax) * 100;
                    ?>
                    <rect x="<?= esc((string) round($x - 7, 1)) ?>" y="<?= esc((string) round(128 - $barHeight, 1)) ?>" width="14" height="<?= esc((string) round($barHeight, 1)) ?>" rx="4" fill="#7ddea0"></rect>
                    <text x="<?= esc((string) round($x, 1)) ?>" y="148" text-anchor="middle"><?= esc($point['label']) ?></text>
                <?php endforeach; ?>
                <?php if ($line !== []) : ?>
                    <polyline points="<?= esc(implode(' ', $line)) ?>" fill="none" stroke="#1e60d5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                <?php endif; ?>
            </svg>
        </section>
        <section class="dash-card dash-products">
            <h3>Distribusi Pengajuan per Produk</h3>
            <?php if ($products === []) : ?>
                <p class="dash-empty">Belum ada pengajuan pada periode ini.</p>
            <?php else : ?>
                <div class="dash-donut-wrap">
                    <div class="dash-donut" style="background: conic-gradient(<?= esc(rtrim($donut, ', ')) ?>)">
                        <span><strong><?= esc((string) ($summary['total'] ?? 0)) ?></strong><small>Total</small></span>
                    </div>
                    <ul>
                        <?php foreach ($products as $index => $product) : ?>
                            <li>
                                <i style="background: <?= esc($donutColors[$index % count($donutColors)]) ?>"></i>
                                <span><?= esc($product['name']) ?></span>
                                <b><?= esc((string) $product['count']) ?> (<?= esc((string) $product['share']) ?>%)</b>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
        <aside class="dash-side">
            <section class="dash-card">
                <header>
                    <h3>Notifikasi Terbaru</h3>
                    <a href="<?= site_url('notifications') ?>">Lihat Semua</a>
                </header>
                <?php if ($notes === []) : ?>
                    <p class="dash-empty">Belum ada notifikasi.</p>
                <?php endif; ?>
                <ul class="dash-notes">
                    <?php foreach (array_slice($notes, 0, 4) as $note) : ?>
                        <li>
                            <span></span>
                            <div>
                                <strong><?= esc($note['title']) ?></strong>
                                <small><?= esc($ago((string) ($note['created_at'] ?? ''))) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <section class="dash-card">
                <h3>Quick Action</h3>
                <div class="dash-actions">
                    <?php if ($can('scoring.submit')) : ?>
                        <a href="<?= site_url('transactions/new') ?>"><strong>Pengajuan Scoring</strong><span>Buat pengajuan baru</span></a>
                        <a href="<?= site_url('rescore') ?>"><strong>Request Ulang</strong><span>Ajukan re-scoring</span></a>
                    <?php endif; ?>
                    <a href="<?= site_url($can('scoring.approve') || $can('scoring.assign') ? 'approvals' : 'transactions') ?>"><strong>Cek Status</strong><span>Lihat status pengajuan</span></a>
                    <a href="<?= site_url('reports/scoring') ?>"><strong>Laporan Scoring</strong><span>Download laporan</span></a>
                </div>
            </section>
            <section class="dash-card">
                <h3>Link Terkait</h3>
                <div class="dash-links">
                    <a href="<?= site_url('reports/scoring') ?>"><strong>User Guide</strong><span>Panduan penggunaan aplikasi</span></a>
                    <a href="<?= site_url('notifications') ?>"><strong>FAQ</strong><span>Pertanyaan yang sering ditanyakan</span></a>
                </div>
            </section>
        </aside>
        <section class="dash-card dash-table">
            <header class="dash-tabs">
                <div role="tablist">
                    <button type="button" class="is-on" data-dash-tab="all">Pengajuan Terbaru</button>
                    <button type="button" data-dash-tab="pending">Menunggu Persetujuan</button>
                    <button type="button" data-dash-tab="returned">Dikembalikan</button>
                    <button type="button" data-dash-tab="approved">Disetujui</button>
                    <button type="button" data-dash-tab="rejected">Ditolak</button>
                </div>
                <a href="<?= site_url('reports/scoring') ?>">Lihat Semua</a>
            </header>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>No</th><th>Nomor Pengajuan</th><th>Nama Debitur</th><th>Produk</th><th>Tanggal Pengajuan</th><th>Status</th><th>Tahap Saat Ini</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if ($recent === []) : ?>
                            <tr><td colspan="8">Belum ada pengajuan.</td></tr>
                        <?php endif; ?>
                        <?php foreach (array_slice($recent, 0, 5) as $index => $item) : ?>
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
                            ?>
                            <tr data-status="<?= esc($tab) ?>">
                                <td><?= esc((string) ($index + 1)) ?></td>
                                <td><?= esc($item['transaction_no']) ?></td>
                                <td><?= esc($item['debtor_name']) ?></td>
                                <td><?= esc($item['product_name']) ?></td>
                                <td><?= esc(date('d M Y H:i', strtotime((string) $item['created_at']))) ?></td>
                                <td><span class="badge badge-<?= esc($status) ?>"><?= esc($item['status_label']) ?></span></td>
                                <td><?= esc($stages[$status] ?? $item['status_label']) ?></td>
                                <td><a class="dash-eye" href="<?= esc($href) ?>" aria-label="Lihat pengajuan">◉</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
<script>
    document.querySelectorAll('[data-dash-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-dash-tab]').forEach(function (item) { item.classList.remove('is-on'); });
            button.classList.add('is-on');
            var tab = button.getAttribute('data-dash-tab');
            document.querySelectorAll('.dash-table tbody tr[data-status]').forEach(function (row) {
                row.hidden = tab !== 'all' && row.getAttribute('data-status') !== tab;
            });
        });
    });
</script>
<?= view('partials/shell_end') ?>
