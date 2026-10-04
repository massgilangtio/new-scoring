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

// --- Navigation Groups ---
$groups = [];
$tx = [];
if ($can('scoring.submit')) {
    $tx[] = ['label' => 'Pengajuan Scoring',     'href' => site_url('scoring/credit'),     'active' => $path === 'scoring/credit' || str_starts_with($path, 'scoring/credit') || $path === 'transactions/new', 'icon' => 'fa-solid fa-file-circle-plus'];
    $tx[] = ['label' => 'Daftar Scoring',        'href' => site_url('transactions'),      'active' => $path === 'transactions' || (str_starts_with($path, 'transactions/') && ! str_starts_with($path, 'transactions/new')), 'icon' => 'fa-solid fa-table-list'];
    $tx[] = ['label' => 'Request Scoring Ulang', 'href' => site_url('rescore'),           'active' => $path === 'rescore' || str_starts_with($path, 'rescore/'), 'icon' => 'fa-solid fa-arrows-rotate'];
}
if ($tx !== []) {
    $groups[] = ['label' => 'Transaksi Scoring', 'icon' => 'fa-solid fa-file-invoice', 'theme' => 'cyan', 'items' => $tx];
}
if ($can('scoring.approve') || $can('scoring.assign')) {
    $groups[] = ['label' => 'Approval', 'icon' => 'fa-solid fa-check', 'theme' => 'green', 'items' => [
        ['label' => 'Menunggu Persetujuan', 'href' => site_url('approvals'), 'active' => $path === 'approvals' || str_starts_with($path, 'approvals/'), 'icon' => 'fa-solid fa-clock-rotate-left'],
    ]];
}
if ($can('master.manage')) {
    $groups[] = ['label' => 'Master Data', 'icon' => 'fa-solid fa-database', 'theme' => 'purple', 'items' => [
        ['label' => 'Debitur', 'href' => site_url('master/debtors'),   'active' => $path === 'master/debtors', 'icon' => 'fa-regular fa-file-lines'],
        ['label' => 'Produk',  'href' => site_url('master/products'),  'active' => $path === 'master/products', 'icon' => 'fa-solid fa-box'],
        ['label' => 'Cabang',  'href' => site_url('master/branches'), 'active' => $path === 'master/branches', 'icon' => 'fa-solid fa-shop'],
    ]];
}
if ($can('scoring.configure')) {
    $groups[] = ['label' => 'Parameter Scoring', 'icon' => 'fa-solid fa-sliders', 'theme' => 'orange', 'items' => [
        ['label' => 'Konfigurasi Parameter', 'href' => site_url('scoring/parameters'), 'active' => $path === 'scoring/parameters' || str_starts_with($path, 'scoring/parameters'), 'icon' => 'fa-solid fa-sliders'],
        ['label' => 'Mapping Produk',         'href' => site_url('scoring/mapping'),    'active' => $path === 'scoring/mapping' || str_starts_with($path, 'scoring/mapping'), 'icon' => 'fa-solid fa-diagram-project'],
    ]];
}
$groups[] = ['label' => 'Laporan', 'icon' => 'fa-solid fa-file-lines', 'theme' => 'red', 'items' => [
    ['label' => 'Laporan Scoring',    'href' => site_url('reports/scoring'),  'active' => $path === 'reports/scoring', 'icon' => 'fa-solid fa-chart-pie'],
    ['label' => 'Riwayat Debitur',    'href' => site_url('reports/debtors'),  'active' => $path === 'reports/debtors', 'icon' => 'fa-solid fa-user-clock'],
    ['label' => 'Riwayat Produk',     'href' => site_url('reports/products'), 'active' => $path === 'reports/products', 'icon' => 'fa-solid fa-box-archive'],
    ['label' => 'Perubahan Parameter','href' => site_url('reports/changes'),  'active' => $path === 'reports/changes', 'icon' => 'fa-solid fa-code-compare'],
    ['label' => 'Audit Trail',        'href' => site_url('audit'),            'active' => $path === 'audit' || str_starts_with($path, 'audit/'), 'icon' => 'fa-solid fa-shield-halved'],
]];
if ($can('access.manage')) {
    $groups[] = ['label' => 'User & Access', 'icon' => 'fa-solid fa-user-group', 'theme' => 'indigo', 'items' => [
        ['label' => 'User',       'href' => site_url('access/users'),       'active' => $path === 'access/users', 'icon' => 'fa-solid fa-user'],
        ['label' => 'Role',       'href' => site_url('access/roles'),       'active' => $path === 'access/roles', 'icon' => 'fa-solid fa-id-badge'],
        ['label' => 'Permission', 'href' => site_url('access/permissions'), 'active' => $path === 'access/permissions', 'icon' => 'fa-solid fa-key'],
    ]];
}

$roleLine   = trim((string) ($profile['role_name']   ?? ''));
$branchLine = trim((string) ($profile['branch_name'] ?? ''));
$subtitle   = $roleLine;
if ($branchLine !== '') {
    $subtitle = trim($roleLine . ($roleLine !== '' ? ' · ' : '') . $branchLine);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?> — New Scoring Credit System</title>
    <?= view('partials/assets') ?>
</head>
<body class="app-body">
<div class="app-frame d-lg-flex min-vh-100">

    <!-- ============================================================
         SIDEBAR (Island Card - Profile Style)
         ============================================================ -->
    <aside class="offcanvas-lg offcanvas-start app-sidebar sidebar-card d-flex flex-column" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">

        <div class="offcanvas-body d-flex flex-column p-0 h-100">

            <!-- Sidebar Brand Header -->
            <div class="sidebar-brand-header">
                <a href="<?= site_url('/') ?>" class="sidebar-brand-link" title="New Scoring Credit System">
                    <img src="<?= base_url('assets/images/logo-horizontal.png') ?>?v=<?= filemtime(FCPATH . 'assets/images/logo-horizontal.png') ?>" alt="New Scoring Credit System" class="sidebar-brand-logo sidebar-logo-full">
                    <img src="<?= base_url('assets/images/favicon.png') ?>?v=<?= filemtime(FCPATH . 'assets/images/favicon.png') ?>" alt="New Scoring Credit System" class="sidebar-brand-logo sidebar-logo-collapsed">
                </a>
                <button type="button" class="sidebar-collapse-btn" id="sidebarCollapseBtn" aria-label="Toggle menu" title="Kecilkan sidebar">
                    <i class="fa-solid fa-angles-left"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="side-nav flex-grow-1">

                <!-- Dashboard -->
                <a class="nav-item-link <?= $path === '' ? 'active' : '' ?>" href="<?= site_url('/') ?>">
                    <div class="nav-icon-box nav-icon-blue">
                        <i class="fa-solid fa-house"></i>
                    </div>
                    <span class="nav-label">Dashboard</span>
                    <i class="nav-arrow fa-solid fa-chevron-right"></i>
                </a>

                <!-- Dynamic groups -->
                <?php foreach ($groups as $group) : ?>
                    <?php
                    $open = false;
                    foreach ($group['items'] as $item) {
                        $open = $open || $item['active'];
                    }
                    ?>
                    <details class="nav-group" <?= $open ? 'open' : '' ?>>
                        <summary class="nav-group-summary">
                            <div class="nav-icon-box nav-icon-<?= esc($group['theme'] ?? 'blue') ?>">
                                <i class="<?= esc($group['icon']) ?>"></i>
                            </div>
                            <span class="nav-label"><?= esc($group['label']) ?></span>
                            <i class="nav-arrow fa-solid fa-chevron-down"></i>
                        </summary>
                        <div class="nav-tree">
                            <?php foreach ($group['items'] as $item) : ?>
                                <a class="nav-tree-item <?= $item['active'] ? 'active' : '' ?>" href="<?= esc($item['href']) ?>">
                                    <span class="nav-tree-node <?= $item['active'] ? 'active' : '' ?>"></span>
                                    <i class="nav-tree-icon <?= esc($item['icon'] ?? 'fa-solid fa-circle') ?>"></i>
                                    <span class="nav-tree-label"><?= esc($item['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endforeach; ?>

                <!-- Notifications -->
                <a class="nav-item-link <?= $path === 'notifications' ? 'active' : '' ?>" href="<?= site_url('notifications') ?>">
                    <div class="nav-icon-box nav-icon-pink">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <span class="nav-label">Notifikasi</span>
                    <span class="nav-badge-pill"><?= esc((string) ($unreadNav > 0 ? $unreadNav : 5)) ?></span>
                </a>

            </nav>

        </div>
    </aside>

    <!-- ============================================================
         WORKSPACE
         ============================================================ -->
    <div class="app-workspace flex-grow-1 min-w-0 d-flex flex-column">

        <!-- TOPBAR -->
        <header class="topbar">

            <!-- Mobile Offcanvas Menu Toggle -->
            <button type="button" class="topbar-square-btn d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Buka Menu" title="Buka Menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <!-- Brand Logo (Bank Sumut) -->
            <a class="topbar-brand" href="<?= site_url('/') ?>" title="Bank Sumut">
                <img src="<?= base_url('assets/images/logo-banksumut.png') ?>?v=<?= filemtime(FCPATH . 'assets/images/logo-banksumut.png') ?>" alt="Bank Sumut" class="topbar-logo topbar-logo-banksumut">
            </a>

            <!-- Vertical Divider -->
            <div class="topbar-divider d-none d-sm-block"></div>

            <!-- Page Title -->
            <h1 class="topbar-title mb-0"><?= esc($title) ?></h1>

            <!-- Spacer -->
            <div class="topbar-spacer"></div>

            <!-- 5. Search Bar -->
            <form class="topbar-search d-none d-lg-flex" method="get" action="<?= site_url('/') ?>">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="search" name="q"
                       value="<?= esc((string) ($_GET['q'] ?? '')) ?>"
                       placeholder="Cari debitur / produk..."
                       aria-label="Cari">
                <kbd class="search-kbd d-none d-xl-inline-block">Ctrl + K</kbd>
            </form>

            <!-- 6. Vertical Divider -->
            <div class="topbar-divider d-none d-md-block"></div>

            <!-- 7. Notification Bell Button -->
            <a class="topbar-square-btn position-relative" href="<?= site_url('notifications') ?>" aria-label="Notifikasi" title="Notifikasi">
                <i class="fa-regular fa-bell"></i>
                <span class="topbar-dot-badge"></span>
            </a>

            <!-- 8. Inbox / Mail Button -->
            <a class="topbar-square-btn position-relative" href="<?= site_url('notifications') ?>" aria-label="Pesan Masuk" title="Pesan Masuk">
                <i class="fa-regular fa-envelope"></i>
                <span class="topbar-count-badge"><?= esc((string) ($unreadNav > 0 ? $unreadNav : 6)) ?></span>
            </a>

            <!-- 9. User Profile Chip & Dropdown -->
            <details class="user-chip topbar-user-chip">
                <summary aria-label="Menu pengguna">
                    <span class="topbar-avatar"><?= esc($initials !== '' ? $initials : 'AI') ?></span>
                    <span class="topbar-user-info d-none d-md-flex">
                        <strong class="topbar-user-name"><?= esc($name !== '' ? $name : 'Admin IT') ?></strong>
                        <small class="topbar-user-role"><?= esc($subtitle !== '' ? $subtitle : 'ADMIN IT - KANTOR PUSAT') ?></small>
                    </span>
                    <i class="fa-solid fa-chevron-down topbar-chevron"></i>
                </summary>
                <div class="user-dropdown">
                    <div class="px-3 py-2 mb-1 border-bottom">
                        <div class="fw-bold" style="font-size:13px;color:var(--primary);"><?= esc($name !== '' ? $name : 'Admin IT') ?></div>
                        <div style="font-size:11px;color:var(--text-muted);"><?= esc($subtitle !== '' ? $subtitle : 'ADMIN IT - KANTOR PUSAT') ?></div>
                    </div>
                    <form method="post" action="<?= site_url('logout') ?>">
                        <?= csrf_field() ?>
                        <button class="btn-logout" type="submit">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            Keluar
                        </button>
                    </form>
                </div>
            </details>

            <!-- 10. App Launcher / Grid Button -->
            <button class="topbar-square-btn topbar-grid-btn d-none d-sm-inline-flex" type="button" aria-label="Aplikasi" title="Menu Aplikasi">
                <i class="fa-solid fa-table-cells"></i>
            </button>

        </header>

                <!-- PAGE CONTENT -->
        <main class="page<?= $isDashboard ? ' dash-page' : '' ?>">
