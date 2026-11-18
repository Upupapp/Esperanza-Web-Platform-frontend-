<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * QA sweep 2026-11-18, assignment web-5: Sakuna's Casualties tab, the office
 * and barangay dashboards and their report sheet, the resident's Dashboard,
 * Dokyu and Events pages, and the Papeles document viewer.
 */
class Sweep1118web5Test extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** The text between two markers, so an assertion reads one function, not the whole file. */
    private function between(string $text, string $from, string $to): string
    {
        $start = strpos($text, $from);
        $this->assertNotFalse($start, "marker not found: {$from}");
        $end = strpos($text, $to, $start + strlen($from));
        $this->assertNotFalse($end, "end marker not found: {$to}");

        return substr($text, $start, $end - $start);
    }

    // ------------------------------------------------------------ Casualties

    /** The Incident select had only the newest hundred incidents: last quarter's typhoon could not be chosen. */
    public function test_the_casualty_form_offers_every_incident(): void
    {
        $js = $this->source('resources/js/sakuna-casualties.js');
        $load = $this->between($js, 'async loadIncidents(', 'async startAdd()');

        $this->assertStringContainsString("api.getAll('/admin/sakuna/incidents')", $load, 'every page, not one of 100');
        $this->assertStringNotContainsString('per_page: 100', $load);
        // The incident the link asked for is fetched on its own when the list lacks it.
        $this->assertStringContainsString("api.get('/admin/sakuna/incidents/' + encodeURIComponent(want))", $load);
        $this->assertStringContainsString('if (want && !list.some((i) => i.ref === want)) {', $load);

        $add = $this->between($js, 'async startAdd()', 'startEdit()');
        $this->assertStringContainsString("const want = this.f.incident || '';\n            await this.loadIncidents(want);", $add);
    }

    /** The province's name list: Peña came out as PeÃ±a, quotes broke columns, '=' ran as a formula. */
    public function test_the_casualty_csv_uses_the_safe_writer(): void
    {
        $js = $this->source('resources/js/sakuna-casualties.js');
        $this->assertStringContainsString("import { downloadCsv } from './resident-import';", $js);
        $this->assertStringContainsString("downloadCsv('casualty-list.csv', [keys, ...rows.map((r) => keys.map((k) => r[k]))]);", $js);
        $this->assertStringNotContainsString("this.exportCSV(rows, 'casualty-list.csv')", $js);

        // The writer it uses: a BOM, quotes doubled, a formula opened as text.
        $writer = $this->between($this->source('resources/js/resident-import.js'), 'const csvCell = (v) => {', 'export async function downloadTemplate');
        $this->assertStringContainsString('const safe = /^[=+\-@\t\r]/.test(s) ? "\'" + s : s;', $writer);
        $this->assertStringContainsString('safe.replace(/"/g, \'""\')', $writer);
        $this->assertStringContainsString("const text = '\u{FEFF}' + rows.map((r) => r.map(csvCell).join(',')).join('\\r\\n');", $writer);
    }

    /** A quiet reload with the old filters, answering last, put every category back under 'Dead'. */
    public function test_the_casualty_list_shows_only_the_latest_answer(): void
    {
        $js = $this->source('resources/js/sakuna-casualties.js');
        $load = $this->between($js, 'async load(page = 1, quiet = false) {', 'loadSummary(quiet = false) {');

        $this->assertStringContainsString('const seq = ++this.loadSeq;', $load);
        $this->assertStringContainsString("const res = await api.get('/admin/sakuna/missing', { params });\n                if (seq !== this.loadSeq) return;", $load);
        $this->assertStringContainsString('if (quiet || seq !== this.loadSeq) return;', $load);
        $this->assertStringContainsString('} finally { if (seq === this.loadSeq) this.loading = false; }', $load);

        $summary = $this->between($js, 'loadSummary(quiet = false) {', 'setCategory(c)');
        $this->assertStringContainsString('const seq = ++this.summarySeq;', $summary);
        $this->assertStringContainsString('.then((r) => { if (seq !== this.summarySeq) return;', $summary);

        // And a live reload waits for a load the officer asked for.
        $this->assertStringContainsString('|| this.saving || this.loading });', $js);
    }

    // ------------------------------------------------------------ Dashboards

    /** A live reload during a period change dropped the new answer and, failing, left the old figures. */
    public function test_a_live_reload_waits_for_the_dashboard_load_the_officer_asked_for(): void
    {
        $js = $this->source('resources/js/dashboards.js');
        $this->assertSame(2, substr_count($js, "if (quiet && this.loading) { this.liveAfter = true; return; }\n"));
        $this->assertSame(2, substr_count($js, 'if (this.liveAfter) { this.liveAfter = false; this.load(true); }'));

        foreach (['export function deptDashboard()', 'export function brgyDashboard()'] as $board) {
            $part = substr($js, strpos($js, $board));
            $load = $this->between($part, 'async load(quiet = false) {', 'catch (e) {');
            // The guard comes before the sequence moves on, or it would still drop the visible answer.
            $this->assertLessThan(strpos($load, 'const mine = ++this.seq;'), strpos($load, 'if (quiet && this.loading)'), $board);
            $this->assertStringContainsString('liveAfter: false,', $part);
        }
    }

    /** A slower earlier report arriving last replaced the one asked for; Print then sent the wrong key. */
    public function test_the_report_sheet_shows_only_the_latest_report(): void
    {
        $js = $this->source('resources/js/dashboards.js');
        $show = $this->between($js, 'async show(detail) {', 'query() {');

        $this->assertStringStartsWith("async show(detail) {\n            const mine = ++this.seq;", $show);
        $this->assertStringContainsString("{ params: this.query() });\n                if (mine !== this.seq) return;\n                this.report = res.data;", $show);
        $this->assertStringContainsString("} catch (e) {\n                if (mine !== this.seq) return;", $show);
        $this->assertStringContainsString("} finally {\n                if (mine === this.seq) {\n                    this.loading = false;", $show);
    }

    // ------------------------------------------------------------ Resident Dashboard

    /** A failed load said 'You have no requests yet', zero tiles and no emergency alerts. */
    public function test_the_resident_dashboard_says_when_a_part_did_not_load(): void
    {
        $page = $this->source('resources/views/citizen/dashboard.blade.php');
        $this->assertStringContainsString('failed: { requests: false, balita: false, events: false, alerts: false },', $page);
        foreach (['requests', 'balita', 'events'] as $part) {
            $this->assertStringContainsString("finally { if (loaded || !quiet) this.failed.{$part} = !loaded; }", $page, $part);
        }
        $this->assertStringContainsString('if (loaded || !quiet) this.failed.alerts = !loaded;', $page);

        // The empty states only when the list really is empty.
        $this->assertStringContainsString('x-show="!requests.length && !failed.requests"', $page);
        $this->assertStringContainsString('x-show="!balitaPreview.length && !failed.balita"', $page);
        $this->assertStringContainsString('x-show="!events.length && !failed.events"', $page);
        // An error with Try again in their place.
        $this->assertStringContainsString('retryExpr="loadRequests()"', $page);
        $this->assertStringContainsString('retryExpr="loadBalita()"', $page);
        $this->assertStringContainsString('retryExpr="loadEvents()"', $page);
        $this->assertStringContainsString('<div x-show="failed.alerts" x-cloak role="alert"', $page);
        $this->assertStringContainsString('@click="loadAlerts()"', $page);
        // A dash in the tiles, never a zero.
        $this->assertStringContainsString("valueBind=\"failed.requests ? '—' : kpi.dokyu\"", $page);
        $this->assertStringContainsString("valueBind=\"failed.requests ? '—' : kpi.tulong\"", $page);
    }

    /** 'Lahat ng Dokyu · Lahat ng Tulong' stuck out of the card at phone width. */
    public function test_the_recent_requests_header_wraps_on_a_phone(): void
    {
        $page = $this->source('resources/views/citizen/dashboard.blade.php');
        $header = $this->between($page, 'Wraps on a phone', 'Mga Kamakailang Kahilingan');
        $this->assertStringContainsString('<div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-5 pt-4 pb-2">', $header);
    }

    // ------------------------------------------------------------ Dokyu

    /** Track Request's copy left out the form number and the signature block the wizard showed. */
    public function test_the_issued_copy_keeps_its_form_number_and_signatures(): void
    {
        $page = $this->source('resources/views/citizen/document-requests.blade.php');
        $this->assertStringContainsString('printByKey: @js($printByKey),', $page);
        $this->assertSame(2, substr_count($page, '...(this.printByKey['), 'both ways resolvedDocument returns');
        foreach (['formNo', 'officiant', 'officiantTitle', 'technician', 'technicianTitle', 'verifier', 'verifierTitle'] as $prop) {
            $this->assertStringContainsString("{$prop}=\"resolvedDocument.{$prop}\"", $page, $prop);
            $this->assertStringNotContainsString("{$prop}=\"selectedRequest.{$prop}\"", $page, $prop);
        }

        // Every form that prints a number or signatures is found by its docKey.
        $lookup = collect(config('esperanza_document_type_lookup'))->pluck('docKey')->unique();
        $printed = collect(config('esperanza_municipal_documents.documents'))
            ->filter(fn ($d) => ! empty($d['formNo']) || ! empty($d['officiant']) || ! empty($d['technician']) || ! empty($d['verifier']));
        $this->assertNotEmpty($printed);
        foreach ($printed as $d) {
            $this->assertTrue($lookup->contains($d['key']), $d['key'].' has a document template');
        }
    }

    /** 'Landline No. (if any)' took (056) 333-1000 as a bad mobile number and blocked Continue. */
    public function test_a_landline_is_not_held_to_the_mobile_number_rule(): void
    {
        $page = $this->source('resources/views/citizen/document-requests.blade.php');
        $this->assertStringContainsString(".filter((f) => f.type === 'tel' && !this.isLandline(f))", $page);
        $this->assertStringContainsString(":maxlength=\"f.type === 'tel' ? (isLandline(f) ? 60 : 11) : null\"", $page);
        $this->assertStringContainsString("formData[f.key] = isLandline(f) ? \$event.target.value.replace(", $page);

        // The page's own test, run on the real forms' telephone fields.
        $this->assertSame(1, preg_match("/isLandline\\(f\\) \\{\\s*return f\\?\\.type === 'tel' && (\\/.+?\\/i)\\.test\\(/", $page, $m));
        $pattern = $m[1];
        $tel = collect(config('esperanza_municipal_documents.documents'))
            ->flatMap(fn ($d) => $d['fields'] ?? [])
            ->filter(fn ($f) => ($f['type'] ?? '') === 'tel')
            ->mapWithKeys(fn ($f) => [$f['key'] => $f['label'] ?? '']);
        $landline = fn (string $key) => preg_match($pattern, $key.' '.($tel[$key] ?? '')) === 1;

        $this->assertTrue($tel->has('landline_no') && $landline('landline_no'));
        $this->assertTrue($tel->has('org_tel_no') && $landline('org_tel_no'));
        foreach (['mobile_no', 'contact_number', 'emergency_contact_number', 'partner_contact'] as $mobile) {
            if ($tel->has($mobile)) {
                $this->assertFalse($landline($mobile), $mobile.' is still a mobile number');
            }
        }
    }

    /** Who to Contact gave BPLO 333-1050 and OSCA 333-1088 while the directory said 333-1078 and 333-1085. */
    public function test_who_to_contact_reads_the_government_directory(): void
    {
        $page = $this->source('resources/views/citizen/document-requests.blade.php');
        $this->assertStringContainsString("try { this.directory = (await \$api.get('/directory')).data || []; } catch { this.directory = null; }", $page);
        $this->assertStringContainsString("this.proofOpen = false;\n                this.loadDirectory();", $page);
        $this->assertStringContainsString("const entry = name ? (this.directory || []).find((d) => d.contact && (d.office || '').trim().toLowerCase() === name) : null;", $page);
        $this->assertStringNotContainsString('pickupDirectory', $page);
        $this->assertStringNotContainsString('Municipal Hall Trunkline', $page, 'no guessed number');
        $this->assertStringContainsString("route('citizen.directory')", $page);
    }

    // ------------------------------------------------------------ Events

    /** A five-line agenda of 90 characters was cut at three lines with no Read more. */
    public function test_read_more_shows_whenever_the_description_is_cut(): void
    {
        $page = $this->source('resources/views/citizen/events.blade.php');
        $this->assertStringNotContainsString('event.description.length > 160', $page);
        $this->assertStringContainsString('x-data="{ clamped: false }"', $page);
        $this->assertStringContainsString('x-effect="if (!open[event.ref]) $nextTick(() => { clamped = $el.scrollHeight > $el.clientHeight + 1 })"', $page);
        $this->assertStringContainsString('x-show="event.description && (open[event.ref] || clamped || event.description.split(String.fromCharCode(10)).length > 3)"', $page);
    }

    /** Two quick Next clicks let December's late answer land under January. */
    public function test_a_late_month_answer_is_dropped(): void
    {
        $page = $this->source('resources/views/citizen/events.blade.php');
        $load = $this->between($page, 'async load() {', 'async init() {');
        $this->assertStringStartsWith("async load() {\n                const mine = ++this.seq;", $load);
        $this->assertStringContainsString("{ params: { month: monthParam } });\n                    if (mine !== this.seq) return;", $load);
        $this->assertStringContainsString("} catch {\n                    if (mine !== this.seq) return;", $load);
        $this->assertStringContainsString('if (mine === this.seq) this.loading = false;', $load);
    }

    /** A tapped empty day, or Barangay Only, said 'No events this month' beside a calendar full of dots. */
    public function test_the_empty_list_says_why_it_is_empty(): void
    {
        $page = $this->source('resources/views/citizen/events.blade.php');
        $empty = $this->between($page, 'Why it is empty', '</p>');
        $this->assertStringContainsString('en="No events on this day."', $empty);
        $this->assertStringContainsString('en="No events in your barangay on this day."', $empty);
        $this->assertStringContainsString('en="No events in your barangay this month."', $empty);
        $this->assertStringContainsString('<span x-show="!selectedDate && !(myBarangayOnly && events.length)"><x-ui.t fil="Walang kaganapan sa buwang ito." en="No events this month." /></span>', $empty);
    }

    // ------------------------------------------------------------ Papeles

    /** The resident window's focus trap sent focus back to the eye button: no reason could be typed. */
    public function test_the_papeles_viewer_has_its_own_focus_trap(): void
    {
        $page = $this->source('resources/views/admin/papeles.blade.php');
        $overlay = $this->between($page, 'data-modal-overlay x-show="previewDoc"', 'role="dialog" aria-modal="true"');
        $this->assertStringContainsString('x-trap="!!previewDoc"', $overlay);
        // The viewer is outside the resident window, which is why it needs one.
        $this->assertGreaterThan(strpos($page, '</x-ui.modal>'), strpos($page, 'data-modal-overlay x-show="previewDoc"'));
        $this->assertStringContainsString('x-trap="open"', $this->source('resources/views/components/ui/modal.blade.php'));
        $this->assertGreaterThan(strpos($page, 'x-trap="!!previewDoc"'), strpos($page, 'id="papeles-return-reason"'));
        $this->assertGreaterThan(strpos($page, 'x-trap="!!previewDoc"'), strpos($page, 'id="papeles-archive-reason"'));
    }
}
