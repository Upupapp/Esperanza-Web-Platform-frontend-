<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Who leads whom in Balita, said where it is done (owner, 2026-10-21: "who is
 * the Municipal Information Officer's lead? ... approve a post, delete,
 * post"). The Municipal Administrator leads the Information Office for the
 * Office of the Mayor; the Super Administrator keeps the system. Posts and
 * events are deleted only once they are a draft, sent back or archived.
 */
class BalitaRolesFlowUiTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_each_role_that_runs_balita_is_told_its_part_and_its_lead(): void
    {
        $page = $this->source('resources/views/admin/communications.blade.php');
        $rbac = $this->source('config/esperanza_rbac.php');

        $this->assertStringContainsString('<span x-text="roleNote"></span>', $page);
        foreach (['super_admin:', 'municipal_admin:', 'info_officer:', 'department_head:'] as $role) {
            $this->assertStringContainsString($role, $page, $role);
        }
        $this->assertStringContainsString('Your lead is the Municipal Administrator', $page);
        $this->assertStringContainsString('you lead the Municipal Information Office', $page);
        $this->assertStringContainsString('Led by the Municipal Administrator, for the Office of the Mayor.', $rbac);
        $this->assertStringContainsString('leads the Municipal Information Officer', $rbac);
        $this->assertStringContainsString('goes to the Municipal Administrator, its lead', $this->source('resources/views/admin/settings.blade.php'));
    }

    public function test_posts_and_events_are_deleted_only_once_residents_cannot_see_them(): void
    {
        $js = $this->source('resources/js/scope-admin-records.js');

        $this->assertStringContainsString("canDelete(post) { return ['Draft', 'Rejected', 'Archived'].includes(post.status) && this.can('delete')", $js);
        $this->assertStringContainsString("await api.delete('/admin/announcements/' + post.id);", $js);
        $this->assertStringContainsString('x-show="canDelete(a)" @click="deletePost(a)"', $this->source('resources/views/admin/communications/balita.blade.php'));
        $this->assertStringContainsString("canDelete(e) { return ['Draft', 'Rejected', 'Archived'].includes(e.status) && this.can('delete'); }", $js);
        $this->assertStringContainsString("await api.delete('/admin/events/' + encodeURIComponent(e.key));", $js);
        $this->assertStringContainsString('x-show="canDelete(e)" @click="deleteEvent(e)"', $this->source('resources/views/admin/communications.blade.php'));
    }
}
