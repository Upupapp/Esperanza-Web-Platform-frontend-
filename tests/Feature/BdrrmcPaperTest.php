<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The six BDRRMC capture forms follow Barangay Labangtaytay's paper files
 * (public/documents/email documents/LABANGTATAY BDRRMC), owner decisions of
 * 2026-10-27: the paper's wording (two spelling corrections only), A4 in the
 * file's orientation, the files' rows, column groups, highlights, TOTAL rows
 * and signature lines; the recorded headcount as a note, not a column; one
 * estimated damage on the housing form; Prepared by left blank; and filling
 * in, printing and reprinting drawn from one description.
 */
class BdrrmcPaperTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    private function spec(): string
    {
        return $this->source('resources/js/bdrrmc-paper.js');
    }

    private function heading(): string
    {
        return $this->source('resources/views/admin/partials/sakuna/bdrrmc-paper-heading.blade.php');
    }

    public function test_the_wording_is_the_papers_with_only_the_two_approved_corrections(): void
    {
        $all = $this->spec().$this->heading().$this->source('resources/views/admin/partials/sakuna/bdrrmc-paper.blade.php');

        // Corrected (owner, 2026-10-27).
        $this->assertStringNotContainsString('ASSESMENT FORM', $all);
        $this->assertStringContainsString('CALAMITY/ DAMAGES CAPTURE AND ASSESSMENT FORM', $all);
        $this->assertStringNotContainsString("'Assign Kagawad", $all);
        $this->assertStringContainsString("prepared: 'Assigned Kagawad (Purok)'", $this->spec());
        // Kept as printed.
        $this->assertStringContainsString("group('No. of Individual',", $this->spec());
        $this->assertStringContainsString("group('Others Parts',", $this->spec());
        $this->assertStringContainsString('BARANGAY DISASTER AND RISK REDUCTION MANAGEMENT COMMITTEE', $this->heading());
        $this->assertSame(3, substr_count($this->heading(), 'BARANGAY DISASTER RISK REDUCTION AND MANAGEMENT COMMITTEE'));
        $this->assertStringContainsString('Dromic form 1.2', $this->heading());
        $this->assertStringContainsString('PROFILE OF EVACUEES INSIDE THE EVACUATION CENTER', $this->heading());
        $this->assertStringContainsString('CALAMITY DAMAGES CAPTURED ASSESSMENT FORM', $this->heading());
        $this->assertStringContainsString("'Barangay ' + ({$this->brgy()} || '__________') + ',Esperanza,Masbate'", $this->heading());
        $this->assertStringContainsString('RELIEF DISTRIBUTION SHEET (RDS)', $this->heading());
        $this->assertStringContainsString('*Levels of flood height: (1) Ankle; (2) knee; (3) waist; (4) chest; (5) head', $this->source('resources/views/admin/partials/sakuna/bdrrmc-paper.blade.php'));
        $this->assertStringContainsString('Note: List should be by families (Family Head and Dependents)', $this->source('resources/views/admin/partials/sakuna/bdrrmc-paper.blade.php'));
    }

    private function brgy(): string
    {
        // The heading is drawn for a form being filled in and for a saved one.
        return '{{ $brgy }}';
    }

    public function test_each_form_is_an_a4_sheet_in_the_files_orientation_with_its_rows(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');
        $this->assertStringContainsString('@page { size: A4 landscape;', $tab);
        $this->assertStringContainsString('@page bdrrmc-portrait { size: A4 portrait;', $tab);
        $this->assertSame(4, substr_count($this->spec(), "orientation: 'landscape'"));
        $this->assertSame(2, substr_count($this->spec(), "orientation: 'portrait'"));
        // 20 numbered rows a sheet; 25 on the relief sheet; a further sheet when full.
        $this->assertSame(5, substr_count($this->spec(), 'rows: 20,'));
        $this->assertSame(1, substr_count($this->spec(), 'rows: 25,'));
        $this->assertStringContainsString('const count = Math.max(1, Math.ceil(all.length / n));', $this->source('resources/js/sakuna-bdrrmc.js'));
        // The right-hand No. column on the four forms that have it.
        $this->assertSame(4, substr_count($this->spec(), "leaf('No.', 'no2'"));
        // The files' highlights.
        $this->assertSame(5, substr_count($this->spec(), "leaf('HH Code', 'hh', { hl: 'yellow'"));
        $this->assertStringContainsString("leaf('Business Code', 'code', { hl: 'green'", $this->spec());
    }

    public function test_the_housing_form_has_one_estimated_damage_and_the_profile_no_headcount_column(): void
    {
        $this->assertStringContainsString("leaf('Estimated Damage (PhP)', 'est_damage', { type: 'num', w: 69.9 })", $this->spec());
        $this->assertStringNotContainsString('est_partial', $this->spec());
        $this->assertStringNotContainsString('est_total', $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php'));
        $this->assertStringNotContainsString('Headcount', $this->spec());
    }

    public function test_prepared_by_is_left_blank_to_be_written_in(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');
        $this->assertStringContainsString("this.signoff.prepared = '';", $tab);
        $this->assertStringNotContainsString("this.signoff.prepared = account.name || '';", $tab);
        // Still editable, and still required before the form is saved.
        $this->assertStringContainsString('x-model="signoff[s.model]"', $this->source('resources/views/components/admin/sakuna/bdrrmc-signoff.blade.php'));
        $this->assertStringContainsString("if (!this.signoff.prepared.trim()) return fail('Type the name of the person who prepared the form.');", $tab);
    }

    public function test_filling_printing_and_reprinting_use_one_drawing(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');
        $this->assertStringContainsString("@include('admin.partials.sakuna.bdrrmc-paper', ['mode' => 'edit'])", $tab);
        $this->assertStringContainsString("@include('admin.partials.sakuna.bdrrmc-paper', ['mode' => 'view'])", $tab);
        // A form saved under older columns keeps its plain table.
        $this->assertStringContainsString('<template x-if="!paperFits()">', $tab);
        // Printed as written on paper: nothing for no choice, the flood level as its number.
        $js = $this->source('resources/js/sakuna-bdrrmc.js');
        $this->assertStringContainsString("text.textContent = s.value === '' ? '' : (s.dataset.print === 'value' ? s.value : (s.options[s.selectedIndex]?.text || ''));", $js);
        $this->assertStringContainsString('data-print="value"', $this->source('resources/views/admin/partials/sakuna/bdrrmc-paper.blade.php'));
        // The barangay chosen by Fill from records stays shown (and printed) on the redrawn sheet.
        $this->assertStringContainsString(':selected="b.name === barangay"', $this->source('resources/views/components/admin/sakuna/paper-field.blade.php'));
    }
}
