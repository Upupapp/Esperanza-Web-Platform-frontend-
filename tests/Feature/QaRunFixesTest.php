<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * What the live QA run of 2026-10-04 found on the deployed site (resident
 * portal and staff portal, against staging), pinned so it cannot come back.
 * Each test is named for what a resident or an officer saw.
 */
class QaRunFixesTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** Sign-up stopped at step 1: the browser validated the hidden steps 2 and 3 too, and said nothing. */
    public function test_each_sign_up_step_checks_only_its_own_fields(): void
    {
        $view = $this->source('resources/views/auth/citizen-register.blade.php');

        $this->assertStringContainsString('novalidate', $view, 'the browser must not validate all three steps at once');
        foreach (['step1', 'step2', 'step3'] as $ref) {
            $this->assertStringContainsString('x-ref="'.$ref.'"', $view);
        }
        $this->assertStringContainsString('if (!this.validStep()) return;', $view);
    }

    /** No officer could verify a resident: GET /auth/me sends a list of grants and canPerm() read a map. */
    public function test_the_permission_list_from_the_server_is_read_as_a_map(): void
    {
        $app = $this->source('resources/js/app.js');

        $this->assertStringContainsString('function permissionMap(raw)', $app);
        $this->assertStringContainsString('permissions: permissionMap(raw.permissions)', $app);
        $this->assertStringContainsString("account: withPermissionMap(readJson('esperanza_session'))", $app, 'an account cached before the fix is read too');
    }

    /** The Sakuna bar showed a Red Alert and a July 2026 monsoon; the Dokyu queue a 2.4-day average. Neither was real. */
    public function test_no_staff_screen_shows_made_up_figures(): void
    {
        $sakuna = $this->source('resources/views/admin/sakuna.blade.php');
        $this->assertStringNotContainsString("opStatus: 'Red Alert'", $sakuna);
        $this->assertStringNotContainsString('Southwest Monsoon & Flooding — July 2026', $sakuna);
        $this->assertStringNotContainsString('Updated Jul 15, 2026', $sakuna);
        $this->assertStringContainsString("\$api.get('/admin/sakuna/command-center')", $sakuna, 'the bar reads the live board');
        $this->assertArrayNotHasKey('periods', require base_path('resources/data/admin/sakuna.php'));

        $queue = $this->source('resources/views/admin/document-requests.blade.php');
        $this->assertStringNotContainsString('value="2.4 days"', $queue);
        $this->assertStringContainsString('valueBind="avgProcessingLabel"', $queue);
    }

    /** "Assign to me" only changed the screen; a reload showed the case unassigned. */
    public function test_assign_to_me_is_saved_on_the_server(): void
    {
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/admin/{$page}.blade.php");
            $this->assertStringNotContainsString('Assigned to yourself.', $view, $page);
            $this->assertStringContainsString("this.apiBase(this.selected.ref) + '/assign'", $view, $page);
            $this->assertStringContainsString('assigned: row.assigned || null', $view, $page);
        }
    }

    /** "Still being scanned" was shown for a scan that had failed and would never finish. */
    public function test_a_file_that_cannot_be_opened_says_why(): void
    {
        $review = $this->source('resources/views/admin/constituents/modal-citizen-account.blade.php');
        $this->assertStringNotContainsString('Still being scanned.', $review);
        $this->assertStringContainsString('window.fileNotice(part[1])', $review);

        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/admin/{$page}.blade.php");
            $this->assertStringContainsString('file_notice: window.fileNotice(q.file)', $view, $page);
            $this->assertStringContainsString('Open the file in a new tab', $view, $page.': the viewer links the file');
        }
    }
}
