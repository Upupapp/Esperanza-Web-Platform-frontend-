<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The live test of 2026-10-07 (report XUgnEPLB…): the website findings it
 * raised, each pinned so it cannot come back.
 */
class LiveTestFixesTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** PR-1, PR-2, PR-5: security and caching headers, from this build's API origin. */
    public function test_the_export_writes_security_headers_for_its_own_api(): void
    {
        require_once base_path('build/export_manifest.php');
        $headers = esperanza_response_headers('https://api.example.test');

        $this->assertStringContainsString("connect-src 'self' https://api.example.test", $headers);
        $this->assertStringContainsString("frame-ancestors 'none'", $headers);
        $this->assertStringContainsString("object-src 'none'", $headers);
        $this->assertStringContainsString('X-Content-Type-Options: nosniff', $headers);
        $this->assertStringContainsString("/admin/*\n  X-Robots-Tag: noindex, nofollow", $headers);
        $this->assertStringContainsString("/build/assets/*\n  Cache-Control: public, max-age=31536000, immutable", $headers);
        $this->assertStringContainsString("file_put_contents(\$distDir.'/_headers', esperanza_response_headers(\$apiOrigin));", $this->source('export.php'));
        $this->assertStringContainsString('Disallow: /admin/', $this->source('public/robots.txt'));
    }

    /** The CSP allows no inline script: no page may carry an inline handler. */
    public function test_no_page_uses_an_inline_event_handler(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('resources/views')));
        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression('/\son(error|click|load|change|submit)="/', file_get_contents($file->getPathname()), $file->getPathname());
        }
    }

    /** PR-3: a wrong address gets a real "page not found", in both languages. */
    public function test_a_wrong_address_gets_a_page_not_found(): void
    {
        $this->get('/404')->assertOk()->assertSee('Hindi nahanap ang pahinang ito', false)->assertSee('This page was not found', false);
        $this->assertStringContainsString('to     = "/404/index.html"', $this->source('netlify.toml'));
    }

    /** PR-8: staff choose their own password; My Profile is real. */
    public function test_staff_can_change_their_own_password(): void
    {
        $profile = $this->source('resources/views/components/admin/my-profile.blade.php');
        $this->assertStringContainsString("await \$api.put('/admin/me/password', this.form);", $profile);
        $this->assertStringContainsString('@password-change-required.window="openFor(true)"', $profile);
        $this->assertStringContainsString("\$dispatch('open-my-profile')", $this->source('resources/views/components/admin/topbar.blade.php'));
        $this->assertStringNotContainsString('Personnel profile page is a frontend preview', $this->source('resources/views/components/admin/topbar.blade.php'));
        $this->assertStringNotContainsString('Frontend na preview lamang', $this->source('resources/views/citizen/document-requests.blade.php'));
    }

    /** NAV-4, NAV-1, UX-17: Sakuna's tabs wrap, Send alert is in the header, a moved link says so, incidents use their own words. */
    public function test_sakuna_alerts_are_never_off_screen(): void
    {
        $sakuna = $this->source('resources/views/admin/sakuna.blade.php');
        $this->assertStringContainsString('<nav class="flex flex-wrap border-b border-slate-100">', $sakuna);
        $this->assertStringNotContainsString('min-w-max', $sakuna);
        $this->assertStringContainsString("(window.location.href = tabUrls['alerts'] + '?compose=1')", $sakuna);
        $this->assertStringContainsString("params.set('no_access', this.activeTab);", $sakuna);
        $this->assertStringContainsString("'Processing': 'Dispatched',", $sakuna);
        $this->assertStringContainsString('x-text="incidentStatusLabel(inc.status)"', $this->source('resources/views/admin/partials/sakuna/tab-incidents.blade.php'));
    }

    /** BUG-4, UX-7: on a phone each request is a card; the tiles add up. */
    public function test_resident_request_lists_fit_a_phone_and_add_up(): void
    {
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/citizen/{$page}.blade.php");
            $this->assertStringContainsString('<div class="sm:hidden space-y-2.5">', $view, $page);
            $this->assertStringContainsString('<x-ui.table class="hidden sm:block">', $view, $page);
            $this->assertStringContainsString('label="Hindi Natuloy" labelEn="Not Completed"', $view, $page);
            $this->assertStringContainsString("if (this.tab === 'stopped') return stopped;", $view, $page);
        }
    }

    /** UX-10: the capital is asked once, as a number. */
    public function test_the_business_permit_asks_the_capital_once(): void
    {
        $view = $this->source('resources/views/citizen/document-requests.blade.php');
        $this->assertStringContainsString("feeInputSources: { declared_capital: 'capital_investment' },", $view);
        $this->assertStringContainsString('<template x-for="name in feeInputsAsked" :key="name">', $view);
        $this->assertStringContainsString("['key' => 'capital_investment', 'label' => 'Capital Investment (₱)', 'type' => 'number'],", $this->source('config/esperanza_municipal_documents.php'));
    }

    /** UX-12: free assistance has no payment step. */
    public function test_free_assistance_skips_the_payment_step(): void
    {
        $view = $this->source('resources/views/citizen/assistance-requests.blade.php');
        $this->assertStringContainsString('if (this.step === 3 && this.tulongFree) this.step = 4;', $view);
        $this->assertStringContainsString('x-show="!(i === 2 && tulongFree)"', $view);
        $this->assertStringContainsString("'Done at the office'", $this->source('resources/views/admin/assistance-requests.blade.php'));
    }

    /** UX-8: the website fills and corrects the Resident Profile. */
    public function test_the_website_fills_the_resident_profile(): void
    {
        $this->assertStringContainsString("await api.put('/citizen/resident-profile', this.payload());", $this->source('resources/js/resident-profile-form.js'));
        $this->assertStringContainsString("Alpine.data('residentProfileForm', residentProfileForm);", $this->source('resources/js/app.js'));
        $this->assertStringContainsString('<x-citizen.resident-profile-form />', $this->source('resources/views/citizen/profile.blade.php'));
    }

    /** PR-10, UX-9, UX-6, NAV-5: SMS greyed out, a verified welcome, the FAQ in Filipino, the legal page's own title. */
    public function test_resident_site_says_what_is_true(): void
    {
        $this->assertStringContainsString('this.smsAvailable = result.data?.sms_available !== false;', $this->source('resources/views/citizen/settings.blade.php'));
        $this->assertStringContainsString('get pendingWelcome()', $this->source('resources/js/app.js'));
        $this->assertStringContainsString('<x-citizen.verified-popup />', $this->source('resources/views/components/layouts/citizen.blade.php'));
        $this->assertStringContainsString("x-text=\"faqText(faq, 'q')\"", $this->source('resources/views/citizen/help.blade.php'));
        $this->assertStringNotContainsString('walang aktwal na video na naka-play', $this->source('resources/views/citizen/help.blade.php'));
        $this->assertStringContainsString('active="legal"', $this->source('resources/views/citizen/legal.blade.php'));
        $this->assertStringContainsString('label-fil="Petsa ng Kapanganakan"', $this->source('resources/views/auth/citizen-register.blade.php'));
    }

    /** UX-4, UX-13, UX-14, UX-5: one word per thing; counts that agree; offices spelled out; one close button. */
    public function test_staff_pages_use_one_word_per_thing(): void
    {
        $constituents = $this->source('resources/views/admin/constituents.blade.php');
        $this->assertStringContainsString('label="Records Confirmed"', $constituents);
        $this->assertStringContainsString(':title="verificationQueueBreakdown"', $constituents);
        $this->assertStringContainsString('label="Archived or Rejected"', $this->source('resources/views/admin/users.blade.php'));
        $this->assertStringContainsString("officeNames: { hr: 'HR (MHRMO)'", $this->source('resources/views/admin/internal-forms.blade.php'));
        $this->assertStringContainsString('@if($title)', $this->source('resources/views/components/ui/modal.blade.php'));
    }
}
