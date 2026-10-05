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

// --- Navigation Groups (functionality preserved) ---
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
$displayName = $name !== '' ? $name : 'Admin IT';
$displayRole = $subtitle !== '' ? $subtitle : 'ADMIN IT - KANTOR PUSAT';
$displayInitials = $initials !== '' ? $initials : 'AI';
$badgeCount = $unreadNav > 0 ? $unreadNav : 0;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= esc($title) ?> — New Scoring Credit System</title>
    <?= view('partials/assets') ?>
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
                    <img src="<?= base_url('assets/images/logo-horizontal.png') ?>?v=<?= @filemtime(FCPATH . 'assets/images/logo-horizontal.png') ?: time() ?>" alt="New Scoring Credit System" class="brand-logo-img">
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
                            <div class="fw-bold"><?= esc($displayName) ?></div>
                            <div class="small text-muted"><?= esc($displayRole) ?></div>
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
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1"><?= esc($displayName) ?></div>
                                    <div class="menu-caret ms-auto"></div>
                                </div>
                                <small><?= esc($displayRole) ?></small>
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
