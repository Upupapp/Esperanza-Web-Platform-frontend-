<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * UI sweep 2026-11-18: Internal Forms told Front Desk, Records Officer and the
 * Information Officer "Personal view - you can only see forms you submitted"
 * and "My Submissions - 6 submitted by you" over a list of other staff's forms.
 * The server hides other offices only from the office-held roles (backend
 * Rbac/Scope.php), and none of them is limited to its own forms, so the page
 * follows the same rule ($store.session.deptRestricted) and says so. The office
 * tab strip scrolls inside its card at 390 px instead of running past it.
 */
class InternalFormsVisibilityTest extends TestCase
{
    private function page(): string
    {
        return file_get_contents(base_path('resources/views/admin/internal-forms.blade.php'));
    }

    public function test_the_banner_follows_the_servers_office_rule(): void
    {
        $page = $this->page();

        $this->assertStringContainsString('return !this.$store.session.deptRestricted?.();', $page, 'cross-office roles get the Full visibility banner');
        $this->assertStringNotContainsString("['super_admin', 'municipal_admin', 'department_head'].includes(role)", $page);
        $this->assertStringNotContainsString('Personal view', $page, 'no role sees only its own forms');
        $this->assertStringNotContainsString('submitted by you', $page);
        $this->assertStringNotContainsString("'My Submissions'", $page);
        // One heading for every role, in sentence case like the page's other card heading.
        $this->assertStringContainsString('<h3 class="text-sm font-semibold text-ink">Recent submissions</h3>', $page);
        $this->assertStringContainsString('x-show="!isAuthority && !isOfficeHead"', $page, 'office staff are told they see their office');
        $this->assertStringContainsString("Other offices' forms are hidden.", $page);
    }

    public function test_the_office_tabs_scroll_inside_their_card(): void
    {
        $this->assertStringContainsString('surface-pressed rounded-xl p-1 w-fit max-w-full overflow-x-auto', $this->page());
    }
}
