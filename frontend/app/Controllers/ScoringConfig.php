<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class ScoringConfig extends BaseController
{
    private function client(): ApiClient
    {
        return new ApiClient();
    }

    private function token(): string
    {
        return (string) session()->get('access_token');
    }

    private function gate(string|array $permission = 'scoring.configure')
    {
        $result = $this->client()->get('/api/v1/auth/me', $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            session()->destroy();

            return [redirect()->to('/login'), []];
        }
        $profile = $result['result'];
        $userPerms = $profile['permissions'] ?? [];
        $required = is_array($permission) ? $permission : [$permission];
        $hasAccess = false;
        foreach ($required as $perm) {
            if (in_array($perm, $userPerms, true)) {
                $hasAccess = true;
                break;
            }
        }
        if (! $hasAccess) {
            return [redirect()->to('/')->with('error', 'Anda tidak memiliki hak akses'), []];
        }

        return [null, $profile];
    }

    // ==========================================
    // 1. Konfigurasi Parameter
    // ==========================================

    public function parameters()
    {
        [$denied, $profile] = $this->gate('scoring.configure');
        if ($denied) {
            return $denied;
        }

        $result = $this->client()->get('/api/v1/scoring/parameters/master', $this->token());
        $parameters = $result['result']['items'] ?? [];

        $totalSubParams = 0;
        foreach ($parameters as $p) {
            $totalSubParams += count($p['sub_parameters'] ?? []);
        }

        return view('scoring/parameters', [
            'profile'        => $profile,
            'parameters'     => $parameters,
            'totalSubParams' => $totalSubParams,
            'error'          => session()->getFlashdata('error'),
            'message'        => session()->getFlashdata('message'),
        ]);
    }

    public function storeMasterParameter()
    {
        [$denied] = $this->gate('scoring.configure');
        if ($denied) {
            return $denied;
        }

        $name = trim((string) $this->request->getPost('name'));
        $codes = (array) $this->request->getPost('sub_code');
        $descs = (array) $this->request->getPost('sub_desc');
        $weights = (array) $this->request->getPost('sub_weight');
        $values = (array) $this->request->getPost('sub_value');

        if ($name === '') {
            return redirect()->to('/scoring/parameters')->with('error', 'Nama Parameter wajib diisi');
        }

        $subParams = [];
        foreach ($codes as $idx => $c) {
            $code = trim((string) $c);
            $desc = trim((string) ($descs[$idx] ?? ''));
            if ($code === '' && $desc === '') {
                continue;
            }
            $w = (float) ($weights[$idx] ?? 0);
            $v = (float) ($values[$idx] ?? 0);
            $subParams[] = [
                'code'          => $code,
                'description'   => $desc,
                'weight'        => $w,
                'value'         => $v,
                'total'         => $w * $v,
                'display_order' => $idx + 1,
            ];
        }

        if (empty($subParams)) {
            return redirect()->to('/scoring/parameters')->with('error', 'Minimal harus ada 1 Sub Parameter');
        }

        $payload = [
            'name'           => $name,
            'sub_parameters' => $subParams,
        ];

        $result = $this->client()->post('/api/v1/scoring/parameters/master', $payload, $this->token());
        return redirect()->to('/scoring/parameters')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) ($result['message'] ?? 'Parameter berhasil disimpan')
        );
    }

    public function updateMasterParameter(int $id)
    {
        [$denied] = $this->gate('scoring.configure');
        if ($denied) {
            return $denied;
        }

        $name = trim((string) $this->request->getPost('name'));
        $codes = (array) $this->request->getPost('sub_code');
        $descs = (array) $this->request->getPost('sub_desc');
        $weights = (array) $this->request->getPost('sub_weight');
        $values = (array) $this->request->getPost('sub_value');

        $subParams = [];
        foreach ($codes as $idx => $c) {
            $code = trim((string) $c);
            $desc = trim((string) ($descs[$idx] ?? ''));
            if ($code === '' && $desc === '') {
                continue;
            }
            $w = (float) ($weights[$idx] ?? 0);
            $v = (float) ($values[$idx] ?? 0);
            $subParams[] = [
                'code'          => $code,
                'description'   => $desc,
                'weight'        => $w,
                'value'         => $v,
                'total'         => $w * $v,
                'display_order' => $idx + 1,
            ];
        }

        $payload = [
            'name'           => $name,
            'sub_parameters' => $subParams,
        ];

        $result = $this->client()->put('/api/v1/scoring/parameters/master/' . $id, $payload, $this->token());
        return redirect()->to('/scoring/parameters')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) ($result['message'] ?? 'Parameter berhasil diperbarui')
        );
    }

    public function deleteMasterParameter(int $id)
    {
        [$denied] = $this->gate('scoring.configure');
        if ($denied) {
            return $denied;
        }

        $result = $this->client()->delete('/api/v1/scoring/parameters/master/' . $id, $this->token());
        return redirect()->to('/scoring/parameters')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) ($result['message'] ?? 'Parameter berhasil dihapus')
        );
    }

    // ==========================================
    // 2. Mapping Parameter & Produk
    // ==========================================

    public function mapping()
    {
        [$denied, $profile] = $this->gate('scoring.configure');
        if ($denied) {
            return $denied;
        }

        $prodRes = $this->client()->get('/api/v1/master/products', $this->token());
        $paramRes = $this->client()->get('/api/v1/scoring/parameters/master', $this->token());
        $mappingRes = $this->client()->get('/api/v1/scoring/parameters/mappings', $this->token());

        $products = array_filter($prodRes['result']['items'] ?? [], fn($p) => ! empty($p['is_active']));
        $parameters = $paramRes['result']['items'] ?? [];
        $mappings = $mappingRes['result']['items'] ?? [];

        return view('scoring/mapping', [
            'profile'    => $profile,
            'products'   => array_values($products),
            'parameters' => $parameters,
            'mappings'   => $mappings,
            'error'      => session()->getFlashdata('error'),
            'message'    => session()->getFlashdata('message'),
        ]);
    }

    public function storeMapping()
    {
        [$denied] = $this->gate('scoring.configure');
        if ($denied) {
            return $denied;
        }

        $productId = (int) $this->request->getPost('product_id');
        $versionName = trim((string) $this->request->getPost('version_name'));
        $passingScore = (float) ($this->request->getPost('passing_score') ?: 350.0);
        $attachmentName = trim((string) $this->request->getPost('attachment_name')) ?: null;
        $attachmentPath = trim((string) $this->request->getPost('attachment_path')) ?: null;

        $paramIds = (array) $this->request->getPost('mapping_param_id');
        $paramNames = (array) $this->request->getPost('mapping_param_name');
        $codes = (array) $this->request->getPost('mapping_code');
        $descs = (array) $this->request->getPost('mapping_desc');
        $weights = (array) $this->request->getPost('mapping_weight');
        $values = (array) $this->request->getPost('mapping_value');
        $totals = (array) $this->request->getPost('mapping_total');

        $items = [];
        foreach ($codes as $idx => $c) {
            $code = trim((string) $c);
            $desc = trim((string) ($descs[$idx] ?? ''));
            if ($code === '' && $desc === '') {
                continue;
            }
            $pName = trim((string) ($paramNames[$idx] ?? ''));
            $pId = ! empty($paramIds[$idx]) ? (int) $paramIds[$idx] : null;
            $w = (float) ($weights[$idx] ?? 0);
            $v = (float) ($values[$idx] ?? 0);
            $tot = (float) ($totals[$idx] ?? ($w * $v));

            $items[] = [
                'parameter_id'   => $pId,
                'parameter_name' => $pName,
                'code'           => $code,
                'description'    => $desc,
                'weight'         => $w,
                'value'          => $v,
                'total'          => $tot,
                'display_order'  => $idx + 1,
            ];
        }

        if (empty($items)) {
            return redirect()->to('/scoring/mapping')->with('error', 'Minimal harus ada 1 parameter scoring yang dipilih!');
        }

        // Validasi total bobot parameter harus tepat 100, tidak boleh kurang atau lebih
        $paramWeights = [];
        foreach ($items as $it) {
            $key = $it['parameter_id'] ? 'id_' . $it['parameter_id'] : 'name_' . $it['parameter_name'];
            if (!isset($paramWeights[$key])) {
                $paramWeights[$key] = (float) $it['weight'];
            }
        }
        $totalBobot = array_sum($paramWeights);
        if (abs($totalBobot - 100.0) > 0.01) {
            $diff = round(abs(100.0 - $totalBobot), 2);
            $ket = $totalBobot < 100.0 ? "Kurang {$diff}" : "Lebih {$diff}";
            return redirect()->to('/scoring/mapping')->with(
                'error',
                "Nilai bobot parameter harus 100, tidak boleh kurang atau lebih! Saat ini total bobot adalah {$totalBobot} ({$ket})."
            );
        }

        $payload = [
            'product_id'      => $productId,
            'version_name'    => $versionName,
            'passing_score'   => $passingScore,
            'attachment_name' => $attachmentName,
            'attachment_path' => $attachmentPath,
            'items'           => $items,
        ];

        $result = $this->client()->post('/api/v1/scoring/parameters/mappings', $payload, $this->token());
        return redirect()->to('/scoring/mapping')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) ($result['message'] ?? 'Mapping Produk berhasil disimpan')
        );
    }

    public function updateMapping(int $id)
    {
        [$denied] = $this->gate('scoring.configure');
        if ($denied) {
            return $denied;
        }

        $productId = (int) $this->request->getPost('product_id');
        $versionName = trim((string) $this->request->getPost('version_name'));
        $passingScore = (float) ($this->request->getPost('passing_score') ?: 350.0);
        $attachmentName = trim((string) $this->request->getPost('attachment_name')) ?: null;
        $attachmentPath = trim((string) $this->request->getPost('attachment_path')) ?: null;

        $paramIds = (array) $this->request->getPost('mapping_param_id');
        $paramNames = (array) $this->request->getPost('mapping_param_name');
        $codes = (array) $this->request->getPost('mapping_code');
        $descs = (array) $this->request->getPost('mapping_desc');
        $weights = (array) $this->request->getPost('mapping_weight');
        $values = (array) $this->request->getPost('mapping_value');
        $totals = (array) $this->request->getPost('mapping_total');

        $items = [];
        foreach ($codes as $idx => $c) {
            $code = trim((string) $c);
            $desc = trim((string) ($descs[$idx] ?? ''));
            if ($code === '' && $desc === '') {
                continue;
            }
            $pName = trim((string) ($paramNames[$idx] ?? ''));
            $pId = ! empty($paramIds[$idx]) ? (int) $paramIds[$idx] : null;
            $w = (float) ($weights[$idx] ?? 0);
            $v = (float) ($values[$idx] ?? 0);
            $tot = (float) ($totals[$idx] ?? ($w * $v));

            $items[] = [
                'parameter_id'   => $pId,
                'parameter_name' => $pName,
                'code'           => $code,
                'description'    => $desc,
                'weight'         => $w,
                'value'          => $v,
                'total'          => $tot,
                'display_order'  => $idx + 1,
            ];
        }

        if (empty($items)) {
            return redirect()->to('/scoring/mapping')->with('error', 'Minimal harus ada 1 parameter scoring yang dipilih!');
        }

        // Validasi total bobot parameter harus tepat 100, tidak boleh kurang atau lebih
        $paramWeights = [];
        foreach ($items as $it) {
            $key = $it['parameter_id'] ? 'id_' . $it['parameter_id'] : 'name_' . $it['parameter_name'];
            if (!isset($paramWeights[$key])) {
                $paramWeights[$key] = (float) $it['weight'];
            }
        }
        $totalBobot = array_sum($paramWeights);
        if (abs($totalBobot - 100.0) > 0.01) {
            $diff = round(abs(100.0 - $totalBobot), 2);
            $ket = $totalBobot < 100.0 ? "Kurang {$diff}" : "Lebih {$diff}";
            return redirect()->to('/scoring/mapping')->with(
                'error',
                "Nilai bobot parameter harus 100, tidak boleh kurang atau lebih! Saat ini total bobot adalah {$totalBobot} ({$ket})."
            );
        }

        $payload = [
            'product_id'      => $productId,
            'version_name'    => $versionName,
            'passing_score'   => $passingScore,
            'attachment_name' => $attachmentName,
            'attachment_path' => $attachmentPath,
            'items'           => $items,
        ];

        $result = $this->client()->put('/api/v1/scoring/parameters/mappings/' . $id, $payload, $this->token());
        return redirect()->to('/scoring/mapping')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) ($result['message'] ?? 'Mapping Produk berhasil diperbarui')
        );
    }

    public function uploadMappingAttachment()
    {
        [$denied] = $this->gate('scoring.configure');
        if ($denied) {
            return $this->response->setJSON(['rcode' => '04', 'message' => 'Akses ditolak']);
        }

        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            return $this->response->setJSON(['rcode' => '01', 'message' => 'File tidak valid']);
        }

        $uploadDir = FCPATH . 'uploads/mappings/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = $file->getClientName();
        $randomName = $file->getRandomName();
        $file->move($uploadDir, $randomName);

        return $this->response->setJSON([
            'rcode'   => '00',
            'message' => 'File berhasil diunggah',
            'result'  => [
                'attachment_name' => $fileName,
                'attachment_path' => '/uploads/mappings/' . $randomName,
            ],
        ]);
    }

    public function mappingDetail(int $mappingId)
    {
        [$denied] = $this->gate('scoring.configure');
        if ($denied) {
            return $this->response->setJSON(['rcode' => '04', 'message' => 'Akses ditolak']);
        }

        $result = $this->client()->get('/api/v1/scoring/parameters/mappings/' . $mappingId, $this->token());
        return $this->response->setJSON($result);
    }

    public function deleteMapping(int $mappingId)
    {
        [$denied] = $this->gate('scoring.configure');
        if ($denied) {
            return redirect()->to('/scoring/mapping')->with('error', 'Akses ditolak');
        }

        $result = $this->client()->delete('/api/v1/scoring/parameters/mappings/' . $mappingId, $this->token());
        return redirect()->to('/scoring/mapping')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) ($result['message'] ?? 'Mapping berhasil dihapus')
        );
    }

    // ==========================================
    // 3. Scoring Kredit
    // ==========================================

    public function creditScoring()
    {
        [$denied, $profile] = $this->gate(['scoring.submit', 'scoring.configure']);
        if ($denied) {
            return $denied;
        }

        $debtorsRes = $this->client()->get('/api/v1/scoring/parameters/credit/debtors', $this->token());
        $productsRes = $this->client()->get('/api/v1/scoring/parameters/credit/products', $this->token());
        $supervisorsRes = $this->client()->get('/api/v1/scoring/parameters/credit/supervisors', $this->token());

        $debtors = $debtorsRes['result']['items'] ?? [];
        $products = $productsRes['result']['items'] ?? [];
        $supervisors = $supervisorsRes['result']['items'] ?? [];

        return view('scoring/credit', [
            'profile'     => $profile,
            'debtors'     => $debtors,
            'products'    => $products,
            'supervisors' => $supervisors,
            'error'       => session()->getFlashdata('error'),
            'message'     => session()->getFlashdata('message'),
        ]);
    }

    public function creditMappingItems(int $productId)
    {
        [$denied] = $this->gate(['scoring.submit', 'scoring.configure']);
        if ($denied) {
            return $this->response->setJSON(['rcode' => '04', 'message' => 'Akses ditolak']);
        }

        $result = $this->client()->get('/api/v1/scoring/parameters/credit/mapping-items/' . $productId, $this->token());
        return $this->response->setJSON($result);
    }

    public function saveCreditScoring()
    {
        [$denied] = $this->gate(['scoring.submit', 'scoring.configure']);
        if ($denied) {
            return $this->response->setJSON(['rcode' => '04', 'message' => 'Akses ditolak']);
        }

        $raw = $this->request->getJSON(true) ?? $this->request->getPost();
        $result = $this->client()->post('/api/v1/scoring/parameters/credit/save', $raw, $this->token());
        return $this->response->setJSON($result);
    }

    public function duplicateSetting()
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->put('/api/v1/rescore/duplicate-setting', [
            'enabled' => $this->request->getPost('enabled') === '1',
        ], $this->token());

        return redirect()->to('/scoring/products')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function products()
    {
        [$denied, $profile] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $rows = $this->client()->get('/api/v1/master/products', $this->token());
        $setting = $this->client()->get('/api/v1/rescore/duplicate-setting', $this->token());

        return view('scoring/products', [
            'profile'  => $profile,
            'products' => $rows['result']['items'] ?? [],
            'duplicateEnabled' => ($setting['result']['enabled'] ?? false) === true,
            'error'    => session()->getFlashdata('error'),
            'message'  => session()->getFlashdata('message'),
        ]);
    }

    public function versions(int $productId)
    {
        [$denied, $profile] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $rows = $this->client()->get('/api/v1/scoring/products/' . $productId . '/versions', $this->token());
        if (($rows['rcode'] ?? '') !== '00') {
            return redirect()->to('/scoring/products')->with('error', (string) $rows['message']);
        }

        return view('scoring/versions', [
            'profile' => $profile,
            'product' => $rows['result']['product'],
            'items'   => $rows['result']['items'] ?? [],
            'error'   => session()->getFlashdata('error'),
            'message' => session()->getFlashdata('message'),
        ]);
    }

    public function createVersion(int $productId)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/scoring/products/' . $productId . '/versions', ['create' => true], $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            return redirect()->to('/scoring/products/' . $productId)->with('error', (string) $result['message']);
        }

        return redirect()->to('/scoring/versions/' . $result['result']['id']);
    }

    public function show(int $versionId)
    {
        [$denied, $profile] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->get('/api/v1/scoring/versions/' . $versionId, $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            return redirect()->to('/scoring/products')->with('error', (string) $result['message']);
        }

        return view('scoring/version', [
            'profile' => $profile,
            'version' => $result['result'],
            'error'   => session()->getFlashdata('error'),
            'message' => session()->getFlashdata('message'),
            'preview' => session()->getFlashdata('preview'),
        ]);
    }

    public function storeParameter(int $versionId)
    {
        return $this->postVersion($versionId, '/api/v1/scoring/versions/' . $versionId . '/parameters', [
            'name'          => trim((string) $this->request->getPost('name')),
            'weight'        => (string) $this->request->getPost('weight'),
            'display_order' => (int) $this->request->getPost('display_order'),
        ]);
    }

    public function storeOption(int $parameterId)
    {
        $versionId = (int) $this->request->getPost('version_id');

        return $this->postVersion($versionId, '/api/v1/scoring/parameters/' . $parameterId . '/options', [
            'label'         => trim((string) $this->request->getPost('label')),
            'value'         => (string) $this->request->getPost('value'),
            'display_order' => (int) $this->request->getPost('display_order'),
        ]);
    }

    public function storeThreshold(int $versionId)
    {
        $max = trim((string) $this->request->getPost('max_score'));

        return $this->postVersion($versionId, '/api/v1/scoring/versions/' . $versionId . '/thresholds', [
            'min_score'     => (string) $this->request->getPost('min_score'),
            'max_score'     => $max === '' ? null : $max,
            'result_label'  => trim((string) $this->request->getPost('result_label')),
            'display_order' => (int) $this->request->getPost('display_order'),
        ]);
    }

    public function storeField(int $versionId)
    {
        return $this->postVersion($versionId, '/api/v1/scoring/versions/' . $versionId . '/fields', [
            'field_key'     => trim((string) $this->request->getPost('field_key')),
            'label'         => trim((string) $this->request->getPost('label')),
            'field_type'    => (string) $this->request->getPost('field_type'),
            'is_required'   => $this->request->getPost('is_required') === '1',
            'is_active'     => $this->request->getPost('is_active') === '1',
            'display_order' => (int) $this->request->getPost('display_order'),
        ]);
    }

    public function storeFieldOption(int $fieldId)
    {
        $versionId = (int) $this->request->getPost('version_id');

        return $this->postVersion($versionId, '/api/v1/scoring/fields/' . $fieldId . '/options', [
            'label'         => trim((string) $this->request->getPost('label')),
            'value'         => trim((string) $this->request->getPost('value')),
            'display_order' => (int) $this->request->getPost('display_order'),
        ]);
    }

    public function activate(int $versionId)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/scoring/versions/' . $versionId . '/activate', ['activate' => true], $this->token());

        return redirect()->to('/scoring/versions/' . $versionId)->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function copyVersion(int $versionId)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/scoring/versions/' . $versionId . '/copy', ['copy' => true], $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            return redirect()->back()->with('error', (string) $result['message']);
        }

        return redirect()->to('/scoring/versions/' . $result['result']['id'])->with('message', (string) $result['message']);
    }

    public function calculate(int $versionId)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $selected = $this->request->getPost('option');
        $answers = [];
        if (is_array($selected)) {
            foreach ($selected as $parameterId => $optionId) {
                $answers[] = ['parameter_id' => (int) $parameterId, 'option_id' => (int) $optionId];
            }
        }
        $result = $this->client()->post('/api/v1/scoring/versions/' . $versionId . '/calculate', ['answers' => $answers], $this->token());
        $flash = ($result['rcode'] ?? '') === '00' ? 'preview' : 'error';
        $value = ($result['rcode'] ?? '') === '00' ? $result['result'] : (string) $result['message'];

        return redirect()->to('/scoring/versions/' . $versionId)->with($flash, $value);
    }

    private function postVersion(int $versionId, string $path, array $payload)
    {
        [$denied] = $this->gate();
        if ($denied) {
            return $denied;
        }
        $result = $this->client()->post($path, $payload, $this->token());

        return redirect()->to('/scoring/versions/' . $versionId)->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }
}
