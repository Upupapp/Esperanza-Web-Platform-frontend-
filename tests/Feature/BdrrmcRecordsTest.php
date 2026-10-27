<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The Profile of Evacuees (DROMIC Form 1.2) and the Relief Distribution Sheet
 * filled from records, saved forms reopened, and the officer who files a form
 * kept from approving it (owner, 2026-10-27, Phase 1). The backend refuses
 * self-approval and writes where saved rows came from; these hold the portal
 * to its side of it.
 */
class BdrrmcRecordsTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_two_forms_fill_from_the_records_and_nothing_else_does(): void
    {
        $js = $this->source('resources/js/sakuna-bdrrmc.js');
        $view = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');

        $this->assertStringContainsString("'/admin/sakuna/bdrrmc-forms/evacuees/draft?'", $js);
        $this->assertStringContainsString("'/admin/sakuna/bdrrmc-forms/rds/draft?'", $js);
        $this->assertStringContainsString("@click=\"fillFromRecords('evacuees')\"", $view);
        $this->assertStringContainsString("@click=\"fillFromRecords('rds')\"", $view);
        foreach (['human-housing', 'agriculture', 'fishing', 'business'] as $other) {
            $this->assertStringNotContainsString("fillFromRecords('{$other}')", $view, "{$other} is not automated yet");
        }
        // Loaded with the Sakuna pages, and merged into the tab's component.
        $this->assertStringContainsString("area(/^\\/admin\\/sakuna(\\/|$)/.test(areaPath), () => import('./sakuna-bdrrmc'));", $this->source('resources/js/app.js'));
        $this->assertStringContainsString('...(window.bdrrmcRecords ? bdrrmcRecords() : {}),', $view);
        // Drafting needs the filing permission and view on the records read.
        $this->assertStringContainsString("s.canPerm('sakuna_damage', 'create') && s.canPerm(key === 'rds' ? 'sakuna_relief' : 'sakuna_evacuees', 'view')", $js);
    }

    public function test_save_sends_where_the_rows_came_from_and_only_the_forms_columns(): void
    {
        $js = $this->source('resources/js/sakuna-bdrrmc.js');
        $view = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');

        // Still the one submit route, and still a plain print.
        $this->assertStringContainsString("'/admin/sakuna/bdrrmc-forms/' + key + '/submit'", $view);
        $this->assertStringContainsString("source: this.sourcePayload ? this.sourcePayload(key, rows) : { kind: 'manual' },", $view);
        $this->assertStringContainsString('rows: rows.map((r) => (this.cleanRow ? this.cleanRow(r) : r)),', $view);
        $this->assertStringContainsString("Object.entries(r).filter(([k]) => !k.startsWith('_'))", $js);
        $this->assertStringContainsString('row_refs: rows.map((r) => (r._src === undefined || r._src === null ? null : d.sources[r._src].ref)),', $js);
        // A drafted row left out needs a reason, saved with the form.
        $this->assertStringContainsString('this.drafted[key].removed.push({ ref: src.ref, reason });', $js);
        $this->assertStringContainsString("@click=\"removeRow('evacuees', ri)\"", $view);
        $this->assertStringContainsString("@click=\"removeRow('rds', ri)\"", $view);
    }

    public function test_the_evacuee_profile_keeps_families_of_five_apart_and_shows_the_recorded_headcount(): void
    {
        $view = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');

        $this->assertStringContainsString("familySizes().lt5 + (fiveRule() === 'fewer' ? familySizes().eq5 : 0)", $view);
        $this->assertStringContainsString("familySizes().gt5 + (fiveRule() === 'more' ? familySizes().eq5 : 0)", $view);
        $this->assertStringContainsString('Families with exactly 5 members:', $view);
        $this->assertStringContainsString('This report is provisional.', $view);
        $this->assertStringContainsString('Recorded Headcount', $view);
        $this->assertStringContainsString("x-text=\"sexTotal(r, 'm')\"", $view);
        $this->assertStringContainsString("x-text=\"sexTotal(r, 'f')\"", $view);
    }

    /** The six forms on the files Barangay Labangtaytay sent (2026-10-27). */
    public function test_the_forms_follow_the_barangays_files(): void
    {
        $view = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');

        // Human, housing and flooding: outer wall and other parts.
        foreach (['r.wall_partial', 'r.wall_total', 'r.other_partial', 'r.other_total'] as $model) {
            $this->assertStringContainsString('x-model.number="'.$model.'"', $view);
        }
        $this->assertStringContainsString('>Outer Wall</th>', $view);
        // Agriculture: area with and without a chance of recovery; loss in metric tons.
        $this->assertStringContainsString('x-model="r.area_recoverable"', $view);
        $this->assertStringContainsString('x-model="r.area_unrecoverable"', $view);
        $this->assertStringNotContainsString('r.loss_recovery', $view);
        $this->assertStringNotContainsString('r.area_affected', $view);
        // Business: the damage described with its quantity, not a Total/Partial choice alone.
        $this->assertStringContainsString('x-model="r.merch" placeholder="e.g. 10 sacks rice, Total"', $view);
        // A TOTAL row on each damage form.
        foreach (['human-housing', 'agriculture', 'fishing', 'business'] as $form) {
            $this->assertStringContainsString("formTotal('{$form}', ", $view, $form);
        }
        // The relief sheet: the date of occurrence apart from the distribution date.
        $this->assertStringContainsString('x-model="distributedOn"', $view);
        $this->assertStringContainsString('Republic of the Philippines', $view);
        $this->assertStringContainsString('Barangay Disaster Risk Reduction and Management Committee', $view);
        // Fishing and the relief sheet print portrait, as on paper.
        $this->assertSame(2, substr_count($view, 'data-print-orientation="portrait"'));
        // The real officials' names on the files stay off the forms.
        foreach (['CACHILA', 'BRUZA'] as $name) {
            $this->assertStringNotContainsString($name, $view);
        }
    }

    public function test_the_relief_sheet_leaves_the_signature_for_paper(): void
    {
        $view = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');

        $this->assertStringNotContainsString('signed on release', $view);
        // One column, as on the paper: no staff tick standing in for the beneficiary.
        $this->assertStringNotContainsString('x-model="r.received"', $view);
        $this->assertStringContainsString('Received Family Food Packs in Good Condition', $view);
        $this->assertStringContainsString('signed on the printed sheet', $view);
    }

    public function test_a_saved_form_reopens_as_it_was_saved_and_only_another_officer_approves_it(): void
    {
        $js = $this->source('resources/js/sakuna-bdrrmc.js');
        $view = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');

        $this->assertStringContainsString("api.get('/admin/sakuna/bdrrmc-forms/submissions/' + encodeURIComponent(sub.ref))", $js);
        // The columns it was filed against, before today's template.
        $this->assertStringContainsString('const cols = [...(v.meta?.definition?.columns || this.definitionsByKey?.[v.form]?.columns || [])];', $js);
        // A form saved under older columns still shows every column it holds.
        $this->assertStringContainsString("if (!known.has(k)) { known.add(k); cols.push({ key: k, label: k.replace(/_/g, ' ') }); }", $js);
        $this->assertStringContainsString('@click="viewSubmission(sub)"', $view);
        $this->assertStringContainsString('<template x-if="viewing">', $view);
        // Approve shows only where the server says this viewer may.
        $this->assertStringContainsString('x-show="sub.can_approve" @click="approve(sub)"', $view);
        $this->assertStringContainsString('x-show="viewing.can_approve" @click="approve(viewing)"', $view);
        $this->assertStringNotContainsString("x-show=\"canApprove && sub.status === 'Submitted'\"", $view);
    }
}
