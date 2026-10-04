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
