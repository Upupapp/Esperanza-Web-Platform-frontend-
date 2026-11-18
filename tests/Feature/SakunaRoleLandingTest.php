<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * UI sweep of staging (2026-11-18): Sakuna for the roles that may open it but
 * not the Command Center (Front Desk, Records Officer), the Incidents tiles,
 * and the Vulnerability figures' width.
 */
class SakunaRoleLandingTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** #12: the sidebar's Sakuna link opens the role's first tab without a "no access" toast. */
    public function test_sakuna_front_page_moves_a_role_without_the_command_center_quietly(): void
    {
        $page = $this->source('resources/views/admin/sakuna.blade.php');

        $this->assertStringContainsString("if (this.activeTab !== 'command-center') params.set('no_access', this.activeTab);", $page);
        $this->assertStringContainsString("window.location.replace(this.tabUrls[first] + (params.toString() ? '?' + params : ''));", $page);
    }

    /** #13, #28: no Status unknown / Live board not available chips for a role without the board. */
    public function test_the_status_bar_shows_the_live_board_only_to_a_role_that_has_it(): void
    {
        $page = $this->source('resources/views/admin/sakuna.blade.php');

        $this->assertStringContainsString("get boardAllowed() { return this.\$store.session.permsReady && this.\$store.session.canPerm('sakuna_command', 'view'); },", $page);
        $this->assertStringContainsString('x-show="boardAllowed && !headerBoard?.alerts?.length"', $page);
        $this->assertStringContainsString('x-show="boardAllowed" @click="loadHeader()"', $page);
        // Not having the board is not a failure (the alert form read it as offline).
        $this->assertStringContainsString('if (!this.boardAllowed) return;', $page);
        $this->assertStringNotContainsString("canPerm('sakuna_command', 'view')) { this.headerFailed = true; return; }", $page);
    }

    /** #24: Not valid (Rejected) incidents are closed, as the server counts them. */
    public function test_not_valid_incidents_are_not_counted_as_active(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-incidents.blade.php');
        $page = $this->source('resources/views/admin/sakuna.blade.php');

        $this->assertStringContainsString("incidentsList.filter(i => !['Completed','Archived','Rejected'].includes(i.status)).length", $tab);
        $this->assertStringNotContainsString("!['Completed','Archived'].includes(i.status)", $tab);
        // The resources' deploy list offers open incidents only, as the Command Center already does.
        $this->assertStringContainsString("this.resourceIncidentsOpen = (incidents.data || []).filter(i => !['Completed', 'Archived', 'Rejected'].includes(i.status));", $page);
    }

    /** #27: the Vulnerability figures shrink before the buttons wrap, so no band is left beside them. */
    public function test_the_vulnerability_figures_share_the_row_with_their_buttons(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-vulnerability.blade.php');

        $this->assertStringContainsString('<div class="sk-stats min-[901px]:!flex-1 min-[901px]:!min-w-min min-[901px]:!max-w-max">', $tab);
    }
}
