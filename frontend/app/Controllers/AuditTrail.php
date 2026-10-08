<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class AuditTrail extends BaseController
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
        if ($denied = $this->denyUnlessCan($profile, 'audit.view')) {
            return $denied;
        }

        $meta = $this->client()->get(
            '/api/v1/audit/datatables?' . http_build_query(['page' => 1, 'per_page' => 1]),
            $this->token()
        );

        $res = $meta['result'] ?? [];

        return view('audit/index', [
            'profile'      => $profile,
            'total'        => (int) ($res['total'] ?? 0),
            'stats'        => $res['stats'] ?? [],
            'modules'      => $res['modules'] ?? [],
            'actions'      => $res['actions'] ?? [],
            'object_types' => $res['object_types'] ?? [],
            'actors'       => $res['actors'] ?? [],
        ]);
    }

    public function detail(int $id)
    {
        $profile = $this->profile();
        if ($profile === null || ! $this->profileCan($profile, 'audit.view')) {
            return $this->response->setStatusCode(401)->setJSON(['rcode' => '04', 'message' => 'Akses ditolak']);
        }

        $result = $this->client()->get('/api/v1/audit/' . $id, $this->token());
        return $this->response->setJSON($result);
    }

    public function datatables()
    {
        $profile = $this->profile();
        if ($profile === null || ! $this->profileCan($profile, 'audit.view')) {
            return $this->response->setStatusCode(401)->setJSON([
                'draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
            ]);
        }

        $dt = $this->dtParams([
            0 => 'occurred_at',
            1 => 'actor_role_name',
            2 => 'action',
            3 => 'object_type',
            4 => 'reason',
            5 => 'id',
            6 => 'id',
        ], 'id', 'desc');

        $filterSearch     = (string) ($this->request->getGet('filterSearch') ?? '');
        $filterDateFrom   = (string) ($this->request->getGet('filterDateFrom') ?? '');
        $filterDateTo     = (string) ($this->request->getGet('filterDateTo') ?? '');
        $filterModule     = (string) ($this->request->getGet('filterModule') ?? '');
        $filterAction     = (string) ($this->request->getGet('filterAction') ?? '');
        $filterObjectType = (string) ($this->request->getGet('filterObjectType') ?? '');
        $filterActor      = (string) ($this->request->getGet('filterActor') ?? '');
        $effectiveSearch  = $filterSearch !== '' ? $filterSearch : $dt['search'];

        $query = [
            'page'         => $dt['page'],
            'per_page'     => $dt['per_page'],
            'search'       => $effectiveSearch,
            'date_from'    => $filterDateFrom,
            'date_to'      => $filterDateTo,
            'module'       => $filterModule,
            'action'       => $filterAction,
            'object_type'  => $filterObjectType,
            'actor_filter' => $filterActor,
            'sort_by'      => $dt['sort_by'],
            'sort_dir'     => $dt['sort_dir'],
        ];

        $response = $this->client()->get(
            '/api/v1/audit/datatables?' . http_build_query($query),
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
}
