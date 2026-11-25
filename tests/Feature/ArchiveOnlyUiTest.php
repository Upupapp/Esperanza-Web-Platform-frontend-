<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Records are archived, never deleted (owner, 2026-11-14: "i want it also to
 * be archives only no delete"). The staff screens offer Archive where they
 * offered Delete or Remove, and no longer call the delete endpoints the
 * server took away (Balita posts, events, banners).
 */
class ArchiveOnlyUiTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_balita_posts_are_archived_and_may_leave_the_facebook_page_too(): void
    {
        $js = $this->source('resources/js/scope-admin-records.js');
        $view = $this->source('resources/views/admin/communications/balita.blade.php');

        $this->assertStringNotContainsString("api.delete('/admin/announcements/", $js);
        $this->assertStringNotContainsString('deletePost(', $js.$view);
        $this->assertStringNotContainsString('delete_on_facebook', $js);
        $this->assertStringContainsString('@click="archivePost(a)"', $view);
        $this->assertStringContainsString('<x-ui.modal title="Archive Post"', $view);
        $this->assertStringContainsString('x-show="archPost?.facebook?.status === \'posted\'"', $view);
        $this->assertStringContainsString('Also remove it from the Facebook Page', $view);
        $this->assertStringContainsString("{ to: 'Archived', remove_from_facebook: fromFb || undefined }", $js);
        $this->assertStringContainsString("'Archived, and removed from the Facebook Page.'", $js);
    }

    public function test_events_and_banners_have_no_delete(): void
    {
        $records = $this->source('resources/js/scope-admin-records.js');
        $occasions = $this->source('resources/js/admin-occasions.js');

        $this->assertStringNotContainsString("api.delete('/admin/events/", $records);
        $this->assertStringNotContainsString('deleteEvent(', $records.$this->source('resources/views/admin/communications.blade.php'));
        $this->assertStringNotContainsString("api.delete('/admin/banners/", $occasions);
        $this->assertStringNotContainsString('title="Delete banner"', $this->source('resources/views/admin/communications/banners.blade.php'));
        $this->assertStringContainsString("@click=\"transition(b, 'Archived')\"", $this->source('resources/views/admin/communications/banners.blade.php'));
    }

    public function test_directory_entries_and_hotlines_are_archived(): void
    {
        $directory = $this->source('resources/views/admin/settings/directory.blade.php');
        $settings = $this->source('resources/js/scope-admin-settings.js');
        $alerts = $this->source('resources/views/admin/partials/sakuna/tab-alerts.blade.php');

        $this->assertStringContainsString('<x-ui.modal title="Archive Office"', $directory);
        $this->assertStringContainsString('@click="confirmArchive()">Archive</x-ui.button>', $directory);
        $this->assertStringNotContainsString('Remove Office', $directory);
        $this->assertStringContainsString("toast(o.office + ' archived. Residents no longer see it in Direktoryo.');", $settings);
        $this->assertStringNotContainsString('was removed from the directory', $settings);

        $this->assertStringContainsString('@click="archiveHotline(h)" class="sk-btn sk-btn-p">Archive</button>', $alerts);
        $this->assertStringContainsString("this.toast('Hotline archived.', 'success');", $alerts);
        $this->assertStringNotContainsString('Hotline removed.', $alerts);
    }

    public function test_custom_roles_are_archived(): void
    {
        $users = $this->source('resources/views/admin/users.blade.php');

        $this->assertStringContainsString('@click="archiveRole(role)"', $users);
        $this->assertStringContainsString("x-text=\"confirmArchive === role.key ? 'Confirm archive' : 'Archive'\"", $users);
        $this->assertStringNotContainsString("'Confirm delete'", $users);
        $this->assertStringNotContainsString('deleteRole(', $users);
        $this->assertStringContainsString("message: 'Role ' + role.name + ' archived.'", $users);
    }

    public function test_appointment_schedules_are_archived_and_closed_days_opened_again(): void
    {
        $view = $this->source('resources/views/components/admin/scope/appointments.blade.php');
        $js = $this->source('resources/js/scope-admin-requests.js');

        $this->assertStringContainsString('<x-ui.modal title="Archive Schedule"', $view);
        $this->assertStringContainsString('x-show="canS(\'delete\') && s.active" @click="askArchive(s)">Archive</x-ui.button>', $view);
        $this->assertStringNotContainsString('Delete Schedule', $view);
        $this->assertStringContainsString("toast('Archived. Residents can no longer book it.');", $js);
        $this->assertStringNotContainsString("toast('Schedule deleted.')", $js);
        $this->assertStringContainsString("toast(day(c.date) + ' is open for booking again.');", $js);
    }

    public function test_session_cases_import_undo_and_step_undo_say_archived(): void
    {
        $desk = $this->source('resources/views/lazy/tulong-session-desk.blade.php');
        $this->assertStringContainsString('Yes, take off</button>', $desk);
        $this->assertStringContainsString("' was taken off the session. The case waits for a session again.'", $this->source('resources/js/welfare-admin.js'));

        $import = $this->source('resources/views/admin/partials/modal-request-import-window.blade.php');
        $this->assertStringContainsString("' records archived'", $import);
        $this->assertStringContainsString('Undo archives every record this import added', $import);
        $this->assertStringNotContainsString('Undo removes every record', $import);
        $this->assertStringContainsString("' records archived.'", $this->source('resources/js/request-import-ui.js'));

        $this->assertStringContainsString('title="Marks the step not done; the mark is archived and stays in the history"', $this->source('resources/views/components/admin/scope/request-workflow.blade.php'));
    }
}
