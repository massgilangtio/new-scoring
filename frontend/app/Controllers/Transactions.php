<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class Transactions extends BaseController
{
    private function client(): ApiClient
    {
        return new ApiClient();
    }

    private function token(): string
    {
        return (string) session()->get('access_token');
    }

    private function gate()
    {
        $result = $this->client()->get('/api/v1/auth/me', $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            session()->destroy();

            return [redirect()->to('/login'), []];
        }
        $profile = $result['result'];
        if (! in_array('scoring.submit', $profile['permissions'] ?? [], true)) {
            return [redirect()->to('/')->with('error', 'Anda tidak memiliki hak akses'), []];
        }

        return [null, $profile];
    }

    public function index()
    {
        [$denied, $profile] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $rows = $this->client()->get('/api/v1/transactions', $this->token());

        return view('transactions/index', [
            'profile' => $profile,
            'items'   => $rows['result']['items'] ?? [],
        ]);
    }

    public function start()
    {
        return redirect()->to('/scoring/credit');
    }

    public function create()
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/transactions', [
            'product_id' => (int) $this->request->getPost('product_id'),
            'debtor_id'  => (int) $this->request->getPost('debtor_id'),
        ], $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            return redirect()->to('/transactions/new')->with('error', (string) $result['message']);
        }
        $warning = (int) ($result['result']['existing_scoring'] ?? 0) > 0
            ? 'NIK dan produk ini sudah memiliki scoring.'
            : '';

        return redirect()->to('/transactions/' . $result['result']['id'])->with('message', $warning);
    }

    public function input(int $id)
    {
        [$denied, $profile] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $detail = $this->client()->get('/api/v1/transactions/' . $id, $this->token());
        if (($detail['rcode'] ?? '') !== '00') {
            return redirect()->to('/transactions')->with('error', (string) $detail['message']);
        }

        return view('transactions/input', [
            'profile' => $profile,
            'item'    => $detail['result'],
            'error'   => session()->getFlashdata('error'),
            'message' => session()->getFlashdata('message'),
        ]);
    }

    public function save(int $id)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $answers = [];
        $selected = $this->request->getPost('option');
        if (is_array($selected)) {
            foreach ($selected as $parameterId => $optionId) {
                $answers[] = ['parameter_id' => (int) $parameterId, 'option_id' => (int) $optionId];
            }
        }
        $fields = [];
        $posted = $this->request->getPost('field');
        if (is_array($posted)) {
            foreach ($posted as $fieldId => $value) {
                $fields[] = ['field_id' => (int) $fieldId, 'value' => (string) $value, 'option_ids' => []];
            }
        }
        $result = $this->client()->put('/api/v1/transactions/' . $id . '/answers', [
            'answers' => $answers,
            'fields'  => $fields,
        ], $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            return redirect()->to('/transactions/' . $id)->with('error', (string) $result['message']);
        }

        return redirect()->to('/transactions/' . $id . '/review');
    }

    public function review(int $id)
    {
        [$denied, $profile] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $detail = $this->client()->get('/api/v1/transactions/' . $id, $this->token());
        if (($detail['rcode'] ?? '') !== '00') {
            return redirect()->to('/transactions')->with('error', (string) $detail['message']);
        }
        $item = $detail['result'];
        $preview = null;
        if (! empty($item['editable']) && $item['answers'] !== []) {
            $calc = $this->client()->post('/api/v1/scoring/versions/' . $item['version']['id'] . '/calculate', [
                'answers' => $item['answers'],
            ], $this->token());
            if (($calc['rcode'] ?? '') === '00') {
                $preview = $calc['result'];
            } else {
                session()->setFlashdata('error', (string) $calc['message']);
            }
        }

        return view('transactions/review', [
            'profile' => $profile,
            'item'    => $item,
            'preview' => $preview,
            'error'   => session()->getFlashdata('error'),
        ]);
    }

    public function submit(int $id)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/transactions/' . $id . '/submit', ['submit' => true], $this->token());

        return redirect()->to('/transactions/' . $id . '/review')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) ($result['rcode'] === '00'
                ? 'Skor ' . $result['result']['total_score'] . ' — ' . $result['result']['result_label']
                : $result['message'])
        );
    }

    public function duplicate(int $id)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/transactions/' . $id . '/duplicate', ['duplicate' => true], $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            return redirect()->to('/transactions/' . $id . '/review')->with('error', (string) $result['message']);
        }

        return redirect()->to('/transactions/' . $result['result']['id'])->with('message', (string) $result['message']);
    }
}
