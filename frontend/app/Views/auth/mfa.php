<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autentikator — New Scoring Credit System</title>
    <?= view('partials/assets') ?>
</head>
<body class="auth-body">
    <main class="container-fluid px-0">
        <div class="row g-0 min-vh-100">
            <?= view('partials/auth_brand') ?>
            <section class="col-12 col-lg-6 auth-panel d-flex align-items-center justify-content-center p-4">
                <article class="auth-card mfa-card w-100" style="max-width: 520px;">
                    <?php $setup = ($step ?? '') === 'mfa_setup'; ?>
                    <ol class="steps list-unstyled d-flex justify-content-between mb-4">
                        <li class="<?= $setup ? 'current text-primary' : 'done text-success' ?> d-flex align-items-center gap-2">
                            <span class="badge rounded-circle <?= $setup ? 'bg-primary' : 'bg-success' ?> text-white">1</span>
                            <div>
                                <strong class="d-block small"><?= $setup ? 'Scan QR Code' : 'Login' ?></strong>
                                <small class="text-muted d-block" style="font-size: 11px;"><?= $setup ? 'Google Authenticator' : 'Berhasil' ?></small>
                            </div>
                        </li>
                        <li class="<?= $setup ? 'text-muted' : 'current text-primary' ?> d-flex align-items-center gap-2">
                            <span class="badge rounded-circle <?= $setup ? 'bg-secondary' : 'bg-primary' ?> text-white">2</span>
                            <div>
                                <strong class="d-block small">Verifikasi Kode</strong>
                                <small class="text-muted d-block" style="font-size: 11px;">6 Digit OTP</small>
                            </div>
                        </li>
                        <li class="text-muted d-flex align-items-center gap-2">
                            <span class="badge rounded-circle bg-secondary text-white">3</span>
                            <div>
                                <strong class="d-block small">Selesai</strong>
                                <small class="text-muted d-block" style="font-size: 11px;">Masuk Sistem</small>
                            </div>
                        </li>
                    </ol>

                    <?php if ($setup) : ?>
                        <h2 class="fw-bold fs-4 mb-1 text-dark">Aktifkan Google Authenticator</h2>
                        <p class="text-muted small mb-4">Pindai kode QR di bawah dengan aplikasi Google Authenticator, lalu masukkan 6 digit kode yang tampil.</p>
                    <?php else : ?>
                        <h2 class="fw-bold fs-4 mb-1 text-dark">Verifikasi Dua Langkah</h2>
                        <p class="text-muted small mb-4">Masukkan 6 digit kode dari aplikasi Google Authenticator di perangkat Anda.</p>
                    <?php endif; ?>

                    <?php if (! empty($error)) : ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
                            <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                            <div><?= esc($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 align-items-center mb-4">
                        <?php if ($setup && ! empty($qr)) : ?>
                            <div class="col-12 col-md-5 text-center">
                                <div class="qr p-2 bg-white border rounded shadow-sm d-inline-block">
                                    <?= $qr ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-12<?= ($setup && ! empty($qr)) ? ' col-md-7' : '' ?>">
                            <form method="post" action="<?= site_url('mfa') ?>">
                                <?= csrf_field() ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="code">Kode OTP (6 Digit)</label>
                                    <input id="code" name="code" class="form-control form-control-lg text-center font-monospace fs-4 tracking-wider" 
                                           inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                                    <i class="fa-solid fa-shield-check me-2"></i>Verifikasi &amp; Masuk
                                </button>
                                <p class="text-muted small mb-0">
                                    <i class="fa-regular fa-clock me-1"></i>Kode berubah otomatis setiap 30 detik.
                                </p>
                            </form>
                        </div>
                    </div>

                    <div class="border-top pt-3 text-center">
                        <a href="<?= site_url('login') ?>" class="text-decoration-none small text-muted">
                            <i class="fa-solid fa-arrow-left me-1"></i>Kembali ke halaman login
                        </a>
                    </div>
                </article>
            </section>
        </div>
    </main>
</body>
</html>
