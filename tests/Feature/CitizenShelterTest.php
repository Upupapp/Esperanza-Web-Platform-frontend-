<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The resident's side of shelter and relief, Phase 3 (owner, 2026-10-27), on
 * the Emergency page: their own stays and relief, confirming an item came
 * (beside the paper signature, never instead of it), a mistake sent as a
 * support ticket, and a household list kept ready with consent while the LGU
 * has it on. The backend enforces ownership and the switch; these hold the
 * portal to its side.
 */
class CitizenShelterTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_emergency_page_loads_the_residents_records(): void
    {
        $view = $this->source('resources/views/citizen/emergency.blade.php');
        $js = $this->source('resources/js/citizen-shelter.js');

        $this->assertStringContainsString("area(/^\\/citizen\\/emergency(\\/|$)/.test(areaPath), () => import('./citizen-shelter'));", $this->source('resources/js/app.js'));
        $this->assertStringContainsString('id="my-records"', $view, 'the release notification links here');
        $this->assertStringContainsString('x-data="citizenShelter"', $view);
        $this->assertStringContainsString("api.get('/citizen/sakuna/records')", $js);
        // An account not yet linked to the registry is told why it sees nothing.
        $this->assertStringContainsString('data-records-unlinked', $view);
    }

    public function test_confirming_relief_sits_beside_the_signature_and_a_mistake_is_a_ticket(): void
    {
        $view = $this->source('resources/views/citizen/emergency.blade.php');
        $js = $this->source('resources/js/citizen-shelter.js');

        $this->assertStringContainsString("'/citizen/sakuna/relief/' + encodeURIComponent(r.ref) + '/acknowledge', { received, note: note || null }", $js);
        $this->assertStringContainsString('@click="acknowledge(r, true)"', $view);
        $this->assertStringContainsString('@click="acknowledge(r, false)"', $view);
        $this->assertStringContainsString('x-show="r.can_acknowledge"', $view);
        $this->assertStringContainsString('it does not replace the signature', $view);
        $this->assertStringContainsString("api.post('/citizen/sakuna/records/report', { ref: this.reportFor, message: this.reportMessage.trim() })", $js);
    }

    public function test_the_household_list_needs_consent_shows_only_when_on_and_can_be_deleted(): void
    {
        $view = $this->source('resources/views/citizen/emergency.blade.php');
        $js = $this->source('resources/js/citizen-shelter.js');

        $this->assertStringContainsString('x-show="hhState === \'loaded\' && hhEnabled"', $view);
        $this->assertStringContainsString('x-model="hhForm.consent"', $view);
        $this->assertStringContainsString('return f.consent && f.barangay && f.members.some((m) => m.name.trim());', $js);
        $this->assertStringContainsString('consent: true,', $js);
        $this->assertStringContainsString("await api.delete('/citizen/sakuna/household');", $js);
        $this->assertStringContainsString("including children's details, only for check-in", $view);
    }

    public function test_staff_see_the_residents_answer_and_the_lists_residents_keep(): void
    {
        $relief = $this->source('resources/views/admin/partials/sakuna/tab-relief.blade.php');
        $evacuees = $this->source('resources/views/admin/partials/sakuna/tab-evacuees.blade.php');

        // The resident answers on the website too, so neither line says "in the app" (19 Nov).
        $this->assertStringContainsString("'Resident confirmed received' : 'Resident says not received'", $relief);
        $this->assertStringContainsString("? 'Confirmed received' + (reliefViewing", $relief);
        $this->assertStringNotContainsString('in the app', $relief);
        $this->assertStringContainsString("x-show=\"f.source === 'kept_by_resident'\"", $evacuees);
        $this->assertStringContainsString("'Filled from the list the resident keeps: '", $evacuees);
    }
}
