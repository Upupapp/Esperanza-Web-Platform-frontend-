<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Sakuna (owner, 2026-11-03): the situation report's PDF and XLSX open with an
 * executive summary, key figures and charts read from the same export; the
 * FACED card is a document viewer that prints on margined A4 pages; the
 * BDRRMC capture forms are separate cards.
 */
class SakunaReportExportTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_both_exports_say_the_same_thing_from_the_same_export(): void
    {
        $report = $this->source('resources/js/sakuna-report.js');
        $insights = $this->source('resources/js/sakuna-insights.js');

        // One source for the words, the figures and the charts.
        $this->assertStringContainsString("const loadInsights = () => import('./sakuna-insights');", $report);
        $this->assertStringContainsString('const { executiveSummary, kpis: reportKpis, charts: reportCharts } = await loadInsights();', $report);
        $this->assertStringContainsString('summary: executiveSummary(d),', $report);
        $this->assertStringContainsString('executiveSummary(d).forEach((ptext)', $report);
        $this->assertStringContainsString('reportCharts(d).forEach((c) =>', $report);
        // Read from the export, never invented: an empty chart is left out.
        $this->assertStringContainsString('const add = (c) => { if (c.series.some((x) => x.values.some((v) => Number(v) > 0))) out.push(c); };', $insights);
        $this->assertStringContainsString("p1.push(scope(d) + ', no incidents were recorded.');", $insights);
        // Evacuation and relief are not narrowed by a type or status filter, and the reader is told.
        $this->assertStringContainsString('Evacuation and relief figures are not linked to a single incident', $insights);
    }

    public function test_the_workbook_has_native_charts_styles_and_formats(): void
    {
        $xlsx = $this->source('resources/js/xlsx-report.js');
        $report = $this->source('resources/js/sakuna-report.js');

        $this->assertStringContainsString('<c:barChart><c:barDir val="${horizontal ? \'bar\' : \'col\'}"/>', $xlsx);
        $this->assertStringContainsString('<c:val><c:numRef><c:f>${vRef}</c:f>', $xlsx, 'a chart reads its numbers from the sheet');
        $this->assertStringContainsString('<numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="#,##0.00"/>', $xlsx);
        $this->assertStringContainsString("return CFB.write(zip, { fileType: 'zip', type: 'array', compression: true });", $xlsx);
        $this->assertStringContainsString("const sum = { name: 'Executive Summary'", $report);
        $this->assertStringContainsString("const an = { name: 'Analytics'", $report);
    }

    public function test_the_pdf_puts_filters_summary_figures_and_charts_before_the_tables(): void
    {
        $pdf = $this->source('resources/js/table-pdf.js');

        $this->assertStringContainsString("    drawFilters(st, filters);\n    drawSummary(st, summary);\n    drawKpis(st, kpis);\n    drawCharts(st, charts);", $pdf);
        $this->assertStringContainsString("sectionHeading(st, 'Detailed statistics and supporting tables');", $pdf);
    }

    public function test_the_faced_card_fits_its_window_and_prints_on_margined_pages(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-evacuees.blade.php');
        $css = $this->source('resources/css/app.css');

        $this->assertStringContainsString('<div id="dp-print-area" class="doc-paper faced-sheet', $tab);
        $this->assertStringContainsString('max-h-full flex flex-col overflow-hidden', $tab, 'the window is no taller than the screen');
        $this->assertStringContainsString('<div class="flex-1 min-h-0 overflow-y-auto', $tab, 'a large family scrolls inside it');
        $this->assertStringContainsString('@click="printFacedCard()"', $tab);
        // On screen as tall as its content; printed on the reports' margined A4 page.
        $this->assertStringContainsString(".doc-paper.faced-sheet {\n    width: 100%;\n    max-width: 940px;\n    min-height: 0;", $css);
        $this->assertStringContainsString("body.dp-printing #dp-print-area.faced-sheet {\n        page: rep-portrait;", $css);
    }

    public function test_each_bdrrmc_form_is_its_own_card_and_still_opens(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');

        $this->assertStringContainsString('<div class="sk-tile sk-form-tile flex items-center gap-3 px-3.5 py-3">', $tab);
        $this->assertStringContainsString("@click=\"openForm = '{{ \$f['key'] }}'; fillOpen = false;", $tab);
    }
}
