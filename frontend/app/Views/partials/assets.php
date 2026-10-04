<?php
// Phase 3: Plugin Integration — semua CDN dan asset dependencies
// Urutan load: Font → CSS Plugins → JS Plugins (jQuery, Bootstrap, DataTables, Select2, SweetAlert2) → app.css
?>
<link rel="icon" href="<?= base_url('assets/images/favicon.png') ?>">

<!-- Google Fonts: DM Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap"
  rel="stylesheet"
/>

<!-- Font Awesome 6 Free -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

<!-- Bootstrap 5 CSS -->
<link rel="stylesheet" href="<?= base_url('vendor/bootstrap/bootstrap.min.css') ?>">

<!-- DataTables CSS (Bootstrap 5 theme) -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.min.css">

<!-- Select2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css">

<!-- App CSS -->
<link rel="stylesheet" href="<?= base_url('css/app.css') ?>?v=<?= filemtime(FCPATH . 'css/app.css') ?>">

<!-- Core JavaScript Libraries loaded upfront for view scripts -->
<!-- jQuery 3.7.x -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>

<!-- Bootstrap 5 Bundle (Popper included) -->
<script src="<?= base_url('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>

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
<script src="<?= base_url('js/app.js') ?>?v=<?= filemtime(FCPATH . 'js/app.js') ?>"></script>
