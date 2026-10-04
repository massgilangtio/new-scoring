<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class AuditTrail extends BaseController
{
    public function index()
    {
        $client = new ApiClient();
        $token = (string) session()->get('access_token');
        $me = $client->get('/api/v1/auth/me', $token);
        if (($me['rcode'] ?? '') !== '00') {
            session()->destroy();

            return redirect()->to('/login');
        }
        $rows = $client->get('/api/v1/audit', $token);

        return view('audit/index', [
            'profile' => $me['result'],
            'items'   => $rows['result']['items'] ?? [],
        ]);
    }
}
