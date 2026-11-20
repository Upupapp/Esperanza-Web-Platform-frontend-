<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The last small fixes from the 20 Nov 2026 picture review: a report's
 * figures, PDF cells and Excel summary; the import history's plurals; and one
 * wording for dates, "Naitala ng" and time-ago on the resident's side.
 */
class FlowsFinalFixesTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_a_report_tile_figure_stays_on_one_line_and_its_pdf_and_excel_read_cleanly(): void
    {
        $sheet = $this->source('resources/views/components/admin/report-sheet.blade.php');
        $css = $this->source('resources/css/app.css');
        $js = $this->source('resources/js/dashboards.js');
        $xlsx = $this->source('resources/js/xlsx-report.js');

        // "₱423,000.0 / 0": the figure never breaks; a long one is smaller.
        $this->assertStringContainsString("String(t.value).length >= 10 && 'rep-tile-value-long'", $sheet);
        $this->assertStringContainsString(".rep-tile .rep-tile-value {\n    white-space: nowrap;", $css);
        $this->assertStringContainsString('.rep-tiles-7 .rep-tile-value-long,', $css);

        // PDF (2026-11-21): html2canvas measures its text baseline with a probe
        // image in the LIVE page, so the probe is kept inline there while it
        // draws (a style in its copy never reached it); a table that runs on
        // repeats its header row; the report code loads with the PDF only.
        $pdf = $this->source('resources/js/report-pdf.js');
        $this->assertStringContainsString('img[src^="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP"]', $pdf);
        $this->assertStringContainsString("style.textContent = PROBE + ' { display: inline !important; } body > div:has(> ' + PROBE + '):not(#_):not(#_):not(#_) { display: block !important; }';", $pdf);
        // The citizen slip and the certificates draw under body.dp-printing, which hid the probe (text drawn high).
        foreach (['resources/js/scope-citizen.js', 'resources/js/welfare-certificate.js'] as $file) {
            $src = $this->source($file);
            $this->assertStringContainsString("[html2pdf, { withTrueBaseline }] = await Promise.all([window.loadHtml2Pdf(), import('./report-pdf')]);", $src);
            $this->assertStringContainsString('await withTrueBaseline(() => html2pdf().set({', $src);
        }
        $this->assertStringContainsString('document.head.appendChild(style);', $pdf);
        $this->assertStringContainsString('if (thead) next.appendChild(thead.cloneNode(true));', $pdf);
        $this->assertStringContainsString("import('./report-pdf')", $js);
        $this->assertStringNotContainsString("from './report-pdf'", $this->source('resources/js/app.js').$js);
        $this->assertStringContainsString('paginate(this.prop.container, this.prop.pageSize.inner.px.height);', $js);
        $this->assertStringNotContainsString('img { display: inline-block; }', $js);

        // Excel: each summary paragraph and each part's note one merged wrapped
        // cell; amounts in pesos, the at-a-glance amount a number too.
        $this->assertStringContainsString('aoa.push([{ v: String(p), s: S.para }]);', $js);
        $this->assertStringContainsString('aoa.push([{ v: String(s.note), s: S.note }]);', $js);
        $this->assertStringContainsString("const merges = paras.map(([row]) => 'A' + (row + 1) + ':' + colName(widest - 1) + (row + 1));", $js);
        $this->assertStringContainsString('s: m[1] ? S.peso : S.num }', $js);
        $this->assertStringContainsString('aoa[row][col] = { v: Number(v), s: S.peso };', $js);
        $this->assertStringNotContainsString('(line + \' \' + word).length > 110', $js);
        $this->assertStringContainsString('peso: 12 };', $xlsx);
        $this->assertStringContainsString('<numFmt numFmtId="167" formatCode="&quot;₱&quot;#,##0.00"/>', $xlsx);
        $this->assertStringContainsString('<cellXfs count="13">', $xlsx);
        $this->assertSame(13, substr_count(substr($xlsx, strpos($xlsx, '<cellXfs'), strpos($xlsx, '</cellXfs>') - strpos($xlsx, '<cellXfs')), '<xf '));
    }

    public function test_the_import_history_says_one_resident_and_one_record(): void
    {
        $modal = $this->source('resources/views/admin/partials/modal-request-import.blade.php');
        $this->assertStringContainsString("(h.linked === 1 ? ' linked to a resident · ' : ' linked to residents · ')", $modal);
        $this->assertStringContainsString("=== 1 ? ' record archived' : ' records archived')", $modal);
    }

    public function test_the_residents_side_has_one_wording_for_dates_recorded_by_and_time_ago(): void
    {
        $records = $this->source('resources/js/citizen-records.js');
        $this->assertStringContainsString("'Naitala ng ' + q.office", $records);
        foreach (['resources/js/citizen-records.js', 'resources/js/guardian-scan.js'] as $path) {
            $this->assertStringNotContainsString('Itinala', $this->source($path), $path);
        }

        $notifications = $this->source('resources/views/citizen/notifications.blade.php');
        $this->assertStringContainsString("hrs + ' oras ang nakalipas'", $notifications);

        // Short month names are English in both languages, as in the app.
        foreach (['resources/js/scope-citizen.js', 'resources/js/citizen-events.js', 'resources/js/welfare-citizen.js', 'resources/js/citizen-shelter.js'] as $path) {
            $this->assertStringNotContainsString("'fil-PH'", $this->source($path), $path);
        }
        $this->assertStringNotContainsString("'fil-PH', { month: 'short'", $this->source('resources/views/citizen/dashboard.blade.php'));
    }
}
