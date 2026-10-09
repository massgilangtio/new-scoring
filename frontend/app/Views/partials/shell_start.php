<?php
helper('url');
$permissions = $profile['permissions'] ?? [];
$path = trim(uri_string(), '/');
$can = static function (string $code) use ($permissions): bool {
    return in_array($code, $permissions, true);
};
$isDashboard = ! empty($isDashboard);
$name = trim((string) ($profile['full_name'] ?? ''));
$initials = '';
foreach (array_slice(preg_split('/\s+/', $name) ?: [], 0, 2) as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}
$unreadNav = 0;
$token = session()->get('access_token');
if (is_string($token) && $token !== '') {
    $notes = (new \App\Libraries\ApiClient())->get('/api/v1/notifications', $token);
    $unreadNav = (int) ($notes['result']['unread_count'] ?? 0);
}

// --- Navigation Groups (otorisasi per-menu) ---
// Umbrella permissions (master.manage / scoring.configure / access.manage)
// sudah di-expand di /auth/me, jadi cukup cek kode menu.
$groups = [];
$tx = [];
if ($can('scoring.submit')) {
    $tx[] = ['label' => 'Pengajuan Scoring', 'href' => site_url('scoring/credit'), 'active' => $path === 'scoring/credit' || str_starts_with($path, 'scoring/credit') || $path === 'transactions/new', 'icon' => 'fa-solid fa-file-circle-plus'];
    $tx[] = ['label' => 'Daftar Scoring',    'href' => site_url('transactions'),  'active' => $path === 'transactions' || (str_starts_with($path, 'transactions/') && ! str_starts_with($path, 'transactions/new')), 'icon' => 'fa-solid fa-table-list'];
}
if ($tx !== []) {
    $groups[] = ['label' => 'Transaksi Scoring', 'icon' => 'fa-solid fa-file-invoice', 'theme' => 'cyan', 'items' => $tx];
}
if ($can('scoring.approve') || $can('scoring.assign')) {
    $groups[] = ['label' => 'Approval', 'icon' => 'fa-solid fa-check', 'theme' => 'green', 'items' => [
        ['label' => 'Menunggu Persetujuan', 'href' => site_url('approvals'), 'active' => $path === 'approvals' || str_starts_with($path, 'approvals/'), 'icon' => 'fa-solid fa-clock-rotate-left'],
    ]];
}
$master = [];
if ($can('master.debtors') || $can('master.manage')) {
    $master[] = ['label' => 'Debitur', 'href' => site_url('master/debtors'),   'active' => $path === 'master/debtors', 'icon' => 'fa-regular fa-file-lines'];
}
if ($can('master.products') || $can('master.manage')) {
    $master[] = ['label' => 'Produk',  'href' => site_url('master/products'),  'active' => $path === 'master/products', 'icon' => 'fa-solid fa-box'];
}
if ($can('master.branches') || $can('master.manage')) {
    $master[] = ['label' => 'Cabang',  'href' => site_url('master/branches'), 'active' => $path === 'master/branches', 'icon' => 'fa-solid fa-shop'];
}
if ($master !== []) {
    $groups[] = ['label' => 'Master Data', 'icon' => 'fa-solid fa-database', 'theme' => 'purple', 'items' => $master];
}
$param = [];
if ($can('scoring.parameters') || $can('scoring.configure')) {
    $param[] = ['label' => 'Konfigurasi Parameter', 'href' => site_url('scoring/parameters'), 'active' => $path === 'scoring/parameters' || str_starts_with($path, 'scoring/parameters'), 'icon' => 'fa-solid fa-sliders'];
}
if ($can('scoring.mapping') || $can('scoring.configure')) {
    $param[] = ['label' => 'Mapping Produk', 'href' => site_url('scoring/mapping'), 'active' => $path === 'scoring/mapping' || str_starts_with($path, 'scoring/mapping'), 'icon' => 'fa-solid fa-diagram-project'];
}
if ($param !== []) {
    $groups[] = ['label' => 'Parameter Scoring', 'icon' => 'fa-solid fa-sliders', 'theme' => 'orange', 'items' => $param];
}
$reports = [];
if ($can('report.scoring')) {
    $reports[] = ['label' => 'Laporan Scoring', 'href' => site_url('reports/scoring'), 'active' => $path === 'reports/scoring', 'icon' => 'fa-solid fa-chart-pie'];
}
if ($can('report.debtors')) {
    $reports[] = ['label' => 'Riwayat Debitur', 'href' => site_url('reports/debtors'), 'active' => $path === 'reports/debtors', 'icon' => 'fa-solid fa-user-clock'];
}
if ($can('report.products')) {
    $reports[] = ['label' => 'Riwayat Produk', 'href' => site_url('reports/products'), 'active' => $path === 'reports/products', 'icon' => 'fa-solid fa-box-archive'];
}
if ($can('report.changes')) {
    $reports[] = ['label' => 'Perubahan Parameter', 'href' => site_url('reports/changes'), 'active' => $path === 'reports/changes', 'icon' => 'fa-solid fa-code-compare'];
}
if ($can('audit.view')) {
    $reports[] = ['label' => 'Audit Trail', 'href' => site_url('audit'), 'active' => $path === 'audit' || str_starts_with($path, 'audit/'), 'icon' => 'fa-solid fa-shield-halved'];
}
if ($can('system.logs') || $can('audit.view')) {
    $reports[] = ['label' => 'Log Sistem & Error', 'href' => site_url('system-logs'), 'active' => $path === 'system-logs' || str_starts_with($path, 'system-logs/'), 'icon' => 'fa-solid fa-bug'];
}
if ($reports !== []) {
    $groups[] = ['label' => 'Laporan', 'icon' => 'fa-solid fa-file-lines', 'theme' => 'red', 'items' => $reports];
}
$access = [];
if ($can('access.users') || $can('access.manage')) {
    $access[] = ['label' => 'User', 'href' => site_url('access/users'), 'active' => $path === 'access/users', 'icon' => 'fa-solid fa-user'];
    $access[] = ['label' => 'Kelompok Jabatan', 'href' => site_url('access/job-groups'), 'active' => $path === 'access/job-groups' || str_starts_with($path, 'access/job-groups/'), 'icon' => 'fa-solid fa-briefcase'];
}
if ($can('access.roles') || $can('access.manage')) {
    $access[] = ['label' => 'Role', 'href' => site_url('access/roles'), 'active' => $path === 'access/roles' || str_starts_with($path, 'access/roles/'), 'icon' => 'fa-solid fa-id-badge'];
}
if ($can('access.permissions') || $can('access.manage')) {
    $access[] = ['label' => 'Permission', 'href' => site_url('access/permissions'), 'active' => $path === 'access/permissions', 'icon' => 'fa-solid fa-key'];
}
if ($access !== []) {
    $groups[] = ['label' => 'User & Access', 'icon' => 'fa-solid fa-user-group', 'theme' => 'indigo', 'items' => $access];
}

$roleLine   = trim((string) ($profile['rolenm'] ?? $profile['role_name'] ?? session()->get('rolenm') ?? ''));
$branchLine = trim((string) ($profile['branch_name'] ?? session()->get('branch_name') ?? ''));
$branchId   = trim((string) ($profile['branchid'] ?? session()->get('branchid') ?? ''));
$jabatanLine = trim((string) ($profile['jabatan'] ?? session()->get('jabatan') ?? ''));
$nppVal     = trim((string) ($profile['npp'] ?? session()->get('npp') ?? ''));
$subtitle   = $roleLine;
if ($branchLine !== '') {
    $subtitle = trim($roleLine . ($roleLine !== '' ? ' · ' : '') . $branchLine);
}
$displayName = $name !== '' ? $name : (session()->get('nama') ?: 'Administrator');
$displayRole = $subtitle !== '' ? $subtitle : 'Administrator';
$displayInitials = $initials !== '' ? $initials : 'AD';
$badgeCount = $unreadNav > 0 ? $unreadNav : 0;
?>
<!doctype html>
<html lang="id" class="ca-ui">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= esc($title) ?> — New Scoring Credit System</title>
    <?= view('partials/assets') ?>
    <script>
    window.AppAuth = {
        permissions: <?= json_encode(array_values(array_map('strval', $permissions)), JSON_UNESCAPED_UNICODE) ?>,
        can: function (code) {
            return Array.isArray(this.permissions) && this.permissions.indexOf(code) !== -1;
        },
        canViewScoreDetails: function () {
            return this.can('scoring.view_score_details');
        }
    };
    </script>
</head>
<body>
    <!-- BEGIN #loader -->
    <div id="loader" class="app-loader"><span class="spinner"></span></div>
    <!-- END #loader -->

    <!-- BEGIN #app -->
    <div id="app" class="app app-header-fixed app-sidebar-fixed">

        <!-- BEGIN #appHeader -->
        <div id="appHeader" class="app-header">
            <div class="brand">
                <div class="mobile-toggler">
                    <button type="button" class="menu-toggler" data-toggle-class="app-sidebar-mobile-toggled" data-toggle-target=".app" aria-label="Buka Menu">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                </div>
                <a href="<?= site_url('/') ?>" class="brand-logo" title="New Scoring Credit System">
                    <span class="brand-icon"></span>
                    <b class="me-1">New</b> Scoring
                </a>
            </div>

            <div class="menu">
                <div class="menu-item menu-item-form d-none d-md-flex">
                    <form action="<?= site_url('/') ?>" method="get" name="search">
                        <input type="text" class="form-control" name="q" value="<?= esc((string) ($_GET['q'] ?? '')) ?>" placeholder="Cari debitur / produk..." aria-label="Cari">
                        <button type="submit" class="btn btn-search" aria-label="Cari">
                            <iconify-icon icon="octicon:search-16"></iconify-icon>
                        </button>
                    </form>
                </div>

                <div class="menu-item dropdown">
                    <a href="<?= site_url('/') ?>" class="menu-link" title="Menu">
                        <iconify-icon icon="solar:widget-bold-duotone"></iconify-icon>
                    </a>
                </div>

                <div class="menu-item dropdown">
                    <a href="<?= site_url('notifications') ?>" class="menu-link" title="Notifikasi">
                        <iconify-icon icon="solar:bell-bing-bold-duotone"></iconify-icon>
                        <?php if ($badgeCount > 0) : ?>
                            <span class="badge"><?= esc((string) $badgeCount) ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="menu-item dropdown">
                    <a href="#" class="menu-link dropdown-toggle" data-bs-toggle="dropdown" aria-label="Menu pengguna">
                        <span class="rounded-circle bg-theme text-white d-inline-flex align-items-center justify-content-center me-2" style="width:30px;height:30px;font-size:12px;font-weight:700;">
                            <?= esc($displayInitials) ?>
                        </span>
                        <span class="d-none d-md-inline"><?= esc($displayName) ?></span>
                        <b class="caret d-none"></b>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end me-1">
                        <div class="px-3 py-2 border-bottom mb-1">
                            <div class="fw-bold d-flex align-items-center gap-1 mb-1">
                                <span><?= esc($displayName) ?></span>
                                <?php if ($nppVal !== '') : ?>
                                    <span class="badge bg-secondary font-monospace" style="font-size:10px;"><?= esc($nppVal) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted" style="font-size:11px; line-height: 1.35;"><?= esc($roleLine ?: 'Administrator') ?></div>
                            <?php if ($branchLine !== '') : ?>
                                <div class="small text-theme fw-semibold mt-1 pt-1" style="font-size:11px; line-height: 1.35;">
                                    <i class="fa fa-building me-1 opacity-75"></i><?= esc($branchLine) ?><?= $branchId !== '' ? ' (' . esc($branchId) . ')' : '' ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <a href="<?= site_url('notifications') ?>" class="dropdown-item">Notifikasi</a>
                        <div class="dropdown-divider"></div>
                        <form method="post" action="<?= site_url('logout') ?>" class="header-logout-form">
                            <?= csrf_field() ?>
                            <button class="dropdown-item" type="submit">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- END #appHeader -->

        <!-- BEGIN #sidebar -->
        <div id="sidebar" class="app-sidebar" data-bs-theme="dark">
            <div class="app-sidebar-content" data-scrollbar="true" data-height="100%">
                <div class="menu">
                    <div class="menu-profile position-relative">
                        <a href="javascript:;" class="menu-link" data-bs-toggle="dropdown">
                            <div class="menu-profile-cover with-shadow"></div>
                            <div class="menu-profile-image avatar-initials"><?= esc($displayInitials) ?></div>
                            <div class="menu-profile-info">
                                <div class="d-flex align-items-center mb-1">
                                    <div class="flex-grow-1 text-truncate fw-bold"><?= esc($displayName) ?></div>
                                    <div class="menu-caret ms-auto"></div>
                                </div>
                                <small class="d-block text-truncate text-white-50" style="line-height: 1.4; margin-bottom: 3px;"><?= esc($roleLine ?: 'Administrator') ?></small>
                                <?php if ($branchLine !== '') : ?>
                                    <small class="d-block text-theme text-truncate mt-1" style="font-size: 10px; font-weight: 600; line-height: 1.4; padding-top: 1px;">
                                        <i class="fa fa-building me-1 opacity-75"></i><?= esc($branchLine) ?><?= $branchId !== '' ? ' (' . esc($branchId) . ')' : '' ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                        </a>
                        <div class="dropdown-menu w-100 mt-1">
                            <a href="<?= site_url('notifications') ?>" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                <i class="fa-solid fa-bell opacity-50"></i>
                                <div class="flex-fill">Notifikasi</div>
                                <?php if ($badgeCount > 0) : ?>
                                    <span class="badge bg-danger rounded-pill"><?= esc((string) $badgeCount) ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-divider"></div>
                            <form method="post" action="<?= site_url('logout') ?>" class="header-logout-form">
                                <?= csrf_field() ?>
                                <button class="dropdown-item d-flex align-items-center gap-2 py-2" type="submit">
                                    <i class="fa-solid fa-right-from-bracket opacity-50"></i>
                                    <div class="flex-fill">Keluar</div>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="menu-header">Navigation</div>

                    <div class="menu-item<?= $path === '' ? ' active' : '' ?>">
                        <a href="<?= site_url('/') ?>" class="menu-link">
                            <div class="menu-icon"><i class="fa-solid fa-house"></i></div>
                            <div class="menu-text">Dashboard</div>
                        </a>
                    </div>

                    <?php foreach ($groups as $group) : ?>
                        <?php
                        $open = false;
                        foreach ($group['items'] as $item) {
                            $open = $open || ! empty($item['active']);
                        }
                        ?>
                        <div class="menu-item has-sub<?= $open ? ' active' : '' ?>">
                            <a href="javascript:;" class="menu-link">
                                <div class="menu-icon"><i class="<?= esc($group['icon']) ?>"></i></div>
                                <div class="menu-text"><?= esc($group['label']) ?></div>
                                <div class="menu-caret"></div>
                            </a>
                            <div class="menu-submenu">
                                <?php foreach ($group['items'] as $item) : ?>
                                    <div class="menu-item<?= ! empty($item['active']) ? ' active' : '' ?>">
                                        <a href="<?= esc($item['href']) ?>" class="menu-link">
                                            <div class="menu-text">
                                                <i class="<?= esc($item['icon'] ?? 'fa-solid fa-circle') ?> me-1 opacity-50"></i>
                                                <?= esc($item['label']) ?>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="menu-item<?= $path === 'notifications' ? ' active' : '' ?>">
                        <a href="<?= site_url('notifications') ?>" class="menu-link">
                            <div class="menu-icon"><i class="fa-solid fa-bell"></i></div>
                            <div class="menu-text">Notifikasi</div>
                            <?php if ($badgeCount > 0) : ?>
                                <span class="menu-label"><?= esc((string) $badgeCount) ?></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <div class="menu-item d-flex">
                        <a href="javascript:;" class="app-sidebar-minify-btn ms-auto d-flex align-items-center text-decoration-none" data-toggle="app-sidebar-minify" aria-label="Kecilkan sidebar">
                            <i class="fa fa-angle-double-left"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-sidebar-mobile-backdrop"><a href="#" data-dismiss="app-sidebar-mobile" class="stretched-link"></a></div>
        <!-- END #sidebar -->

        <!-- BEGIN #content -->
        <div id="content" class="app-content">
            <ol class="breadcrumb float-xl-end">
                <li class="breadcrumb-item"><a href="<?= site_url('/') ?>">Home</a></li>
                <li class="breadcrumb-item active"><?= esc($title) ?></li>
            </ol>
            <h1 class="page-header">
                <?= esc($title) ?>
                <?php if ($isDashboard) : ?>
                    <small>ringkasan aktivitas scoring</small>
                <?php endif; ?>
            </h1>
            <div class="page<?= $isDashboard ? ' dash-page' : '' ?>">
