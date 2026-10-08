<?php
$active = $active ?? 'users';
$items = [
    'users' => [
        'href'  => site_url('access/users'),
        'icon'  => 'fa fa-users',
        'label' => 'Pengguna',
    ],
    'job_groups' => [
        'href'  => site_url('access/job-groups'),
        'icon'  => 'fa fa-briefcase',
        'label' => 'Kelompok Jabatan',
    ],
    'roles' => [
        'href'  => site_url('access/roles'),
        'icon'  => 'fa fa-user-shield',
        'label' => 'Role',
    ],
    'permissions' => [
        'href'  => site_url('access/permissions'),
        'icon'  => 'fa fa-key',
        'label' => 'Hak Akses',
    ],
];
?>
<div class="card card-borderless access-nav-card mb-3">
    <div class="card-body py-2 px-3">
        <ul class="nav nav-pills access-subnav flex-wrap gap-1">
            <?php foreach ($items as $key => $item) : ?>
            <li class="nav-item">
                <a class="nav-link<?= $active === $key ? ' active' : '' ?>" href="<?= esc($item['href']) ?>">
                    <i class="<?= esc($item['icon']) ?> me-1"></i><?= esc($item['label']) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
