<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Screen sweep 18 Nov (web-4): confirmed defects in the Dokyu and Tulong
 * officer pages, the shared app script and three resident components - an age
 * that was 2026 minus the birth year, a prepared document looked up by display
 * name, a backwards relationship on the intake sheet, tabs a hidden status
 * filter overrode, lists that stopped at 2,000 without a word, a PDF viewer
 * that drew an earlier file, a resident profile left on a shared computer,
 * a top-bar search that never saw most residents, a disclaimer on an issued
 * certificate, My IDs that misread or missed IDs, and a certificate signature
 * cut off on phones. Source-reading, as the rest of this suite.
 */
class Sweep1118web4Test extends TestCase
{
    private function source(string $path): string
    {
        return str_replace("\r\n", "\n", file_get_contents(base_path($path)));
    }

    private function app(): string
    {
        return $this->source('resources/js/app.js');
    }

    private function dokyu(): string
    {
        return $this->source('resources/views/admin/document-requests.blade.php');
    }

    private function tulong(): string
    {
        return $this->source('resources/views/admin/assistance-requests.blade.php');
    }

    public function test_the_review_age_counts_from_the_whole_birth_date_on_both_pages(): void
    {
        $app = $this->app();
        $this->assertStringContainsString('window.ageFromBirthDate = (iso, today = new Date()) => {', $app);
        // The date part only, so the day never shifts with the time zone.
        $this->assertStringContainsString("const [y, m, d] = String(iso ?? '').slice(0, 10).split('-').map(Number);", $app);
        // A year off until this year's birthday has come.
        $this->assertStringContainsString('if (today.getMonth() + 1 < m || (today.getMonth() + 1 === m && today.getDate() < d)) age--;', $app);

        foreach (['dokyu' => $this->dokyu(), 'tulong' => $this->tulong()] as $page => $view) {
            $this->assertStringContainsString("const age = window.ageFromBirthDate(source.answer);\n                return age === null ? '—' : age + ' years old';", $view, $page);
            $this->assertStringNotContainsString('(2026 - y)', $view, $page);
        }
        // The document snapshot's own age uses the same helper.
        $this->assertStringContainsString('const age = window.ageFromBirthDate(snapshot.date_of_birth);', $this->dokyu());
    }

    public function test_the_prepared_document_is_found_by_the_service_key_first(): void
    {
        $view = $this->dokyu();
        $this->assertStringContainsString("const meta = (r.service_key && Object.values(this.docTypeLookup).find((m) => m.docKey === r.service_key))\n                    || this.docTypeLookup[r.type];", $view);
        $this->assertStringNotContainsString('const meta = this.docTypeLookup[r.type];', $view);

        // The five services the name lookup missed all have a template under their key.
        $docKeys = array_column(config('esperanza_document_type_lookup'), 'docKey');
        foreach (['osca_senior_citizen', 'dokyu_business_new', 'mcro_live_birth', 'mcro_marriage', 'mcro_delayed_registration'] as $key) {
            $this->assertContains($key, $docKeys, $key);
        }
    }

    public function test_the_intake_sheet_asks_the_relationship_the_way_it_prints_it(): void
    {
        $view = $this->tulong();
        // Printed as "<beneficiary> (<relation> of the client)".
        $this->assertStringContainsString("' (' + selected.case.intake.beneficiary_relation + ' of the client)'", $view);
        $this->assertStringContainsString("Beneficiary's relationship to the client\n                                        <input type=\"text\" x-model=\"intake.beneficiary_relation\" maxlength=\"60\" placeholder=\"e.g. Son\"", $view);
        $this->assertStringNotContainsString("Client's relationship to the beneficiary", $view);
    }

    public function test_tulong_tabs_cards_and_the_status_filter_agree(): void
    {
        $view = $this->tulong();
        // ...and the statuses a dashboard link named (?status=a,b, 18 Nov).
        $this->assertStringContainsString("pickTab(tab) {\n                this.tab = tab;\n                this.statusFilter = '';\n                this.statusList = [];\n                this.openOnly = false;\n            },", $view);
        $this->assertStringContainsString("type=\"button\" @click=\"pickTab('{{ \$key }}')\"", $view);
        foreach (["@click=\"pickTab('all'); openOnly = true\" class=\"cursor-pointer\" label=\"Active Cases\"", "@click=\"pickTab('action')\"", "@click=\"pickTab('release')\"", "@click=\"pickTab('done')\" class=\"cursor-pointer\" label=\"Completed\"", "@click=\"pickTab('all')\" class=\"cursor-pointer\" label=\"All Cases\""] as $card) {
            $this->assertStringContainsString($card, $view);
        }
        $this->assertStringNotContainsString('<x-ui.stat-card layout="compact" @click="tab = ', $view);
        // A status picked under Advanced shows on the Advanced button and moves the tabs to All.
        $this->assertStringContainsString(':class="showAdvancedFilters || statusFilter || officeFilter', $view);
        $this->assertStringContainsString("x-model=\"statusFilter\" @change=\"if (\$event.target.value) { tab = 'all'; openOnly = false; }\"", $view);
        // Completed means Completed, as the card counts it.
        $this->assertStringContainsString("if (this.tab === 'done') return r.status === 'Completed';", $view);
        $this->assertStringNotContainsString("if (this.tab === 'done') return this.terminalStatuses.includes(r.status);", $view);
    }

    public function test_officer_lists_say_when_they_hold_only_the_newest_and_find_an_older_ref(): void
    {
        foreach (['dokyu' => [$this->dokyu(), 'requests'], 'tulong' => [$this->tulong(), 'cases']] as $page => [$view, $noun]) {
            $this->assertStringContainsString('this.requestsTotal = res.meta?.truncated ? res.meta.total : 0;', $view, $page);
            $this->assertStringContainsString('<p x-show="requestsTotal > requests.length" x-cloak', $view, $page);
            $this->assertStringContainsString("' of ' + requestsTotal.toLocaleString() + ' {$noun}. An older one opens by its reference.'", $view, $page);
            // By the list's ?q= (the ref), which opens nothing on the server.
            $this->assertStringContainsString('async findByRef(ref) {', $view, $page);
            $this->assertStringContainsString("\$api.get('/admin/' + this.requestType + '/requests', { params: { q: text, per_page: 10 } });", $view, $page);
            // A ?ref= link to an older request opens it too.
            $this->assertStringContainsString("const match = await this.findByRef(ref);\n                    if (match) this.\$nextTick(() => this.openRequest(match));", $view, $page);
            $this->assertStringContainsString('@click="findByRef(search).then((r) => { if (r) openRequest(r); })">Look Up Reference</button>', $view, $page);
        }
    }

    public function test_the_pdf_viewer_never_draws_an_earlier_file(): void
    {
        $app = $this->app();
        $this->assertStringContainsString('isCurrent = () => true } = {}) => {', $app);
        // Checked before the container is cleared and around every page.
        $this->assertSame(3, substr_count($app, 'if (stale()) return null;'));
        $this->assertStringContainsString("if (isCurrent()) return false;\n        doc.destroy();", $app);

        $this->assertStringContainsString("const pages = await window.renderPdfInto(this.\$refs.pages, url, { isCurrent: current });\n                        if (current()) this.pages = pages;", $app);
        $this->assertStringContainsString("reset() {\n                run += 1;", $app);
        // Not reactive: show() runs in an x-effect.
        $this->assertStringContainsString("Alpine.data('pdfPreview', () => {", $app);
        $this->assertStringContainsString('let run = 0;', $app);
    }

    public function test_the_resident_profile_is_kept_beside_the_sign_in(): void
    {
        $app = $this->app();
        $this->assertStringContainsString('account: readCitizenCache(),', $app);
        $this->assertStringContainsString('writeCitizenCache(this.account);', $app);
        $this->assertStringContainsString('writeCitizenCache(null);', $app);
        $this->assertStringNotContainsString("writeJson('esperanza_citizen_session'", $app);
        // A tab-only sign-in keeps it in sessionStorage; one left in localStorage goes.
        $this->assertStringContainsString("if (localStorage.getItem('esperanza_token')) return localStorage;\n        if (sessionStorage.getItem('esperanza_token')) return sessionStorage;", $app);
        $this->assertStringContainsString('if (store !== localStorage) localStorage.removeItem(CITIZEN_CACHE_KEY);', $app);
        // The key read above is the API client's own.
        $this->assertStringContainsString("const TOKEN_KEY = 'esperanza_token';", $this->source('resources/js/api/client.js'));
    }

    public function test_the_staff_top_bar_asks_the_server_for_residents(): void
    {
        $app = $this->app();
        $this->assertStringNotContainsString("api.getAll('/admin/residents')", $app);
        $this->assertStringContainsString("this.\$watch('q', (value) => {", $app);
        $this->assertStringContainsString('const params = { q: text, per_page: 10 };', $app);
        $this->assertStringContainsString("can('constituents') ? residentsFound() : [],", $app);
        // The server matches one phrase: several words also ask by the longest, and every word is matched here.
        $this->assertStringContainsString("words.length > 1 ? ask('/admin/residents', { q: longest, per_page: 100 }) : [],", $app);
        $this->assertStringContainsString('if (!words.every((w) => hay.includes(w))) return;', $app);
        // A request list longer than its load is asked by reference.
        $this->assertStringContainsString("can('dokyu') && byRef && (this.partial.dokyu || !this.loaded) ? ask('/admin/dokyu/requests') : [],", $app);
        $this->assertStringContainsString('if (seq !== this.findSeq) return;', $app);
        $this->assertStringContainsString('get loading() { return this.listsLoading || this.finding; },', $app);
        $this->assertStringContainsString('[...this.pages, ...this.items, ...found].forEach((it) => {', $app);
    }

    public function test_the_issued_indigency_certificate_carries_no_request_disclaimer(): void
    {
        $view = $this->source('resources/views/components/citizen/municipal-request-preview.blade.php');
        $this->assertStringContainsString("<div x-show=\"{{ \$docKey }} !== 'dokyu_indigency'\" class=\"flex items-start gap-2.5 rounded-xl bg-slate-50", $view);
        // The civil registry only for the MCRO papers.
        $this->assertStringContainsString("upon verification<span x-show=\"String(({{ \$docKey }}) || '').startsWith('mcro_')\"> against the civil registry</span>.", $view);
    }

    public function test_my_ids_reads_accepted_as_verified_and_lists_the_wallet(): void
    {
        $view = $this->source('resources/views/components/citizen/topbar.blade.php');
        $this->assertStringContainsString("'Accepted': ['Verified', 'Verified'],", $view);
        $this->assertStringContainsString("'Submitted': ['Under review', 'Sinusuri'],", $view);
        $this->assertStringContainsString("status: en === 'Verified' ? 'Verified' : 'Unverified'", $view);
        $this->assertStringContainsString(".filter((d) => d.category === 'Government ID')", $view);
        // Asked of the server by category: the registration ID is the oldest paper, past a 25-row page.
        $this->assertStringContainsString("\$api.get('/citizen/papeles', { params: { category: 'Government ID', per_page: 100 } })", $view);
        $this->assertStringContainsString('const name = d.name || d.type;', $view);
        $this->assertStringNotContainsString("'Identity / Verification'", $view);
    }

    public function test_the_certificate_signature_rows_wrap_on_a_narrow_paper(): void
    {
        $view = $this->source('resources/views/components/citizen/barangay-certificate-preview.blade.php');
        $this->assertStringContainsString('class="doc-paper @container ', $view);
        $this->assertStringContainsString('<div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">', $view);
        $this->assertStringContainsString('<div class="text-center min-w-0 max-w-full" x-show="!', $view);
        $this->assertStringContainsString('<div class="flex flex-wrap items-end gap-3">', $view);
        $this->assertStringContainsString("=== 'brgy_cert_late_registration'\" class=\"flex flex-wrap items-end gap-4 mb-4", $view);
        // Two columns only where the paper is wide enough (a printed page is).
        $this->assertSame(4, substr_count($view, 'grid grid-cols-1 @md:grid-cols-2 gap-x-4'));
        $this->assertStringNotContainsString('grid grid-cols-2 gap-x-4', $view);
        $this->assertStringNotContainsString('class="col-span-2 ', $view);
    }
}
