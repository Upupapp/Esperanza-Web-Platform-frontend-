<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The offices' emailed papers (public/documents/email documents, 2026-11-03):
 * Barangay Labangtaytay's certificates, profile and officials; the MHRMO and
 * MGSO internal forms with their office parts, printed as their papers.
 */
class OfficePapersTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    private function barangayDocument(string $key): array
    {
        return collect(config('esperanza_barangay_documents.labangtaytay.documents'))->firstWhere('key', $key);
    }

    public function test_each_certificate_signs_as_its_paper(): void
    {
        $cert = $this->source('resources/views/components/citizen/barangay-certificate-preview.blade.php');

        $this->assertStringContainsString("'Resident Signature'", $cert, 'Clearance and Business Clearance');
        $this->assertStringContainsString('Right Thumbmark', $cert);
        $this->assertStringContainsString('Certified by:', $cert, 'Death and Late Registration');
        $this->assertStringContainsString('(Residency certificate valid three (3) months from the date of issuance)', $cert);
        $this->assertStringContainsString('(Indigency certificate valid three (3) months from the date of issuance)', $cert);
        $this->assertStringContainsString('This certification is valid for one (1) year from the date of issuance.', $cert);
        $this->assertStringContainsString('Signed this <span x-text="issuanceDate"></span>', $cert, 'the Jobseeker certificate is signed, not issued');
        $this->assertStringContainsString('LATE REGISTRATION of his/her birth', $cert, "the paper's 'DEATH REGISTRATION of the child's birth' slip");
        $this->assertStringContainsString('Issued this <span x-text="issuanceDate"></span>, upon request of the interested party for the', $cert, 'Death carries its date');
        $this->assertStringNotContainsString('record file', $cert);
        $this->assertStringNotContainsString('Non-Transferrable', $cert);
        // The date the copy was issued, not the day it is reopened.
        $this->assertStringContainsString('d.issued_on', $cert);
        // The admin preview shows the barangay's own seal.
        $this->assertStringContainsString('seal="barangayCatalog[selected.barangay]?.seal || null"', $this->source('resources/views/admin/document-requests.blade.php'));
    }

    public function test_the_certificates_ask_what_their_papers_ask(): void
    {
        $this->assertSame(['Medical Assistance/Financial Assistance', 'Burial Assistance', 'Educational Assistance'],
            collect($this->barangayDocument('brgy_indigency')['fields'])->firstWhere('key', 'purpose')['options']);
        $this->assertNotContains('Others', collect($this->barangayDocument('brgy_clearance')['fields'])->firstWhere('key', 'purpose')['options'],
            'Others is the typed box, not a ninth tick');
        $this->assertSame(['capital_figures', 'capital_words'], array_values(array_filter(
            array_column($this->barangayDocument('brgy_business_clearance')['fields'], 'key'), fn ($k) => str_starts_with($k, 'capital'))));
        // A typed "Others" joins the list on a paper with no Others tick, in the preview too.
        $wizard = $this->source('resources/views/citizen/document-requests.blade.php');
        $this->assertStringContainsString("[...list, 'Others: ' + v]", $wizard);
        $this->assertStringContainsString('bind="previewData"', $wizard);

        $lookup = config('esperanza_document_type_lookup');
        $this->assertSame(3, $lookup['Barangay Residency']['validityMonths']);
        $this->assertSame(12, $lookup['First Time Jobseeker Certificate (RA 11261)']['validityMonths']);
        $this->assertArrayNotHasKey('certSubtitle', $lookup['Barangay Certification (Late Registration)']);
    }

    public function test_labangtaytay_officials_follow_their_portraits(): void
    {
        $profile = config('esperanza_barangay_documents.labangtaytay');
        $roster = collect($profile['officialsRoster'])->keyBy('name');

        $this->assertSame('SK Chairperson', $roster['Hon. John Lester T. Conde']['position']);
        $this->assertSame('Barangay Kagawad', $roster['Hon. Maria Remjie R. Abilong']['position']);
        foreach ($profile['officialsRoster'] as $o) {
            $this->assertFileExists(public_path($o['photo']), $o['name']);
        }
        $this->assertSame('920', $profile['history']['facts'][0]['value']);
        $this->assertCount(7, $profile['history']['awards']['items']);
        $this->assertStringContainsString('data-barangay-awards', $this->source('resources/views/citizen/directory.blade.php'));
    }

    public function test_internal_forms_follow_the_office_papers(): void
    {
        $offices = config('esperanza_internal_forms.offices');
        $this->assertSame(25, array_sum(array_map(fn ($o) => count($o['forms']), $offices)));

        $types = ['text', 'number', 'date', 'time', 'textarea', 'select', 'checkbox', 'checkbox-group', 'table'];
        foreach (['hr', 'gso'] as $office) {
            foreach ($offices[$office]['forms'] as $form) {
                $this->assertMatchesRegularExpression('/^submit/i', $form['process'][0], $form['key']);
                $fields = $form['fields'];
                foreach (array_slice($form['process'], 1) as $step) {
                    $fields = [...$fields, ...(is_array($step) ? ($step['fields'] ?? []) : [])];
                }
                foreach ($fields as $f) {
                    $this->assertContains($f['type'] ?? 'text', $types, "{$form['key']}.{$f['key']}");
                }
                $this->assertSame(count($fields), count(array_unique(array_column($fields, 'key'))), "{$form['key']} keys are unique");
            }
        }

        // Every type the papers need draws, a filed form opens as its paper and prints.
        $input = $this->source('resources/views/components/admin/internal-forms/field-input.blade.php');
        foreach (["f.type === 'textarea'", "'time'", "f.type === 'checkbox'"] as $t) {
            $this->assertStringContainsString($t, $input);
        }
        $page = $this->source('resources/views/admin/internal-forms.blade.php');
        $this->assertStringContainsString('@click="openView(s)"', $page);
        $this->assertStringContainsString('printPaper()', $page);
        $this->assertStringContainsString('data: Object.keys(filled).length ? filled : undefined', $page, "a step's own part goes with the decision");
        $this->assertStringContainsString('Other entries (earlier version of this form)', $page);
        // A Department Head is told what the server shows them: their office's view.
        $this->assertStringContainsString('x-show="isAuthority && !isOfficeHead && ownBarangay === null"', $page);
        // A wide field spans the row without adding columns to a one-column grid (the approval dialog).
        $this->assertStringContainsString("? 'col-span-full' : ''", $this->source('resources/views/components/admin/internal-forms/field-input.blade.php'));
        $this->assertStringContainsString('paperSignatures(viewing)', $this->source('resources/views/components/admin/internal-forms/paper.blade.php'));
    }
}
