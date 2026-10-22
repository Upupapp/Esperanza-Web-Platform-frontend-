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
        $this->assertStringContainsString('<nav class="flex flex-wrap gap-1 m-4 mb-1 p-1 rounded-2xl surface-pressed-sm">', $sakuna);
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
        // Sign-up follows the app since 2026-10-12 (no date of birth there); its labels stay bilingual.
        $this->assertStringContainsString('label-fil="Pangalan"', $this->source('resources/views/auth/citizen-register.blade.php'));
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
        $this->assertStringContainsString("if (!f.required || f.type === 'derived_age' || !this.fieldShown(f)) return false;", $view);
        $this->assertStringContainsString('for (const f of (type?.fields || [])) if (!this.fieldShown(f)) delete formData[f.key];', $view);
        $this->assertStringContainsString('x-show="fieldShown(f)"', $view);
        $this->assertStringContainsString("'/withdraw', { reason: this.withdrawReason.trim() || null }", $view);
        $this->assertStringContainsString('r.canWithdraw = !!d.can_withdraw;', $view);
        $this->assertStringContainsString('x-show="selectedRequest.canWithdraw"', $view);
    }

    /** Owner, 2026-10-07: as few sidebar entries as possible. Directory and Blotter are tabs of Balita; Settings, with Support Tickets and Audit Logs, is in the profile menu. */
    public function test_the_staff_sidebar_keeps_balita_whole_and_settings_in_the_profile_menu(): void
    {
        $sidebar = $this->source('resources/views/components/admin/sidebar.blade.php');
        $this->assertSame(1, substr_count($sidebar, ":href=\"route('admin.communications')\""), 'one Balita entry');
        $this->assertStringContainsString('label="Balita" :active="$active === \'communications\'"', $sidebar);
        foreach (['Balita & Events', 'label="Directory"', 'label="Blotter"', 'label="Settings"', 'label="Audit Logs"', 'label="Support Tickets"'] as $gone) {
            $this->assertStringNotContainsString($gone, $sidebar, $gone);
        }
        // Support tickets moved to Balita's Feedback tab (scope round 2026-10-15);
        // staff who only answer them get a Feedback entry instead of Balita.
        $this->assertStringNotContainsString('Support Tickets', $this->source('resources/views/admin/settings.blade.php'));
        $this->assertSame(1, substr_count($sidebar, ":href=\"route('admin.communications.feedback')\""), 'one Feedback entry, for desk-only staff');

        $balita = $this->source('resources/views/admin/communications.blade.php');
        $this->assertStringContainsString("<x-layouts.admin :title=\"\$tab === 'feedback' ? 'Feedback' : 'Balita'\"", $balita);
        $this->assertStringNotContainsString('inGroup(', $balita);
        foreach (['balita', 'banners', 'events', 'offices', 'barangays', 'blotter', 'feedback'] as $tab) {
            $this->assertStringContainsString("<button x-show=\"tabAllowed('{$tab}')\" @click=\"go('{$tab}')\"", $balita, $tab);
        }
        $this->assertStringContainsString('<i data-lucide="settings" class="w-4 h-4 text-ink-muted"></i>Settings', $this->source('resources/views/components/admin/topbar.blade.php'));
        $this->assertStringContainsString("'audit-logs' => ['scroll-text', 'Audit Logs'", $this->source('resources/views/admin/settings.blade.php'));
    }

    /** Resident portal, 2026-10-07: My Documents shows each file, opens it in place and downloads it. */
    public function test_my_documents_show_the_file_open_it_in_place_and_download_it(): void
    {
        $view = $this->source('resources/views/citizen/document-requests.blade.php');
        $this->assertStringContainsString('x-effect="open && loadThumb(doc, $el)"', $view, 'a thumbnail per card, once the list is open');
        $this->assertStringContainsString('await window.renderPdfInto(el, url, { maxPages: 1,', $view, 'page 1 of a PDF');
        $this->assertStringContainsString("this.thumbs[doc.id] = { state: 'img', src: await window.imageBlobUrl(url, kind) };", $view, 'a photo');
        $this->assertStringContainsString('@click="view(doc)"', $view, 'Buksan opens a viewer');
        $this->assertStringContainsString('x-on:papeles-view.window="open = true"', $view);
        $this->assertStringContainsString('<x-ui.t fil="I-download" en="Download" />', $view);
        $this->assertStringContainsString('await window.downloadFile(url, window.downloadName(doc.name, doc.file_type));', $view);
        $this->assertStringNotContainsString("window.open(url, '_blank', 'noopener')", $view, 'no new tab that only downloads');

        $js = $this->source('resources/js/app.js');
        $this->assertStringContainsString('window.downloadFile = async (url, name) => {', $js);
        $this->assertStringContainsString('canvas.className = canvasClass;', $js);
    }

    /** 2026-10-07: Log Blotter Entry covered only the page area. A page's fade-in must leave no transform behind. */
    public function test_a_page_fade_in_leaves_no_transform_that_traps_a_dialog(): void
    {
        $css = $this->source('resources/css/app.css');
        $this->assertStringContainsString('--animate-fade-up: fade-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) backwards;', $css);
        $this->assertMatchesRegularExpression('/@keyframes fade-up \{\s*from \{[^}]*\}\s*to \{ opacity: 1; transform: none; \}/', $css);
    }

    /** 2026-10-07: the request wizards' step indicator is equal columns joined centre to centre. */
    public function test_the_request_wizards_step_indicator_is_even(): void
    {
        foreach (['document-requests' => 'grid grid-cols-4 mb-8', 'assistance-requests' => "tulongFree ? 'grid-cols-3' : 'grid-cols-4'"] as $page => $grid) {
            $view = $this->source("resources/views/citizen/{$page}.blade.php");
            $this->assertStringContainsString($grid, $view, $page);
            $this->assertStringContainsString('absolute top-4 left-[calc(50%_+_26px)] right-[calc(-50%_+_26px)] h-1', $view, $page);
            $this->assertStringNotContainsString('mx-1.5 -mt-6', $view, $page);
        }
    }

    /** 2026-10-07: a doughnut's tooltip covered its centre total. The centre shows the hovered slice instead. */
    public function test_doughnuts_show_the_hovered_slice_in_the_centre_not_a_tooltip(): void
    {
        $js = $this->source('resources/js/app.js');
        // 2026-10-08: one look for every chart; Chart.js is gone.
        $this->assertStringContainsString("Alpine.data('clayDonut'", $js);
        $this->assertStringNotContainsString("import('chart.js')", $js);
        // The clay doughnut (dashboard, reports): the centre is the total or the hovered slice.
        $donut = $this->source('resources/views/components/ui/clay-donut.blade.php');
        $this->assertStringContainsString('x-effect="draw({{ $rows }})" @mouseleave="hover = null"', $donut);
        $this->assertStringContainsString("rows[hover].label + ' · ' + pct(rows[hover]) + '%'", $donut);
        foreach ([
            'resources/views/admin/dashboard.blade.php' => ['<x-ui.clay-donut rows="docStatus"', '<x-ui.clay-donut rows="assistStatus"'],
            'resources/views/admin/reports.blade.php' => ['<x-ui.clay-donut rows="internalOfficeSplit"', '<x-ui.clay-donut rows="incidentSeveritySplit"'],
            'resources/views/admin/constituents/tab-profiling.blade.php' => ['<x-ui.clay-donut rows="barangayStatusBreakdown"'],
            'resources/views/admin/partials/sakuna/tab-reports.blade.php' => ['<x-ui.clay-donut rows="reportTypeBreakdown()', '<x-ui.clay-trend data="{ labels: reportTrendLabels()'],
        ] as $path => $draws) {
            $view = $this->source($path);
            foreach ($draws as $draw) {
                $this->assertStringContainsString($draw, $view, $path);
            }
            $this->assertStringNotContainsString("type: 'doughnut'", $view, $path.': one shared chart');
        }
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

    /** 2026-10-08: Download PDF saved a blank page (resident) or never finished (staff); the resident's threw on oklch. */
    public function test_download_pdf_captures_the_document(): void
    {
        $css = $this->source('resources/css/app.css');
        $this->assertStringContainsString('body.dp-printing > *:not(:has(#dp-print-area)):not(#dp-print-area):not(.html2canvas-container)', $css, "html2canvas's own iframe is never hidden");
        $this->assertStringContainsString('body.dp-printing *:has(#dp-print-area):not(.html2pdf__overlay, .html2pdf__container) {', $css, "html2pdf's container keeps its A4 width");
        $this->assertStringContainsString('body.dp-printing .html2pdf__container .doc-paper:last-child', $css, 'no blank second page');
        $this->assertStringContainsString('export function exportClone(el)', $this->source('resources/js/dokyu-editor.js'));
        foreach (['admin', 'citizen'] as $side) {
            $view = $this->source("resources/views/{$side}/document-requests.blade.php");
            $this->assertStringContainsString('.from(window.DokyuEditor.exportClone(el)).save()', $view, $side.': a copy Alpine never starts');
            $this->assertStringContainsString('window.DokyuEditor.sanitizeExportColors(document.body)', $view, $side.': oklch swapped for rgb');
        }
    }

    /** 2026-10-08: the photo showed on the profile only; the top-bar searches found nothing; dropdown arrows sat on the edge. */
    public function test_the_photo_shows_where_the_resident_does_and_the_searches_work(): void
    {
        $js = $this->source('resources/js/app.js');
        $this->assertStringContainsString('photoUrl: p.photo?.url || null,', $js, 'the session keeps the approved photo');
        $this->assertStringContainsString("Alpine.data('globalSearch', (cfg) => ({", $js);
        foreach (['resources/views/components/citizen/topbar.blade.php', 'resources/views/citizen/dashboard.blade.php'] as $path) {
            $this->assertStringContainsString('<x-citizen.avatar', $this->source($path), $path);
        }
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/admin/{$page}.blade.php");
            $this->assertStringContainsString('photo: row.applicant_photo || null,', $view, $page);
            $this->assertStringContainsString(':src="selected.photo"', $view, $page.': the Citizen cell');
            $this->assertStringNotContainsString('citizenAvatars', $view, $page);
        }
        $this->assertStringContainsString('photoOf(r.ref)', $this->source('resources/views/admin/constituents/view-individuals.blade.php'));
        foreach (['components/citizen/topbar', 'components/admin/topbar'] as $bar) {
            $this->assertStringContainsString('<x-ui.global-search :config="$searchConfig"', $this->source("resources/views/{$bar}.blade.php"), $bar);
        }
        $this->assertStringContainsString("const wanted = params.get('ref');", $this->source('resources/views/admin/constituents.blade.php'), 'a resident opens from search');
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $this->assertStringContainsString("get('new'); if (!want) return;", $this->source("resources/views/citizen/{$page}.blade.php"), $page.': the wizard opens from search');
        }
        $this->assertStringContainsString('select:not([multiple]):not([size]):not(.appearance-none):not(.doc-toolbar-select)', $this->source('resources/css/app.css'));
    }

    /** 2026-10-23: every question the form builder makes is drawn, and only what is on screen is required. */
    public function test_every_form_builder_question_is_drawn_on_dokyu_and_tulong(): void
    {
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/citizen/{$page}.blade.php");
            foreach (["x-if=\"f.type === 'textarea'\"", "x-if=\"f.type === 'checkbox'\"", "f.type === 'checkbox-group' || f.type === 'multiselect'", "x-if=\"f.type === 'derived_age'\"", 'x-show="fieldShown(f)"'] as $needle) {
                $this->assertStringContainsString($needle, $view, $page.': '.$needle);
            }
        }
        $dokyu = $this->source('resources/views/citizen/document-requests.blade.php');
        // A required question that is hidden, a table, or worked out is never asked for.
        $this->assertStringContainsString("if (!f.required || f.type === 'table' || f.type === 'derived_age' || !this.fieldShown(f)) return false;", $dokyu);
        $this->assertStringContainsString('for (const f of (doc?.fields || [])) if (!this.fieldShown(f)) delete formData[f.key];', $dokyu);
    }
}
