<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Sprint 1, access and roles (owner, 2026-11-05). The MDRRMO's two roles are
 * part of the role contract, with the grants they held on staging; Missing
 * Persons is read only by the MDRRMO, the Super Administrator, the Municipal
 * Administrator and the Municipal Information Officer; and an MDRRMO Head
 * changes only the posts the server says it may (can_change).
 */
class MdrrmoRolesContractTest extends TestCase
{
    private const MDRRMO_HEAD = [
        'comm_feedback' => ['view', 'assign', 'edit'],
        'comm_news' => ['view', 'create', 'edit', 'delete', 'review', 'approve', 'reject', 'archive'],
        'const_barangay' => ['view'],
        'const_data_quality' => ['view'],
        'const_family' => ['view'],
        'const_household' => ['view'],
        'const_resident' => ['view'],
        'sakuna_alerts' => ['view', 'create', 'edit', 'review', 'approve', 'publish', 'cancel', 'archive', 'export'],
        'sakuna_centers' => ['view', 'create', 'edit', 'assign', 'open', 'close', 'export'],
        'sakuna_command' => ['view', 'create', 'edit', 'dispatch', 'escalate', 'close', 'export'],
        'sakuna_damage' => ['view', 'create', 'edit', 'validate', 'approve', 'export'],
        'sakuna_evacuees' => ['view', 'create', 'edit', 'check_in', 'transfer', 'check_out', 'export'],
        'sakuna_incidents' => ['view', 'create', 'edit', 'validate', 'assign', 'dispatch', 'escalate', 'resolve', 'close', 'export'],
        'sakuna_missing' => ['view', 'create', 'edit', 'close'],
        'sakuna_relief' => ['view', 'create', 'edit', 'approve', 'release', 'export'],
        'sakuna_reports' => ['view', 'print', 'export'],
        'sakuna_resources' => ['view', 'create', 'edit', 'assign', 'deploy', 'return', 'export'],
        'sakuna_vulnerability' => ['view', 'create', 'edit', 'approve', 'export'],
    ];

    private const MDRRMO_STAFF = [
        'comm_news' => ['view', 'create', 'edit', 'review'],
        'const_barangay' => ['view'],
        'const_data_quality' => ['view'],
        'const_family' => ['view'],
        'const_household' => ['view'],
        'const_resident' => ['view'],
        'sakuna_alerts' => ['view', 'create', 'edit'],
        'sakuna_centers' => ['view', 'create', 'edit'],
        'sakuna_command' => ['view', 'create', 'edit'],
        'sakuna_damage' => ['view', 'create', 'edit'],
        'sakuna_evacuees' => ['view', 'create', 'edit', 'check_in', 'transfer', 'check_out'],
        'sakuna_incidents' => ['view', 'create', 'edit', 'validate'],
        'sakuna_missing' => ['view', 'create', 'edit', 'close'],
        'sakuna_relief' => ['view', 'create', 'edit', 'release'],
        'sakuna_reports' => ['view', 'print', 'export'],
        'sakuna_resources' => ['view', 'create', 'edit'],
        'sakuna_vulnerability' => ['view', 'create', 'edit'],
    ];

    /** A bundle in one order: submodules by key, each one's actions sorted. */
    private function canonical(array $bundle): array
    {
        ksort($bundle);

        return array_map(function ($perms) {
            sort($perms);

            return $perms;
        }, $bundle);
    }

    private function triples(array $bundle): int
    {
        return array_sum(array_map('count', $bundle));
    }

    public function test_both_mdrrmo_roles_are_in_the_contract_with_their_staging_grants(): void
    {
        $roles = collect(config('esperanza_rbac.roles'))->keyBy('key');
        $keys = $roles->keys()->all();
        $this->assertSame(array_search('mio_staff', $keys) + 1, array_search('mdrrmo_head', $keys), 'right after MIO Staff');
        $this->assertSame(array_search('mdrrmo_head', $keys) + 1, array_search('mdrrmo_staff', $keys));

        $this->assertSame('MDRRMO Head', $roles['mdrrmo_head']['name']);
        $this->assertSame('MDRRMO Staff', $roles['mdrrmo_staff']['name']);
        $this->assertSame($this->canonical(self::MDRRMO_HEAD), $this->canonical($roles['mdrrmo_head']['permissions']));
        $this->assertSame($this->canonical(self::MDRRMO_STAFF), $this->canonical($roles['mdrrmo_staff']['permissions']));
        $this->assertSame(87, $this->triples($roles['mdrrmo_head']['permissions']));
        $this->assertSame(48, $this->triples($roles['mdrrmo_staff']['permissions']));

        // Every grant is a submodule and action the contract declares.
        $declared = [];
        foreach (config('esperanza_rbac.permission_modules') as $module) {
            foreach ($module['submodules'] as $sub) {
                $declared[$sub['key']] = $sub['perms'];
            }
        }
        foreach (['mdrrmo_head', 'mdrrmo_staff'] as $key) {
            foreach ($roles[$key]['permissions'] as $sub => $perms) {
                $this->assertArrayHasKey($sub, $declared, "{$key}: {$sub}");
                $this->assertSame([], array_values(array_diff($perms, $declared[$sub])), "{$key}: {$sub}");
            }
        }
    }

    public function test_only_the_owners_list_reads_missing_persons(): void
    {
        $viewers = collect(config('esperanza_rbac.roles'))
            ->filter(fn ($role) => in_array('view', $role['permissions']['sakuna_missing'] ?? [], true))
            ->pluck('key')->sort()->values()->all();

        $this->assertSame(['info_officer', 'mdrrmo_head', 'mdrrmo_staff', 'municipal_admin', 'super_admin'], $viewers);

        $roles = collect(config('esperanza_rbac.roles'))->keyBy('key');
        foreach (['department_head', 'department_staff', 'front_desk'] as $key) {
            $this->assertArrayNotHasKey('sakuna_missing', $roles[$key]['permissions'], $key);
        }
        $this->assertSame(['view'], $roles['info_officer']['permissions']['sakuna_missing']);
    }

    public function test_balita_hides_editor_actions_where_the_server_says_can_change_false(): void
    {
        $js = file_get_contents(base_path('resources/js/scope-admin-records.js'));

        $this->assertStringContainsString('mayChange(post) { return post.can_change !== false && (this.approver || !!post.mine); },', $js);
        $this->assertStringContainsString('return !!action && this.can(action) && this.mayChange(post);', $js, 'approve, publish, send back, archive');
        $this->assertStringContainsString("this.can('edit') && this.mayChange(post); }", $js, 'edit');
        $this->assertStringNotContainsString("this.can('delete') && this.mayChange(post); }", $js, 'no delete: posts are archived, never deleted (2026-11-14)');
        $this->assertStringContainsString("a.status === 'Rejected' && this.mayChange(a)", $js);
        $this->assertStringContainsString("this.approver && post.can_change !== false && post.status === 'Released'", $js, 'post to Facebook');
        $this->assertStringContainsString('post.available_transitions?.[to]', $js, 'the server still lists the moves');

        // Hiding a comment and showing it again follow the same office limit.
        $this->assertStringContainsString('moderate: this.approver && this.mayChange(post) }', $js, 'comment moderation');
        $this->assertStringContainsString('<div x-show="bcModerate" class="shrink-0">', file_get_contents(base_path('resources/views/admin/communications.blade.php')));

        // MDRRMO Staff are told their own head approves, in the note and after sending.
        $this->assertStringContainsString("roleKey === 'mdrrmo_staff' ? 'Your MDRRMO Head approves it.'", $js);
        $balita = file_get_contents(base_path('resources/views/admin/communications/balita.blade.php'));
        $this->assertStringContainsString("roleKey === 'mdrrmo_staff' ? 'Your MDRRMO Head approves and publishes them.' : 'Your Department Head or the Municipal Information Office approves and publishes them.'", $balita);
    }
}
