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
        // One paper drawing serves all six forms (2026-10-27); its rows are removed the same way.
        $this->assertStringContainsString('@click="removeRow(openForm, slot.i)"', $this->source('resources/views/admin/partials/sakuna/bdrrmc-paper.blade.php'));
    }

    public function test_the_evacuee_profile_keeps_families_of_five_apart_and_shows_the_recorded_headcount(): void
    {
        $heading = $this->source('resources/views/admin/partials/sakuna/bdrrmc-paper-heading.blade.php');
        $paper = $this->source('resources/views/admin/partials/sakuna/bdrrmc-paper.blade.php');
        $spec = $this->source('resources/js/bdrrmc-paper.js');

        // The paper's two lines, top right; families of five in neither until the LGU rules.
        $this->assertStringContainsString("familySizes().lt5 + (fiveRule() === 'fewer' ? familySizes().eq5 : 0)", $heading);
        $this->assertStringContainsString("familySizes().gt5 + (fiveRule() === 'more' ? familySizes().eq5 : 0)", $heading);
        $this->assertStringContainsString('Families with exactly 5 members:', $paper);
        $this->assertStringContainsString('This report is provisional.', $paper);
        // The recorded headcount is a note under the table, never lost; not a column (owner, 2026-10-27).
        $this->assertStringContainsString('Recorded headcount: <strong x-text="recordedHeadcount()"></strong>', $paper);
        $this->assertStringNotContainsString('Recorded Headcount', $spec);
        // The computed Total M / F / Grand Total cells.
        $this->assertStringContainsString("if (type === 'sum-m') return this.sexTotal(r, 'm');", $this->source('resources/js/sakuna-bdrrmc.js'));
        $this->assertStringContainsString("group('Total', [leaf('M', 'tm', { type: 'sum-m'", $spec);
    }

    /** The six forms on the files Barangay Labangtaytay sent (2026-10-27). */
    public function test_the_forms_follow_the_barangays_files(): void
    {
        $spec = $this->source('resources/js/bdrrmc-paper.js');
        $heading = $this->source('resources/views/admin/partials/sakuna/bdrrmc-paper-heading.blade.php');

        // Human, housing and flooding: outer wall and others parts.
        foreach (["'wall_partial'", "'wall_total'", "'other_partial'", "'other_total'"] as $key) {
            $this->assertStringContainsString($key, $spec);
        }
        $this->assertStringContainsString("group('Outer Wall',", $spec);
        // Agriculture: area with and without a chance of recovery; loss in metric tons.
        $this->assertStringContainsString("'area_recoverable'", $spec);
        $this->assertStringContainsString("'area_unrecoverable'", $spec);
        $this->assertStringNotContainsString('loss_recovery', $spec);
        // Business: the damage described with its quantity.
        $this->assertStringContainsString("leaf('Merchandise', 'merch', { small: true, sub: '(Qty,Desc", $spec);
        $this->assertStringContainsString("n) (Total/Partial)'", $spec);
        // A TOTAL row on each damage form.
        $this->assertSame(4, substr_count($spec, "totals: { label: 'TOTAL'"));
        // The relief sheet: the date of occurrence apart from the distribution date.
        $this->assertStringContainsString('model="distributedOn"', $heading);
        $this->assertStringContainsString('Republic of the Philippines', $heading);
        $this->assertStringContainsString('BARANGAY DISASTER RISK REDUCTION AND MANAGEMENT COMMITTEE', $heading);
        // Fishing and the relief sheet print portrait, as on paper.
        $this->assertSame(2, substr_count($spec, "orientation: 'portrait'"));
        // The real officials' names on the files stay off the forms.
        foreach (['CACHILA', 'BRUZA'] as $name) {
            foreach ([$spec, $heading, $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php')] as $source) {
                $this->assertStringNotContainsString($name, $source);
            }
        }
    }

    public function test_the_relief_sheet_leaves_the_signature_for_paper(): void
    {
        $spec = $this->source('resources/js/bdrrmc-paper.js');
        $paper = $this->source('resources/views/admin/partials/sakuna/bdrrmc-paper.blade.php');

        $this->assertStringNotContainsString('signed on release', $paper);
        // One column, as on the paper: no staff tick standing in for the beneficiary.
        $this->assertStringNotContainsString("'received'", $spec);
        $this->assertStringContainsString("leaf('Signature/', 'sign', { type: 'sign', sub: 'Thumbmark Received Family Food Packs in Good Condition'", $spec);
        $this->assertStringContainsString('signed on the printed sheet', $paper);
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
