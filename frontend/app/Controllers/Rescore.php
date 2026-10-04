<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class Rescore extends BaseController
{
    private function client(): ApiClient
    {
        return new ApiClient();
    }

    private function token(): string
    {
        return (string) session()->get('access_token');
    }

    public function index()
    {
        $me = $this->client()->get('/api/v1/auth/me', $this->token());
        if (($me['rcode'] ?? '') !== '00') {
            session()->destroy();

            return redirect()->to('/login');
        }
        $profile = $me['result'];
        $permissions = $profile['permissions'] ?? [];
        if (! in_array('scoring.submit', $permissions, true) && ! in_array('scoring.approve', $permissions, true)) {
            return redirect()->to('/')->with('error', 'Anda tidak memiliki hak akses');
        }
        $rows = $this->client()->get('/api/v1/rescore', $this->token());
        $options = in_array('scoring.submit', $permissions, true)
            ? $this->client()->get('/api/v1/transactions/options', $this->token())
            : ['result' => ['products' => [], 'debtors' => []]];

        return view('rescore/index', [
            'profile'  => $profile,
            'items'    => $rows['result']['items'] ?? [],
            'products' => $options['result']['products'] ?? [],
            'debtors'  => $options['result']['debtors'] ?? [],
            'error'    => session()->getFlashdata('error'),
            'message'  => session()->getFlashdata('message'),
        ]);
    }

    public function create()
    {
        $result = $this->client()->post('/api/v1/rescore', [
            'debtor_id'  => (int) $this->request->getPost('debtor_id'),
            'product_id' => (int) $this->request->getPost('product_id'),
            'reason'     => trim((string) $this->request->getPost('reason')),
        ], $this->token());

        return redirect()->to('/rescore')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function approve(int $id)
    {
        $result = $this->client()->post('/api/v1/rescore/' . $id . '/approve', ['approve' => true], $this->token());

        return redirect()->to('/rescore')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }
}
