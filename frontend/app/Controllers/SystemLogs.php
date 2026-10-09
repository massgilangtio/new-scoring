<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class SystemLogs extends BaseController
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

    private function canAccess(array $profile): bool
    {
        return $this->profileCan($profile, ['system.logs', 'audit.view', 'access.manage']);
    }

    private function dtParams(array $colMap, string $defaultSort = 'id', string $defaultDir = 'desc'): array
    {
        $draw   = (int) ($this->request->getGet('draw') ?? 1);
        $start  = (int) ($this->request->getGet('start') ?? 0);
        $length = (int) ($this->request->getGet('length') ?? 10);
        if ($length <= 0) {
            $length = 10;
        }
        if ($length > 500) {
            $length = 500;
        }
        $page = intdiv($start, $length) + 1;

        $searchParam = $this->request->getGet('search');
        $searchValue = is_array($searchParam) ? (string) ($searchParam['value'] ?? '') : (string) $searchParam;

        $sortCol = $defaultSort;
        $sortDir = $defaultDir;
        $orderParam = $this->request->getGet('order');
        if (is_array($orderParam) && isset($orderParam[0]['column'])) {
            $colIdx = (int) $orderParam[0]['column'];
            $dir    = strtolower((string) ($orderParam[0]['dir'] ?? $defaultDir));
            $sortDir = in_array($dir, ['asc', 'desc'], true) ? $dir : $defaultDir;
            if (isset($colMap[$colIdx])) {
                $sortCol = $colMap[$colIdx];
            }
        }

        return [
            'draw'     => $draw,
            'page'     => $page,
            'per_page' => $length,
            'search'   => $searchValue,
            'sort_by'  => $sortCol,
            'sort_dir' => $sortDir,
        ];
    }

    public function index()
    {
        $profile = $this->profile();
        if ($profile === null || ! $this->canAccess($profile)) {
            return redirect()->to(site_url('/'))->with('error', 'Anda tidak memiliki hak akses untuk melihat Log Sistem');
        }

        $meta = $this->client()->get(
            '/api/v1/system-logs/datatables?' . http_build_query(['page' => 1, 'per_page' => 1]),
            $this->token()
        );

        $res = $meta['result'] ?? [];

        return view('system_logs/index', [
            'profile' => $profile,
            'total'   => (int) ($res['total'] ?? 0),
            'stats'   => $res['stats'] ?? [],
        ]);
    }

    public function detail(int $id)
    {
        $profile = $this->profile();
        if ($profile === null || ! $this->canAccess($profile)) {
            return $this->response->setStatusCode(401)->setJSON(['rcode' => '04', 'message' => 'Akses ditolak']);
        }

        $result = $this->client()->get('/api/v1/system-logs/' . $id, $this->token());
        return $this->response->setJSON($result);
    }

    public function datatables()
    {
        $profile = $this->profile();
        if ($profile === null || ! $this->canAccess($profile)) {
            return $this->response->setStatusCode(401)->setJSON([
                'draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
            ]);
        }

        $dt = $this->dtParams([
            0 => 'created_at',
            1 => 'level',
            2 => 'method',
            3 => 'path',
            4 => 'status_code',
            5 => 'execution_time_ms',
            6 => 'username',
            7 => 'client_ip',
            8 => 'id',
        ], 'id', 'desc');

        $filterSearch   = (string) ($this->request->getGet('filterSearch') ?? '');
        $filterDateFrom = (string) ($this->request->getGet('filterDateFrom') ?? '');
        $filterDateTo   = (string) ($this->request->getGet('filterDateTo') ?? '');
        $filterLevel    = (string) ($this->request->getGet('filterLevel') ?? '');
        $filterMethod   = (string) ($this->request->getGet('filterMethod') ?? '');
        $filterStatus   = (string) ($this->request->getGet('filterStatus') ?? '');
        $effectiveSearch = $filterSearch !== '' ? $filterSearch : $dt['search'];

        $query = [
            'page'        => $dt['page'],
            'per_page'    => $dt['per_page'],
            'search'      => $effectiveSearch,
            'date_from'   => $filterDateFrom,
            'date_to'     => $filterDateTo,
            'level'       => $filterLevel,
            'method'      => $filterMethod,
            'status_code' => $filterStatus !== '' ? (int) $filterStatus : null,
            'sort_by'     => $dt['sort_by'],
            'sort_dir'    => $dt['sort_dir'],
        ];

        // Remove null entries
        $query = array_filter($query, static fn($v) => $v !== null && $v !== '');

        $response = $this->client()->get(
            '/api/v1/system-logs/datatables?' . http_build_query($query),
            $this->token()
        );

        return $this->response->setJSON([
            'draw'            => $dt['draw'],
            'recordsTotal'    => (int) ($response['result']['total'] ?? 0),
            'recordsFiltered' => (int) ($response['result']['filtered'] ?? 0),
            'data'            => $response['result']['items'] ?? [],
            'stats'           => $response['result']['stats'] ?? [],
        ]);
    }

    public function clear()
    {
        $profile = $this->profile();
        if ($profile === null || ! $this->canAccess($profile)) {
            return $this->response->setStatusCode(401)->setJSON(['rcode' => '04', 'message' => 'Akses ditolak']);
        }

        $days = (int) ($this->request->getPost('days') ?? 30);
        $result = $this->client()->post('/api/v1/system-logs/clear?days=' . $days, [], $this->token());
        return $this->response->setJSON($result);
    }
}
