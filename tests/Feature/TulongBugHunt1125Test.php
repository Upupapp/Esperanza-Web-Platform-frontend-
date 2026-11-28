<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The Tulong bug hunt of 25 Nov 2026 (owner: "you may fix them"): the staff
 * window shows only what the server allows, a reassessment starts from the
 * earlier one, and the Head's reason stays on screen.
 */
class TulongBugHunt1125Test extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_reject_on_a_document_is_shown_only_to_who_may_reject(): void
    {
        $admin = $this->source('resources/views/admin/assistance-requests.blade.php');
        $this->assertStringContainsString('<button type="button" x-show="may(\'reject\')" @click="startRequirementDecision(req, \'Rejected\')"', $admin);
    }

    public function test_import_and_record_given_follow_the_import_step_the_session_lists(): void
    {
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString('steps: raw.steps || null,', $app);
        $this->assertStringContainsString('canStep(submoduleKey, step) {', $app);
        $this->assertStringContainsString("if (s?.canStep) return this.keys.some((k) => s.canStep(k, 'import'));", $this->source('resources/js/request-import-ui.js'));
        $this->assertStringContainsString("canStep(typePerm(this.apType || { key: this.apKey }), 'import')", $this->source('resources/js/welfare-admin.js'));
    }

    public function test_a_returned_case_reopens_on_its_assessment_with_the_heads_reason(): void
    {
        $admin = $this->source('resources/views/admin/assistance-requests.blade.php');
        $this->assertStringContainsString("if (['Assessment', 'Returned for Reassessment'].includes(r.status)) {", $admin);
        $this->assertStringContainsString("if (this.assessNotes === '' && c.assessment_notes) this.assessNotes = c.assessment_notes;", $admin);
        $this->assertStringContainsString('<span class="font-semibold">Returned by the Head:</span>', $admin);
        $this->assertStringContainsString('remark: h.remark || null', $admin);
        $this->assertStringContainsString('x-show="h.remark" x-text="h.remark" data-history-remark', $admin);
    }

    public function test_a_case_is_cancelled_from_every_open_stage(): void
    {
        $transitions = config('esperanza_tulong_lifecycle.transitions');
        foreach (['Submitted', 'Initial Screening', 'Needs Requirements', 'Assessment', 'For Approval', 'Returned for Reassessment', 'Assistance Processing', 'Ready for Release'] as $from) {
            $this->assertContains('Cancelled', $transitions[$from], $from);
        }
        $this->assertContains('Rejected', $transitions['Returned for Reassessment']);
        $this->assertNotContains('Rejected', $transitions['Assistance Processing']);
    }

    public function test_reports_split_the_inbox_by_kind_count_clients_and_weigh_the_average_by_its_cases(): void
    {
        $dash = $this->source('resources/js/dashboards.js');
        $this->assertStringContainsString('INBOX_ICONS[item.group || item.key]', $dash);
        $this->assertStringContainsString('keys.includes(i.group || i.key)', $dash);
        $this->assertStringContainsString('valueBind="num(k.served_tulong ?? k.served)"', $this->source('resources/views/components/admin/dashboards/department.blade.php'));
        $this->assertStringContainsString('const weight = (t) => Number(t.avg_days_count ?? t.released) || 0;', $this->source('resources/views/admin/reports.blade.php'));
    }

    public function test_the_residents_dashboard_styles_every_tulong_status_and_counts_approvals_by_their_date(): void
    {
        $dashboard = $this->source('resources/views/citizen/dashboard.blade.php');
        foreach (config('esperanza_tulong_lifecycle.all') as $status) {
            $this->assertStringContainsString("'{$status}': ['", $dashboard, $status);
        }
        $this->assertStringContainsString(':class="tulongStyle(req.status)[0]" data-tulong-status', $dashboard);
        $this->assertStringContainsString('r.approved && manilaMonth(r.approved) === thisMonth', $dashboard);
    }

    public function test_the_residents_tulong_page_reads_in_their_language_and_says_what_happened(): void
    {
        $page = $this->source('resources/views/citizen/assistance-requests.blade.php');
        // C2 withdrawn, C6 presented, C7 toast, C10 Other, C11 every document, C13 chips, C14 label.
        $this->assertStringContainsString('en="You withdrew this request"', $page);
        $this->assertStringContainsString("this.lang('Presented at the office', 'Ipinakita sa opisina')", $page);
        $this->assertStringContainsString('Pindutin ang Isumite muli para maipadala sa opisina.', $page);
        $this->assertStringNotContainsString("hinihintay ang pagsusuri.') : this.lang('Document attached.'", $page);
        $this->assertStringContainsString("f.allowOther && /^others?$/i.test(String(v ?? ''))", $page);
        $this->assertStringContainsString('r.purpose = d.purpose ||', $page);
        $this->assertStringContainsString('$api.getAll(\'/citizen/papeles\')', $page);
        $this->assertStringContainsString("'Flagged for Replacement': this.lang('Needs a replacement', 'Kailangang palitan')", $page);
        $this->assertStringNotContainsString(': req.status)"', $page);
        $this->assertStringContainsString("lang('Received on ', 'Natanggap noong ')", $page);

        $scope = $this->source('resources/js/scope-citizen.js');
        $this->assertStringContainsString("!['session', 'counseling', 'referral'].includes(this.r?.assistance?.processing_kind)", $scope);
        $this->assertStringContainsString("window.reqKind?.(q.name) !== 'office'", $scope);
        $this->assertStringContainsString(".get('certificate')", $this->source('resources/js/welfare-citizen.js'));
    }
}
