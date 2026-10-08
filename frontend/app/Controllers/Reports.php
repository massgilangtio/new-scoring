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

    /**
     * Parse DataTables request → page/per_page/search/sort for FastAPI.
     *
     * @param array<int,string> $colMap column index → API sort_by field
     * @return array{draw:int,page:int,per_page:int,search:string,sort_by:string,sort_dir:string}
     */
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

    public function scoring()
    {
        $profile = $this->profile();
        if ($denied = $this->denyUnlessCan($profile, 'report.scoring')) {
            return $denied;
        }

        // Lightweight bootstrap for KPI + Select2 statuses (page 1, 1 row)
        $meta = $this->client()->get(
            '/api/v1/reports/scoring/datatables?' . http_build_query(['page' => 1, 'per_page' => 1]),
            $this->token()
        );

        return view('reports/scoring', [
            'profile'  => $profile,
            'stats'    => $meta['result']['stats'] ?? ['total' => 0, 'approved' => 0, 'rejected' => 0],
            'statuses' => $meta['result']['statuses'] ?? [],
        ]);
    }

    public function scoringDatatables()
    {
        $profile = $this->profile();
        if ($profile === null || ! $this->profileCan($profile, 'report.scoring')) {
            return $this->response->setStatusCode(401)->setJSON([
                'draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
            ]);
        }

        $dt = $this->dtParams([
            0 => 'transaction_no',
            1 => 'debtor_name',
            2 => 'nik',
            3 => 'product_name',
            4 => 'branch_name',
            5 => 'version_no',
            6 => 'total_score',
            7 => 'result_label',
            8 => 'status',
        ], 'id', 'desc');

        $filterSearch = (string) ($this->request->getGet('filterSearch') ?? '');
        $filterStatus = (string) ($this->request->getGet('filterStatus') ?? '');
        $effectiveSearch = $filterSearch !== '' ? $filterSearch : $dt['search'];

        $query = [
            'page'     => $dt['page'],
            'per_page' => $dt['per_page'],
            'search'   => $effectiveSearch,
            'status'   => $filterStatus,
            'sort_by'  => $dt['sort_by'],
            'sort_dir' => $dt['sort_dir'],
        ];

        $response = $this->client()->get(
            '/api/v1/reports/scoring/datatables?' . http_build_query($query),
            $this->token()
        );

        return $this->response->setJSON([
            'draw'            => $dt['draw'],
            'recordsTotal'    => (int) ($response['result']['total'] ?? 0),
            'recordsFiltered' => (int) ($response['result']['filtered'] ?? 0),
            'data'            => $response['result']['items'] ?? [],
            'stats'           => $response['result']['stats'] ?? [],
            'statuses'        => $response['result']['statuses'] ?? [],
        ]);
    }

    public function debtors()
    {
        $profile = $this->profile();
        if ($denied = $this->denyUnlessCan($profile, 'report.debtors')) {
            return $denied;
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

    public function debtorsDatatables(int $id)
    {
        $profile = $this->profile();
        if ($profile === null || ! $this->profileCan($profile, 'report.debtors')) {
            return $this->response->setStatusCode(401)->setJSON([
                'draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
            ]);
        }

        $dt = $this->dtParams([
            0 => 'transaction_no',
            1 => 'product_name',
            2 => 'total_score',
            3 => 'result_label',
            4 => 'status',
        ], 'id', 'desc');

        $filterSearch = (string) ($this->request->getGet('filterSearch') ?? '');
        $filterStatus = (string) ($this->request->getGet('filterStatus') ?? '');
        $effectiveSearch = $filterSearch !== '' ? $filterSearch : $dt['search'];

        $query = [
            'page'     => $dt['page'],
            'per_page' => $dt['per_page'],
            'search'   => $effectiveSearch,
            'status'   => $filterStatus,
            'sort_by'  => $dt['sort_by'],
            'sort_dir' => $dt['sort_dir'],
        ];

        $response = $this->client()->get(
            '/api/v1/reports/debtors/' . $id . '/datatables?' . http_build_query($query),
            $this->token()
        );

        return $this->response->setJSON([
            'draw'            => $dt['draw'],
            'recordsTotal'    => (int) ($response['result']['total'] ?? 0),
            'recordsFiltered' => (int) ($response['result']['filtered'] ?? 0),
            'data'            => $response['result']['items'] ?? [],
            'debtor'          => $response['result']['debtor'] ?? null,
            'stats'           => $response['result']['stats'] ?? [],
        ]);
    }

    public function products()
    {
        $profile = $this->profile();
        if ($denied = $this->denyUnlessCan($profile, 'report.products')) {
            return $denied;
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
        if ($denied = $this->denyUnlessCan($profile, 'report.changes')) {
            return $denied;
        }

        $meta = $this->client()->get(
            '/api/v1/reports/parameter-changes/datatables?' . http_build_query(['page' => 1, 'per_page' => 1]),
            $this->token()
        );

        return view('reports/changes', [
            'profile'      => $profile,
            'total'        => (int) ($meta['result']['total'] ?? 0),
            'actions'      => $meta['result']['actions'] ?? [],
            'object_types' => $meta['result']['object_types'] ?? [],
        ]);
    }

    public function changesDatatables()
    {
        $profile = $this->profile();
        if ($profile === null || ! $this->profileCan($profile, 'report.changes')) {
            return $this->response->setStatusCode(401)->setJSON([
                'draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
            ]);
        }

        $dt = $this->dtParams([
            0 => 'occurred_at',
            1 => 'action',
            2 => 'actor_role_name',
            3 => 'object_type',
        ], 'id', 'desc');

        $filterSearch     = (string) ($this->request->getGet('filterSearch') ?? '');
        $filterAction     = (string) ($this->request->getGet('filterAction') ?? '');
        $filterObjectType = (string) ($this->request->getGet('filterObjectType') ?? '');
        $effectiveSearch  = $filterSearch !== '' ? $filterSearch : $dt['search'];

        $query = [
            'page'        => $dt['page'],
            'per_page'    => $dt['per_page'],
            'search'      => $effectiveSearch,
            'action'      => $filterAction,
            'object_type' => $filterObjectType,
            'sort_by'     => $dt['sort_by'],
            'sort_dir'    => $dt['sort_dir'],
        ];

        $response = $this->client()->get(
            '/api/v1/reports/parameter-changes/datatables?' . http_build_query($query),
            $this->token()
        );

        return $this->response->setJSON([
            'draw'            => $dt['draw'],
            'recordsTotal'    => (int) ($response['result']['total'] ?? 0),
            'recordsFiltered' => (int) ($response['result']['filtered'] ?? 0),
            'data'            => $response['result']['items'] ?? [],
        ]);
    }
}
