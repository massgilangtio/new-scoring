<?php

/**
 * Permission module meta for Access UI grouping.
 *
 * @return array{key:string,label:string,icon:string,tone:string}
 */
function access_perm_module(string $code): array
{
    $prefix = strtolower(explode('.', $code, 2)[0] ?? 'other');

    $map = [
        'dashboard' => ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-solid fa-gauge-high', 'tone' => 'dashboard'],
        'scoring'   => ['key' => 'scoring', 'label' => 'Transaksi & Scoring', 'icon' => 'fa-solid fa-file-invoice', 'tone' => 'scoring'],
        'master'    => ['key' => 'master', 'label' => 'Master Data', 'icon' => 'fa-solid fa-database', 'tone' => 'master'],
        'report'    => ['key' => 'report', 'label' => 'Laporan', 'icon' => 'fa-solid fa-chart-pie', 'tone' => 'report'],
        'audit'     => ['key' => 'audit', 'label' => 'Audit Trail', 'icon' => 'fa-solid fa-shield-halved', 'tone' => 'audit'],
        'system'    => ['key' => 'audit', 'label' => 'Log Sistem & Error', 'icon' => 'fa-solid fa-server', 'tone' => 'audit'],
        'access'    => ['key' => 'access', 'label' => 'User & Access', 'icon' => 'fa-solid fa-user-group', 'tone' => 'access'],
        'branch'    => ['key' => 'other', 'label' => 'Lainnya', 'icon' => 'fa-solid fa-ellipsis', 'tone' => 'other'],
    ];

    return $map[$prefix] ?? ['key' => 'other', 'label' => 'Lainnya', 'icon' => 'fa-solid fa-ellipsis', 'tone' => 'other'];
}

/**
 * True if profile owns any of the given permission codes.
 *
 * @param array<string,mixed> $profile
 * @param string|list<string> $codes
 */
function profile_can(array $profile, string|array $codes): bool
{
    $owned = $profile['permissions'] ?? [];
    if (! is_array($owned)) {
        return false;
    }
    foreach ((array) $codes as $code) {
        if (in_array($code, $owned, true)) {
            return true;
        }
    }

    return false;
}

/**
 * Flexible gate for score metrics (batas skor, kelayakan, bobot, nilai, skor).
 *
 * @param array<string,mixed> $profile
 */
function can_view_score_details(array $profile): bool
{
    return profile_can($profile, 'scoring.view_score_details');
}

/**
 * Group permission rows by module key, preserving catalog order within group.
 *
 * @param list<array<string,mixed>> $permissions
 * @return array<string, array{meta: array{key:string,label:string,icon:string,tone:string}, items: list<array<string,mixed>>}>
 */
function access_group_permissions(array $permissions): array
{
    $order = ['dashboard', 'scoring', 'master', 'report', 'audit', 'access', 'other'];
    $groups = [];

    foreach ($permissions as $permission) {
        $code = (string) ($permission['code'] ?? '');
        $meta = access_perm_module($code);
        $key = $meta['key'];
        if (! isset($groups[$key])) {
            $groups[$key] = ['meta' => $meta, 'items' => []];
        }
        $groups[$key]['items'][] = $permission;
    }

    $sorted = [];
    foreach ($order as $key) {
        if (isset($groups[$key])) {
            $sorted[$key] = $groups[$key];
        }
    }
    foreach ($groups as $key => $group) {
        if (! isset($sorted[$key])) {
            $sorted[$key] = $group;
        }
    }

    return $sorted;
}

/**
 * Initials from a display name.
 */
function access_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
    }

    return $initials !== '' ? $initials : '?';
}
