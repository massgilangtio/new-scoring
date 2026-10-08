<!doctype html>
<html lang="id" class="ca-ui">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Autentikator — New Scoring Credit System</title>
    <?= view('partials/assets_auth') ?>
</head>
<body class="pace-top">
    <div id="loader" class="app-loader"><span class="spinner"></span></div>

    <div id="app" class="app app-full-height app-without-header">
        <div class="auth auth-with-box auth-with-media">
            <div class="auth-container">
                <div class="auth-media">
                    <img src="<?= base_url('assets/images/background-login.png') ?>" class="auth-media-img object-fit-cover" alt="New Scoring Credit System">
                    <div class="auth-media-content p-0" aria-hidden="true"></div>
                </div>
                <div class="auth-content">
                    <?php $setup = ($step ?? '') === 'mfa_setup'; ?>
                    <div class="text-center mb-3">
                        <img class="auth-brand-logo auth-brand-logo-light" src="<?= base_url('assets/images/logo-horizontal.png') ?>" alt="New Scoring Credit System">
                    </div>

                    <div class="row g-2 mb-4 text-center">
                        <div class="col-4">
                            <div class="small fw-semibold <?= $setup ? 'text-theme' : 'text-success' ?>">1. <?= $setup ? 'Scan QR' : 'Login' ?></div>
                        </div>
                        <div class="col-4">
                            <div class="small fw-semibold <?= $setup ? 'text-muted' : 'text-theme' ?>">2. Verifikasi OTP</div>
                        </div>
                        <div class="col-4">
                            <div class="small fw-semibold text-muted">3. Selesai</div>
                        </div>
                    </div>

                    <?php if ($setup) : ?>
                        <h1 class="text-center fs-3">Aktifkan Authenticator</h1>
                        <div class="text-muted text-center mb-4">Pindai QR Code, lalu masukkan 6 digit kode.</div>
                    <?php else : ?>
                        <h1 class="text-center fs-3">Verifikasi Dua Langkah</h1>
                        <div class="text-muted text-center mb-4">Masukkan 6 digit kode dari Google Authenticator.</div>
                    <?php endif; ?>

                    <?php if (! empty($error)) : ?>
                        <div class="alert alert-danger"><?= esc($error) ?></div>
                    <?php endif; ?>

                    <div class="row g-3 align-items-center mb-3">
                        <?php if ($setup && ! empty($qr)) : ?>
                            <div class="col-12 col-md-5 text-center">
                                <div class="p-2 bg-white border rounded d-inline-block">
                                    <?= $qr ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-12<?= ($setup && ! empty($qr)) ? ' col-md-7' : '' ?>">
                            <form method="post" action="<?= site_url('mfa') ?>">
                                <?= csrf_field() ?>
                                <div class="mb-3">
                                    <label class="form-label" for="code">Kode OTP (6 Digit) <span class="text-danger">*</span></label>
                                    <input id="code" name="code" class="form-control form-control-lg h-45px fs-15px text-center" inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus>
                                </div>
                                <button type="submit" class="btn btn-theme btn-lg fw-bold d-flex align-items-center justify-content-center w-100 h-45px">
                                    Verifikasi &amp; Masuk
                                </button>
                                <p class="text-muted small mt-2 mb-0">Kode berubah otomatis setiap 30 detik.</p>
                            </form>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="<?= site_url('login') ?>" class="link-dark fw-bold small">← Kembali ke halaman login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
