<?php

namespace App\Controllers;

use App\Libraries\ApiClient;

class Access extends BaseController
{
    private function client(): ApiClient
    {
        return new ApiClient();
    }

    private function token(): string
    {
        return (string) session()->get('access_token');
    }

    private function profile(): array
    {
        $result = $this->client()->get('/api/v1/auth/me', $this->token());
        if (($result['rcode'] ?? '') !== '00') {
            session()->destroy();

            return [];
        }

        return $result['result'];
    }

    private function allowed(array $profile)
    {
        if ($profile === []) {
            return redirect()->to('/login');
        }
        if (! in_array('access.manage', $profile['permissions'] ?? [], true)) {
            return redirect()->to('/')->with('error', 'Anda tidak memiliki hak akses');
        }

        return null;
    }

    public function users()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile)) {
            return $denied;
        }
        $token = $this->token();
        $users = $this->client()->get('/api/v1/access/users', $token);
        $roles = $this->client()->get('/api/v1/access/roles', $token);
        $branches = $this->client()->get('/api/v1/access/branches', $token);

        return view('access/users', [
            'profile'  => $profile,
            'users'    => $users['result']['items'] ?? [],
            'roles'    => $roles['result']['items'] ?? [],
            'branches' => $branches['result']['items'] ?? [],
            'error'    => session()->getFlashdata('error'),
            'message'  => session()->getFlashdata('message'),
        ]);
    }

    public function storeUser()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile)) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/access/users', [
            'username'  => trim((string) $this->request->getPost('username')),
            'password'  => (string) $this->request->getPost('password'),
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'role_id'   => (int) $this->request->getPost('role_id'),
            'branch_id' => (int) $this->request->getPost('branch_id'),
            'is_active' => $this->request->getPost('is_active') === '1',
        ], $this->token());

        return redirect()->to('/access/users')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function updateUser(int $id)
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile)) {
            return $denied;
        }
        $payload = [
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'role_id'   => (int) $this->request->getPost('role_id'),
            'branch_id' => (int) $this->request->getPost('branch_id'),
            'is_active' => $this->request->getPost('is_active') === '1',
        ];
        $password = (string) $this->request->getPost('password');
        if ($password !== '') {
            $payload['password'] = $password;
        }
        $result = $this->client()->patch('/api/v1/access/users/' . $id, $payload, $this->token());

        return redirect()->to('/access/users')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function roles()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile)) {
            return $denied;
        }
        $token = $this->token();
        $roles = $this->client()->get('/api/v1/access/roles', $token);
        $permissions = $this->client()->get('/api/v1/access/permissions', $token);

        return view('access/roles', [
            'profile'     => $profile,
            'roles'       => $roles['result']['items'] ?? [],
            'permissions' => $permissions['result']['items'] ?? [],
            'error'       => session()->getFlashdata('error'),
            'message'     => session()->getFlashdata('message'),
        ]);
    }

    public function storeRole()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile)) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/access/roles', [
            'code'      => trim((string) $this->request->getPost('code')),
            'name'      => trim((string) $this->request->getPost('name')),
            'is_active' => true,
        ], $this->token());

        return redirect()->to('/access/roles')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function savePermissions(int $id)
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile)) {
            return $denied;
        }
        $ids = $this->request->getPost('permission_ids');
        $result = $this->client()->put('/api/v1/access/roles/' . $id . '/permissions', [
            'permission_ids' => array_map('intval', is_array($ids) ? $ids : []),
        ], $this->token());

        return redirect()->to('/access/roles')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function permissions()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile)) {
            return $denied;
        }
        $permissions = $this->client()->get('/api/v1/access/permissions', $this->token());

        return view('access/permissions', [
            'profile'     => $profile,
            'permissions' => $permissions['result']['items'] ?? [],
        ]);
    }
}
