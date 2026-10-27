<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Shelter and relief records, Phase 2 (owner, 2026-10-27): check-in fills
 * from the resident registry, the head is marked, purok and a typed household
 * code are kept apart from the sitio and the registry's code, a family already
 * staying somewhere is asked about, records are corrected at the source, and
 * relief says where it was given. The backend enforces each of these; these
 * hold the portal to its side.
 */
class ShelterRecordsTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_module_is_loaded_with_the_sakuna_pages_and_merged_into_the_page(): void
    {
        $this->assertStringContainsString("area(/^\\/admin\\/sakuna(\\/|$)/.test(areaPath), () => import('./sakuna-shelter'));", $this->source('resources/js/app.js'));
        $this->assertStringContainsString('...(window.sakunaShelter ? sakunaShelter() : {}),', $this->source('resources/views/admin/sakuna.blade.php'));
    }

    public function test_check_in_fills_from_the_registry_and_sends_only_who_arrived(): void
    {
        $js = $this->source('resources/js/sakuna-shelter.js');
        $page = $this->source('resources/views/admin/sakuna.blade.php');
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-evacuees.blade.php');

        $this->assertStringContainsString("'/admin/sakuna/registry/families?' + new URLSearchParams({ q })", $js);
        $this->assertStringContainsString('if (q.length < 3) {', $js);
        $this->assertStringContainsString('individual_id: m.individual_id, is_head: !!m.is_head, arrived: true,', $js);
        $this->assertStringContainsString('return this.checkInForm.members.filter((m) => m.arrived !== false && m.name.trim());', $js);
        $this->assertStringContainsString('const members = this.checkInArrived()', $page);
        $this->assertStringContainsString('...this.checkInExtras(confirm) };', $page);
        $this->assertStringContainsString('@click="pickRegistryFamily(f)"', $tab);
        $this->assertStringContainsString('x-model="m.arrived"', $tab);
        // One head: a radio, and the server refuses two.
        $this->assertStringContainsString('name="checkin-head-pick"', $tab);
        $this->assertStringContainsString('this.checkInForm.members.forEach((m, i) => { m.is_head = i === idx; });', $js);
    }

    public function test_a_sitio_is_not_a_purok_and_a_typed_household_code_is_never_the_registrys(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-evacuees.blade.php');

        $this->assertStringNotContainsString('Sitio/Purok', $tab);
        $this->assertStringContainsString('x-model="checkInForm.purok"', $tab);
        $this->assertStringContainsString('x-model="checkInForm.hh_code"', $tab);
        // A registry household hides the typed field at check-in and locks it on the record.
        $this->assertStringContainsString('x-show="!registryPicked?.household_ref"', $tab);
        $this->assertStringContainsString(':disabled="!!selectedEvacuee?.household_ref"', $tab);
        $this->assertStringContainsString("'Typed at the centre, not verified'", $tab);
    }

    public function test_the_same_family_already_staying_is_asked_about_not_silently_added(): void
    {
        $page = $this->source('resources/views/admin/sakuna.blade.php');
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-evacuees.blade.php');

        $this->assertStringContainsString("if (err.code === 'ALREADY_SHELTERED') { this.checkInDuplicate = err.localised('en'); return; }", $page);
        $this->assertStringContainsString('@click="submitCheckIn(true)"', $tab);
        $this->assertStringContainsString('confirm_duplicate: confirm ? true : undefined,', $this->source('resources/js/sakuna-shelter.js'));
    }

    public function test_records_are_corrected_at_the_source_and_faced_stays_with_the_home_barangay(): void
    {
        $js = $this->source('resources/js/sakuna-shelter.js');
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-evacuees.blade.php');

        $this->assertStringContainsString("api.put('/admin/sakuna/evacuees/' + this.selectedEvacuee.ref, {", $js);
        $this->assertStringContainsString("const base = '/admin/sakuna/evacuees/' + this.selectedEvacuee.ref + '/members';", $js);
        $this->assertStringContainsString("return this.can('sakuna_evacuees', 'edit') && this.selectedEvacuee?.status === 'Processing';", $js);
        $this->assertStringContainsString('@click="editShelterRecord()"', $tab);
        $this->assertStringContainsString('@click="editMember(m)"', $tab);
        $this->assertStringContainsString('@click="newMember()"', $tab);
        // Missing age or sex is shown, so it is corrected rather than guessed.
        $this->assertStringContainsString("'No sex recorded' : 'No age recorded'", $tab);
        $this->assertStringContainsString('x-show="selectedEvacuee?.faced_restricted"', $tab);
        $this->assertStringContainsString('&& !selectedEvacuee?.faced_restricted" @click="editFaced()"', $tab);
    }

    public function test_relief_says_where_it_was_given_and_takes_several_items_for_one_family(): void
    {
        $page = $this->source('resources/views/admin/sakuna.blade.php');
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-relief.blade.php');

        $this->assertStringContainsString('x-model="distributionForm.center"', $tab);
        $this->assertStringContainsString('x-model="distributionForm.location"', $tab);
        $this->assertStringContainsString('center: f.center || undefined,', $page);
        $this->assertStringContainsString('this.distributionForm.center = this.reliefCenterFor(ev);', $page);
        // A barangay officer's relief to a guest family is their barangay's (the server refuses otherwise).
        $this->assertStringContainsString('this.distributionForm.barangay = this.$store.session.inScope(ev.barangay) ? ev.barangay : this.$store.session.account.scope;', $page);
        $this->assertStringContainsString('@click="submitDistribution(true)"', $tab);
        $this->assertStringContainsString("Object.assign(this.distributionForm, { item: '', quantity: '', unit: '', cost: '' });", $page);
    }
}
