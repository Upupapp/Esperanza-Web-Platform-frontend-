<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Citizen sign-up follows the mobile app (owner, 2026-10-12: "the website
 * should follow the flow of the mobile"). The app's flow is
 * Esperanza-Mobile-App/lib/screens/auth/register_screen.dart; these pin the
 * web page to it, so the two cannot drift apart again unnoticed.
 */
class RegistrationParityTest extends TestCase
{
    private function page(): string
    {
        return file_get_contents(base_path('resources/views/auth/citizen-register.blade.php'));
    }

    public function test_the_steps_are_the_apps_in_its_order(): void
    {
        $view = $this->page();

        // Didit's screen (hosted): Personal Information, Terms, Review, Verify
        // Identity. The portal's own ID screens (own): the ID and the selfie
        // between Terms and Review, as on the phone.
        $this->assertStringContainsString("this.hosted ? ['personal', 'terms', 'review', 'status'] : ['personal', 'terms', 'id', 'selfie', 'review', 'status']", $view);
        $this->assertStringContainsString("(await \$api.get('/portal-settings')).data?.identity_capture === 'hosted'", $view);
        foreach (['Personal Information', 'Terms & Conditions', 'Valid ID Upload', 'Review', 'Verify Identity', 'Verification Status'] as $label) {
            $this->assertStringContainsString("'{$label}'", $view, "the step \"{$label}\" is missing");
        }
    }

    public function test_the_email_is_proven_before_the_account_exists(): void
    {
        $view = $this->page();

        $this->assertStringContainsString("\$api.post('/auth/citizen/email/send-code', { email })", $view);
        $this->assertStringContainsString("\$api.post('/auth/citizen/email/verify-code'", $view);
        $this->assertStringContainsString('email_verification_token: this.emailState.token', $view);
        // Continue stops until it is.
        $this->assertStringContainsString("if (!this.emailState.verified) return this.t('Please verify your email before continuing.'", $view);
    }

    public function test_it_asks_what_the_app_asks_and_no_more(): void
    {
        $view = $this->page();

        foreach (['form.first_name', 'form.last_name', 'form.email', 'form.mobile', 'form.barangay', 'form.purok', 'form.password'] as $field) {
            $this->assertStringContainsString("x-model=\"{$field}\"", $view);
        }
        // The app's account form has none of these; the Resident Profile asks them later.
        foreach (['form.birthdate', 'form.sex', 'form.civil_status', 'form.middle_name'] as $absent) {
            $this->assertStringNotContainsString($absent, $view);
        }
        // The five password rules the app shows, checked before Continue.
        foreach (['8 characters', '1 uppercase letter', '1 lowercase letter', '1 number', '1 special character'] as $rule) {
            $this->assertStringContainsString("'{$rule}'", $view);
        }
        $this->assertStringContainsString('termsAccepted', $view);
        $this->assertStringContainsString('accepts_terms: true', $view);
    }

    public function test_the_last_step_offers_the_identity_check_and_the_portal(): void
    {
        $view = $this->page();

        // Signed in straight away when the email was proven.
        $this->assertStringContainsString('const needsCode = result?.verification?.required ?? true;', $view);
        $this->assertStringContainsString('await this.$store.citizenSession.login(this.signInIdentifier(), this.form.password);', $view);
        // Didit, or "do it later" from My Profile.
        $this->assertStringContainsString("\$api.post('/citizen/verification/session', { platform: 'web' })", $view);
        $this->assertStringContainsString("'Verify my identity'", $view);
        $this->assertStringContainsString("'Continue to app'", $view);
        // Own capture: the ID and the selfie go with the new session.
        $this->assertStringContainsString("\$api.post('/citizen/verification/id', form)", $view);
    }
}
