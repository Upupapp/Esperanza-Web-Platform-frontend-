<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * One dashboard per role (owner, 18 Nov): "show them a similar dashboard as
 * the super admin with kpi cards and graphs and charts but of course aligned
 * to the data that they handle". The server lists each account's views and
 * counts everything in scope (GET /admin/dashboard/subjects, /summary,
 * /inbox); the web draws them. Each pin below is a part of that design.
 */
class RoleDashboardsUiTest extends TestCase
{
    private const AREAS = ['balita', 'sakuna', 'records', 'desk'];

    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    private function dashboardFiles(): array
    {
        $files = ['resources/views/admin/dashboard.blade.php'];
        foreach (glob(base_path('resources/views/components/admin/dashboards/*.blade.php')) as $file) {
            $files[] = 'resources/views/components/admin/dashboards/'.basename($file);
        }

        return $files;
    }

    public function test_the_server_says_which_views_an_account_has_and_its_home(): void
    {
        $js = $this->source('resources/js/dashboards.js');
        $page = $this->source('resources/views/admin/dashboard.blade.php');

        $this->assertStringContainsString('const listed = (this.subjects.views || []).filter((v) => VIEW_TABS[v]);', $js);
        $this->assertStringContainsString('this.view = this.views.includes(asked) ? asked : this.views[0];', $js);
        // Tabs only with more than one view: a department or barangay account has none.
        $this->assertStringContainsString('get showTabs() { return this.subjectsReady && this.views.length > 1; }', $js);
        $this->assertStringContainsString('<div x-show="showTabs" x-cloak class="flex flex-wrap items-center gap-3">', $page);
        $this->assertStringContainsString('<template x-for="v in views" :key="v">', $page);
        foreach (["desk: { label: 'Front desk'", "records: { label: 'Records'", "balita: { label: 'Balita'", "sakuna: { label: 'Sakuna'", "return 'My office';", "return 'My barangay';"] as $word) {
            $this->assertStringContainsString($word, $js);
        }
        $this->assertStringNotContainsString("'My Office'", $js);
        $this->assertStringNotContainsString('officeTabLabel', $page);
    }

    public function test_each_area_has_its_own_component_on_the_page(): void
    {
        $page = $this->source('resources/views/admin/dashboard.blade.php');
        $js = $this->source('resources/js/dashboards.js');

        foreach (self::AREAS as $area) {
            $this->assertStringContainsString("<template x-if=\"subjectsReady && view === '{$area}'\">\n            <x-admin.dashboards.{$area} />", $page, $area);
            $view = $this->source("resources/views/components/admin/dashboards/{$area}.blade.php");
            $this->assertStringContainsString("<div x-data=\"areaDashboard('{$area}')\" class=\"flex flex-col gap-[22px]\">", $view, $area);
            $this->assertStringContainsString('<x-admin.dashboards.toolbar eyebrow=', $view, $area);
            $this->assertStringContainsString('<x-admin.dashboards.action-list />', $view, $area);
            $this->assertStringContainsString('<x-admin.dashboards.activity', $view, $area);
            $this->assertStringContainsString("{$area}: {", $js, $area.' getters');
        }
        $this->assertStringContainsString("Alpine.data('areaDashboard', areaDashboard);", $this->source('resources/js/app.js'));

        // One scoped answer per area, reloaded on its own live topics.
        $this->assertStringContainsString("const res = await api.get('/admin/dashboard/summary', { params });", $js);
        $this->assertStringContainsString("const AREA_TOPICS = { balita: 'balita', sakuna: 'sakuna', records: 'residents', desk: ['requests', 'appointments', 'residents'] };", $js);
        $this->assertStringContainsString('this.$store.live.on(this.$el, AREA_TOPICS[area], () => this.load(true));', $js);
        // Getters survive the mixing (a spread would read each one once).
        $this->assertStringContainsString('Object.defineProperties(target, Object.getOwnPropertyDescriptors(s))', $js);
        $this->assertStringContainsString('return mix(board, AREAS[area] || {});', $js);
    }

    public function test_needs_your_action_comes_from_the_inbox_on_every_home(): void
    {
        $js = $this->source('resources/js/dashboards.js');
        $list = $this->source('resources/views/components/admin/dashboards/action-list.blade.php');

        $this->assertStringContainsString("const res = await api.get('/admin/dashboard/inbox');", $js);
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'any', () => this.loadInbox(true));", $js);
        $this->assertStringContainsString('<template x-for="(item, i) in shownInbox" :key="item.key">', $list);
        // A tab that is not the account's home lists that area's items only.
        $this->assertStringContainsString('return this.isHome || !keys ? this.inbox : this.inbox.filter((i) => keys.includes(i.key));', $js);
        $this->assertStringContainsString('<a :href="item.href"', $list);
        $this->assertStringContainsString('Nothing waiting on you.', $list);

        // The office's and the barangay's own account get it on their home; the Overview too.
        $this->assertStringContainsString('<x-admin.dashboards.action-list x-show="isHome" />', $this->source('resources/views/components/admin/dashboards/department.blade.php'));
        $this->assertStringContainsString('<x-admin.dashboards.action-list x-show="isHome" />', $this->source('resources/views/components/admin/dashboards/barangay.blade.php'));
        $this->assertStringContainsString("<x-admin.dashboards.action-list ::class=\"hasCounts ? 'xl:col-span-2' : 'xl:col-span-5'\" />", $this->source('resources/views/admin/dashboard.blade.php'));
    }

    /** 7.9: /admin/coverage 403'd without const_barangay.view and the Promise.all lost the whole dashboard. */
    public function test_the_overview_no_longer_asks_for_coverage(): void
    {
        $page = $this->source('resources/views/admin/dashboard.blade.php');
        $this->assertStringNotContainsString('/admin/coverage', $page);
        $this->assertStringNotContainsString('Promise.all', $page);
        $this->assertStringContainsString("const dash = await \$api.get('/admin/dashboard');", $page);
        $this->assertStringContainsString('get registeredResidents() { return this.dash?.residents?.total ?? null; },', $page);
    }

    /** 7.2: the feed showed other officers' sensitive-record views; for most roles it is their own now. */
    public function test_recent_activity_is_your_own_without_audit_logs(): void
    {
        $page = $this->source('resources/views/admin/dashboard.blade.php');
        $card = $this->source('resources/views/components/admin/dashboards/activity.blade.php');

        $this->assertStringContainsString("own: 'Your recent activity'", $page);
        $this->assertStringContainsString("return scope ? scope !== 'own' : this.\$store.session.canPerm('um_audit', 'view');", $page);
        $this->assertStringContainsString('<h3 class="text-lg font-extrabold text-ink" x-text="activityTitle">Recent activity</h3>', $card);
        $this->assertStringContainsString('<a x-show="mayAudit" x-cloak href="{{ route(\'admin.audit-logs\') }}"', $card);
        // Shown on every view, so the Overview's figures reload on any of them.
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'any', () => this.load(true));\n                await this.load();", $page);
    }

    /** 7.8: MIO Staff and the MDRRMO were sent to a Reports page they cannot open. */
    public function test_export_report_only_for_reports_and_sakuna_reports_for_the_mdrrmo(): void
    {
        $page = $this->source('resources/views/admin/dashboard.blade.php');
        $bar = $this->source('resources/views/components/admin/dashboards/toolbar.blade.php');

        $this->assertStringContainsString('<x-ui.button :href="route(\'admin.reports\')" icon="download" x-show="$store.session.can(\'reports\')" class="ml-auto">Export Report</x-ui.button>', $page);
        $this->assertStringNotContainsString('>Export report<', $page);
        $this->assertSame(2, substr_count($bar, 'x-show="$store.session.can(\'reports\')" x-cloak>Export Report</x-ui.button>'));
        $this->assertStringContainsString('x-show="!$store.session.can(\'reports\') && $store.session.canPerm(\'sakuna_reports\', \'view\')" x-cloak>Open Sakuna Reports</x-ui.button>', $bar);
        $this->assertStringContainsString('<x-admin.dashboards.toolbar eyebrow="Sakuna dashboard" :sakuna-reports="true">', $this->source('resources/views/components/admin/dashboards/sakuna.blade.php'));
    }

    /** Owner, 18 Nov: titles and buttons in Title Case; tiles, labels and tabs in sentence case. */
    public function test_tile_labels_are_sentence_case(): void
    {
        $proper = ['Dokyu', 'Tulong', 'Balita', 'Sakuna', 'Papeles', 'ID'];
        $found = 0;
        foreach ($this->dashboardFiles() as $path) {
            preg_match_all('/\slabel="([^"]+)"/', $this->source($path), $m);
            foreach ($m[1] as $label) {
                $found++;
                foreach (array_slice(preg_split('/\s+/', $label), 1) as $word) {
                    $this->assertTrue(! preg_match('/^[A-Z]/', $word) || in_array($word, $proper, true), "{$path}: \"{$label}\"");
                }
            }
        }
        $this->assertGreaterThan(50, $found);
        foreach (['Needs Action', 'Registered Residents', 'Clients Served', 'Amount Released', 'Processing Time', 'Certificates Released', 'Assistance Received', 'Dokyu Open', 'Tulong Open'] as $old) {
            foreach ($this->dashboardFiles() as $path) {
                $this->assertStringNotContainsString('label="'.$old.'"', $this->source($path), $path);
            }
        }
        foreach (['Download report', 'View details', 'View all alerts', '>View all<'] as $button) {
            foreach ($this->dashboardFiles() as $path) {
                $this->assertStringNotContainsString($button, $this->source($path), $path);
            }
        }
    }

    /** 7.7: a Dokyu-only role read "Dokyu released + Tulong completed". */
    public function test_overview_sublabels_name_only_the_modules_held(): void
    {
        $page = $this->source('resources/views/admin/dashboard.blade.php');
        $this->assertStringNotContainsString('sublabel="Dokyu released + Tulong completed"', $page);
        $this->assertStringNotContainsString('sublabel="Dokyu + Tulong"', $page);
        $this->assertStringContainsString('label="Released this month" valueBind="dash?.requests?.released_this_month ?? 0" icon="package-check" color="slate" sublabelBind="releasedText"', $page);
        $this->assertStringContainsString('label="Filed today" valueBind="dash?.requests?.submitted_today ?? 0" icon="calendar-clock" color="gold" sublabelBind="modulesText"', $page);
        // 7.4: no requests card for an account without Dokyu or Tulong.
        $this->assertStringContainsString("get hasCounts() { return this.\$store.session.can('dokyu') || this.\$store.session.can('tulong'); },", $page);
        $this->assertStringContainsString('<div x-show="hasCounts" class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">', $page);
    }

    public function test_the_office_dashboard_tells_a_head_from_staff(): void
    {
        $js = $this->source('resources/js/dashboards.js');
        $view = $this->source('resources/views/components/admin/dashboards/department.blade.php');

        $this->assertStringContainsString('get isHead() { return !!this.k.ready_to_approve; },', $js);
        $this->assertStringContainsString('<div x-show="isHead"><x-ui.stat-card layout="compact" class="h-full" :href="route(\'admin.document-requests\')" x-bind:href="approvalUrl" label="Ready for your approval"', $view);
        $this->assertStringContainsString('<div x-show="!isHead && k.assigned_to_me !== undefined"><x-ui.stat-card', $view);
        $this->assertStringContainsString('label="Assigned to you"', $view);
        // The head: who carries the open cases; everyone: how requests came in, and their own open cases.
        $this->assertStringContainsString('<x-ui.card x-show="data.by_assignee">', $view);
        $this->assertStringContainsString('<x-ui.clay-bars rows="team" empty="No open cases." />', $view);
        $this->assertStringContainsString('<x-ui.clay-donut rows="channels" total-label="Requests"', $view);
        $this->assertStringContainsString('const src = this.pipeMine && this.hasMine ? this.data.pipeline_mine : (this.data?.pipeline || []);', $js);
        // 7.12: no sublabel that repeats its tile's value.
        $this->assertStringNotContainsString("peso(k.amount_released) + ' to clients'", $view);
        $this->assertStringContainsString('sublabel="Average, filing to release"', $view);
    }

    public function test_the_barangay_account_has_its_own_tiles_and_series_only_for_its_modules(): void
    {
        $js = $this->source('resources/js/dashboards.js');
        $view = $this->source('resources/views/components/admin/dashboards/barangay.blade.php');

        $this->assertStringContainsString('get own() { return !this.choosesBarangay && !!this.barangay && this.$store.session.account?.scope === this.barangay; },', $js);
        foreach (['Certificates to act on', 'Appointments today', 'Accounts to verify', 'Residents helped'] as $label) {
            $this->assertStringContainsString('label="'.$label.'"', $view);
        }
        $this->assertStringContainsString('<x-ui.clay-bars rows="stages" empty="No open certificate requests." />', $view);
        // 7.3: each trend series only with its module.
        $this->assertStringContainsString("t.documents ? { label: 'Certificates released'", $js);
        $this->assertStringContainsString("t.assistance ? { label: 'Assistance completed'", $js);
        $this->assertStringContainsString('<span x-show="data.trend && data.trend.documents"', $view);
    }

    public function test_one_bar_list_component_replaces_the_inline_copies(): void
    {
        foreach (['resources/views/admin/dashboard.blade.php', 'resources/views/components/admin/dashboards/department.blade.php', 'resources/views/components/admin/dashboards/barangay.blade.php'] as $path) {
            $view = $this->source($path);
            $this->assertStringNotContainsString('rounded-full bg-track shadow-pressed-sm p-[2px]', $view, $path);
            $this->assertStringContainsString('<x-ui.clay-bars rows="', $view, $path);
        }
        $bars = $this->source('resources/views/components/ui/clay-bars.blade.php');
        $this->assertStringContainsString("@props(['rows', 'empty' => 'Nothing to show yet.', 'valueSuffix' => '', 'tone' => 'brand', 'columns' => false])", $bars);
        $this->assertStringContainsString('<template x-for="row in ({{ $rows }})" :key="row.key ?? row.label">', $bars);
    }

    /** 7.10: the office-bound accounts read "Municipality-wide". */
    public function test_the_scope_pill_names_the_office_of_an_office_bound_account(): void
    {
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString("if (this.deptRestricted() && a.dept) return officeShort(a.dept) + ' · all barangays';", $app);
        foreach (['resources/views/components/admin/sidebar.blade.php', 'resources/views/components/admin/topbar.blade.php'] as $path) {
            $view = $this->source($path);
            $this->assertStringContainsString('<span x-text="$store.session.scopePill()"></span>', $view, $path);
            $this->assertStringNotContainsString("? 'Municipality-wide' : 'Brgy. '", $view, $path);
        }
    }

    /** The inbox's links name a status, mine, a tab or a rung; the lists they open read them. */
    public function test_the_lists_read_the_inbox_links(): void
    {
        $dokyu = $this->source('resources/views/admin/document-requests.blade.php');
        $this->assertStringContainsString("this.statusList = (params.get('status') || '').split(',').map((x) => x.trim()).filter(Boolean);", $dokyu);
        $this->assertStringContainsString('if (this.statusList.length) return this.statusList.includes(r.status);', $dokyu);

        $tulong = $this->source('resources/views/admin/assistance-requests.blade.php');
        $this->assertStringContainsString('if (asked.length === 1) this.statusFilter = asked[0]; else this.statusList = asked;', $tulong);

        $records = $this->source('resources/js/scope-admin-records.js');
        $this->assertStringContainsString("this.mineOnly = params.get('mine') === '1';", $records);
        $this->assertStringContainsString('&& (!this.mineOnly || !!a.mine)', $records);
        $this->assertStringContainsString("if (params.get('tab') === 'events' && this.statuses.includes(params.get('status'))) this.status = params.get('status');", $records);
        $this->assertStringContainsString('<button type="button" x-show="mineOnly" x-cloak @click="mineOnly = false"', $this->source('resources/views/admin/communications/balita.blade.php'));

        $people = $this->source('resources/views/admin/constituents.blade.php');
        $this->assertStringContainsString("if (['registry', 'data-quality', 'citizen-verification'].includes(params.get('tab'))) this.tab = params.get('tab');", $people);
        $this->assertStringContainsString("this.verificationFilter = params.get('verification');", $people);
        $this->assertStringContainsString('if (this.dqMineOnly && i.assigned_to !== this.$store.session.account?.name) return false;', $people);

        $sakuna = $this->source('resources/views/admin/sakuna.blade.php');
        $this->assertStringContainsString("const who = params.get('filter_assigned') || params.get('assigned');", $sakuna);
        $this->assertStringContainsString('<option value="me">Assigned to you</option>', $this->source('resources/views/admin/partials/sakuna/tab-incidents.blade.php'));
        $this->assertStringContainsString("if (['For validation', 'Validated'].includes(asked.get('validation'))) this.f.validation = asked.get('validation');", $this->source('resources/js/sakuna-casualties.js'));
    }

    /** Review of the 18 Nov screenshots: ragged rows, dots starting a line, "0%" for a real count, tables cut on a phone. */
    public function test_rows_share_a_height_and_small_things_read_right(): void
    {
        foreach ($this->dashboardFiles() as $path) {
            foreach (explode("\n", $this->source($path)) as $line) {
                if (str_contains($line, 'grid') && str_contains($line, 'gap-5')) {
                    $this->assertStringNotContainsString(' items-start', $line, $path.': cards of one row share its height');
                }
            }
        }
        $this->assertStringContainsString("x-text=\"String(({{ \$sublabelBind }}) ?? '').replace(/ · /g, '\\u00a0· ')\"", $this->source('resources/views/components/ui/stat-card.blade.php'));
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString("if (p === 0) return '<1';", $app);
        $this->assertStringContainsString("return p === 100 && row.value < this.total ? '>99' : p;", $app);
        // A phone keeps the office's tables to the columns that fit.
        $this->assertStringContainsString('<th class="py-2 pl-2 text-right hidden sm:table-cell" x-show="hasTulong">Released</th>', $this->source('resources/views/components/admin/dashboards/department.blade.php'));

        $office = $this->source('resources/views/components/admin/dashboards/department.blade.php');
        $this->assertStringContainsString('<th class="py-2 px-2 text-right hidden sm:table-cell">Rejected</th>', $office);
        $this->assertStringContainsString('<th class="py-2 px-2 hidden sm:table-cell">Status</th>', $office);
        // Report cards grow to fill each row, so a last row of one leaves no band.
        $this->assertStringContainsString('<div class="flex flex-wrap gap-3">', $office);
        $this->assertStringContainsString('class="group grow basis-[300px] flex items-start', $office);
    }

    /** No double quote inside an x-data on the new parts: one ends the attribute and kills Alpine on the page. */
    public function test_no_double_quote_inside_the_dashboard_x_data(): void
    {
        $page = $this->source('resources/views/admin/dashboard.blade.php');
        $start = strpos($page, "<div\n        x-data=\"{");
        $this->assertNotFalse($start);
        $end = strpos($page, "}\"\n        class=\"flex flex-col gap-[22px]\"", $start);
        $this->assertNotFalse($end, 'the x-data ends where it should, not at an earlier double quote');
    }
}
