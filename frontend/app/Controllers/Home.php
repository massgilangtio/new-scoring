<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class Home extends BaseController
{
    public function index()
    {
        $result = (new ApiClient())->get('/api/v1/auth/me', (string) session()->get('access_token'));
        if (($result['rcode'] ?? '') !== '00') {
            session()->destroy();

            return redirect()->to('/login')->with('error', (string) ($result['message'] ?? 'Sesi tidak berlaku'));
        }

        $user = $result['result'] ?? [];
        session()->set([
            'npp'              => (string) ($user['npp'] ?? $user['username'] ?? ''),
            'nama'             => (string) ($user['nama'] ?? $user['full_name'] ?? ''),
            'jabatan'          => (string) ($user['jabatan'] ?? ''),
            'rolenm'           => (string) ($user['rolenm'] ?? $user['role_name'] ?? ''),
            'roleid'           => (string) ($user['roleid'] ?? ''),
            'branchid'         => (string) ($user['branchid'] ?? ''),
            'id_unit_kerja'    => (string) ($user['id_unit_kerja'] ?? ''),
            'nm_unit_kerja'    => (string) ($user['nm_unit_kerja'] ?? ''),
            'id_kel_jabatan'   => (string) ($user['id_kel_jabatan'] ?? ''),
            'nama_kel_jabatan' => (string) ($user['nama_kel_jabatan'] ?? ''),
            'username'         => (string) ($user['username'] ?? ''),
            'full_name'        => (string) ($user['full_name'] ?? $user['nama'] ?? ''),
            'role_name'        => (string) ($user['role_name'] ?? $user['rolenm'] ?? ''),
            'branch_name'      => (string) ($user['branch_name'] ?? ''),
            'permissions'      => $user['permissions'] ?? [],
            'user'             => $user,
        ]);

        $token = (string) session()->get('access_token');
        $period = trim((string) $this->request->getGet('period'));
        $dashboardPath = '/api/v1/dashboard' . ($period !== '' ? '?period=' . rawurlencode($period) : '');
        $dashboard = (new ApiClient())->get($dashboardPath, $token);
        $notes = (new ApiClient())->get('/api/v1/notifications', $token);

        return view('home/index', [
            'profile'     => $result['result'],
            'dashboard'   => $dashboard['result'] ?? [],
            'notes'       => $notes['result']['items'] ?? [],
            'isDashboard' => true,
            'error'       => session()->getFlashdata('error'),
        ]);
    }
}
