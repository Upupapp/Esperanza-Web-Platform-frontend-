<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Owner, 2026-11-20 (flows review): the Tulong page exports the office's
 * standard Summary Report (dept_summary: figures, graphs, executive summary),
 * Tulong only; imported past records count by their real date everywhere and
 * can be left out or shown alone (Source); they came in by import, never
 * "Online"; a case returned for reassessment sits on Assessment.
 */
class TulongReportAndSourcesTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_tulong_page_downloads_the_standard_summary_report(): void
    {
        $page = $this->source('resources/views/admin/assistance-requests.blade.php');
        $modal = $this->source('resources/views/admin/partials/modal-tulong-report.blade.php');
        $js = $this->source('resources/js/dashboards.js');
        $app = $this->source('resources/js/app.js');

        $this->assertStringContainsString("@include('admin.partials.modal-tulong-report')", $page);
        // The preview, PDF, Excel and print of the dashboards, on this page too.
        $this->assertStringContainsString('<x-admin.report-sheet />', $page);
        $this->assertStringContainsString('x-data="tulongReport()"', $modal);
        $this->assertStringContainsString('<x-admin.dashboards.period-picker />', $modal);
        $this->assertStringContainsString('x-model="source"', $modal);
        $this->assertStringContainsString("Alpine.data('tulongReport', tulongReport);", $app);
        // The Summary Report, narrowed to Tulong, with the program and source.
        $this->assertStringContainsString("openReport({ key: 'dept_summary', office: this.office, from: this.from, to: this.to, type: 'tulong', service: this.service || '', source: this.source || '' });", $js);
        // Offices with Tulong programs whose reports the account may open.
        $this->assertStringContainsString("(o) => o.can_view && (o.types || []).includes('tulong')", $js);
        // The sheet sends the kind and source on preview, print and Excel.
        $this->assertStringContainsString("['office', 'barangay', 'service', 'type', 'sex', 'age', 'sector', 'source']", $js);
        // Imported past records are mostly last year's.
        $this->assertStringContainsString("{ key: 'last_year', label: 'Last year' }", $js);
        $this->assertStringContainsString("if (key === 'last_year') return { from: ymd(t.y - 1, 1, 1), to: ymd(t.y - 1, 12, 31) };", $js);
    }

    public function test_imported_past_records_have_their_own_source_and_channel(): void
    {
        $js = $this->source('resources/js/dashboards.js');
        $dept = $this->source('resources/views/components/admin/dashboards/department.blade.php');
        $reports = $this->source('resources/views/admin/reports.blade.php');

        $this->assertStringContainsString("const SOURCE_LABELS = { filed: 'Filed in Esperanza', imported: 'Imported past records' };", $js);
        $this->assertStringContainsString("['service', 'sex', 'age', 'sector', 'source'].forEach", $js);
        $this->assertStringContainsString("import: { label: 'Imported past records', color: '#64748b' },", $js);
        $this->assertStringContainsString('id="dept-source" x-model="source"', $dept);
        $this->assertStringContainsString('<x-ui.compact-select id="pr-source" label="Source" model="source"', $reports);
        $this->assertStringContainsString("service = ''; sex = ''; age = ''; sector = ''; source = ''", $reports);
    }

    public function test_a_case_returned_for_reassessment_sits_on_assessment(): void
    {
        $page = $this->source('resources/views/citizen/assistance-requests.blade.php');

        $this->assertStringContainsString("const side = { 'Needs Requirements': 'Initial Screening', 'Returned for Reassessment': 'Assessment' };", $page);
    }

    public function test_the_registry_note_says_held_changes_wait(): void
    {
        $profile = $this->source('resources/views/citizen/profile.blade.php');

        $this->assertStringContainsString('and any changes you send are kept and applied once they confirm.', $profile);
        $this->assertStringContainsString('at ang mga babaguhin mo ay itatabi muna at ilalagay kapag nakumpirma na.', $profile);
    }
}
