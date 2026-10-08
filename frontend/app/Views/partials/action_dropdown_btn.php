<?php
/**
 * Table action column — Export-style split dropdown with yellow gradient.
 * Left control shows icon only (no "Aksi" text).
 *
 * @var string $label    Accessible label / title (default: Aksi)
 * @var string $icon     Font Awesome icon classes (default: fa-solid fa-ellipsis-vertical)
 * @var string $menuHtml Inner <li>…</li> for the dropdown menu
 * @var string $align    dropdown-menu-end|dropdown-menu-start (default: end)
 */
$label = $label ?? 'Aksi';
$icon = $icon ?? 'fa-solid fa-ellipsis-vertical';
$menuHtml = $menuHtml ?? '';
$align = $align ?? 'dropdown-menu-end';
?>
<div class="btn-group btn-action-group">
    <button type="button"
            class="btn btn-xs btn-action-yellow btn-action-yellow-icon"
            title="<?= esc($label) ?>"
            aria-label="<?= esc($label) ?>"
            onclick="this.nextElementSibling.click()">
        <i class="<?= esc($icon) ?>"></i>
    </button>
    <button type="button"
            class="btn btn-xs btn-action-yellow dropdown-toggle dropdown-toggle-split"
            data-bs-toggle="dropdown"
            data-bs-popper-config='{"strategy":"fixed"}'
            aria-expanded="false"
            title="Menu <?= esc($label) ?>"
            aria-label="Menu <?= esc($label) ?>">
        <span class="visually-hidden">Toggle Dropdown</span>
        <b class="caret"></b>
    </button>
    <ul class="dropdown-menu <?= esc($align) ?> dropdown-action-menu shadow-lg">
        <?= $menuHtml ?>
    </ul>
</div>
