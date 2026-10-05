<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * What the live QA run of 2026-10-04 found on the deployed site (resident
 * portal and staff portal, against staging), pinned so it cannot come back.
 * Each test is named for what a resident or an officer saw.
 */
class QaRunFixesTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** Sign-up stopped at step 1: the browser validated the hidden steps 2 and 3 too, and said nothing. */
    public function test_each_sign_up_step_checks_only_its_own_fields(): void
    {
        $view = $this->source('resources/views/auth/citizen-register.blade.php');

        $this->assertStringContainsString('novalidate', $view, 'the browser must not validate all three steps at once');
        foreach (['step1', 'step2', 'step3'] as $ref) {
            $this->assertStringContainsString('x-ref="'.$ref.'"', $view);
        }
        $this->assertStringContainsString('if (!this.validStep()) return;', $view);
    }

    /** No officer could verify a resident: GET /auth/me sends a list of grants and canPerm() read a map. */
    public function test_the_permission_list_from_the_server_is_read_as_a_map(): void
    {
        $app = $this->source('resources/js/app.js');

        $this->assertStringContainsString('function permissionMap(raw)', $app);
        $this->assertStringContainsString('permissions: permissionMap(raw.permissions)', $app);
        $this->assertStringContainsString("account: withPermissionMap(readJson('esperanza_session'))", $app, 'an account cached before the fix is read too');
    }

    /** The Sakuna bar showed a Red Alert and a July 2026 monsoon; the Dokyu queue a 2.4-day average. Neither was real. */
    public function test_no_staff_screen_shows_made_up_figures(): void
    {
        $sakuna = $this->source('resources/views/admin/sakuna.blade.php');
        $this->assertStringNotContainsString("opStatus: 'Red Alert'", $sakuna);
        $this->assertStringNotContainsString('Southwest Monsoon & Flooding — July 2026', $sakuna);
        $this->assertStringNotContainsString('Updated Jul 15, 2026', $sakuna);
        $this->assertStringContainsString("\$api.get('/admin/sakuna/command-center')", $sakuna, 'the bar reads the live board');
        $this->assertArrayNotHasKey('periods', require base_path('resources/data/admin/sakuna.php'));

        $queue = $this->source('resources/views/admin/document-requests.blade.php');
        $this->assertStringNotContainsString('value="2.4 days"', $queue);
        $this->assertStringContainsString('valueBind="avgProcessingLabel"', $queue);
    }

    /** The BDRRMC capture forms opened with sample families and one barangay's officials, and Save stored nothing. */
    public function test_the_bdrrmc_capture_forms_start_blank_and_save_to_the_server(): void
    {
        $view = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');
        foreach (['Rodrigo M. Panganiban', 'value="LABANGTAYTAY" readonly', 'Capture form saved.', 'Form sent to printer.', 'CACHILA', 'BRUZA'] as $fake) {
            $this->assertStringNotContainsString($fake, $view, "still in the forms: {$fake}");
        }
        $this->assertStringContainsString("'/admin/sakuna/bdrrmc-forms/' + key + '/submit'", $view, 'Save submits to the server');
        $this->assertStringContainsString('window.print()', $view, 'Print prints');
        $this->assertStringContainsString('x-model="signoff.', $this->source('resources/views/components/admin/sakuna/bdrrmc-signoff.blade.php'));
    }

    /** "Assign to me" only changed the screen; a reload showed the case unassigned. */
    public function test_assign_to_me_is_saved_on_the_server(): void
    {
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/admin/{$page}.blade.php");
            $this->assertStringNotContainsString('Assigned to yourself.', $view, $page);
            $this->assertStringContainsString("this.apiBase(this.selected.ref) + '/assign'", $view, $page);
            $this->assertStringContainsString('assigned: row.assigned || null', $view, $page);
        }
    }

    /** "Still being scanned" was shown for a scan that had failed and would never finish. */
    public function test_a_file_that_cannot_be_opened_says_why(): void
    {
        $review = $this->source('resources/views/admin/constituents/modal-citizen-account.blade.php');
        $this->assertStringNotContainsString('Still being scanned.', $review);
        $this->assertStringContainsString('window.fileNotice(part[1])', $review);

        foreach (['document-requests', 'assistance-requests'] as $page) {
            $view = $this->source("resources/views/admin/{$page}.blade.php");
            $this->assertStringContainsString('file_notice: window.fileNotice(q.file)', $view, $page);
            $this->assertStringContainsString('Download the file', $view, $page.': the viewer links the file');
            $this->assertStringNotContainsString('<iframe :src="previewDoc?.file_url"', $view, $page.': uploads are downloads, never framed');
            $this->assertStringContainsString("'Not submitted'", $view, $page.': a requirement with no file does not read Submitted');
        }
    }

    /** The site is exported once, so a date printed with now() is the build's date: the dashboard read "Sunday, October 4" on a Monday the 5th. */
    public function test_no_page_prints_the_build_date_as_today(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('resources/views'), \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $this->assertDoesNotMatchRegularExpression('/now\(\)->/', file_get_contents($file->getPathname()), $file->getPathname());
            }
        }
    }

    /** No page showed a published banner, and the resident's Balita feed left out every post's title. */
    public function test_published_banners_and_balita_titles_reach_the_resident(): void
    {
        $carousel = $this->source('resources/views/components/citizen/banner-carousel.blade.php');
        $this->assertStringContainsString("\$api.get('/banners')", $carousel, 'the carousel reads the live banners');
        $this->assertStringContainsString('x-init="load()"', $carousel);
        $this->assertStringContainsString('<x-citizen.banner-carousel />', $this->source('resources/views/citizen/dashboard.blade.php'), 'the dashboard shows it');

        $feed = $this->source('resources/views/citizen/announcements.blade.php');
        $this->assertStringContainsString('title: a.title', $feed);
        $this->assertStringContainsString('x-text="post.title"', $feed);
        $this->assertStringContainsString('x-text="post.title"', $this->source('resources/views/citizen/dashboard.blade.php'), 'the dashboard preview too');
    }

    /** New Role only toasted that it needed a backend; the Add Personnel dialog promised an email nobody sends. */
    public function test_staff_can_create_and_delete_a_role_and_are_not_promised_an_email(): void
    {
        $users = $this->source('resources/views/admin/users.blade.php');
        $this->assertStringNotContainsString('connect to backend to persist new roles', $users);
        $this->assertStringContainsString("\$api.post('/admin/roles'", $users);
        $this->assertStringContainsString("\$api.delete('/admin/roles/' + role.key)", $users);
        $this->assertStringContainsString("window.addEventListener('roles-changed'", $users, 'the permission editor sees a new role');
        $this->assertStringNotContainsString('will be sent to the personnel', $users);
    }

    /** The personnel list offered only Archive: no edit, no password reset, no way back from an archive. */
    public function test_staff_can_edit_restore_and_reset_an_officer(): void
    {
        $users = $this->source('resources/views/admin/users.blade.php');
        $this->assertStringContainsString("\$api.put('/admin/users/' + this.edit.id, changes)", $users);
        $this->assertStringContainsString("'/reset-password', { password: this.tempPassword }", $users);
        $this->assertStringContainsString('<option value="Approved">Active</option>', $users, 'an archived officer can be restored');
    }

    /** Settings saved a Default Language and Privacy/Terms versions that the resident site never read; other settings claimed effects they lack. */
    public function test_settings_reach_the_resident_site_or_say_they_do_not(): void
    {
        $this->assertStringContainsString("api.get('/portal-settings')", $this->source('resources/js/app.js'));

        $legal = $this->source('resources/views/citizen/legal.blade.php');
        $this->assertStringContainsString("\$api.get('/site-content/' + key)", $legal, 'the legal page loads the published text');
        $this->assertStringNotContainsString('@foreach($privacy', $legal);

        $settings = $this->source('resources/views/admin/settings.blade.php');
        $this->assertStringContainsString('administrators are not sent these alerts today', $settings);
        $this->assertStringContainsString('neither portal shows this theme or name today', $settings);
        $this->assertStringNotContainsString('Branding ships to both portals', $settings);
    }

    /** The resident saw neither the fee nor the receipt; the cashier could not tell which request a payment was for. */
    public function test_the_resident_sees_the_fee_and_the_cashier_sees_the_request(): void
    {
        $fee = $this->source('resources/views/components/citizen/request-fee.blade.php');
        $this->assertStringContainsString('selectedRequest?.payment?.receipt_no', $fee, 'the receipt number is shown');
        $this->assertStringContainsString("selectedRequest?.payment?.status === 'Approved'", $fee, 'Approved reads as paid');
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $this->assertStringContainsString('<x-citizen.request-fee />', $this->source("resources/views/citizen/{$page}.blade.php"), $page);
        }

        $payments = $this->source('resources/views/admin/payments.blade.php');
        $this->assertStringContainsString("' · ' + p.request_ref", $payments, 'each payment names its request');
        $this->assertStringContainsString("(p.request_ref || '').toLowerCase().includes(q)", $payments, 'the search finds a payment by its request');
    }
}
