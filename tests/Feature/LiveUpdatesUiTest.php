<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Live updates (owner, 2026-11-15: "make sure the systems are auto refreshing
 * every second ... like the data changes real time"). The API keeps one public
 * file of change counters, <API origin>/live/changes.json; the staff and
 * resident sites read it every second while the tab is in view and reload a
 * page's own data, quietly, when its topic's counter moves.
 */
class LiveUpdatesUiTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** The live store's own code, from its definition to the queue counts after it. */
    private function store(): string
    {
        $app = $this->source('resources/js/app.js');
        $start = strpos($app, "Alpine.store('live', {");
        $this->assertNotFalse($start, 'The live store is gone from app.js.');

        return substr($app, $start, strpos($app, "Alpine.store('queueCounts'", $start) - $start);
    }

    public function test_the_live_store_reads_the_public_file_every_second_while_the_tab_is_in_view(): void
    {
        $app = $this->source('resources/js/app.js');
        $store = $this->store();

        // The API's origin, not its /api/v1 path.
        $this->assertStringContainsString("return new URL(api.baseUrl, window.location.href).origin + '/live/changes.json';", $app);
        $this->assertStringContainsString('const LIVE_EVERY_MS = 1000;', $app);
        $this->assertStringContainsString('setInterval(() => this.read(), LIVE_EVERY_MS);', $store);
        // In view only, at once when the tab comes back, one read at a time.
        $this->assertStringContainsString("if (document.visibilityState !== 'visible' || liveReading) return;", $store);
        $this->assertStringContainsString("document.addEventListener('visibilitychange', () => this.read());", $store);
        // A plain GET: no headers of our own, so no CORS preflight, and never a cached copy.
        $this->assertStringContainsString("await fetch(LIVE_URL, { cache: 'no-store', signal: abort.signal });", $store);
        $this->assertStringNotContainsString('headers', $store);
        $this->assertStringContainsString('if (!res.ok) return;', $store);
    }

    public function test_the_first_read_is_remembered_and_each_moved_topic_fires_an_event(): void
    {
        $store = $this->store();

        $this->assertStringContainsString('if (!before) return;', $store);
        $this->assertStringContainsString('const topics = Object.keys(t).filter((k) => t[k] !== before[k]);', $store);
        $this->assertStringContainsString("window.dispatchEvent(new CustomEvent('live:' + topic, { detail: { topics } }))", $store);
        $this->assertStringContainsString("window.dispatchEvent(new CustomEvent('live:any', { detail: { topics } }));", $store);
    }

    public function test_a_page_reload_waits_for_the_person_and_runs_once_at_a_time(): void
    {
        $store = $this->store();

        $this->assertStringContainsString('on(el, topics, run, { busy = null, dialogs = true, typing = true, wait = 700 } = {}) {', $store);
        // A field being typed in, a dialog open, the page's own busy(), a tab not shown.
        $this->assertStringContainsString("const held = () => (typing && liveTyping()) || (dialogs && liveDialogOpen()) || !!(busy && busy()) || !!el?.closest?.('[style*=\"display: none\"]');", $store);
        $this->assertStringContainsString('if (held()) { timer = setTimeout(fire, wait); return; }', $store);
        $this->assertStringContainsString('if (running) { again = true; return; }', $store);
        $this->assertStringContainsString('if (el) Alpine.onElRemoved(el, off);', $store);
        $this->assertStringContainsString("document.querySelectorAll('[role=\"dialog\"], [role=\"alertdialog\"], [aria-modal=\"true\"]')", $this->source('resources/js/app.js'));
    }

    public function test_both_topbars_start_it_beside_the_bells_and_the_bells_and_badges_follow_it(): void
    {
        $app = $this->source('resources/js/app.js');

        $this->assertStringContainsString('$store.adminNotifications.load(); $store.adminNotifications.poll(); $store.live.start()"', $this->source('resources/views/components/admin/topbar.blade.php'));
        $this->assertStringContainsString('if (!guest) { $store.citizenNotifications.load(); $store.citizenNotifications.poll(); $store.live.start() }"', $this->source('resources/views/components/citizen/topbar.blade.php'));

        $this->assertStringContainsString("Alpine.store('live').on(null, 'notifications', () => (this.state !== 'loading' ? this.load(true) : null), { dialogs: false, typing: false });", $app);
        $this->assertStringContainsString("Alpine.store('live').on(null, ['requests', 'appointments'], () => this.recount(side, needsAction, true), { dialogs: false, typing: false });", $app);
        $this->assertStringContainsString('.catch(failed(type))', $app);
    }

    public function test_staff_pages_reload_quietly_on_their_topic(): void
    {
        $dashboard = $this->source('resources/views/admin/dashboard.blade.php');
        // Every view shows the Overview's activity and alerts now (18 Nov), so they reload on any view.
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'any', () => this.load(true));\n                await this.load();", $dashboard);
        $this->assertStringContainsString('retryExpr="load()"', $dashboard);
        $this->assertSame(2, substr_count($this->source('resources/js/dashboards.js'), "this.\$store.live.on(this.\$el, 'any', () => this.load(true));"));

        // Dokyu and Tulong: the list only. Opening a request (GET .../requests/{ref})
        // starts its review on the server, so nothing re-reads it by itself.
        foreach (['resources/views/admin/document-requests.blade.php', 'resources/views/admin/assistance-requests.blade.php'] as $path) {
            $page = $this->source($path);
            $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'requests', () => this.loadRequests(true), { busy: () => this.open });", $page, $path);
            $this->assertStringContainsString('async loadRequests(quiet = false) {', $page, $path);
            $this->assertStringContainsString('if (seq === this.loadSeq && !quiet) this.requestsFailed = true;', $page, $path);
            $this->assertDoesNotMatchRegularExpression('/live\\.on\\([^;]*refreshSelected/', $page, $path);
        }

        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'appointments', () => Promise.all([this.loadBookings(true), this.loadUpcoming(true)]), { busy: () => this.cancelOpen || !!this.actingRef });", $this->source('resources/js/scope-admin-requests.js'));

        $records = $this->source('resources/js/scope-admin-records.js');
        $this->assertSame(2, substr_count($records, "this.\$store.live.on(this.\$el, 'balita', () => (this.loadedOnce ? this.load(true) : null)"));
        // The feedback desk moved to its own file (Settings, 2026-11-18).
        $this->assertStringContainsString("this.\$store.live.on(null, ['other', 'balita'], () => (this.started ? this.load(true) : this.loadCounts())", $this->source('resources/js/feedback-desk.js'));
        $this->assertStringContainsString('if (!quiet) this.openLinked();', $records);
    }

    public function test_sakuna_follows_live_changes_with_a_slow_safety_timer(): void
    {
        $sakuna = $this->source('resources/views/admin/sakuna.blade.php');

        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'sakuna', () => this.liveRefresh());", $sakuna);
        $this->assertStringContainsString("setInterval(() => { if (document.visibilityState === 'visible') this.loadHeader(); }, 180000);", $sakuna);
        $this->assertStringNotContainsString('this.loadHeader(); }, 60000);', $sakuna);
        foreach (['loadCommandCenter', 'loadIncidentsList', 'loadCenters', 'loadAlerts', 'loadEvacuees', 'loadResources', 'loadRelief'] as $loader) {
            $this->assertStringContainsString("async {$loader}(quiet = false) {", $sakuna, $loader);
            $this->assertStringContainsString("this.{$loader}(true)", $sakuna, $loader);
        }
        // A live reload never reopens the centre named in the address.
        $this->assertStringContainsString("            if (quiet) return;\n            // A center opened from the Command Center's map (?center=REF).", $sakuna);

        $casualties = $this->source('resources/js/sakuna-casualties.js');
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'sakuna', () => Promise.all([this.load(this.page, true), this.loadSummary(true)])", $casualties);
        $this->assertStringContainsString('async load(page = 1, quiet = false) {', $casualties);
        $this->assertStringContainsString('@sakuna-missing-reload.window="load(page); loadSummary()"', $this->source('resources/views/admin/partials/sakuna/tab-missing.blade.php'));
    }

    public function test_resident_pages_reload_quietly_on_their_topic(): void
    {
        $dashboard = $this->source('resources/views/citizen/dashboard.blade.php');
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'requests', () => this.loadRequests(true));", $dashboard);
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'balita', () => Promise.all([this.loadBalita(true), this.loadEvents(true)])", $dashboard);
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'sakuna', () => this.loadAlerts(true));", $dashboard);
        $this->assertStringContainsString('} catch { if (!quiet) this.requests = []; }', $dashboard);

        // The list waits for an open window; the open request refreshes in place.
        foreach (['resources/views/citizen/document-requests.blade.php', 'resources/views/citizen/assistance-requests.blade.php'] as $path) {
            $page = $this->source($path);
            $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'requests', () => this.loadRequests(true));", $page, $path);
            $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'requests', () => this.refreshProgress(), { dialogs: false, busy: () => this.resubmitting", $page, $path);
            $this->assertStringContainsString('!!this.selectedRequest?.requirements?.some((q) => q._busy)', $page, $path);
            $this->assertStringContainsString('if (this.progressOpen && this.selectedRequest === r) this.applyDetail(r, res.data);', $page, $path);
        }

        $citizen = $this->source('resources/js/scope-citizen.js');
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'appointments', () => this.loadMine(true)", $citizen);
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'sakuna', () => Promise.all([this.loadAlerts(true), this.loadDirectory(true), this.loadIncidents(true)])", $citizen);
        $this->assertStringContainsString("this.\$store.live.on(this.\$el, 'balita', () => this.loadFirst(true));", $citizen);
        // A live Balita update keeps what the resident has open.
        $this->assertStringContainsString('if (quiet) return this.refreshFirst();', $citizen);
        $this->assertStringContainsString('if (!f || p.liking || p.sending) return;', $citizen);
    }
}
