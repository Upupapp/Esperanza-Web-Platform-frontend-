<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The MSWD's own forms, checked into the catalog (2026-10-26): the Solo
 * Parent ID (DSWD Annex B, 2023) with its category documents, the PRPWD
 * and FACED items the catalog lacked, the OSCA attestation step, and the
 * citizen wizards that ask only what applies and fill the family from the
 * Resident Profile.
 */
class MswdoFormsTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    private function document(string $key): array
    {
        return collect(config('esperanza_municipal_documents.documents'))->firstWhere('key', $key);
    }

    public function test_the_solo_parent_id_follows_annex_b(): void
    {
        $spic = $this->document('msw_solo_parent_id');
        $this->assertNotNull($spic, 'the Solo Parent ID is a Dokyu service');
        $this->assertSame('msw_pwd_id', $spic['submodule'], 'it shares the MSWDO ID desk permissions');
        $fields = collect($spic['fields'])->keyBy('key');

        $this->assertSame(['New', 'Renewal', 'Lost ID (replacement)'], $fields['application_type']['options']);
        $this->assertCount(13, $fields['solo_parent_category']['options'], 'the 13 categories of the second sheet');
        foreach (['(a1)', '(a7)', '(b1)', '(b2)', '(c)', '(d)', '(e)', '(f)'] as $code) {
            $this->assertNotEmpty(collect($fields['solo_parent_category']['options'])->filter(fn ($o) => str_starts_with($o, $code)), $code);
        }
        $this->assertSame('table', $fields['family_composition']['type']);
        $this->assertTrue($fields['certify_and_consent']['required']);

        // Each category's own documents: a widow owes the spouse's death
        // certificate, an unmarried parent the CENOMAR, only when new or renewed.
        $reqs = collect($spic['requirements'])->filter(fn ($r) => is_array($r))->keyBy('label');
        $death = $reqs['Death certificate of the spouse']['showIf'];
        $this->assertSame(['field' => 'solo_parent_category', 'contains' => '(a2)'], $death[1]);
        $this->assertSame(['New', 'Renewal'], $reqs['Certificate of No Marriage (CENOMAR)']['showIf'][0]['contains']);
        $this->assertSame('Lost', $reqs['Affidavit of loss of the Solo Parent ID']['showIf']['contains']);
    }

    public function test_the_pwd_id_and_faced_have_the_items_their_papers_have(): void
    {
        $pwd = collect($this->document('msw_pwd_id')['fields'])->keyBy('key');
        foreach (['ethnic_group', 'landline_no', 'mobile_no', 'email', 'employment_category', 'employment_type', 'org_affiliated'] as $key) {
            $this->assertArrayHasKey($key, $pwd->all(), "PRPWD item {$key}");
        }
        $this->assertSame('select', $pwd['occupation']['type'], 'the PRPWD occupation groups');
        $this->assertSame(['field' => 'employment_status', 'contains' => 'Employed'], $pwd['employment_type']['showIf']);

        $faced = collect(collect(config('esperanza_tulong_types.types'))->firstWhere('key', 'tulong_food')['fields'])->keyBy('key');
        foreach (['district', 'occupation', 'house_block_lot', 'street', 'zip_code'] as $key) {
            $this->assertArrayHasKey($key, $faced->all(), "FACED item {$key}");
        }
        $this->assertTrue($faced['data_privacy_consent']['required'], 'item 33, the data privacy declaration');

        $this->assertContains('Barangay Captain Attestation', $this->document('osca_senior_citizen')['process']);
    }

    public function test_the_wizards_ask_only_what_applies_and_fill_the_family(): void
    {
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString('function showIfMet(rule, answers) {', $app);
        $this->assertStringContainsString('if (Array.isArray(rule)) return rule.every((r) => showIfMet(r, answers));', $app);
        $this->assertStringContainsString('window.prefillFamilyTables = (fields, formData) => {', $app);
        $this->assertStringContainsString("familyLoad = api.get('/citizen/resident-profile')", $app);
        $this->assertStringContainsString('marital_status: account.civilStatus,', $app, 'the OSCA form fills from the profile');

        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/citizen/{$page}.blade.php");
            $this->assertStringContainsString('get shownReqs() {', $view, $page);
            $this->assertStringContainsString('<template x-for="({ req, i }) in shownReqs" :key="i">', $view, $page);
            $this->assertStringContainsString('window.prefillFamilyTables(', $view, $page);
            $this->assertStringContainsString('return window.showIfMet(f?.showIf, this.formData);', $view, $page);
        }
        // Dokyu draws a family table now (it skipped every table before).
        $dokyu = $this->source('resources/views/citizen/document-requests.blade.php');
        $this->assertStringContainsString("filter(f => f.type === 'table' && fieldShown(f))", $dokyu);
        $this->assertStringContainsString("'requirementRules' => array_values(array_map(", $dokyu);
    }
}
