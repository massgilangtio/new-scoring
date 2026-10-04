<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class Notifications extends BaseController
{
    private function profile()
    {
        $result = (new ApiClient())->get('/api/v1/auth/me', (string) session()->get('access_token'));
        if (($result['rcode'] ?? '') !== '00') {
            session()->destroy();

            return null;
        }

        return $result['result'];
    }

    public function index()
    {
        $profile = $this->profile();
        if ($profile === null) {
            return redirect()->to('/login');
        }
        $rows = (new ApiClient())->get('/api/v1/notifications', (string) session()->get('access_token'));

        return view('notifications/index', [
            'profile' => $profile,
            'unread'  => $rows['result']['unread_count'] ?? 0,
            'items'   => $rows['result']['items'] ?? [],
        ]);
    }

    public function read(int $id)
    {
        (new ApiClient())->post('/api/v1/notifications/' . $id . '/read', ['read' => true], (string) session()->get('access_token'));

        return redirect()->to('/notifications');
    }
}
