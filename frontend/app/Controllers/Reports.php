<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class Reports extends BaseController
{
    private function client(): ApiClient
    {
        return new ApiClient();
    }

    private function token(): string
    {
        return (string) session()->get('access_token');
    }

    private function profile()
    {
        $result = $this->client()->get('/api/v1/auth/me', $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            session()->destroy();

            return null;
        }

        return $result['result'];
    }

    public function scoring()
    {
        $profile = $this->profile();
        if ($profile === null) {
            return redirect()->to('/login');
        }
        $rows = $this->client()->get('/api/v1/reports/scoring', $this->token());

        return view('reports/scoring', [
            'profile' => $profile,
            'items'   => $rows['result']['items'] ?? [],
        ]);
    }

    public function debtors()
    {
        $profile = $this->profile();
        if ($profile === null) {
            return redirect()->to('/login');
        }
        $list = $this->client()->get('/api/v1/master/debtors', $this->token());
        $history = null;
        $selected = (int) $this->request->getGet('id');
        if ($selected > 0) {
            $detail = $this->client()->get('/api/v1/reports/debtors/' . $selected, $this->token());
            $history = ($detail['rcode'] ?? '') === '00' ? $detail['result'] : ['error' => $detail['message'] ?? ''];
        }

        return view('reports/debtors', [
            'profile'  => $profile,
            'debtors'  => $list['result']['items'] ?? [],
            'history'  => $history,
            'selected' => $selected,
        ]);
    }

    public function products()
    {
        $profile = $this->profile();
        if ($profile === null) {
            return redirect()->to('/login');
        }
        $list = $this->client()->get('/api/v1/master/products', $this->token());
        $history = null;
        $selected = (int) $this->request->getGet('id');
        if ($selected > 0) {
            $detail = $this->client()->get('/api/v1/reports/products/' . $selected, $this->token());
            $history = ($detail['rcode'] ?? '') === '00' ? $detail['result'] : ['error' => $detail['message'] ?? ''];
        }

        return view('reports/products', [
            'profile'  => $profile,
            'products' => $list['result']['items'] ?? [],
            'history'  => $history,
            'selected' => $selected,
        ]);
    }

    public function changes()
    {
        $profile = $this->profile();
        if ($profile === null) {
            return redirect()->to('/login');
        }
        $rows = $this->client()->get('/api/v1/reports/parameter-changes', $this->token());

        return view('reports/changes', [
            'profile' => $profile,
            'items'   => $rows['result']['items'] ?? [],
        ]);
    }
}
