<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — New Scoring Credit System</title>
    <?= view('partials/assets') ?>
</head>
<body class="auth-body login-fit">
    <main class="login-stage">
        <section class="auth-panel">
            <article class="auth-card login-card">
                <p class="login-kicker">Selamat Datang</p>
                <h2 class="login-title">New Scoring<br><span>Credit System</span></h2>
                <div class="login-rule" aria-hidden="true"></div>
                <p class="login-lead">Silakan masuk untuk mengakses sistem.</p>
                <?php if (! empty($error)) : ?>
                    <p id="loginError" class="alert"><?= esc($error) ?></p>
                <?php else : ?>
                    <p id="loginError" class="alert" hidden></p>
                <?php endif; ?>
                <form id="loginForm" method="post" action="<?= site_url('login') ?>">
                    <?= csrf_field() ?>
                    <label class="field" for="username">
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 19c1.2-3 3.4-4.5 6.5-4.5S17.3 16 18.5 19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </span>
                        <input id="username" name="username" autocomplete="username" placeholder="Masukkan username Anda" required>
                    </label>
                    <label class="field" for="password">
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 11V8a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                        </span>
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password Anda" required>
                        <button class="reveal" type="button" aria-label="Tampilkan password">
                            <svg viewBox="0 0 24 24"><path d="M3 12s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                        </button>
                    </label>
                    <button class="login-submit" type="submit">
                        <span>Masuk</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>
                <aside class="secure-note">
                    <span class="secure-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 3.2l7 2.6v5.7c0 4.2-2.7 7.2-7 8.6-4.3-1.4-7-4.4-7-8.6V5.8l7-2.6z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9 12.1l2 2 4-4.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>
                        <strong>Akses Aman &amp; Terpercaya</strong>
                        <span>Sistem ini dilindungi dengan autentikasi berlapis demi keamanan data dan informasi.</span>
                    </span>
                </aside>
                <p class="auth-help">Belum memiliki akses?</p>
                <p class="auth-contact">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13a8 8 0 0 1 16 0" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="3" y="13" width="4" height="6" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="17" y="13" width="4" height="6" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                    Hubungi Administrator
                </p>
            </article>
        </section>
    </main>
    <div class="mfa-layer" id="mfaLayer" hidden>
        <section class="mfa-dialog" role="dialog" aria-modal="true" aria-labelledby="mfaScanHeading">
            <aside class="mfa-side">
                <img class="mfa-art" src="<?= base_url('assets/images/background-mfa.png') ?>?v=5" alt="Aktifkan Google Authenticator">
            </aside>
            <div class="mfa-main">
                <button class="mfa-close" type="button" data-mfa-cancel aria-label="Tutup">×</button>
                <ol class="mfa-stepper">
                    <li class="is-on" id="stepScan"><span>1</span>Scan QR Code</li>
                    <li class="mfa-step-line" aria-hidden="true"></li>
                    <li id="stepCode"><span>2</span>Verifikasi Kode</li>
                </ol>
                <div class="mfa-setup" id="mfaSetup" hidden>
                    <h3 id="mfaScanHeading">Scan QR Code</h3>
                    <p>Ikuti langkah-langkah berikut untuk menghubungkan akun Anda dengan Google Authenticator.</p>
                    <div class="mfa-scan">
                        <div class="mfa-qr-frame">
                            <i></i><i></i><i></i><i></i>
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
                                <div><strong>Pilih “Scan a QR code”</strong> kemudian arahkan kamera ke QR Code di samping.</div>
                            </li>
                        </ol>
                    </div>
                    <p class="mfa-note"><span class="mfa-info" aria-hidden="true">i</span><span>Setelah akun tertambah, masukkan kode 6 digit dari aplikasi untuk melanjutkan.</span></p>
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
                            <p>Buka aplikasi Google Authenticator di perangkat Anda, kemudian masukkan 6 digit kode yang ditampilkan.</p>
                        </div>
                    </div>
                    <p id="mfaError" class="alert" hidden></p>
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
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="7.2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 13V9.5M9.2 4.8h5.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            <span>Kode akan berubah dalam <strong id="otpSeconds">30 detik</strong></span>
                        </p>
                        <div class="mfa-meter" aria-hidden="true"><span id="otpMeter"></span></div>
                        <div class="mfa-tip">
                            <span class="mfa-tip-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12 3.2l7 2.6v5.7c0 4.2-2.7 7.2-7 8.6-4.3-1.4-7-4.4-7-8.6V5.8l7-2.6z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9 12.1l2 2 4-4.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <p><strong>Tips:</strong> Pastikan waktu di perangkat Anda sudah otomatis (Use network-provided time) agar kode selalu valid.</p>
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
