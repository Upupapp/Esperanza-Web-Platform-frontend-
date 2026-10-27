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
        $this->assertStringContainsString('en="Your barangay office confirms it when it verifies your profile."', $view);
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
