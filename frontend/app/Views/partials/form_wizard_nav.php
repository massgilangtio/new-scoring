<?php
/**
 * Color Admin form_wizards.html — nav-wizards layout (UX step indicator).
 *
 * @var array  $steps   [['label' => string, 'href' => ?string], ...]
 * @var int    $current 1-based active step
 * @var string $variant '1' | '2' | '3' (default '1')
 */
$steps   = $steps ?? [];
$current = max(1, (int) ($current ?? 1));
$variant = in_array((string) ($variant ?? '1'), ['1', '2', '3'], true) ? (string) $variant : '1';
?>
<div class="nav-wizards-container mb-3">
    <nav class="nav nav-wizards-<?= esc($variant) ?>">
        <?php foreach ($steps as $i => $step) : ?>
            <?php
            $n     = $i + 1;
            $label = (string) ($step['label'] ?? ('Langkah ' . $n));
            $href  = (string) ($step['href'] ?? 'javascript:;');
            $cls   = 'nav-link';
            if ($n < $current) {
                $cls .= ' completed';
            } elseif ($n === $current) {
                $cls .= ' active';
            } else {
                $cls .= ' disabled';
            }
            ?>
            <div class="nav-item col">
                <a class="<?= esc($cls) ?>" href="<?= esc($href) ?>"<?= $n > $current ? ' tabindex="-1" aria-disabled="true"' : '' ?>>
                    <div class="nav-no"><?= esc((string) $n) ?></div>
                    <div class="nav-text"><?= esc($label) ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </nav>
</div>
