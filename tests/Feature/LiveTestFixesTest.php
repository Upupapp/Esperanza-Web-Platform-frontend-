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
        $this->assertStringContainsString('to     = "/404.html"', $this->source('netlify.toml'));
        $this->assertStringContainsString("copy(\$distDir.'/404/index.html', \$distDir.'/404.html');", $this->source('export.php'));
        $this->assertStringContainsString("'/*  /404.html  404',", $this->source('export.php'), 'dist/_redirects, which Netlify reads first');
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
        $form = $this->source('resources/js/resident-profile-form.js');
        $this->assertStringContainsString("await api.put('/citizen/resident-profile', this.payload());", $form);
        // An email on file is not a mobile number (retest: it became "42607").
        $this->assertStringContainsString("if (raw.includes('@')) return '';", $form);
        $this->assertStringContainsString('contact: mobileOrEmpty(me.contact) || mobileOrEmpty(account.mobile),', $form);
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

    /** End-to-end test 2026-10-07: Educational Assistance asks the scholarship form; a Tulong request can be withdrawn. */
    public function test_educational_assistance_asks_the_school_and_a_request_can_be_withdrawn(): void
    {
        $types = (require base_path('config/esperanza_tulong_types.php'))['types'];
        $educational = collect($types)->firstWhere('key', 'tulong_educational');
        $this->assertNotNull($educational);
        $fields = collect($educational['fields'] ?? [])->keyBy('key');
        $this->assertSame('select', $fields['yearOrGradeLevel']['type']);
        $this->assertContains('Grade 10', $fields['yearOrGradeLevel']['options']);
        $this->assertSame(['field' => 'yearOrGradeLevel', 'contains' => 'College'], $fields['degreeProgramOrCourse']['showIf']);
        $this->assertTrue($fields['degreeProgramOrCourse']['required']);
        $this->assertTrue($fields->has('fatherMonthlyIncome') && $fields->has('motherMonthlyIncome'), 'one income per parent');

        $view = $this->source('resources/views/citizen/assistance-requests.blade.php');
        $this->assertStringContainsString('if (!f.required || !this.fieldShown(f)) return false;', $view);
        $this->assertStringContainsString('for (const f of (type?.fields || [])) if (!this.fieldShown(f)) delete formData[f.key];', $view);
        $this->assertStringContainsString('x-show="fieldShown(f)"', $view);
        $this->assertStringContainsString("'/withdraw', { reason: this.withdrawReason.trim() || null }", $view);
        $this->assertStringContainsString('r.canWithdraw = !!d.can_withdraw;', $view);
        $this->assertStringContainsString('x-show="selectedRequest.canWithdraw"', $view);
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
