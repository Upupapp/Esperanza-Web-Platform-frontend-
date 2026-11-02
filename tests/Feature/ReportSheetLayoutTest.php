<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The printed reports and the Reports & Analytics page (user, 2026-11-03):
 * the figures at a glance on one row, an executive summary of at least two
 * paragraphs on every report, a margin at the top of every PDF page, and a
 * clearer page - each picker beside the reports it is for, the analytics only
 * under Analytics.
 */
class ReportSheetLayoutTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_every_report_has_its_summary_one_row_of_tiles_and_a_margin_on_every_page(): void
    {
        $sheet = $this->source('resources/views/components/admin/report-sheet.blade.php');
        $css = $this->source('resources/css/app.css');
        $js = $this->source('resources/js/dashboards.js');

        // The executive summary, written by the server, under the period.
        $this->assertStringContainsString('<section class="rep-summary" x-show="(report.summary || []).length">', $sheet);
        $this->assertStringContainsString('<h2>Executive Summary</h2>', $sheet);
        $this->assertStringContainsString('<template x-for="(p, pi) in (report.summary || [])" :key="pi">', $sheet);
        $this->assertLessThan(strpos($sheet, 'x-for="(s, si) in report.sections"'), strpos($sheet, 'rep-summary'), 'before the sections');
        $this->assertStringContainsString('.rep-summary p {', $css);

        // One row of tiles, however many; smaller type from five on.
        $this->assertStringContainsString(":class=\"'rep-tiles-' + Math.min(s.rows.length, 8)\" :style=\"'grid-template-columns: repeat(' + Math.max(s.rows.length, 1) + ', minmax(0, 1fr))'\"", $sheet);
        foreach (['.rep-tiles-5 .rep-tile-value {', '.rep-tiles-6 .rep-tile-value {', '.rep-tiles-7 .rep-tile-value,'] as $rule) {
            $this->assertStringContainsString($rule, $css);
        }
        $this->assertStringContainsString('min-height: 2.5em;', $css, 'every value on the same line');

        // Every PDF page has its top and bottom margin, and is numbered.
        $this->assertStringContainsString('margin: this.landscape ? [10, 0, 12, 0] : [12, 0, 14, 0],', $js);
        $this->assertStringContainsString("Object.assign(copy.style, { paddingTop: '0', paddingBottom: '0', boxShadow: 'none', borderRadius: '0' });", $js);
        $this->assertStringContainsString("doc.text('Page ' + i + ' of ' + pages, w - 14, h - 6, { align: 'right' });", $js);
        $this->assertStringNotContainsString("margin: 0,\n                    filename: (this.report.key", $js);

        // Excel has the summary too, and so does a Data Export's PDF.
        $this->assertStringContainsString("aoa.push(['Executive Summary']);", $js);
        $table = $this->source('resources/js/table-pdf.js');
        $this->assertStringContainsString("doc.text('Executive Summary', margin, st.y + 3);", $table);
        $this->assertStringContainsString('drawSummary(st, summary);', $table);
        $this->assertStringContainsString('summary: Array.isArray(data?.summary) ? data.summary : [],', $this->source('resources/views/admin/reports.blade.php'));
    }

    public function test_the_reports_page_puts_each_picker_beside_its_reports_and_keeps_analytics_apart(): void
    {
        $page = $this->source('resources/views/admin/reports.blade.php');
        $reports = substr($page, strpos($page, '<!-- Reports -->'), strpos($page, '<!-- Analytics -->') - strpos($page, '<!-- Reports -->'));
        $analytics = substr($page, strpos($page, '<!-- Analytics -->'));

        // The period first, for every report; the office (and what it is
        // narrowed to) above the office reports, the barangay above its own.
        $this->assertLessThan(strpos($reports, "group in [{ key: 'office'"), strpos($reports, '<x-admin.dashboards.period-picker />'));
        $this->assertStringContainsString('Applies to every report below.', $reports);
        // x-if: each picker exists once on the page (one #pr-office).
        $this->assertSame(2, substr_count($reports, "<template x-if=\"group.key === 'office'\">"), 'the office and what it is narrowed to');
        $this->assertSame(1, substr_count($reports, "<template x-if=\"group.key === 'barangay'\">"));
        $this->assertSame(1, substr_count($reports, 'id="pr-office"'));

        // The analytics are read when Analytics opens, not on every visit
        // (they used up the report rate limit before a preview).
        $this->assertStringContainsString("this.\$watch('tab', (t) => { if (t === 'analytics' && !this.analyticsLoaded) this.loadAnalytics(); });", $page);
        $this->assertStringNotContainsString("finally { this.loading = false; }\n                this.loadAnalytics();", $page);

        // The internal forms and incidents are analytics, not under Reports.
        $this->assertStringNotContainsString('Internal Forms &amp; Incident Reports', $reports);
        $this->assertStringContainsString('Internal Forms &amp; Incident Reports', $analytics);

        // Plain figures: all time, working days, "Same day", no rows of zeros.
        $this->assertStringContainsString('sublabel="Dokyu + Tulong, all time"', $page);
        $this->assertStringContainsString('valueBind="avgResolutionText"', $page);
        $this->assertStringContainsString("if (d < 0.5) return 'Same day';", $page);
        $this->assertStringContainsString('return rows.filter((r) => r.requests > 0)', $page);
        $this->assertStringContainsString("' barangays have') + ' no requests on file.'", $page);
    }
}
