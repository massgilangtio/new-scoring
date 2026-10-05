<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Masuk — New Scoring Credit System</title>
    <?= view('partials/assets_auth') ?>
</head>
<body class="pace-top">
    <div id="loader" class="app-loader"><span class="spinner"></span></div>

    <div id="app" class="app app-full-height app-without-header">
        <!-- Color Admin: auth_with_image_sign_in -->
        <div class="auth auth-with-box auth-with-media">
            <div class="auth-container">
                <div class="auth-media">
                    <img src="<?= base_url('assets/images/background-login.png') ?>" class="auth-media-img object-fit-cover" alt="New Scoring Credit System">
                    <!-- Artwork already includes brand copy; keep CA structure with light veil only -->
                    <div class="auth-media-content p-0" aria-hidden="true"></div>
                </div>

                <div class="auth-content">
                    <form id="loginForm" method="post" action="<?= site_url('login') ?>" name="login_form">
                        <div class="text-center mb-3">
                            <img class="auth-brand-logo auth-brand-logo-light" src="<?= base_url('assets/images/logo-horizontal.png') ?>" alt="New Scoring Credit System">
                        </div>
                        <h1 class="text-center">Masuk</h1>
                        <div class="text-muted text-center mb-4">
                            Silakan masuk untuk mengakses New Scoring Credit System.
                        </div>

                        <?php if (! empty($error)) : ?>
                            <p id="loginError" class="alert alert-danger"><?= esc($error) ?></p>
                        <?php else : ?>
                            <p id="loginError" class="alert alert-danger" hidden></p>
                        <?php endif; ?>

                        <?= csrf_field() ?>
                        <div class="mb-3 pb-1">
                            <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                            <input id="username" name="username" type="text" class="form-control form-control-lg h-45px fs-15px" autocomplete="username" placeholder="Masukkan username Anda" required>
                        </div>
                        <div class="mb-3 pb-1">
                            <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                            <div class="password-field">
                                <input id="password" name="password" type="password" class="form-control form-control-lg h-45px fs-15px" autocomplete="current-password" placeholder="Masukkan password Anda" required>
                                <button class="reveal" type="button" aria-label="Tampilkan password">
                                    <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="submit" class="btn btn-theme btn-lg fw-bold d-flex align-items-center justify-content-center w-100 h-45px login-submit">
                                Masuk
                            </button>
                        </div>
                        <div class="text-center text-secondary">
                            Belum memiliki akses? <span class="fw-bold">Hubungi Administrator</span>.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="mfa-layer" id="mfaLayer" hidden>
        <section class="mfa-dialog" role="dialog" aria-modal="true" aria-labelledby="mfaScanHeading">
            <aside class="mfa-side">
                <img class="mfa-art" src="<?= base_url('assets/images/background-mfa.png') ?>" alt="Aktifkan Google Authenticator"
                     onerror="this.style.display='none'">
            </aside>
            <div class="mfa-main">
                <button class="mfa-close" type="button" data-mfa-cancel aria-label="Tutup">×</button>
                <ol class="mfa-stepper">
                    <li class="is-on" id="stepScan"><span>1</span>Scan QR Code</li>
                    <li class="mfa-step-line" aria-hidden="true"></li>
                    <li id="stepCode"><span>2</span>Verifikasi Kode</li>
                </ol>
                <div class="mfa-setup" id="mfaSetup" hidden>
                    <h3 id="mfaScanHeading" class="fs-4 fw-bold mb-2">Scan QR Code</h3>
                    <p class="text-muted">Ikuti langkah-langkah berikut untuk menghubungkan akun Anda dengan Google Authenticator.</p>
                    <div class="mfa-scan">
                        <div class="mfa-qr-frame">
                            <div class="mfa-qr" id="mfaQr"></div>
                        </div>
                        <ol class="mfa-guide">
                            <li>
                                <span>1</span>
                                <img src="<?= base_url('assets/images/icon-auth.png') ?>" alt="">
                                <div><strong>Buka aplikasi Google Authenticator</strong> di perangkat Anda.</div>
                            </li>
                            <li>
                                <span>2</span>
                                <img src="<?= base_url('assets/images/icon-add.png') ?>" alt="">
                                <div><strong>Pilih tombol “+”</strong> atau “Add Account”.</div>
                            </li>
                            <li>
                                <span>3</span>
                                <img src="<?= base_url('assets/images/icon-scan.png') ?>" alt="">
                                <div><strong>Pilih “Scan a QR code”</strong> kemudian arahkan kamera ke QR Code.</div>
                            </li>
                        </ol>
                    </div>
                    <p class="mfa-note"><span class="badge bg-theme rounded-pill">i</span><span>Setelah akun tertambah, masukkan kode 6 digit dari aplikasi untuk melanjutkan.</span></p>
                    <div class="mfa-actions">
                        <button class="mfa-ghost" type="button" data-mfa-cancel>Batal</button>
                        <button class="mfa-next" type="button" id="mfaContinue">Lanjut ke Verifikasi →</button>
                    </div>
                </div>
                <div class="mfa-verify" id="mfaVerify" hidden>
                    <div class="mfa-otp-card">
                        <img src="<?= base_url('assets/images/icon-auth.png') ?>" alt="">
                        <div>
                            <strong>Masukkan Kode Verifikasi</strong>
                            <p class="mb-0 text-muted small">Buka aplikasi Google Authenticator, lalu masukkan 6 digit kode yang ditampilkan.</p>
                        </div>
                    </div>
                    <p id="mfaError" class="alert alert-danger" hidden></p>
                    <form id="mfaForm" method="post" action="<?= site_url('mfa') ?>">
                        <?= csrf_field() ?>
                        <div class="otp-boxes">
                            <input class="otp-digit" inputmode="numeric" maxlength="1" aria-label="Digit 1">
                            <input class="otp-digit" inputmode="numeric" maxlength="1" aria-label="Digit 2">
                            <input class="otp-digit" inputmode="numeric" maxlength="1" aria-label="Digit 3">
                            <input class="otp-digit" inputmode="numeric" maxlength="1" aria-label="Digit 4">
                            <input class="otp-digit" inputmode="numeric" maxlength="1" aria-label="Digit 5">
                            <input class="otp-digit" inputmode="numeric" maxlength="1" aria-label="Digit 6">
                        </div>
                        <input id="otpCode" name="code" type="hidden">
                        <p class="mfa-timer">
                            <iconify-icon icon="solar:clock-circle-bold-duotone"></iconify-icon>
                            <span>Kode akan berubah dalam <strong id="otpSeconds">30 detik</strong></span>
                        </p>
                        <div class="mfa-meter" aria-hidden="true"><span id="otpMeter"></span></div>
                        <div class="mfa-tip">
                            <iconify-icon icon="solar:shield-check-bold-duotone" class="fs-4"></iconify-icon>
                            <p class="mb-0"><strong>Tips:</strong> Pastikan waktu perangkat otomatis (network-provided time) agar kode selalu valid.</p>
                        </div>
                        <div class="mfa-actions">
                            <button class="mfa-ghost" type="button" id="mfaBack">Kembali</button>
                            <button class="mfa-next" type="submit">Verifikasi →</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <script>
        var loginForm = document.getElementById('loginForm');
        var mfaLayer = document.getElementById('mfaLayer');
        var mfaSetup = document.getElementById('mfaSetup');
        var mfaVerify = document.getElementById('mfaVerify');
        var mfaError = document.getElementById('mfaError');

        var csrfToken = '';

        function csrfValue() {
            var match = document.cookie.match(/(?:^|; )csrf_cookie_name=([^;]+)/);
            return match ? decodeURIComponent(match[1]) : '';
        }

        function applyCsrf(data) {
            var token = csrfToken || csrfValue();
            if (token) {
                data.set('csrf_test_name', token);
            }
        }

        function rememberCsrf(body) {
            if (body && body.csrf) {
                csrfToken = body.csrf;
            }
        }

        var otpTimer = null;

        function paintTimer() {
            var left = 30 - (Math.floor(Date.now() / 1000) % 30);
            document.getElementById('otpSeconds').textContent = left + ' detik';
            document.getElementById('otpMeter').style.width = (left / 30 * 100) + '%';
        }

        function startTimer() {
            paintTimer();
            if (otpTimer) {
                clearInterval(otpTimer);
            }
            otpTimer = setInterval(paintTimer, 250);
        }

        function stopTimer() {
            if (otpTimer) {
                clearInterval(otpTimer);
                otpTimer = null;
            }
        }

        function showPanel(setup) {
            mfaSetup.hidden = !setup;
            mfaVerify.hidden = setup;
            document.getElementById('stepScan').classList.toggle('is-on', setup);
            document.getElementById('stepScan').classList.toggle('is-done', !setup);
            document.getElementById('stepCode').classList.toggle('is-on', !setup);
            document.body.classList.add('mfa-open');
            mfaLayer.hidden = false;
            if (setup) {
                stopTimer();
                return;
            }
            startTimer();
            var firstDigit = document.querySelector('.otp-digit');
            if (firstDigit) {
                firstDigit.focus();
            }
        }

        document.querySelector('.reveal').addEventListener('click', function () {
            var input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        });

        loginForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var data = new FormData(loginForm);
            applyCsrf(data);
            fetch(loginForm.action, {
                method: 'POST',
                body: data,
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).then(function (response) { return response.json(); }).then(function (body) {
                rememberCsrf(body);
                if (!body.ok) {
                    document.getElementById('loginError').hidden = false;
                    document.getElementById('loginError').textContent = body.message || 'Login gagal';
                    return;
                }
                document.getElementById('loginError').hidden = true;
                document.getElementById('mfaQr').innerHTML = body.qr || '';
                fromSetup = body.step === 'mfa_setup';
                showPanel(fromSetup);
            });
        });

        var fromSetup = false;

        document.getElementById('mfaContinue').addEventListener('click', function () {
            showPanel(false);
        });

        document.getElementById('mfaBack').addEventListener('click', function () {
            if (fromSetup && mfaSetup.hidden) {
                showPanel(true);
                return;
            }
            cancelMfa();
        });

        document.querySelectorAll('[data-mfa-cancel]').forEach(function (button) {
            button.addEventListener('click', cancelMfa);
        });

        function cancelMfa() {
            var data = new FormData();
            applyCsrf(data);
            fetch('<?= site_url('login/cancel-mfa') ?>', {
                method: 'POST',
                body: data,
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).finally(function () {
                stopTimer();
                document.body.classList.remove('mfa-open');
                mfaLayer.hidden = true;
            });
        }

        var digits = Array.prototype.slice.call(document.querySelectorAll('.otp-digit'));
        digits.forEach(function (digit, index) {
            digit.addEventListener('input', function () {
                digit.value = digit.value.replace(/\D/g, '').slice(-1);
                digit.classList.toggle('is-filled', digit.value !== '');
                if (digit.value && digits[index + 1]) {
                    digits[index + 1].focus();
                }
            });
            digit.addEventListener('keydown', function (event) {
                if (event.key === 'Backspace' && !digit.value && digits[index - 1]) {
                    digits[index - 1].focus();
                }
            });
        });

        document.getElementById('mfaForm').addEventListener('submit', function (event) {
            event.preventDefault();
            var code = digits.map(function (digit) { return digit.value; }).join('');
            document.getElementById('otpCode').value = code;
            var data = new FormData(document.getElementById('mfaForm'));
            applyCsrf(data);
            fetch(document.getElementById('mfaForm').action, {
                method: 'POST',
                body: data,
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).then(function (response) { return response.json(); }).then(function (body) {
                rememberCsrf(body);
                if (!body.ok) {
                    mfaError.hidden = false;
                    mfaError.textContent = body.message || 'Kode autentikator tidak sesuai';
                    return;
                }
                window.location = body.redirect || '/';
            });
        });
    </script>
</body>
</html>
