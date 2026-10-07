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

    private function allowed(array $profile, string|array $codes = 'access.manage')
    {
        if ($profile === []) {
            return redirect()->to('/login');
        }
        $needed = is_array($codes) ? $codes : [$codes, 'access.manage'];
        if (! $this->profileCan($profile, $needed)) {
            return redirect()->to('/')->with('error', 'Anda tidak memiliki hak akses');
        }

        return null;
    }

    public function users()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.users')) {
            return $denied;
        }
        $token = $this->token();
        $hrisResp = $this->client()->get('/api/v1/access/userhris', $token);
        $userHrisItems = $hrisResp['result']['items'] ?? [];
        $users = $this->client()->get('/api/v1/access/users', $token);
        $jobGroups = $this->client()->get('/api/v1/access/job-groups', $token);
        $branches = $this->client()->get('/api/v1/access/branches', $token);

        return view('access/users', [
            'profile'   => $profile,
            'userHris'  => $userHrisItems,
            'users'     => $users['result']['items'] ?? [],
            'jobGroups' => $jobGroups['result']['items'] ?? [],
            'branches'  => $branches['result']['items'] ?? [],
            'error'     => session()->getFlashdata('error'),
            'message'   => session()->getFlashdata('message'),
        ]);
    }

    public function syncUserHris()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.users')) {
            return $denied;
        }
        $userid = trim((string) $this->request->getPost('userid') ?: '1776');
        $kondisi = trim((string) $this->request->getPost('kondisi') ?: '');
        $result = $this->client()->post('/api/v1/access/userhris/sync', [
            'userid' => $userid,
            'kondisi' => $kondisi,
        ], $this->token());

        return redirect()->to('/access/users')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) ($result['message'] ?? 'Sinkronisasi selesai')
        );
    }

    public function storeUser()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.users')) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/access/users', [
            'username'      => trim((string) $this->request->getPost('username')),
            'password'      => (string) $this->request->getPost('password'),
            'full_name'     => trim((string) $this->request->getPost('full_name')),
            'job_group_id'  => (int) $this->request->getPost('job_group_id'),
            'branch_id'     => (int) $this->request->getPost('branch_id'),
            'is_active'     => $this->request->getPost('is_active') === '1',
        ], $this->token());

        return redirect()->to('/access/users')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function updateUser(int $id)
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.users')) {
            return $denied;
        }
        $payload = [
            'full_name'    => trim((string) $this->request->getPost('full_name')),
            'job_group_id' => (int) $this->request->getPost('job_group_id'),
            'branch_id'    => (int) $this->request->getPost('branch_id'),
            'is_active'    => $this->request->getPost('is_active') === '1',
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

    public function jobGroups()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.users')) {
            return $denied;
        }
        $token = $this->token();
        $groups = $this->client()->get('/api/v1/access/job-groups', $token);
        $roles = $this->client()->get('/api/v1/access/roles', $token);

        return view('access/job_groups', [
            'profile'   => $profile,
            'jobGroups' => $groups['result']['items'] ?? [],
            'roles'     => $roles['result']['items'] ?? [],
            'error'     => session()->getFlashdata('error'),
            'message'   => session()->getFlashdata('message'),
        ]);
    }

    public function storeJobGroup()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.users')) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/access/job-groups', [
            'code'      => trim((string) $this->request->getPost('code')),
            'name'      => trim((string) $this->request->getPost('name')),
            'role_id'   => (int) $this->request->getPost('role_id'),
            'is_active' => $this->request->getPost('is_active') === '1',
        ], $this->token());

        return redirect()->to('/access/job-groups')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function updateJobGroup(int $id)
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.users')) {
            return $denied;
        }
        $result = $this->client()->patch('/api/v1/access/job-groups/' . $id, [
            'name'      => trim((string) $this->request->getPost('name')),
            'role_id'   => (int) $this->request->getPost('role_id'),
            'is_active' => $this->request->getPost('is_active') === '1',
        ], $this->token());

        return redirect()->to('/access/job-groups')->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function roles()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.roles')) {
            return $denied;
        }
        $token = $this->token();
        $roles = $this->client()->get('/api/v1/access/roles', $token);

        return view('access/roles', [
            'profile' => $profile,
            'roles'   => $roles['result']['items'] ?? [],
            'error'   => session()->getFlashdata('error'),
            'message' => session()->getFlashdata('message'),
        ]);
    }

    public function storeRole()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.roles')) {
            return $denied;
        }
        $result = $this->client()->post('/api/v1/access/roles', [
            'code'      => trim((string) $this->request->getPost('code')),
            'name'      => trim((string) $this->request->getPost('name')),
            'is_active' => true,
        ], $this->token());

        if (($result['rcode'] ?? '') === '00') {
            $newId = (int) ($result['result']['id'] ?? 0);
            if ($newId > 0) {
                return redirect()->to('/access/roles/' . $newId)->with('message', (string) $result['message']);
            }

            return redirect()->to('/access/roles')->with('message', (string) $result['message']);
        }

        return redirect()->to('/access/roles')->with('error', (string) $result['message']);
    }

    public function editRole(int $id)
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.roles')) {
            return $denied;
        }
        $token = $this->token();
        $roles = $this->client()->get('/api/v1/access/roles', $token);
        $permissions = $this->client()->get('/api/v1/access/permissions', $token);
        $role = null;
        foreach (($roles['result']['items'] ?? []) as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                $role = $item;
                break;
            }
        }
        if ($role === null) {
            return redirect()->to('/access/roles')->with('error', 'Role tidak ditemukan');
        }

        return view('access/role_form', [
            'profile'     => $profile,
            'role'        => $role,
            'permissions' => $permissions['result']['items'] ?? [],
            'error'       => session()->getFlashdata('error'),
            'message'     => session()->getFlashdata('message'),
        ]);
    }

    public function updateRole(int $id)
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.roles')) {
            return $denied;
        }
        $token = $this->token();
        $meta = $this->client()->patch('/api/v1/access/roles/' . $id, [
            'name'      => trim((string) $this->request->getPost('name')),
            'is_active' => $this->request->getPost('is_active') === '1',
        ], $token);
        if (($meta['rcode'] ?? '') !== '00') {
            return redirect()->to('/access/roles/' . $id)->with('error', (string) $meta['message']);
        }

        $ids = $this->request->getPost('permission_ids');
        $result = $this->client()->put('/api/v1/access/roles/' . $id . '/permissions', [
            'permission_ids' => array_map('intval', is_array($ids) ? $ids : []),
        ], $token);

        return redirect()->to('/access/roles/' . $id)->with(
            ($result['rcode'] ?? '') === '00' ? 'message' : 'error',
            (string) $result['message']
        );
    }

    public function savePermissions(int $id)
    {
        return $this->updateRole($id);
    }

    public function permissions()
    {
        $profile = $this->profile();
        if ($denied = $this->allowed($profile, 'access.permissions')) {
            return $denied;
        }
        $permissions = $this->client()->get('/api/v1/access/permissions', $this->token());

        return view('access/permissions', [
            'profile'     => $profile,
            'permissions' => $permissions['result']['items'] ?? [],
        ]);
    }
}
