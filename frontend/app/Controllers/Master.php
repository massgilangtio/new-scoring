<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class Master extends BaseController
{
    private function client(): ApiClient
    {
        return new ApiClient();
    }

    private function token(): string
    {
        return (string) session()->get('access_token');
    }

    private function gate(string|array $permission = 'master.manage')
    {
        $result = $this->client()->get('/api/v1/auth/me', $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            session()->destroy();

            return [redirect()->to('/login'), []];
        }
        $profile = $result['result'];
        $needed = is_array($permission) ? $permission : [$permission, 'master.manage'];
        if (! $this->profileCan($profile, $needed)) {
            return [redirect()->to('/')->with('error', 'Anda tidak memiliki hak akses'), []];
        }

        return [null, $profile];
    }

    public function branches()
    {
        [$denied, $profile] = $this->gate('master.branches');
        if ($denied) {
            return $denied;
        }
        $rows = $this->client()->get('/api/v1/master/branches', $this->token());

        return view('master/branches', [
            'profile'  => $profile,
            'branches' => $rows['result']['items'] ?? [],
            'error'    => session()->getFlashdata('error'),
            'message'  => session()->getFlashdata('message'),
        ]);
    }

    public function storeBranch()
    {
        [$denied] = $this->gate('master.branches');
        if ($denied) {
            return $denied;
        }

        return $this->save('/api/v1/master/branches', $this->branchPayload(), '/master/branches');
    }

    public function updateBranch(int $id)
    {
        [$denied] = $this->gate('master.branches');
        if ($denied) {
            return $denied;
        }

        return $this->save('/api/v1/master/branches/' . $id, $this->branchPayload(), '/master/branches', true);
    }

    public function syncBranches()
    {
        [$denied] = $this->gate('master.branches');
        if ($denied) {
            return $denied;
        }

        $result = $this->client()->post('/api/v1/master/branches/sync', [], $this->token());
        $isOk = ($result['rcode'] ?? '') === '00';
        $msg = $result['message'] ?? ($isOk ? 'Sinkronisasi cabang berhasil' : 'Gagal sinkronisasi cabang dari Core Gateway');

        return redirect()->to('/master/branches')->with(
            $isOk ? 'message' : 'error',
            $msg
        );
    }

    public function products()
    {
        [$denied, $profile] = $this->gate('master.products');
        if ($denied) {
            return $denied;
        }
        // Fetch metadata (types, codes, KPI stats) without loading full table dataset
        $meta = $this->client()->get('/api/v1/master/products/datatables?page=1&per_page=1', $this->token());

        return view('master/products', [
            'profile'        => $profile,
            'products'       => [], // Empty initial array: data loaded dynamically via AJAX DataTables
            'types'          => $meta['result']['types'] ?? [],
            'codes'          => $meta['result']['codes'] ?? [],
            'productOptions' => $meta['result']['product_options'] ?? [],
            'stats'          => $meta['result']['stats'] ?? ['total' => 0, 'active' => 0, 'inactive' => 0],
            'error'          => session()->getFlashdata('error'),
            'message'        => session()->getFlashdata('message'),
        ]);
    }

    public function productsDatatables()
    {
        [$denied] = $this->gate('master.products');
        if ($denied) {
            return $this->response->setStatusCode(403)->setJSON([
                'draw'            => (int) $this->request->getGet('draw'),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => 'Akses ditolak',
            ]);
        }

        $draw   = (int) ($this->request->getGet('draw') ?? 1);
        $start  = (int) ($this->request->getGet('start') ?? 0);
        $length = (int) ($this->request->getGet('length') ?? 10);
        if ($length <= 0) {
            $length = 10;
        }
        $page = intdiv($start, $length) + 1;

        // Global search input
        $searchParam = $this->request->getGet('search');
        $searchValue = is_array($searchParam) ? ($searchParam['value'] ?? '') : (string) $searchParam;

        // Custom Filters
        $filterSearch      = (string) ($this->request->getGet('filterSearch') ?? '');
        $filterCode        = (string) ($this->request->getGet('filterCode') ?? '');
        $filterStatus      = (string) ($this->request->getGet('filterStatus') ?? '');
        $filterProductType = (string) ($this->request->getGet('filterProductType') ?? '');
        $filterBranch      = (string) ($this->request->getGet('filterBranch') ?? '');

        // Order
        $orderParam = $this->request->getGet('order');
        $sortCol    = 'default';
        $sortDir    = 'asc';
        if (is_array($orderParam) && isset($orderParam[0]['column'])) {
            $colIdx = (int) $orderParam[0]['column'];
            $dir    = strtolower((string) ($orderParam[0]['dir'] ?? 'asc'));
            $sortDir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';
            
            $colMap = [
                0 => 'id',
                1 => 'code',
                2 => 'name',
                3 => 'business_unit',
                4 => 'product_type_name',
                5 => 'interest_rate',
                6 => 'is_active',
            ];
            if (isset($colMap[$colIdx])) {
                $sortCol = $colMap[$colIdx];
            }
        }

        $effectiveSearch = $filterSearch !== '' ? $filterSearch : $searchValue;

        $queryParams = [
            'page'         => $page,
            'per_page'     => $length,
            'search'       => $effectiveSearch,
            'code'         => $filterCode,
            'status'       => $filterStatus,
            'branch'       => $filterBranch,
            'product_type' => $filterProductType,
            'sort_by'      => $sortCol,
            'sort_dir'     => $sortDir,
        ];

        $apiUrl = '/api/v1/master/products/datatables?' . http_build_query($queryParams);
        $response = $this->client()->get($apiUrl, $this->token());

        $items           = $response['result']['items'] ?? [];
        $recordsTotal    = (int) ($response['result']['total'] ?? 0);
        $recordsFiltered = (int) ($response['result']['filtered'] ?? 0);
        $stats           = $response['result']['stats'] ?? [];

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $items,
            'stats'           => $stats,
        ]);
    }

    public function storeProduct()
    {
        [$denied] = $this->gate('master.products');
        if ($denied) {
            return $denied;
        }

        return $this->save('/api/v1/master/products', $this->productPayload(), '/master/products');
    }

    public function updateProduct(int $id)
    {
        [$denied] = $this->gate('master.products');
        if ($denied) {
            return $denied;
        }

        return $this->save('/api/v1/master/products/' . $id, $this->productPayload(), '/master/products', true);
    }

    public function debtors()
    {
        [$denied, $profile] = $this->gate('master.debtors');
        if ($denied) {
            return $denied;
        }
        $token = $this->token();
        $rows = $this->client()->get('/api/v1/master/debtors', $token);
        $branches = $this->client()->get('/api/v1/master/branches', $token);

        return view('master/debtors', [
            'profile'  => $profile,
            'debtors'  => $rows['result']['items'] ?? [],
            'branches' => $branches['result']['items'] ?? [],
            'error'    => session()->getFlashdata('error'),
            'message'  => session()->getFlashdata('message'),
            'import'   => session()->getFlashdata('import'),
        ]);
    }

    public function storeDebtor()
    {
        [$denied] = $this->gate('master.debtors');
        if ($denied) {
            return $denied;
        }

        return $this->save('/api/v1/master/debtors', $this->debtorPayload(), '/master/debtors');
    }

    public function updateDebtor(int $id)
    {
        [$denied] = $this->gate('master.debtors');
        if ($denied) {
            return $denied;
        }

        return $this->save('/api/v1/master/debtors/' . $id, $this->debtorPayload(), '/master/debtors', true);
    }

    public function nextCisId()
    {
        [$denied] = $this->gate('master.debtors');
        if ($denied) {
            return $denied;
        }
        $token = $this->token();
        $res = $this->client()->get('/api/v1/master/debtors/next-cis-id', $token);
        return $this->response->setJSON($res);
    }

    public function inquiryCif(string $cif)
    {
        [$denied] = $this->gate('master.debtors');
        if ($denied) {
            return $denied;
        }
        $token = $this->token();
        $res = $this->client()->get('/api/v1/master/debtors/inquiry-cif/' . rawurlencode($cif), $token);
        return $this->response->setJSON($res);
    }

    public function importDebtors()
    {
        [$denied] = $this->gate('master.debtors');
        if ($denied) {
            return $denied;
        }
        $file = $this->request->getFile('file');
        if ($file === null || ! $file->isValid()) {
            return redirect()->to('/master/debtors')->with('error', 'File Excel wajib diunggah');
        }
        $result = $this->client()->postFile('/api/v1/master/debtors/import', $file->getTempName(), $file->getName(), $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            return redirect()->to('/master/debtors')->with('error', (string) $result['message']);
        }

        return redirect()->to('/master/debtors')->with('import', $result['result']);
    }

    private function save(string $path, array $payload, string $back, bool $patch = false)
    {
        $result = $patch
            ? $this->client()->patch($path, $payload, $this->token())
            : $this->client()->post($path, $payload, $this->token());

        return redirect()->to($back)->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    private function branchPayload(): array
    {
        return [
            'code'      => trim((string) $this->request->getPost('code')),
            'name'      => trim((string) $this->request->getPost('name')),
            'is_active' => $this->request->getPost('is_active') === '1',
        ];
    }

    private function optional(string $key): ?string
    {
        $value = trim((string) $this->request->getPost($key));

        return $value === '' ? null : $value;
    }

    private function productPayload(): array
    {
        $payload = [
            'code'      => trim((string) $this->request->getPost('code')),
            'name'      => trim((string) $this->request->getPost('name')),
            'is_active' => $this->request->getPost('is_active') === '1',
        ];
        foreach (['business_unit', 'interest_rate', 'product_type_id'] as $key) {
            if ($this->request->getPost($key) !== null) {
                $payload[$key] = $this->optional($key);
            }
        }

        return $payload;
    }

    private function debtorPayload(): array
    {
        return [
            'nik'         => trim((string) $this->request->getPost('nik')),
            'full_name'   => trim((string) $this->request->getPost('full_name')),
            'branch_id'   => (int) ($this->request->getPost('branch_id') ?: session()->get('branch_id') ?: 1),
            'is_active'   => $this->request->getPost('is_active') === '1',
            'cis_id'      => $this->optional('cis_id'),
            'cif_id'      => $this->optional('cif_id'),
            'npwp'        => $this->optional('npwp'),
            'birth_date'  => $this->optional('birth_date'),
            'birth_place' => $this->optional('birth_place'),
            'mother_name' => $this->optional('mother_name'),
            'gender'      => $this->optional('gender'),
            'phone'       => $this->optional('phone'),
            'address'     => $this->optional('address'),
            'religion'    => $this->optional('religion'),
        ];
    }
}
