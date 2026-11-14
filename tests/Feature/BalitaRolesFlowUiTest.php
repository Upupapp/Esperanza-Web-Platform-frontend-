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

    /** Posts and events are archived, never deleted (owner, 2026-11-14); see ArchiveOnlyUiTest. */
    public function test_posts_and_events_are_archived_never_deleted(): void
    {
        $js = $this->source('resources/js/scope-admin-records.js');

        // Since 2026-10-29 a post on the LGU's Facebook Page can be removed there too, now on archiving.
        $this->assertStringContainsString("await api.post('/admin/announcements/' + post.id + '/transition', { to: 'Archived', remove_from_facebook: fromFb || undefined });", $js);
        $this->assertStringContainsString('@click="archivePost(a)"', $this->source('resources/views/admin/communications/balita.blade.php'));
        $this->assertStringContainsString("if (await this.transition(e, 'Archived')) { balitaToast('Archived.'); await this.load(); }", $js);
        $this->assertStringNotContainsString("api.delete('/admin/announcements/", $js);
        $this->assertStringNotContainsString("api.delete('/admin/events/", $js);
        $this->assertStringNotContainsString('canDelete(', $js);
    }
}
