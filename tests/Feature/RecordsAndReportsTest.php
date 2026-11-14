<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Sprint 2, records and reports (owner, 2026-11-05).
 *
 * Sheet item 7: no services list on the resident's profile. In Constituents,
 * a Tulong case in the resident's "Dokyu & Tulong Received" opens its case
 * notes there, read-only, for whoever may read Tulong records, loaded only
 * when opened. Sheet item 13: Sakuna > Reports filters by the kind of person
 * case, shown to whoever sees the Missing Persons tab.
 */
class RecordsAndReportsTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_a_tulong_case_opens_its_notes_read_only_in_the_residents_window(): void
    {
        $page = $this->source('resources/views/admin/constituents.blade.php');
        $modal = $this->source('resources/views/admin/constituents/modal-individual-detail.blade.php');

        // Only for whoever may read Tulong records, and only on a Tulong row.
        $this->assertStringContainsString("get mayReadCaseNotes() { return this.\$store.session.canPerm('tulong_records', 'view'); },", $page);
        $this->assertStringContainsString('x-show="q.type === \'tulong\' && mayReadCaseNotes"', $modal);
        // Loaded when opened, never for every row.
        $this->assertStringContainsString('@click="toggleCaseNotes(q.ref)"', $modal);
        $this->assertStringContainsString("\$api.get('/admin/services-rendered', { params: { request_ref: ref, include_voided: 1, per_page: 50 }, silent: true });", $page);
        $this->assertStringNotContainsString('loadCaseNotes(', substr($page, strpos($page, 'async openIndividual(r)'), 600), 'not loaded when the window opens');
        // A refusal and an empty case each say so.
        $this->assertStringContainsString('denied: !!err?.isForbidden, failed: !err?.isForbidden', $page);
        $this->assertStringContainsString('>These notes stay with the office that handles the case.</p>', $modal);
        $this->assertStringContainsString('>No notes you can see on this case.</p>', $modal);
        // A voided note shows as voided; how it was done in the case window's words.
        $this->assertStringContainsString("x-text=\"'Voided' + (n.void_reason ? ': ' + n.void_reason : '')\"", $modal);
        $this->assertStringContainsString("noteModes: { office: 'At the office', home_visit: 'Home visit', field: 'In the field', online: 'Online or by phone' },", $page);
        // Read-only: nothing is written from here.
        $this->assertStringNotContainsString("\$api.post('/admin/services-rendered'", $page);
        // The row still opens the case in Tulong.
        $this->assertStringContainsString('@click="q.type === \'tulong\' ? goToTulong(selectedIndividual, q.ref) : goToDokyu(selectedIndividual, q.ref)"', $modal);
    }

    public function test_the_sakuna_report_filters_by_case_kind_for_whoever_sees_missing_persons(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-reports.blade.php');
        $js = $this->source('resources/js/sakuna-report.js');
        $page = $this->source('resources/views/admin/sakuna.blade.php');

        // The Missing Persons tab's own condition, word for word.
        $this->assertStringContainsString("x-show=\"permsReady && tabShown('{{ \$t['key'] }}')\"", $page);
        $this->assertStringContainsString('<select x-model="f.case_kind" @change="load(1)" x-show="permsReady && tabShown(\'missing-persons\')"', $tab);
        $this->assertStringContainsString("\$reportCaseKinds = ['' => 'All cases', 'missing' => 'Missing', 'injured' => 'Injured', 'deceased' => 'Deceased', 'unidentified' => 'Unidentified'];", $tab);
        // Its labels in full at 1440px (QA 2026-11-05: it read "Missing p"): never narrower than its longest.
        $this->assertStringContainsString('aria-label="Case kind" class="sk-sel" style="flex: 1 0 auto; min-width: 118px;"', $tab);
        // Sent with the other filters to the summary, the incidents and the export, and cleared with them.
        $this->assertStringContainsString("status: '', case_kind: '', livelihood_group: ''", $js);
        $this->assertStringContainsString("api.get('/admin/sakuna/reports/summary', { params: this.params() })", $js);
        $this->assertStringContainsString("api.get('/admin/sakuna/reports/incidents', { params: this.params({ page, per_page: 25 }) })", $js);
        $this->assertStringContainsString("api.post('/admin/sakuna/reports/export', { ...this.params(), format })", $js);
        $this->assertStringContainsString('clear() { this.f = blankFilters();', $js);
        // And named in the exports' filters lines.
        $this->assertStringContainsString("[['Case kind', caseKinds[f.case_kind] || 'All cases']]", $js);
        $this->assertStringContainsString("(this.permsReady && this.tabShown('missing-persons'))", $js);
    }

    public function test_missing_persons_switched_off_hides_its_tab_card_chip_and_filter(): void
    {
        $page = $this->source('resources/views/admin/sakuna.blade.php');
        $command = $this->source('resources/views/admin/partials/sakuna/tab-command.blade.php');
        // The Casualties tab's logic (2026-11-14) is sakuna-casualties.js.
        $missing = $this->source('resources/js/sakuna-casualties.js');
        $modal = $this->source('resources/views/admin/partials/sakuna/_incident-detail-modal.blade.php');
        $js = $this->source('resources/js/sakuna-report.js');

        // Recorded once for the page; the Missing Persons tab only while it is on.
        $this->assertStringContainsString('missingOff: false,', $page);
        $this->assertStringContainsString("tabShown(tab) { return this.tabAllowed(tab) && !(tab === 'missing-persons' && this.missingOff); },", $page);
        // One quiet question on a tab that does not ask itself, for whoever may read cases.
        $this->assertStringContainsString("if (['command-center', 'missing-persons'].includes(this.activeTab) || !this.can('sakuna_missing', 'view')) return;", $page);
        $this->assertStringContainsString("try { await \$api.get('/admin/sakuna/missing/summary', { silent: true }); } catch (e) { if (e?.code === 'FEATURE_OFF') this.markMissingOff(); }", $page);
        $this->assertStringContainsString('this.probeMissing();', $page);
        // The requests that already ask learn it there.
        $this->assertStringContainsString("if (e?.code === 'FEATURE_OFF') return markMissingOff();", $command);
        $this->assertStringContainsString("if (err?.code === 'FEATURE_OFF') this.markMissingOff?.();", $missing);
        // The Command Center's card and chip, and the incident's Record a person, hide.
        $this->assertStringContainsString('x-show="!missingOff && missingSummary?.missing"', $command);
        $this->assertStringContainsString('x-show="!missingOff && mp" x-cloak>', $command);
        $this->assertStringContainsString("x-show=\"!missingOff && can('sakuna_missing','create')\"", $modal);
        // A case kind the server refuses as off is cleared and the report reloads; no error toast.
        $this->assertStringContainsString("if (err?.code !== 'FEATURE_OFF' || !this.f.case_kind) return false;", $js);
        $this->assertStringContainsString('if (this.caseKindOff(err)) again = true; else this.failed = true;', $js);
        $this->assertStringContainsString('if (again) this.load(1);', $js);
        $this->assertStringContainsString("window.addEventListener('sakuna-missing-off', () => { if (this.f.case_kind) { this.f.case_kind = ''; this.load(1); } });", $js);
    }

    public function test_an_incident_prints_its_own_report_for_whoever_may_print(): void
    {
        $modal = $this->source('resources/views/admin/partials/sakuna/_incident-detail-modal.blade.php');
        $print = $this->source('resources/views/admin/partials/sakuna/_incident-report-print.blade.php');
        $js = $this->source('resources/js/sakuna-report.js');

        // Only for sakuna_reports.print; fetched when asked, and logged by the server.
        $this->assertStringContainsString("x-show=\"can('sakuna_reports','print') && selectedIncident?.ref\" @click=\"\$dispatch('sakuna-print-incident', { ref: selectedIncident.ref })\"", $modal);
        $this->assertStringContainsString("@include('admin.partials.sakuna._incident-report-print')", $modal);
        $this->assertStringContainsString('<div x-data="incidentReportPrint" @sakuna-print-incident.window="show($event.detail.ref)"', $print);
        $this->assertStringContainsString("api.get('/admin/sakuna/reports/incident/' + encodeURIComponent(ref))", $js);
        $this->assertStringContainsString("Alpine.data('incidentReportPrint', incidentReportPrint);", $this->source('resources/js/app.js'));
        // The portal's printed report: the rep-paper sheet, printed by the site's print isolation.
        $this->assertStringContainsString('<div id="dp-print-area">', $print);
        $this->assertStringContainsString('<article class="rep-paper" data-incident-report>', $print);
        $this->assertStringContainsString("{{ asset('images/esperanza/esperanza-seal.png') }}", $print);
        $this->assertStringContainsString("document.body.classList.add('dp-printing');", $js);
        // The sections; persons only when the server sends a list.
        foreach (['<h2>Incident details</h2>', '<h2>Timeline</h2>', '<h2>Resources</h2>', '<h2>Damage</h2>', '<h2>Persons</h2>', '<h2>Notes</h2>'] as $heading) {
            $this->assertStringContainsString($heading, $print, $heading);
        }
        $this->assertStringContainsString('<template x-if="Array.isArray(report.persons)">', $print);
        // Esperanza time, and who made it.
        $this->assertStringContainsString("const manilaWhen = (iso) => (iso ? new Date(iso).toLocaleString('en-US', { timeZone: TZ, dateStyle: 'medium', timeStyle: 'short' }) : '');", $js);
        $this->assertStringContainsString("'Generated ' + manilaWhen(r.generated_at) + ' by ' + (r.generated_by || '')", $js);
        // The server records it when it opens, and the window says so (QA 2026-11-05).
        $this->assertStringContainsString('Opening this report is recorded under your name.', $print);
        $this->assertStringNotContainsString('Each print is recorded', $print);
        // photo_count, or an older payload's photos list counted.
        $this->assertStringContainsString("['Photos', this.photoCount(i) ? this.photoCount(i) + ' on file' : ''],", $js);
        $this->assertStringContainsString('return Array.isArray(i?.photos) ? i.photos.length : 0;', $js);
    }

    public function test_the_report_leaves_out_person_figures_it_was_not_sent(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-reports.blade.php');
        $js = $this->source('resources/js/sakuna-report.js');
        $insights = $this->source('resources/js/sakuna-insights.js');

        $this->assertStringContainsString("export const hasPersons = (summary) => !!summary?.persons && typeof summary.persons === 'object';", $js);
        // On screen: the tiles, the section tab and the incident table's Missing column.
        $this->assertStringContainsString('<template x-for="card in figureCards()" :key="card[0]">', $tab);
        $this->assertStringNotContainsString('summary.persons.', $tab);
        $this->assertStringContainsString("...(p ? [['Still missing', p.missing, 'sk-t-amber']", $js);
        $this->assertStringContainsString('<template x-for="tab in shownSectionTabs()" :key="tab[0]">', $tab);
        $this->assertStringContainsString('<th scope="col" class="sk-r" x-show="hasPersons()">Missing</th>', $tab);
        $this->assertStringContainsString('<td class="sk-r tabular-nums" x-show="hasPersons()" x-text="cell(r.missing)"></td>', $tab);
        // In the exports: key figures, the tables, the incident columns, the workbook's sheet and contents.
        $this->assertStringContainsString("...(hasPersons(s) ? [['Missing persons', 'Still missing', p.missing]", $js);
        $this->assertStringContainsString("...(hasPersons(s) ? [{ group: 'missing', heading: 'Casualties: missing, found, injured, deceased and unidentified persons'", $js);
        $this->assertStringContainsString('if (all && hasPersons(s)) {', $js);
        $this->assertStringContainsString("...(hasPersons(s) ? [{ key: 'missing', label: 'Missing' }", $js);
        $this->assertSame(0, substr_count($js, 'this.columns()'), 'every export passes the summary to columns()');
        $this->assertStringContainsString(".filter(([group]) => group !== 'missing' || hasPersons(d.summary))", $js);
        $this->assertStringContainsString(".filter(([k]) => k !== 'Missing persons' || hasPersons(d.summary));", $js);
        // The executive summary, the PDF's key figures and its charts.
        $this->assertStringContainsString('const per = s.persons || {};', $insights);
        $this->assertStringContainsString("...(s.persons ? [{ label: 'Still missing'", $insights);
        $this->assertStringContainsString("if (per) add({ key: 'persons',", $insights);
    }
}
