<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class Approvals extends BaseController
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
        $permissions = $profile['permissions'] ?? [];
        if (! in_array('scoring.approve', $permissions, true) && ! in_array('scoring.assign', $permissions, true)) {
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
        $token = $this->token();
        $permissions = $profile['permissions'] ?? [];
        $inbox = in_array('scoring.approve', $permissions, true)
            ? ($this->client()->get('/api/v1/approvals/inbox', $token)['result']['items'] ?? [])
            : [];
        $waiting = in_array('scoring.assign', $permissions, true)
            ? ($this->client()->get('/api/v1/approvals/unassigned', $token)['result']['items'] ?? [])
            : [];
        $duplicates = (in_array('scoring.approve', $permissions, true) || in_array('scoring.assign', $permissions, true))
            ? ($this->client()->get('/api/v1/approvals/duplicate-requests', $token)['result']['items'] ?? [])
            : [];

        return view('approvals/index', [
            'profile'        => $profile,
            'inbox'          => $inbox,
            'waiting'        => $waiting,
            'duplicates'     => $duplicates,
            'duplicateCount' => count($duplicates),
            'error'          => session()->getFlashdata('error'),
            'message'        => session()->getFlashdata('message'),
        ]);
    }

    public function show(int $id)
    {
        [$denied, $profile] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $token = $this->token();
        $detail = $this->client()->get('/api/v1/transactions/' . $id, $token);
        if (($detail['rcode'] ?? '') !== '00') {
            return redirect()->to('/approvals')->with('error', (string) $detail['message']);
        }
        $candidates = [];
        if (in_array('scoring.assign', $profile['permissions'] ?? [], true)) {
            $rows = $this->client()->get('/api/v1/approvals/candidates/' . $id, $token);
            $candidates = $rows['result']['items'] ?? [];
        }

        return view('approvals/show', [
            'profile'    => $profile,
            'item'       => $detail['result'],
            'candidates' => $candidates,
            'error'      => session()->getFlashdata('error'),
            'message'    => session()->getFlashdata('message'),
        ]);
    }

    public function assign(int $id)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/approvals/' . $id . '/assign', [
            'approver_id' => (int) $this->request->getPost('approver_id'),
            'reason'      => trim((string) $this->request->getPost('reason')),
        ], $this->token());

        return redirect()->to('/approvals/' . $id)->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function decide(int $id)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/approvals/' . $id . '/decide', [
            'decision' => (string) $this->request->getPost('decision'),
            'note'     => trim((string) $this->request->getPost('note')),
        ], $this->token());

        return redirect()->to('/approvals/' . $id)->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function duplicateApprove(int $id)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/approvals/' . $id . '/duplicate-approve', [], $this->token());
        return redirect()->to('/approvals')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) ($result['message'] ?? 'Keputusan izin pengajuan ulang berhasil diproses')
        );
    }

    public function duplicateReject(int $id)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $note = trim((string) $this->request->getPost('note'));
        if (! $note) {
            return redirect()->to('/approvals')->with('error', 'Alasan penolakan izin pengajuan ulang wajib diisi');
        }
        $result = $this->client()->post('/api/v1/approvals/' . $id . '/duplicate-reject', [
            'note' => $note,
        ], $this->token());
        return redirect()->to('/approvals')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) ($result['message'] ?? 'Izin pengajuan ulang ditolak')
        );
    }
}
