<?php
/**
 * Color Admin Ultimate ui_widget_boxes — card-header-btn toolbar.
 *
 * @var bool $wrap     Wrap in .card-header-btn (default true)
 * @var bool $expand   Show expand (default true)
 * @var bool $reload   Show reload (default true)
 * @var bool $collapse Show collapse (default true)
 * @var bool $remove   Show remove (default true)
 */
$wrap     = $wrap ?? true;
$expand   = $expand ?? true;
$reload   = $reload ?? true;
$collapse = $collapse ?? true;
$remove   = $remove ?? true;
?>
<?php if ($wrap) : ?><div class="card-header-btn"><?php endif; ?>
    <?php if ($expand) : ?>
    <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="card-expand" title="Perbesar"><i class="fa fa-expand"></i></a>
    <?php endif; ?>
    <?php if ($reload) : ?>
    <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="card-reload" title="Muat ulang"><i class="fa fa-redo"></i></a>
    <?php endif; ?>
    <?php if ($collapse) : ?>
    <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="card-collapse" title="Ciutkan"><i class="fa fa-minus"></i></a>
    <?php endif; ?>
    <?php if ($remove) : ?>
    <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="card-remove" title="Tutup"><i class="fa fa-xmark"></i></a>
    <?php endif; ?>
<?php if ($wrap) : ?></div><?php endif; ?>
