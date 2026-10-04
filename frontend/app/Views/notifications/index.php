<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Notifikasi']) ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-bell me-2 text-primary"></i>Notifikasi</h2>
        <p class="text-muted mb-0"><?= esc((string) $unread) ?> belum dibaca</p>
    </div>
</div>

<?php if (empty($items)) : ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state py-5">
            <i class="fa-regular fa-bell-slash"></i>
            <h6>Tidak Ada Notifikasi</h6>
            <p>Belum ada notifikasi. Notifikasi akan muncul di sini ketika ada aktivitas baru.</p>
        </div>
    </div>
</div>
<?php else : ?>
<div class="notice-list">
    <?php foreach ($items as $item) : ?>
    <article class="notice <?= empty($item['read']) ? 'unread' : '' ?>">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div class="flex-grow-1">
                <p class="eyebrow mb-1">
                    <i class="fa-regular fa-clock me-1"></i><?= esc($item['created_at']) ?>
                    <?php if (empty($item['read'])) : ?>
                        <span class="badge ms-2" style="background:var(--secondary);color:#fff;font-size:10px;">Baru</span>
                    <?php endif; ?>
                </p>
                <h2 class="h6 fw-bold mb-1"><?= esc($item['title']) ?></h2>
                <p class="text-muted mb-0" style="font-size:13px;"><?= esc($item['body']) ?></p>
            </div>
            <?php if (empty($item['read'])) : ?>
            <div class="flex-shrink-0">
                <form method="post" action="<?= site_url('notifications/' . $item['id'] . '/read') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="fa-regular fa-eye me-1"></i> Tandai Dibaca
                    </button>
                </form>
            </div>
            <?php else : ?>
            <div class="flex-shrink-0">
                <span class="text-muted" style="font-size:12px;"><i class="fa-regular fa-circle-check me-1"></i>Sudah dibaca</span>
            </div>
            <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?= view('partials/shell_end') ?>
