<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('access_token')) {
            return redirect()->to('/');
        }

        session()->remove(['mfa_step', 'mfa_token', 'mfa_qr']);

        return view('auth/login', [
            'error' => session()->getFlashdata('error'),
        ]);
    }

    public function authenticate()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        if ($username === '' || $password === '') {
            return $this->failLogin('Username dan password wajib diisi');
        }

        $result = (new ApiClient())->post('/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        if (($result['rcode'] ?? '') !== '00') {
            return $this->failLogin((string) $result['message']);
        }

        $step = (string) ($result['result']['step'] ?? '');
        $token = (string) ($result['result']['mfa_token'] ?? '');
        if (! in_array($step, ['mfa_setup', 'mfa_verify'], true) || $token === '') {
            return $this->failLogin('Respons autentikasi tidak dikenali');
        }

        $qr = (string) ($result['result']['qr_svg'] ?? '');
        session()->regenerate(true);
        session()->set([
            'mfa_step'  => $step,
            'mfa_token' => $token,
            'mfa_qr'    => $this->safeQr($qr),
        ]);

        if ($this->request->isAJAX()) {
            return $this->authJson([
                'ok'   => true,
                'step' => $step,
                'qr'   => session()->get('mfa_qr'),
            ]);
        }

        return redirect()->to('/login');
    }

    public function mfa()
    {
        return redirect()->to('/login');
    }

    public function confirm()
    {
        $step = (string) session()->get('mfa_step');
        $token = (string) session()->get('mfa_token');
        $code = trim((string) $this->request->getPost('code'));
        if ($token === '' || ! preg_match('/^\d{6}$/', $code)) {
            return $this->failMfa('Kode autentikator harus 6 digit');
        }

        $path = $step === 'mfa_setup' ? '/api/v1/auth/mfa/confirm' : '/api/v1/auth/mfa/verify';
        $result = (new ApiClient())->post($path, ['code' => $code], $token);
        if (($result['rcode'] ?? '') !== '00') {
            return $this->failMfa((string) $result['message']);
        }

        session()->regenerate(true);
        session()->set('access_token', (string) ($result['result']['access_token'] ?? ''));

        if ($this->request->isAJAX()) {
            return $this->authJson(['ok' => true, 'redirect' => site_url('/')]);
        }

        return redirect()->to('/');
    }

    public function cancelMfa()
    {
        session()->remove(['mfa_step', 'mfa_token', 'mfa_qr']);
        if ($this->request->isAJAX()) {
            return $this->authJson(['ok' => true]);
        }

        return redirect()->to('/login');
    }

    private function failLogin(string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->authJson(['ok' => false, 'message' => $message]);
        }

        return redirect()->to('/login')->with('error', $message);
    }

    private function failMfa(string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->authJson(['ok' => false, 'message' => $message]);
        }

        return redirect()->to('/login')->with('error', $message);
    }

    public function logout()
    {
        $token = (string) session()->get('access_token');
        if ($token !== '') {
            (new ApiClient())->post('/api/v1/auth/logout', [], $token);
        }
        session()->destroy();

        return redirect()->to('/login');
    }

    private function safeQr(string $svg): string
    {
        if (! str_starts_with($svg, '<svg') || str_contains($svg, '<script') || preg_match('/\son[a-z]+\s*=/i', $svg)) {
            return '';
        }

        return $svg;
    }

    private function authJson(array $payload)
    {
        $payload['csrf'] = csrf_hash();

        return $this->response->setJSON($payload);
    }
}
