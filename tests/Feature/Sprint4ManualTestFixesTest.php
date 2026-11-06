<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Sprint 4: the Sakuna, resident-site and Events fixes from the owner's
 * manual test (2026-11-06). Each test pins the cause that was traced, so it
 * cannot quietly return.
 */
class Sprint4ManualTestFixesTest extends TestCase
{
    private function source(string $path): string
    {
        return str_replace("\r\n", "\n", file_get_contents(base_path($path)));
    }

    // ------------------------------------- 1. one Resolve and close on an incident

    public function test_an_incident_is_resolved_and_closed_in_one_confirmed_step(): void
    {
        $page = $this->source('resources/views/admin/sakuna.blade.php');
        $modal = $this->source('resources/views/admin/partials/sakuna/_incident-detail-modal.blade.php');

        // Offered in place of Mark resolved when the role may also close.
        $this->assertStringContainsString("resolve_close: 'Resolve and close'", $page);
        $this->assertStringContainsString("const both = verbs.includes('resolve') && this.can('sakuna_incidents', 'close');", $page);
        $this->assertStringContainsString(".map(v => (v === 'resolve' && both ? 'resolve_close' : v));", $page);
        // One confirmation, then resolve and close in sequence; a failed close says it stays Resolved.
        $this->assertStringContainsString("if (['resolve', 'resolve_close', 'close'].includes(verb)) { this.incidentConfirmVerb = verb; return; }", $page);
        $this->assertStringContainsString("if (!(await this.actIncidentVerb(inc, 'resolve', {}, { quiet: true }))) return;", $page);
        $this->assertStringContainsString('is marked resolved, but it could not be closed. It stays Resolved', $page);
        $this->assertStringContainsString('return true;', $page);
        // Close alone remains only where the server offers it: an incident already Completed.
        $this->assertStringContainsString("return ['validate', 'dispatch', 'resolve', 'close']\n                .filter(v => verbs.includes(v))", $page);
        $this->assertStringContainsString("resolve_close: 'Resolve and close ' + selectedIncident?.ref", $modal);
        // The window's × no longer reads like the incident's Close.
        $this->assertStringContainsString('aria-label="Close" title="Close" @click="showIncidentDetail = false"', $modal);
        $this->assertStringNotContainsString('title="Close window"', $modal);
    }

    // ------------------------------------- 2. the printed report keeps to the page

    public function test_the_incident_report_keeps_its_note_column_on_the_page(): void
    {
        $print = $this->source('resources/views/admin/partials/sakuna/_incident-report-print.blade.php');
        $css = $this->source('resources/css/app.css');

        $this->assertStringContainsString('<table class="rep-fixed">', $print);
        $this->assertStringContainsString('<colgroup><col style="width:22%"><col style="width:12%"><col style="width:20%"><col style="width:16%"><col style="width:30%"></colgroup>', $print);
        $this->assertStringContainsString("    text-align: left;\n    /* A long word or code breaks inside its cell", $css);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $css);
        $this->assertStringContainsString(".rep-paper table.rep-fixed {\n    table-layout: fixed;\n}", $css);
        // The report takes the named page's 186 mm, over the general 210 mm rule.
        $this->assertStringContainsString("    body.dp-printing .rep-paper {\n        max-width: 186mm !important;\n    }", $css);
        $this->assertStringContainsString("    @page rep-portrait {\n        size: A4 portrait;\n        margin: 12mm 12mm 14mm;", $css);
        // The Dokyu sheet's general print rules are untouched.
        $this->assertStringContainsString("    @page {\n        size: A4 portrait;\n        margin: 0;\n    }\n\n    html, body {\n        width: 210mm !important;", $css);
    }

    // ------------------------------------- 3. My certificates is always there

    public function test_my_certificates_shows_an_empty_message_and_is_linked_from_the_top(): void
    {
        $card = $this->source('resources/views/components/citizen/welfare-received.blade.php');
        $page = $this->source('resources/views/citizen/assistance-requests.blade.php');
        $js = $this->source('resources/js/welfare-citizen.js');

        $this->assertStringContainsString('<div x-data="citizenWelfare()" {{ $attributes }}>', $card);
        $this->assertStringNotContainsString('x-show="certificates.length" x-cloak {{ $attributes }}', $card);
        $this->assertStringContainsString('<section id="my-certificates"', $card);
        $this->assertStringContainsString('<x-ui.t fil="Wala pang sertipiko - lalabas ang mga ito rito kapag naibigay na." en="No certificates yet - they appear here once issued." />', $card);
        $this->assertStringContainsString('certificatesLoading: true,', $js);
        $this->assertStringContainsString('this.certificatesLoading = false;', $js);
        $this->assertStringContainsString('href="#my-certificates"', $page);
    }

    // ------------------------------------- 4. data requests: history first

    public function test_data_requests_list_the_history_first_and_open_the_privacy_tab_from_a_link(): void
    {
        $view = $this->source('resources/views/citizen/settings.blade.php');

        $this->assertStringContainsString("tab: ['notifications', 'security', 'privacy', 'preferences'].includes(new URLSearchParams(window.location.search).get('tab'))", $view);
        $this->assertStringContainsString('dataFormOpen: false,', $view);
        $this->assertStringContainsString('<form x-show="dataFormOpen" x-cloak', $view);
        $this->assertStringContainsString('<x-ui.t fil="Bagong hiling" en="New request" />', $view);
        $this->assertStringContainsString('<x-ui.t fil="Sagot ng LGU" en="The LGU\'s response" />', $view);
        // My data requests comes before the form.
        $this->assertLessThan(
            strpos($view, '<form x-show="dataFormOpen"'),
            strpos($view, '<x-ui.t fil="Aking mga hiling sa datos" en="My data requests" />'),
        );
    }

    // ------------------------------------- 5. Help: Report a Problem, no incident card

    public function test_help_names_report_a_problem_and_leaves_incidents_to_emergency(): void
    {
        $help = $this->source('resources/views/citizen/help.blade.php');

        $this->assertStringContainsString('<x-ui.t fil="Mag-ulat ng Problema" en="Report a Problem" />', $help);
        $this->assertStringNotContainsString('Submit a Support Ticket', $help);
        $this->assertStringNotContainsString('data-testid="help-incident-link"', $help);
        $this->assertStringNotContainsString("route('citizen.emergency') }}?report=1", $help);
    }

    // ------------------------------------- 6. no negative numbers on a Tulong form

    public function test_tulong_number_answers_are_zero_or_more(): void
    {
        $view = $this->source('resources/views/citizen/assistance-requests.blade.php');

        $this->assertStringContainsString(":min=\"f.type === 'number' && !String(f.key).endsWith('net_income') ? 0 : null\" :step=\"f.type === 'number' ? 'any' : null\"", $view);
        // A projected net income may be a loss, as the server allows (QA 2026-11-06).
        $this->assertStringContainsString('!mayBeNegative(f.key) && below(this.formData[f.key])', $view);
        $this->assertStringContainsString(":min=\"col.type === 'number' && !String(col.key).endsWith('net_income') ? 0 : null\"", $view);
        $this->assertStringContainsString('negativeNumbers() {', $view);
        $this->assertStringContainsString("(en ? 'Must be 0 or more: ' : 'Dapat 0 o higit pa: ')", $view);
    }

    // ------------------------------------- 7. resident PDF download never strands the page

    public function test_the_resident_pdf_download_cleans_up_and_gives_up_on_a_stalled_save(): void
    {
        $view = $this->source('resources/views/citizen/document-requests.blade.php');
        $start = strpos($view, 'async downloadDocument() {');
        $body = substr($view, $start, strpos($view, '// Deep-link landing', $start) - $start);

        $try = strpos($body, 'try {', strpos($body, 'loadHtml2Pdf'));
        $this->assertGreaterThan($try, strpos($body, "document.body.classList.add('dp-printing');"), 'dp-printing is added inside the try');
        $this->assertGreaterThan($try, strpos($body, 'await window.DokyuEditor.waitForImages(el);'));
        $this->assertStringContainsString('const PDF_TIMEOUT_MS = 30000;', $body);
        $this->assertStringContainsString('await Promise.race([saving, timeout]);', $body);
        $this->assertStringContainsString("clearTimeout(timer);\n                    try { restoreColors(); }", $body);
        $this->assertStringContainsString("document.body.classList.remove('dp-printing');", $body);
    }

    // ------------------------------------- 8. events with a start and end time

    public function test_staff_set_a_start_and_end_time_for_an_event(): void
    {
        $view = $this->source('resources/views/admin/communications.blade.php');
        $js = $this->source('resources/js/scope-admin-records.js');

        $this->assertStringContainsString('<x-ui.input name="event_start_time" type="time" label="Start time" x-model="form.start_time" />', $view);
        $this->assertStringContainsString('name="event_end_time" type="time" label="End time" x-model="form.end_time"', $view);
        $this->assertStringNotContainsString('x-model="form.time"', $view);
        $this->assertStringContainsString('Time on record: <span class="font-medium text-ink-body" x-text="legacyTime"></span>', $view);
        // An end needs a start and comes after it.
        $this->assertStringContainsString("if (f.end_time && !f.start_time) return 'Set a start time before the end time.';", $js);
        $this->assertStringContainsString("if (f.start_time && f.end_time && f.end_time <= f.start_time) return 'The end time must be after the start time.';", $js);
        // Sent on create, and on an edit only when either changed.
        $this->assertStringContainsString('start_time: f.start_time || null, end_time: f.start_time ? (f.end_time || null) : null,', $js);
        $this->assertStringContainsString('if (f.start_time !== this.timeBase.start || f.end_time !== this.timeBase.end) {', $js);
        $this->assertStringContainsString("form.date + 'T' + (form.start_time || '23:59')", $this->source('resources/views/admin/communications/event-form-extras.blade.php'));
    }

    public function test_residents_see_upcoming_events_by_their_end_time(): void
    {
        $js = $this->source('resources/js/scope-citizen.js');
        $dashboard = $this->source('resources/views/citizen/dashboard.blade.php');
        $events = $this->source('resources/views/citizen/events.blade.php');

        // Ended once now passes ends_at; a day-only fallback before the server sends it.
        $this->assertStringContainsString('if (!Number.isNaN(ends)) return Date.now() >= ends;', $js);
        $this->assertStringContainsString('isPast(e) { return eventEnded(e); },', $js);
        $this->assertStringContainsString("return end ? start + ' – ' + end : start;", $js);
        $this->assertStringContainsString('window.eventTimeRange = eventTimeRange;', $js);
        // Upcoming lists ask for upcoming=1 and keep from.
        $this->assertStringContainsString('params: { per_page: 4, upcoming: 1, from:', $js);
        $this->assertStringContainsString('params: { from, upcoming: 1, per_page: 3 }', $dashboard);
        // The Events page: the time range, and a subtitle that fits the whole month.
        $this->assertStringContainsString('x-text="event.timeLabel"', $events);
        $this->assertStringContainsString('subtitle="Municipal and barangay events."', $events);
        $this->assertStringContainsString("'events' => 'Mga kaganapan ng munisipyo at barangay.',", $this->source('resources/views/components/citizen/topbar.blade.php'));
    }

    // ------------------------------------- the pages still render

    public function test_the_changed_pages_render(): void
    {
        foreach (['/citizen/settings', '/citizen/help', '/citizen/assistance-requests', '/citizen/document-requests', '/citizen/events', '/citizen/dashboard', '/admin/sakuna', '/admin/communications'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
