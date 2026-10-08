<?php

namespace App\Libraries;

use Throwable;

class ApiClient
{
    public function post(string $path, array $payload, ?string $token = null): array
    {
        return $this->request('POST', $path, $payload, $token);
    }

    public function get(string $path, ?string $token = null): array
    {
        return $this->request('GET', $path, null, $token);
    }

    public function patch(string $path, array $payload, ?string $token = null): array
    {
        return $this->request('PATCH', $path, $payload, $token);
    }

    public function put(string $path, array $payload, ?string $token = null): array
    {
        return $this->request('PUT', $path, $payload, $token);
    }

    public function postFile(string $path, string $filePath, string $fileName, ?string $token = null): array
    {
        return $this->request('POST', $path, null, $token, $filePath, $fileName);
    }

    public function delete(string $path, ?string $token = null): array
    {
        return $this->request('DELETE', $path, null, $token);
    }

    private function request(
        string $method,
        string $path,
        ?array $payload,
        ?string $token,
        ?string $filePath = null,
        ?string $fileName = null
    ): array {
        $headers = ['Accept' => 'application/json'];
        if ($token !== null && $token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $options = ['headers' => $headers, 'http_errors' => false];
        if ($filePath !== null) {
            $options['multipart'] = [
                'file' => new \CURLFile($filePath, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $fileName ?? 'import.xlsx'),
            ];
        } elseif ($payload !== null) {
            $options['json'] = $payload;
        }

        try {
            $base = rtrim((string) env('api.baseURL', 'http://127.0.0.1:8000'), '/') . '/';
            $response = \Config\Services::curlrequest([
                'baseURI' => $base,
                'timeout' => 60,
            ], null, null, false)->request($method, ltrim($path, '/'), $options);
            $decoded = json_decode((string) $response->getBody(), true);
        } catch (Throwable $error) {
            return [
                'rcode'   => '99',
                'message' => 'Layanan autentikasi tidak tersedia',
                'result'  => [],
            ];
        }

        if (! is_array($decoded) || ! isset($decoded['rcode'], $decoded['message'])) {
            return [
                'rcode'   => '99',
                'message' => 'Respons autentikasi tidak dikenali',
                'result'  => [],
            ];
        }

        $decoded['result'] ??= [];

        return $decoded;
    }
}
