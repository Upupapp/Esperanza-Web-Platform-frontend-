<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Casualties (client request, 2026-11-14): the Missing Persons tab grown into
 * Sakuna's casualty record - the dead, the injured and the missing, people
 * from other towns and cases outside Esperanza - in the same tab, never a new
 * sidebar entry, and never a delete.
 */
class SakunaCasualtiesUiTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_tab_records_the_dead_injured_and_missing_from_anywhere(): void
    {
        $page = $this->source('resources/views/admin/sakuna.blade.php');
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-missing.blade.php');
        $js = $this->source('resources/js/sakuna-casualties.js');

        // The same tab, renamed; no 13th tab.
        $this->assertStringContainsString("['key' => 'missing-persons',    'label' => 'Casualties',", $page);
        $this->assertStringContainsString('x-data="sakunaCasualties"', $tab);
        $this->assertStringContainsString("import('./sakuna-casualties')", $this->source('resources/js/app.js'));

        // Who: a resident, someone from another town, or not known yet; where: in or outside Esperanza.
        foreach (['Lives in Esperanza', 'Not from Esperanza', 'Not known yet', 'In Esperanza', 'Outside Esperanza', 'Body reference no.', 'Death registry no. (civil registrar)', 'Source of data (who reported or confirmed it)'] as $words) {
            $this->assertStringContainsString($words, $tab);
        }
        $this->assertStringContainsString("['deceased', 'Dead', 'The person died']", $js);
        $this->assertStringContainsString("['injured', 'Injured', 'Hurt or fell ill']", $js);
        // A case outside Esperanza is recorded without an incident.
        $this->assertStringContainsString("api.post('/admin/sakuna/missing', this.body())", $js);
        $this->assertStringContainsString("if (f.place_scope === 'esperanza' && !f.incident && !this.editing) need.push('the incident');", $js);
    }

    public function test_validation_consent_and_the_casualty_list(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-missing.blade.php');
        $js = $this->source('resources/js/sakuna-casualties.js');

        $this->assertStringContainsString("@click=\"setValidation('Validated')\"", $tab);
        $this->assertStringContainsString("'/validation'", $js);
        // Balita only with the reporting party's written consent, and never for an injured person.
        $this->assertStringContainsString("x-show=\"open && open.kind !== 'injured'\"", $tab);
        $this->assertStringContainsString('x-show="open && open.post_consent && open.status !== \'Closed\'', $tab);
        $this->assertStringContainsString("'/consent'", $js);
        // The NDRRMC name list, for those who may edit cases.
        $this->assertStringContainsString("api.get('/admin/sakuna/missing/export'", $js);
        $this->assertStringContainsString("'City/Municipality'", $js);
        $this->assertStringContainsString("x-show=\"can('sakuna_missing','edit')\" role=\"group\" aria-label=\"Casualty list\"", $tab);
        // Archive only: nothing here deletes a case.
        $this->assertStringNotContainsString('api.delete(', $js);
    }

    /**
     * Decided 2026-11-16 (NDRRMC MC 19 s.2016; NPC Circular 2023-06): Validated
     * names its document; casualties count where it happened, so residents hurt
     * or killed outside Esperanza are shown apart; the list carries the Mayor's
     * and the MHO's signature lines; the switch waits for the approved PIA.
     */
    public function test_validation_document_count_where_it_happened_and_the_pia_gate(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-missing.blade.php');
        $js = $this->source('resources/js/sakuna-casualties.js');
        $pdf = $this->source('resources/js/table-pdf.js');

        $this->assertStringContainsString('<template x-for="b in (open?.validation_bases || [])"', $tab);
        $this->assertStringContainsString(':disabled="saving || !validBasis || !validNote.trim()"', $tab);
        $this->assertStringContainsString('validation_basis: validated ? this.validBasis || null : null,', $js);

        $this->assertStringContainsString('x-show="summary?.elsewhere?.total"', $tab);
        $this->assertStringContainsString('x-show="open && open.counted === false"', $tab);
        $this->assertStringContainsString('parts: here.length && away.length ? [{ title: awayTitle, note: awayNote, rows: away }] : [],', $js);
        $this->assertStringContainsString("{ label: 'Countersigned by:', position: 'Municipal Health Officer' },", $js);
        $this->assertStringContainsString('function drawSignatures(st, signatures)', $pdf);

        $this->assertStringContainsString("opens once the Municipal Mayor approves its privacy impact assessment, after the Data Protection Officer's review.", $tab);
    }
}
