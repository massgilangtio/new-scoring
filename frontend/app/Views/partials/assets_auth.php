<?php
// Auth pages: Color Admin core only + thin auth-phase2 (brand media / MFA overlay).
// Do NOT load app.css / app-legacy.css — CA owns typography & form controls.
?>
<link rel="icon" href="<?= base_url('assets/images/favicon.png') ?>">
<link href="<?= base_url('assets/color-admin/css/vendor.min.css') ?>" rel="stylesheet">
<link href="<?= base_url('assets/color-admin/css/default/app.min.css') ?>" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('css/auth-phase2.css') ?>?v=<?= @filemtime(FCPATH . 'css/auth-phase2.css') ?: time() ?>">
<script src="<?= base_url('assets/color-admin/js/iconify/iconify-icon.min.js') ?>"></script>
<script src="<?= base_url('assets/color-admin/js/vendor.min.js') ?>"></script>
<script src="<?= base_url('assets/color-admin/js/app.min.js') ?>"></script>
