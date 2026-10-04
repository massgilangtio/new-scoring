<?= view('partials/shell_start', ['profile' => $profile, 'title' => 'Jejak Audit']) ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h2><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Jejak Audit</h2>
        <p class="text-muted mb-0">Catatan ini hanya dapat ditambah. Isi sebelum dan sesudah perubahan tidak dapat diubah atau dihapus.</p>
    </div>
</div>

<!-- Audit Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="auditTable" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Role</th>
                        <th>Aksi</th>
                        <th>Objek</th>
                        <th>Alasan</th>
                        <th>Sebelum</th>
                        <th>Sesudah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) : ?>
                    <tr>
                        <td style="white-space:nowrap;font-size:12px;" class="text-muted"><?= esc($item['occurred_at']) ?></td>
                        <td>
                            <span class="badge" style="background:var(--info-bg);color:#075985;font-size:11px;">
                                <?= esc($item['actor_role_name']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background:var(--surface-2);color:var(--text);font-size:11px;border:1px solid var(--border);">
                                <?= esc($item['action']) ?>
                            </span>
                        </td>
                        <td style="font-size:12px;">
                            <strong><?= esc($item['object_type']) ?></strong>
                            <small class="text-muted ms-1"><?= esc($item['object_id']) ?></small>
                        </td>
                        <td class="text-muted" style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= esc($item['reason'] ?? '-') ?>
                        </td>
                        <td>
                            <?php if (! empty($item['before'])) : ?>
                            <code class="d-inline-block" style="font-size:10px;max-width:160px;overflow:auto;background:var(--surface-2);padding:4px 6px;border-radius:6px;color:var(--danger);">
                                <?= esc(json_encode($item['before'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?>
                            </code>
                            <?php else : ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (! empty($item['after'])) : ?>
                            <code class="d-inline-block" style="font-size:10px;max-width:160px;overflow:auto;background:var(--success-bg);padding:4px 6px;border-radius:6px;color:var(--success);">
                                <?= esc(json_encode($item['after'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?>
                            </code>
                            <?php else : ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fa-solid fa-shield-halved"></i>
                                <h6>Belum Ada Catatan Audit</h6>
                                <p>Belum ada aktivitas yang tercatat dalam jejak audit.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    App.initDT('#auditTable', {
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [5, 6] },
            { searchable: false, targets: [5, 6] }
        ]
    });
});
</script>

<?= view('partials/shell_end') ?>
