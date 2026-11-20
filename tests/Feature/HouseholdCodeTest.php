<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The household's code in the barangay's resident registry, shown to the
 * resident (owner, 2026-10-27), as the app shows it. The server assigns it
 * (GET /citizen/resident-profile household.ref); the portal only shows it,
 * read only, and says the barangay confirms it until the profile is verified.
 */
class HouseholdCodeTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_profile_page_shows_the_registry_code_from_the_server(): void
    {
        $view = $this->source('resources/views/citizen/profile.blade.php');

        $this->assertStringContainsString('x-show="residentProfileView?.household?.ref" x-cloak data-household-code', $view);
        $this->assertStringContainsString('x-text="residentProfileView?.household?.ref"', $view);
        $this->assertStringContainsString('en="Household code"', $view);
        $this->assertStringContainsString("x-show=\"profileVerification !== 'Verified'\"", $view);
        $this->assertStringContainsString('en="Your barangay office confirms it once they have checked your Resident Profile."', $view);
    }

    public function test_a_verified_account_reads_the_resident_profile_as_a_separate_step_to_send(): void
    {
        // Flows review 2026-11-20: Lourdes, just verified, read "Hindi pa
        // naisusumite" and "kapag na-verify ang iyong profile".
        $view = $this->source('resources/views/citizen/profile.blade.php');

        $this->assertStringContainsString("profileReview?.outcome !== 'returned' && \$store.citizenSession.account?.fullServiceAccess\"><x-ui.t fil=\"Verified na ang account mo. Hiwalay na step ang Resident Profile", $view);
        $this->assertStringContainsString('en="Your account is verified. The Resident Profile is a separate step: fill it in and send it', $view);
        $this->assertStringContainsString('&& !$store.citizenSession.account?.fullServiceAccess"><x-ui.t fil="Hindi mo pa naipapadala.', $view);
        $this->assertStringNotContainsString('kapag na-verify ang iyong profile', $view);
        $this->assertStringNotContainsString('Hindi pa naisusumite', $view);
    }

    public function test_the_form_shows_it_read_only_and_never_sends_it(): void
    {
        $form = $this->source('resources/views/components/citizen/resident-profile-form.blade.php');
        $js = $this->source('resources/js/resident-profile-form.js');

        $this->assertStringContainsString('x-text="householdRef"', $form);
        $this->assertStringNotContainsString('x-model="householdRef"', $form);
        $this->assertStringContainsString("this.householdRef = hh.ref || '';", $js);
        // The household the form sends has no code in it.
        $this->assertStringNotContainsString('ref:', $this->blankHousehold($js));
    }

    private function blankHousehold(string $js): string
    {
        preg_match('/const blankHousehold = \(\) => \(\{[^}]*\}\);/', $js, $m);

        return $m[0] ?? '';
    }
}
