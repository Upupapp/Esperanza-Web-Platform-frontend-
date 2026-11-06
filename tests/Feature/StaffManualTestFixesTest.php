<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Sprint 4: the staff-side fixes from the owner's manual test (2026-11-06).
 * Each test pins the cause that was traced, so it cannot quietly return.
 */
class StaffManualTestFixesTest extends TestCase
{
    private function source(string $path): string
    {
        return str_replace("\r\n", "\n", file_get_contents(base_path($path)));
    }

    /** The JavaScript method body from `name(` up to the next method at the same indent. */
    private function method(string $path, string $signature): string
    {
        $src = $this->source($path);
        $start = strpos($src, $signature);
        $this->assertNotFalse($start, $signature.' exists in '.$path);
        $end = strpos($src, "\n            },\n", $start);

        return substr($src, $start, $end - $start);
    }

    // ------------------------------------------- 1. requirement rows redraw

    public function test_a_requirement_row_is_keyed_by_its_status_so_an_approval_redraws_it(): void
    {
        foreach (['resources/views/admin/document-requests.blade.php', 'resources/views/admin/assistance-requests.blade.php'] as $view) {
            $src = $this->source($view);
            $this->assertStringContainsString('<template x-for="req in (selected.requirements || [])" :key="req.key + \':\' + req.status">', $src, $view);
            $this->assertStringNotContainsString('<template x-for="req in (selected.requirements || [])" :key="req.key">', $src, $view);
        }
    }

    // ------------------------------------------- 2. Download PDF never strands the page

    public function test_download_pdf_always_clears_the_print_mode_and_gives_up_on_a_stalled_save(): void
    {
        $view = 'resources/views/admin/document-requests.blade.php';
        $body = $this->method($view, 'async downloadDocument() {');

        // dp-printing, the image wait and the colour swap are all inside the try,
        // so the finally removes dp-printing whatever throws.
        $try = strpos($body, "                try {\n                    document.body.classList.add('dp-printing');");
        $this->assertNotFalse($try, 'dp-printing is added inside the try');
        $this->assertSame(1, substr_count($body, "classList.add('dp-printing')"));
        $this->assertGreaterThan($try, strpos($body, 'await window.DokyuEditor.waitForImages(el);'));
        $this->assertGreaterThan($try, strpos($body, 'restoreColors = window.DokyuEditor.sanitizeExportColors(document.body);'));
        $this->assertStringContainsString("} finally {\n                    clearTimeout(timer);", $body);
        $this->assertStringContainsString("document.body.classList.remove('dp-printing');\n                }", $body);

        // A save() that never settles loses a race against the timeout into the Print fallback.
        $this->assertStringContainsString('const PDF_TIMEOUT_MS = 30000;', $body);
        $this->assertStringContainsString('await Promise.race([saving, timeout]);', $body);
        $this->assertStringContainsString('Unable to download the PDF. Please try Print and choose Save as PDF.', $body);

        // Closing the document, or Escape, also clears it.
        $src = $this->source($view);
        $this->assertStringContainsString("docPreviewOpen = v; if (!v) { document.body.classList.remove('dp-printing');", $src);
        $this->assertStringContainsString('@keydown.escape.window="document.body.classList.remove(\'dp-printing\')"', $src);
    }

    // ------------------------------------------- 3. Payments opens the request

    public function test_view_full_application_opens_the_request_not_the_payment(): void
    {
        $src = $this->source('resources/views/admin/payments.blade.php');

        $this->assertStringContainsString('x-show="payments[selected].request_ref"', $src);
        $this->assertStringContainsString("(/^TUL-/i.test(payments[selected].request_ref) ? '/admin/assistance-requests' : '/admin/document-requests') + '?ref=' + encodeURIComponent(payments[selected].request_ref)", $src);
        $this->assertStringNotContainsString('encodeURIComponent(payments[selected].ref)', $src);
    }

    // ------------------------------------------- 4. case notes on every type (see WelfareRoundTwoTest)

    public function test_case_notes_are_on_every_tulong_type_and_staff_only(): void
    {
        $js = $this->source('resources/js/welfare-admin.js');
        $this->assertStringContainsString("cnShown() {\n            return !!this.selected;\n        },", $js);
        $this->assertStringContainsString('cnHint() {', $js);
        $this->assertStringContainsString('Staff only: the resident does not see these notes.', $this->source('resources/views/admin/tulong/case-notes.blade.php'));
    }

    // ------------------------------------------- 5. no negative numbers in Apply

    public function test_apply_number_fields_refuse_a_negative(): void
    {
        $apply = $this->source('resources/views/admin/tulong/apply.blade.php');
        $this->assertStringContainsString(":min=\"f.type === 'number' ? (f.min ?? (String(f.key).endsWith('net_income') ? null : 0)) : null\" :step=\"f.type === 'number' ? (f.step ?? 'any') : null\"", $apply);
        $this->assertStringContainsString(":min=\"col.type === 'number' ? (col.min ?? (String(col.key).endsWith('net_income') ? null : 0)) : null\" :step=\"col.type === 'number' ? (col.step ?? 'any') : null\"", $apply);
        // A projected net income may be a loss, as the server allows (QA 2026-11-06).
        $js = $this->source('resources/js/welfare-admin.js');
        $this->assertStringContainsString("if (f.type === 'number' && !mayBeNegative(f.key) && negative(this.apForm[f.key])) return f.label + ' must be 0 or more.';", $js);
        $this->assertStringContainsString("return f.label + ', ' + col.label + ' must be 0 or more.';", $js);
        $this->assertStringContainsString("return 'The amount must be 0 or more.';", $js);
    }

    // ------------------------------------------- 6. finishing a session is one confirm

    public function test_finish_session_opens_its_confirm_once_and_hides_while_it_is_open(): void
    {
        $desk = $this->source('resources/views/admin/tulong/session-desk.blade.php');

        $this->assertStringContainsString('<div id="sd-finish-panel" x-show="sdPanel === \'finish\'"', $desk);
        $this->assertStringContainsString("x-show=\"ses && sdScheduled() && sdCan('release') && sdPanel !== 'finish'\"", $desk);
        $this->assertStringContainsString("document.getElementById('sd-finish-panel')?.scrollIntoView(", $desk);
        $this->assertStringContainsString('>Finish session…</x-ui.button>', $desk);
        // The one button that finishes is the panel's own.
        $this->assertSame(1, substr_count($desk, '>Finish and issue certificates</x-ui.button>'));
    }

    // ------------------------------------------- 7. Papeles finds a resident in any barangay

    public function test_papeles_searches_every_barangay_from_the_folder_grid(): void
    {
        $src = $this->source('resources/views/admin/papeles.blade.php');

        $this->assertStringContainsString("globalSearch: '',", $src);
        $this->assertStringContainsString('get globalResults() {', $src);
        $this->assertStringContainsString('this.$store.session.inScope(d.citizen_barangay)', $src);
        $this->assertStringContainsString("openFromSearch(c) { this.openBarangay(c.barangay); this.globalSearch = ''; this.openCitizen(c.id); },", $src);
        $this->assertStringContainsString('data-testid="papeles-global-search"', $src);
        $this->assertStringContainsString('placeholder="Find a resident in any barangay: name or account number..."', $src);

        $this->get('/admin/papeles')->assertOk()->assertSee('papeles-global-search', false);
    }

    // ------------------------------------------- 8. Appointments: today, and what is coming

    public function test_appointments_shows_the_day_beside_the_upcoming_soonest_first(): void
    {
        $js = $this->source('resources/js/scope-admin-requests.js');
        $this->assertStringContainsString("api.get('/admin/appointments', { params: { from: addDays(manilaToday(), 1), status: ACTIVE.join(','), per_page: 20 } })", $js);
        $this->assertStringContainsString("rows.sort((a, b) => (String(a.date) + ' ' + String(a.start_time || '')).localeCompare(String(b.date) + ' ' + String(b.start_time || '')));", $js);
        // An action on the left refreshes the right.
        $this->assertStringContainsString("this.loadBookings(true);\n                this.loadUpcoming(true);", $js);

        $view = $this->source('resources/views/components/admin/scope/appointments.blade.php');
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_minmax(280px,360px)]', $view);
        $this->assertStringContainsString("(date === today ? 'No appointments today' : 'No appointments on this day.')", $view);
        $this->assertStringContainsString('data-testid="appt-upcoming"', $view);
        $this->assertStringContainsString('@click="openDay(b)"', $view);
        // Day navigation and the Schedules tab stay.
        $this->assertStringContainsString('@click="shiftDate(-1)"', $view);
        $this->assertStringContainsString("@click=\"show('schedules')\"", $view);

        $this->get('/admin/appointments')->assertOk()->assertSee('appt-upcoming', false);
    }
}
