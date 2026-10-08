<?php
/**
 * Solid Color Admin KPI row.
 *
 * @var list<array{label:string,value:string|int|float,sub?:string,tone?:string,icon?:string,id?:string}> $items
 */
$items = $items ?? [];
$tones = ['blue', 'teal', 'red', 'indigo', 'orange', 'cyan'];
$count = max(1, count($items));
$col = match (true) {
    $count <= 1 => 12,
    $count === 2 => 6,
    $count === 4 => 3,
    default => 4,
};
?>
<div class="row g-2 mb-3">
    <?php foreach ($items as $i => $item) :
        $tone = (string) ($item['tone'] ?? $tones[$i % count($tones)]);
        $icon = (string) ($item['icon'] ?? 'solar:chart-bold-duotone');
        $valueId = (string) ($item['id'] ?? '');
        ?>
    <div class="col-<?= (int) $col ?>">
        <div class="card card-borderless rounded-3 overflow-hidden bg-<?= esc($tone) ?> h-100" data-bs-theme="dark">
            <div class="card-body position-relative z-3">
                <div class="fw-bold text-white small mb-1 d-flex align-items-center gap-2">
                    <iconify-icon icon="<?= esc($icon) ?>" class="fs-6"></iconify-icon>
                    <?= esc((string) ($item['label'] ?? '')) ?>
                </div>
                <div class="fw-bold fs-2 text-white"<?= $valueId !== '' ? ' id="' . esc($valueId) . '"' : '' ?>>
                    <?= esc((string) ($item['value'] ?? '0')) ?>
                </div>
                <?php if (! empty($item['sub'])) : ?>
                <div class="fw-semibold text-white text-opacity-75 small mb-0"><?= esc((string) $item['sub']) ?></div>
                <?php endif; ?>
            </div>
            <div class="position-absolute top-0 end-0 mt-n5 ps-5 w-25 d-none d-md-block">
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle ms-n5 position-absolute top-0 start-0"></div>
                <div class="w-250px h-250px bg-black bg-opacity-25 rounded-circle mt-n5 position-absolute top-0 start-0"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
