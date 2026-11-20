<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Staff access (owner decision, 2026-11-20): the Department Head switches
 * which steps the office's Department Staff take on its Dokyu and Tulong
 * requests. A view of each tab, never a sidebar entry; approval and reject
 * are never a switch; the request screens show Staff the steps they hold.
 */
class StaffAccessViewTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** The source with its Blade comments taken out: what can reach the page. */
    private function shown(string $path): string
    {
        return preg_replace('/\{\{--.*?--\}\}/s', '', $this->source($path));
    }

    public function test_each_tab_has_a_staff_access_view_that_is_exported(): void
    {
        require_once base_path('build/export_manifest.php');
        $exported = esperanza_export_routes(app('router')->getRoutes());

        foreach (['/admin/document-requests/staff-access' => 'Dokyu', '/admin/assistance-requests/staff-access' => 'Tulong'] as $uri => $tab) {
            $this->assertArrayHasKey($uri, $exported, $uri.' is written to dist/');
            $this->get($uri)->assertOk()
                ->assertSee('x-data="staffAccess()"', false)
                ->assertSee('What your Staff may do')
                ->assertSee('role="switch"', false)
                ->assertSee('aria-current="page"', false)
                ->assertSee($tab.' views', false)
                ->assertDontSee('<script>', false);
        }
    }

    public function test_it_is_not_a_sidebar_entry_and_shows_only_to_heads_and_the_super_admin(): void
    {
        $this->assertStringNotContainsString('staff-access', $this->source('resources/views/components/layouts/admin.blade.php'));

        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString("canStaffAccess() {\n            return !!this.account && ['department_head', 'super_admin'].includes(this.account.roleKey);", $app);
        $this->assertStringContainsString("import('./staff-access')", $app);

        foreach (['resources/views/admin/assistance-requests.blade.php' => 'admin.assistance-requests.staff-access',
            'resources/views/admin/document-requests.blade.php' => 'admin.document-requests.staff-access'] as $page => $route) {
            $shown = $this->shown($page);
            $this->assertStringContainsString("route('{$route}')", $shown, $page);
            $this->assertStringContainsString('x-show="$store.session.canStaffAccess()"', $shown, $page);
        }
        $this->assertStringContainsString("'staff-access'", $this->source('resources/views/admin/request-types.blade.php'));
    }

    public function test_approval_and_reject_are_never_a_switch(): void
    {
        $js = $this->source('resources/js/staff-access.js');
        $view = $this->shown('resources/views/admin/staff-access.blade.php');

        // The steps come from the server (screen, assess, release, import);
        // nothing on the page names approve or reject as one.
        $this->assertStringNotContainsString("step: 'approve'", $js);
        $this->assertStringNotContainsString("step: 'reject'", $js);
        $this->assertStringContainsString("api.put('/admin/staff-access'", $js);
        $this->assertStringContainsString('head_only', $view, 'the page says what stays with the Head');
        $this->assertStringContainsString('!s.available', $view, 'a step the Head\'s role does not hold cannot be switched on');
    }

    public function test_the_request_screens_show_staff_the_steps_they_hold(): void
    {
        foreach (['resources/views/admin/assistance-requests.blade.php', 'resources/views/admin/document-requests.blade.php'] as $page) {
            $shown = $this->shown($page);
            // Accepting or flagging a paper is the screening step.
            $this->assertStringContainsString("!req._decidingOutcome && may('screen')", $shown, $page);
            $this->assertStringNotContainsString("!req._decidingOutcome && may('review')", $shown, $page);
            // An older server without the steps answers by review.
            $this->assertStringContainsString("(action === 'screen' || action === 'assess')) return !!p.review;", $shown, $page);
        }
        $tulong = $this->shown('resources/views/admin/assistance-requests.blade.php');
        $this->assertStringContainsString("'Assessment'].includes(st)) return 'assess';", $tulong);
        $this->assertStringContainsString("'screen' in p ? p.screen : p.review", $this->source('resources/js/scope-admin-requests.js'));
    }
}
