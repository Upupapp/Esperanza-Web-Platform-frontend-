<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Screen sweep 18 Nov (web-6): late answers that replaced what is on screen,
 * figures that read 0 when their report did not load, a box that was
 * ignored, a window left stale or blank, an action that did nothing without
 * saying why, and two Tulong screens that ran off a phone. Source-reading,
 * as the rest of this suite.
 */
class Sweep1118web6Test extends TestCase
{
    private function source(string $path): string
    {
        return str_replace("\r\n", "\n", file_get_contents(base_path($path)));
    }

    public function test_the_tulong_review_step_and_track_stepper_fit_a_phone(): void
    {
        $page = $this->source('resources/views/citizen/assistance-requests.blade.php');

        // A long label wraps; the answer takes at most half the row.
        $this->assertStringContainsString('<span class="text-ink-muted min-w-0" x-text="f.label"></span>', $page);
        $this->assertStringContainsString('<span class="font-medium text-slate-700 text-right truncate shrink-0 max-w-[50%]" x-text="Array.isArray(formData[f.key])', $page);
        $this->assertStringNotContainsString('<span class="text-ink-muted shrink-0" x-text="f.label"></span>', $page);

        // Seven labelled steps: dots on a phone, the current step named under them.
        $this->assertStringContainsString('<span class="hidden sm:block text-xs max-w-[76px] leading-tight"', $page);
        $this->assertStringContainsString('<div class="flex-1 h-0.5 sm:-mt-4 ', $page);
        $this->assertStringContainsString('data-testid="tulong-stepper-current"', $page);
        $this->assertStringContainsString("lang('Step ' + (stepIndex(selectedRequest.status) + 1) + ' of ' + stepOrder.length + ': ', 'Hakbang ' + (stepIndex(selectedRequest.status) + 1) + ' sa ' + stepOrder.length + ': ')", $page);
        $this->assertStringContainsString('x-text="stepText(stepOrder[stepIndex(selectedRequest.status)])"', $page);
    }

    public function test_an_emergency_report_window_drops_a_late_answer_for_another_report(): void
    {
        $js = $this->source('resources/js/scope-citizen.js');
        $open = substr($js, strpos($js, 'async openIncident(row) {'), 1600);

        $this->assertStringContainsString('const current = () => this.detail?.ref === ref;', $open);
        $this->assertStringContainsString("await api.get('/citizen/incidents/' + encodeURIComponent(row.ref));\n                if (!current()) return;", $open);
        $this->assertStringContainsString('if (current()) this.detailFailure = err;', $open);
        $this->assertStringContainsString('if (current()) this.detailLoading = false;', $open);
    }

    public function test_a_case_the_office_filed_is_not_credited_to_the_resident(): void
    {
        $js = $this->source('resources/js/scope-citizen.js');
        $timeline = substr($js, strpos($js, 'function requestTimeline() {'), 2400);

        $this->assertStringContainsString("const first = String((r.history || [])[0]?.trigger || '');", $timeline);
        $this->assertStringContainsString("const byOffice = first.startsWith('office:') || first === 'import';", $timeline);
        $this->assertStringContainsString("by: office, mine: false, note: this.t('Filed for you at the office.', 'Inihain para sa iyo sa opisina.')", $timeline);
    }

    public function test_analytics_say_not_available_instead_of_zero_when_a_report_did_not_load(): void
    {
        $page = $this->source('resources/views/admin/reports.blade.php');

        // A failed read is marked, not swallowed as an empty report.
        $this->assertStringContainsString('const miss = () => { failed = true; return null; };', $page);
        $this->assertStringNotContainsString('.catch(() => null)', $page);
        $this->assertStringContainsString('this.analyticsFailed = failed;', $page);
        $this->assertStringContainsString('data-testid="analytics-failed"', $page);
        $this->assertStringContainsString('@click="failed ? init() : loadAnalytics()"', $page);

        // Each figure reads '—' while its report is missing.
        foreach ([
            "valueBind=\"requestReportsIn ? totalRequests.toLocaleString() : '—'\"",
            "valueBind=\"sakunaIncidents ? incidentTotal : '—'\"",
            "valueBind=\"sakunaIncidents ? criticalHighCount : '—'\"",
            "valueBind=\"internalForms ? (internalForms.totals?.submissions ?? 0) : '—'\"",
            "valueBind=\"internalForms ? officesCovered : '—'\"",
            "x-text=\"revenue ? peso(revenue.totals?.assessed) : '—'\"",
            "x-text=\"revenue ? peso(revenue.totals?.collected) : '—'\"",
            "x-text=\"revenue ? peso(revenue.totals?.outstanding) : '—'\"",
            "x-text=\"revenue ? (revenue.totals?.receipts || 0) : '—'\"",
        ] as $bind) {
            $this->assertStringContainsString($bind, $page);
        }
        $this->assertStringNotContainsString('valueBind="incidentTotal"', $page);
        $this->assertStringNotContainsString('revenue?.totals?.assessed || 0', $page);
        // Dokyu alone is not "Dokyu + Tulong": with one request report this
        // account may read missing, the total and the average read '—'.
        $this->assertStringContainsString('get requestReportsIn() {', $page);
        $this->assertStringContainsString("get avgResolutionDays() {\n                if (!this.requestReportsIn) return null;", $page);

        // "No requests on file yet." only when the barangay report did load.
        $this->assertStringContainsString('x-show="barangayActivity && !barangayRequestRows.length"', $page);
        $this->assertStringContainsString("naNote(src) { return src ? '' : (this.analyticsLoading || !this.analyticsLoaded ? 'Loading…' : 'Not available'); },", $page);
    }

    public function test_an_unticked_mark_as_attended_box_is_sent_to_the_server(): void
    {
        $js = $this->source('resources/js/event-desk.js');
        $save = substr($js, strpos($js, 'async edAddSave() {'), 2200);

        $this->assertStringContainsString('if (onDay) body.attended = mark;', $save);
        $this->assertLessThan(strpos($save, "'/registrations', body)"), strpos($save, 'if (onDay) body.attended = mark;'));
        // Unticked reads "Not here yet", not "Did not attend".
        $this->assertStringContainsString('{ registrations: [{ id: row.id, attended: null }] }', $save);
        $this->assertStringNotContainsString('row.attended !== true', $save);
    }

    public function test_a_late_scan_answer_never_replaces_the_current_holder(): void
    {
        $js = $this->source('resources/js/id-scan.js');

        $this->assertStringContainsString('let ask = 0;', $js);
        $this->assertStringContainsString("reset() {\n            ask += 1;", $js);
        $this->assertStringContainsString('const mine = ++ask;', $js);
        $this->assertStringContainsString("const res = await scanId(value);\n                if (mine !== ask) return;", $js);
        // The guardian reveal too.
        $reveal = substr($js, strpos($js, 'async reveal() {'), 1400);
        $this->assertStringContainsString('const mine = ask;', $reveal);
        $this->assertStringContainsString("{ reveal: { reason: this.rvReason, note: this.rvNote } });\n                if (mine !== ask) return;", $reveal);
    }

    public function test_a_failed_registrations_read_is_not_taken_for_none(): void
    {
        $js = $this->source('resources/js/citizen-events.js');
        $store = substr($js, strpos($js, 'const eventRegs = {'), 2200);

        // Ready only once read: a failure no longer leaves a registered
        // resident's card saying full or closed.
        $this->assertStringNotContainsString('.finally(() => { this.ready = true; })', $store);
        $this->assertStringContainsString("this.byKey = by;\n                this.failed = false;\n                this.tries = 0;\n                this.ready = true;", $store);
        $this->assertStringContainsString('if (this.tries < 4) setTimeout(() => { if (!this.ready) this.load(); }, 5000 * this.tries);', $store);
        $this->assertStringContainsString('retry() {', $store);
        $this->assertStringContainsString("if (!this.\$store.eventRegs.ready) return 'none';", $js);
    }

    public function test_try_again_on_a_filed_form_shows_the_paper(): void
    {
        $page = $this->source('resources/views/admin/internal-forms.blade.php');

        $this->assertStringContainsString('this.viewing = { ...s, ...res.data, loading: false };', $page);
        $this->assertStringContainsString('retryExpr="openView(viewing)"', $page);
    }

    public function test_the_sakuna_report_range_names_the_days_the_server_counts(): void
    {
        $js = $this->source('resources/js/sakuna-report.js');
        $label = substr($js, strpos($js, 'get rangeLabel() {'), 700);

        $this->assertStringContainsString("if (to) return '30 days to ' + fmt(to, true);", $label);
        $this->assertStringContainsString("return 'Last 30 days';", $label);
        $this->assertStringNotContainsString("'All dates'", $label);
        $this->assertStringNotContainsString("'Until '", $label);
        $this->assertStringNotContainsString("all: ['', '']", $js);
    }

    public function test_weekly_sakuna_trends_are_called_weeks(): void
    {
        $insights = $this->source('resources/js/sakuna-insights.js');

        $this->assertStringContainsString('export const isWeeklyTrend = (trend) => (trend || []).length > 1 && Date.parse(trend[1].label) - Date.parse(trend[0].label) > 864e5;', $insights);
        $this->assertStringContainsString("(isWeeklyTrend(trend) ? 'The busiest week was the week of ' : 'The busiest day was ')", $insights);
        // The server's weeks are never grouped in sevens again.
        $points = substr($insights, strpos($insights, 'function trendPoints(trend) {'), 500);
        $this->assertLessThan(strpos($points, 'if (trend.length <= 31)'), strpos($points, "if (isWeeklyTrend(trend)) return trend.map((t) => ['Wk of ' + day(t.label), t.count]);"));
        $this->assertStringContainsString("note: isWeeklyTrend(trend) || trend.length > 31 ? 'Weekly totals' : 'Per day'", $insights);
        $this->assertStringNotContainsString('tp.length > 31', $insights);

        // The on-screen chart's bars too.
        $report = $this->source('resources/js/sakuna-report.js');
        $this->assertStringContainsString("return trend.map((t) => (weekly ? 'Wk of ' : '') + new Date(t.label + 'T00:00:00')", $report);
    }

    public function test_an_import_whose_close_failed_can_be_closed_again(): void
    {
        $js = $this->source('resources/js/resident-import-ui.js');
        $finish = substr($js, strpos($js, 'async finish() {'), 2600);

        $this->assertStringContainsString("if (this.stoppedAt === null) {\n                    this.closeFailed = true;\n                    this.stoppedAt = 0;\n                }", $finish);
        $this->assertStringContainsString('stopped: this.stoppedAt !== null && !this.closeFailed,', $finish);

        // Try again with every row in closes it, instead of returning.
        $run = substr($js, strpos($js, 'async runImport() {'), 900);
        $this->assertStringContainsString("if (this.importRef && !this.summary) {\n                    this.running = true;\n                    try { await this.finish(); }", $run);
        $this->assertStringNotContainsString("if (!batches.length) return;\n", $run);

        // The paused panel's buttons are what show it (stoppedAt set).
        $modal = $this->source('resources/views/admin/constituents/modal-import.blade.php');
        $this->assertStringContainsString('x-show="!running && stoppedAt !== null" @click="runImport()"', $modal);
    }

    public function test_the_case_window_reads_the_case_again_when_its_session_changes(): void
    {
        $panel = $this->source('resources/views/admin/tulong/case-session.blade.php');

        $this->assertStringContainsString('x-on:welfare-session-changed.window="if (open && !csBusy && selected?.session?.ref && selected.session.ref === $event.detail?.ref && typeof refreshSelected === \'function\') refreshSelected().catch(() => null)"', $panel);
        $this->assertStringContainsString('async refreshSelected() {', $this->source('resources/views/admin/assistance-requests.blade.php'));
    }

    public function test_log_entry_says_which_blotter_fields_are_missing(): void
    {
        $page = $this->source('resources/views/admin/communications.blade.php');
        $start = strpos($page, "x-show=\"tab === 'blotter'\"");
        $blotter = substr($page, $start, strpos($page, '{{-- ============================= FEEDBACK') - $start);

        $this->assertStringContainsString("this.entryTried = true;\n                    if (this.entryMissing) return;", $blotter);
        $this->assertStringContainsString('x-show="entryTried && entryMissing"', $blotter);
        $this->assertStringContainsString('for="blotter-date">Date <span class="text-rose-500">*</span>', $blotter);
        $this->assertStringContainsString('for="blotter-type">Incident Type <span class="text-rose-500">*</span>', $blotter);
        $this->assertStringContainsString('for="blotter-narrative">Details <span class="text-rose-500">*</span>', $blotter);
        $this->assertStringContainsString('@click="showAdd = true; entryTried = false;', $blotter);
    }
}
