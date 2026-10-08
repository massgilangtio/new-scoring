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

        if ($step === 'direct') {
            session()->regenerate(true);
            $user = $result['result']['user'] ?? [];
            session()->set([
                'access_token'     => (string) ($result['result']['access_token'] ?? ''),
                'npp'              => (string) ($user['npp'] ?? $user['username'] ?? ''),
                'nama'             => (string) ($user['nama'] ?? $user['full_name'] ?? ''),
                'jabatan'          => (string) ($user['jabatan'] ?? ''),
                'rolenm'           => (string) ($user['rolenm'] ?? $user['role_name'] ?? ''),
                'roleid'           => (string) ($user['roleid'] ?? ''),
                'branchid'         => (string) ($user['branchid'] ?? ''),
                'branch_name'      => (string) ($user['branch_name'] ?? $user['branchnm'] ?? ''),
                'branchnm'         => (string) ($user['branchnm'] ?? $user['branch_name'] ?? ''),
                'branch_code'      => (string) ($user['branch_code'] ?? $user['branchid'] ?? ''),
                'id_unit_kerja'    => (string) ($user['id_unit_kerja'] ?? ''),
                'nm_unit_kerja'    => (string) ($user['nm_unit_kerja'] ?? ''),
                'id_kel_jabatan'   => (string) ($user['id_kel_jabatan'] ?? ''),
                'nama_kel_jabatan' => (string) ($user['nama_kel_jabatan'] ?? ''),
                'user'             => $user,
            ]);

            if ($this->request->isAJAX()) {
                return $this->authJson([
                    'ok'       => true,
                    'step'     => 'direct',
                    'redirect' => site_url('/'),
                ]);
            }

            return redirect()->to('/');
        }

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
        $user = $result['result']['user'] ?? [];
        session()->set([
            'access_token'     => (string) ($result['result']['access_token'] ?? ''),
            // Informasi profil pegawai spesifik yang diminta
            'npp'              => (string) ($user['npp'] ?? $user['username'] ?? ''),
            'nama'             => (string) ($user['nama'] ?? $user['full_name'] ?? ''),
            'jabatan'          => (string) ($user['jabatan'] ?? ''),
            'rolenm'           => (string) ($user['rolenm'] ?? $user['role_name'] ?? ''),
            'roleid'           => (string) ($user['roleid'] ?? ''),
            'branchid'         => (string) ($user['branchid'] ?? ''),
            'branch_name'      => (string) ($user['branch_name'] ?? $user['branchnm'] ?? ''),
            'branchnm'         => (string) ($user['branchnm'] ?? $user['branch_name'] ?? ''),
            'branch_code'      => (string) ($user['branch_code'] ?? $user['branchid'] ?? ''),
            // Informasi unit & organisasi
            'id_unit_kerja'    => (string) ($user['id_unit_kerja'] ?? ''),
            'nm_unit_kerja'    => (string) ($user['nm_unit_kerja'] ?? ''),
            'id_kel_jabatan'   => (string) ($user['id_kel_jabatan'] ?? ''),
            'nama_kel_jabatan' => (string) ($user['nama_kel_jabatan'] ?? ''),
            // Field standar & objek user utuh
            'username'         => (string) ($user['username'] ?? ''),
            'full_name'        => (string) ($user['full_name'] ?? $user['nama'] ?? ''),
            'role_name'        => (string) ($user['role_name'] ?? $user['rolenm'] ?? ''),
            'permissions'      => $user['permissions'] ?? [],
            'user'             => $user,
        ]);


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

    public function resetMfa()
    {
        $token = (string) session()->get('mfa_token');
        if ($token === '') {
            return $this->failMfa('Sesi autentikasi telah berakhir');
        }

        $result = (new ApiClient())->post('/api/v1/auth/mfa/reset', [], $token);
        if (($result['rcode'] ?? '') !== '00') {
            return $this->failMfa((string) ($result['message'] ?? 'Gagal mereset secret key'));
        }

        $step = (string) ($result['result']['step'] ?? 'mfa_setup');
        $newToken = (string) ($result['result']['mfa_token'] ?? '');
        $qr = (string) ($result['result']['qr_svg'] ?? '');

        session()->set([
            'mfa_step'  => $step,
            'mfa_token' => $newToken,
            'mfa_qr'    => $this->safeQr($qr),
        ]);

        if ($this->request->isAJAX()) {
            return $this->authJson([
                'ok'      => true,
                'step'    => $step,
                'qr'      => session()->get('mfa_qr'),
                'message' => 'Secret key berhasil direset. Silakan scan barcode baru.',
            ]);
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
