<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * What the full QA run of 2026-10-05 found: every staff role signed in on the
 * staff site against staging, plus the resident site. Pinned so it cannot come
 * back. Each test is named for what an officer saw.
 */
class FullQaRunFixesTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** Pages ask whether the role holds a list before asking for it; the permissions arrive after sign-in. */
    public function test_a_page_can_wait_for_the_permissions_before_deciding(): void
    {
        $app = $this->source('resources/js/app.js');

        $this->assertStringContainsString('get permsReady()', $app);
        $this->assertStringContainsString('whenReady() {', $app);
        $this->assertStringContainsString("this.pending = api.get('/auth/me')", $app, 'whenReady waits on the /auth/me every page load runs');
    }

    /** Five roles opened Sakuna tabs outside their part: "Something went wrong" and a "Walang pahintulot" pop-up. */
    public function test_a_role_sees_and_loads_only_the_sakuna_tabs_it_may_view(): void
    {
        $view = $this->source('resources/views/admin/sakuna.blade.php');

        foreach (['command-center', 'vulnerability', 'incidents', 'evacuation-centers', 'evacuees', 'resources', 'relief', 'damage-assessment', 'bdrrmc-forms', 'alerts', 'reports'] as $tab) {
            $this->assertMatchesRegularExpression("/'{$tab}': 'sakuna_[a-z]+'/", $view, "{$tab} names its submodule");
        }
        $this->assertStringContainsString('x-show="permsReady && tabAllowed(', $view, 'the strip shows only permitted tabs');
        $this->assertStringContainsString('<template x-if="activeAllowed">', $view, 'a tab loads only once permitted');
        $this->assertStringContainsString('this.landOnAllowedTab();', $view, 'a tab outside the role sends them to the first they hold');
        $this->assertStringContainsString("x-show=\"can('sakuna_command', 'escalate')\"", $view, 'Activate EOC only for who may escalate');
    }

    /** The Barangay Secretary opened Balita to a pop-up per tab: they hold only their directory entry. */
    public function test_balita_opens_on_a_tab_the_role_holds_and_loads_no_other(): void
    {
        $view = $this->source('resources/views/admin/communications.blade.php');

        $this->assertStringContainsString('x-init="landOnAllowedTab()"', $view);
        $this->assertSame(6, substr_count($view, 'x-init="$store.session.whenReady().then(() => tabAllowed('), 'each of the 6 editorial tabs loads only when permitted (Community Posts went on 2026-10-20)');
        $this->assertSame(7, substr_count($view, '<button x-show="tabAllowed('), 'each of the 7 tab buttons shows only when permitted');
        // The Feedback desk (scope round 2026-10-15) loads itself, and only
        // for an account holding comm_feedback view.
        $desk = $this->source('resources/js/scope-admin-records.js');
        $this->assertStringContainsString("get may() { return this.\$store.session.canPerm('comm_feedback', 'view'); }", $desk);
        $this->assertStringContainsString('if (!this.may) return;', $desk);
        $this->assertStringNotContainsString('x-init="load()"', $view);
    }

    /** Constituents and the Command Center asked every role for the personnel list. */
    public function test_no_page_asks_for_the_personnel_list_without_the_permission(): void
    {
        foreach (['resources/views/admin/constituents.blade.php', 'resources/views/admin/sakuna.blade.php'] as $path) {
            $view = $this->source($path);
            preg_match_all("/(.{0,60})\\\$api\\.getAll\\('\\/admin\\/users'\\)/", $view, $m);
            $this->assertNotEmpty($m[1], $path);
            foreach ($m[1] as $before) {
                $this->assertMatchesRegularExpression("/um_personnel'(, 'view'\\))?\\)? \\? $/", $before, "{$path}: the call sits behind the um_personnel check");
            }
        }

        $sakuna = $this->source('resources/views/admin/sakuna.blade.php');
        $this->assertStringContainsString('mayListCenters()', $sakuna, 'the centers list for check-in and transfer pickers is asked for only when allowed');
        $this->assertStringContainsString("this.allowed('sakuna_incidents') ? \$api.getAll('/admin/sakuna/incidents')", $sakuna);
    }

    /** Vulnerability's Export threw "filteredProfiles is not defined": the list lived below the button's scope. */
    public function test_the_vulnerability_export_sees_the_filtered_list(): void
    {
        $view = $this->source('resources/views/admin/partials/sakuna/tab-vulnerability.blade.php');

        $getter = strpos($view, 'get filteredProfiles()');
        $button = strpos($view, "exportCSV(filteredProfiles, 'vulnerability-profiles-export.csv')");
        $this->assertNotFalse($getter);
        $this->assertNotFalse($button);
        $this->assertLessThan($button, $getter, 'the list is declared on the tab, above the Export button');
        $this->assertSame(1, substr_count($view, 'get filteredProfiles()'));
    }

    /** Department Staff saw every Tulong decision and the secretary Reject; the server refused them all. */
    public function test_decision_buttons_follow_what_the_server_permits(): void
    {
        foreach (['resources/views/admin/document-requests.blade.php', 'resources/views/admin/assistance-requests.blade.php'] as $path) {
            $view = $this->source($path);
            $this->assertStringContainsString('r.permitted = d.permitted || null;', $view, $path);
            $this->assertStringContainsString("&& may('review')\">", $view, "{$path}: document decisions need review");
            $this->assertStringContainsString('may(primaryPermission)', $view, "{$path}: the primary button needs its stage's permission");
            // A required document nobody uploaded cannot be accepted (the server's rule too).
            $this->assertStringContainsString('x-show="mayAccept(req)" @click="verifyRequirement(req)"', $view, $path);
            $this->assertStringContainsString('/if applicable|if available|if any|required if|\\bonly\\s*$|interview|case study|case assessment|details of the record being requested|thumbmark|to be taken at|^\\s*none\\b/i', $view, $path);
        }

        $this->assertStringContainsString(
            "['Under Verification', 'Under Review'].includes(this.selected.status) && this.may('reject')",
            $this->source('resources/views/admin/document-requests.blade.php'),
        );
    }

    /** A correction was resubmitted with the required ID never uploaded; the button now says what is missing. */
    public function test_a_held_back_resubmit_names_the_missing_documents(): void
    {
        foreach (['resources/views/citizen/document-requests.blade.php', 'resources/views/citizen/assistance-requests.blade.php'] as $path) {
            $view = $this->source($path);
            $this->assertStringContainsString('r.missingRequired = d.missing_required || [];', $view, $path);
            $this->assertStringContainsString("'Attach these first, then resubmit: '", $view, $path);
        }
    }

    /** Internal forms could be filed but never approved or rejected on the staff site. */
    public function test_an_internal_form_can_be_approved_or_rejected(): void
    {
        $view = $this->source('resources/views/admin/internal-forms.blade.php');

        $this->assertStringContainsString("/decide', {", $view);
        $this->assertStringContainsString('x-show="s.can_decide"', $view, 'only on the officer whose step it is');
        $this->assertStringContainsString("decision?.kind === 'reject' && !decisionRemarks.trim()", $view, 'a rejection needs a reason');
    }

    /** The dashboard read "null — auth.login_failed". */
    public function test_recent_activity_reads_in_words(): void
    {
        $view = $this->source('resources/views/admin/dashboard.blade.php');

        // The actor and the action in words (the clay dashboard sets them apart).
        $this->assertStringContainsString("x-text=\"activity.actor || 'System'\"", $view);
        $this->assertStringContainsString('x-text="activity.summary || activity.action"', $view);
    }

    /** Resident profiling threw "reading 'ownerDocument'": a chart drew on a canvas that had gone. */
    public function test_a_chart_draws_only_on_a_canvas_still_on_the_page(): void
    {
        foreach (['resources/views/admin/dashboard.blade.php', 'resources/views/admin/reports.blade.php', 'resources/views/admin/constituents/tab-profiling.blade.php'] as $path) {
            $view = $this->source($path);
            $this->assertStringNotContainsString('new Chart($refs.canvas', $view, $path);
            $this->assertSame(substr_count($view, 'new Chart(canvas'), substr_count($view, 'canvas.isConnected'), "{$path}: every draw checks its canvas");
        }
    }

    /** Icon-only buttons (the bell, every dialog's close) had no name for a screen reader. */
    public function test_every_icon_only_button_has_a_name(): void
    {
        $unnamed = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('resources/views')));
        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            preg_match_all('/<button\b([^>]*)>\s*<i data-lucide="[^"]+"[^>]*>\s*<\/i>\s*<\/button>/', file_get_contents($file->getPathname()), $m);
            foreach ($m[1] as $attrs) {
                if (! preg_match('/aria-label|title=/', $attrs)) {
                    $unnamed[] = $file->getFilename();
                }
            }
        }

        $this->assertSame([], $unnamed);
        foreach (['admin', 'citizen'] as $portal) {
            $this->assertStringContainsString('aria-label="Notifications"', $this->source("resources/views/components/{$portal}/topbar.blade.php"));
        }
    }
}
