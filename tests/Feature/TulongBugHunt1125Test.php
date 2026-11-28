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
}
