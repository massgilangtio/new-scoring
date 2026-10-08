<?php
/**
 * Color Admin Ultimate ui_widget_boxes (code-3) — Action split btn-group.
 *
 * @var string      $mode               'table' (Excel/PDF/CSV) or 'print' (PDF/cetak only)
 * @var string|null $importModalTarget  Bootstrap modal selector, e.g. '#importModal'
 * @var string      $label              Button label (default: Export)
 */
$mode = $mode ?? 'table';
$importModalTarget = $importModalTarget ?? null;
$label = $label ?? 'Export';
$quickTitle = $mode === 'print' ? 'Export PDF / Cetak' : 'Export Excel (.xls)';
?>
<div class="btn-group my-n1">
    <button type="button" class="btn btn-success btn-xs" id="btnExportQuick" title="<?= esc($quickTitle) ?>">
        <?= esc($label) ?>
    </button>
    <button type="button" class="btn btn-success btn-xs dropdown-toggle" id="btnExportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <b class="caret"></b>
    </button>
    <div class="dropdown-menu dropdown-menu-end" data-bs-theme="light" aria-labelledby="btnExportDropdown">
        <?php if ($mode === 'print') : ?>
        <button type="button" class="dropdown-item" id="btnExportPdf">
            <i class="fa fa-file-pdf text-danger me-2"></i> Export PDF / Cetak
        </button>
        <?php else : ?>
        <button type="button" class="dropdown-item" id="btnExportExcel">
            <i class="fa fa-file-excel text-success me-2"></i> Export Excel (.xls)
        </button>
        <button type="button" class="dropdown-item" id="btnExportPdf">
            <i class="fa fa-file-pdf text-danger me-2"></i> Export PDF
        </button>
        <button type="button" class="dropdown-item" id="btnExportCsv">
            <i class="fa fa-file-csv text-info me-2"></i> Export CSV (.csv)
        </button>
        <?php if ($importModalTarget !== null && $importModalTarget !== '') : ?>
        <div class="dropdown-divider"></div>
        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="<?= esc($importModalTarget) ?>">
            <i class="fa fa-file-import text-success me-2"></i> Import Excel (.xlsx)
        </button>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
