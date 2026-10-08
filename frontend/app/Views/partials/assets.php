<?php
// Phase 1 — Color Admin core + existing functional plugins
// Order: Color Admin CSS → plugin CSS → compat → core JS → plugin JS → app.js
?>
<link rel="icon" href="<?= base_url('assets/images/favicon.png') ?>">

<!-- Color Admin core CSS -->
<link href="<?= base_url('assets/color-admin/css/vendor.min.css') ?>" rel="stylesheet">
<link href="<?= base_url('assets/color-admin/css/default/app.min.css') ?>" rel="stylesheet">

<!-- DataTables CSS (Bootstrap 5 theme) -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.min.css">

<!-- Select2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css">

<!-- App CSS (legacy chrome quarantined) + CA shell/widgets + Phase 3 quarantine -->
<!-- NOTE: css/app-legacy.css is NOT loaded (old sidebar/topbar island) -->
<link rel="stylesheet" href="<?= base_url('css/app.css') ?>?v=<?= @filemtime(FCPATH . 'css/app.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/shell-phase1.css') ?>?v=<?= @filemtime(FCPATH . 'css/shell-phase1.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/ca-widgets.css') ?>?v=<?= @filemtime(FCPATH . 'css/ca-widgets.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/ca-phase3-quarantine.css') ?>?v=<?= @filemtime(FCPATH . 'css/ca-phase3-quarantine.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/dash-phase3.css') ?>?v=<?= @filemtime(FCPATH . 'css/dash-phase3.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/master-phase4.css') ?>?v=<?= @filemtime(FCPATH . 'css/master-phase4.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/access-phase5.css') ?>?v=<?= @filemtime(FCPATH . 'css/access-phase5.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/scoring-phase6.css') ?>?v=<?= @filemtime(FCPATH . 'css/scoring-phase6.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/transactions-phase7.css') ?>?v=<?= @filemtime(FCPATH . 'css/transactions-phase7.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/approvals-phase8.css') ?>?v=<?= @filemtime(FCPATH . 'css/approvals-phase8.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/reports-phase9.css') ?>?v=<?= @filemtime(FCPATH . 'css/reports-phase9.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('css/form-wizards.css') ?>?v=<?= @filemtime(FCPATH . 'css/form-wizards.css') ?: time() ?>">

<!-- Iconify (Color Admin icons) -->
<script src="<?= base_url('assets/color-admin/js/iconify/iconify-icon.min.js') ?>"></script>

<!-- Color Admin core JS (includes jQuery + Bootstrap) -->
<script src="<?= base_url('assets/color-admin/js/vendor.min.js') ?>"></script>
<script src="<?= base_url('assets/color-admin/js/app.min.js') ?>"></script>

<!-- DataTables 2 + Bootstrap 5 + Responsive -->
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/responsive.bootstrap5.min.js"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"></script>

<!-- Global App JS -->
<script src="<?= base_url('js/app.js') ?>?v=<?= @filemtime(FCPATH . 'js/app.js') ?: time() ?>"></script>
<script src="<?= base_url('js/form-wizard.js') ?>?v=<?= @filemtime(FCPATH . 'js/form-wizard.js') ?: time() ?>"></script>
