<?php
$active = $active ?? 'scoring';
?>
<ul class="nav nav-pills scoring-subnav gap-2 mb-3">
    <li class="nav-item">
        <a class="nav-link<?= $active === 'scoring' ? ' active' : '' ?>" href="<?= site_url('reports/scoring') ?>">
            <i class="fa fa-chart-pie me-1"></i> Scoring
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link<?= $active === 'debtors' ? ' active' : '' ?>" href="<?= site_url('reports/debtors') ?>">
            <i class="fa fa-user-clock me-1"></i> Riwayat Debitur
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link<?= $active === 'products' ? ' active' : '' ?>" href="<?= site_url('reports/products') ?>">
            <i class="fa fa-box-archive me-1"></i> Riwayat Produk
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link<?= $active === 'changes' ? ' active' : '' ?>" href="<?= site_url('reports/changes') ?>">
            <i class="fa fa-code-compare me-1"></i> Perubahan Parameter
        </a>
    </li>
</ul>
