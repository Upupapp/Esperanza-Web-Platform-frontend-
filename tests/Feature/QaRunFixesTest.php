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
        // The names are the form's own, typed on the signature lines (2026-10-27: one
        // line per signer, drawn from the form's description).
        $this->assertStringContainsString('x-model="signoff[s.model]"', $this->source('resources/views/components/admin/sakuna/bdrrmc-signoff.blade.php'));
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
        $this->assertStringContainsString('title: a.title', $this->source('resources/js/scope-citizen.js'));
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
        $this->assertStringNotContainsString('Branding ships to both portals', $settings);
    }

    /** Notifications only loaded with the page, a Sakuna alert opened nowhere, and the settings promised what nothing did. */
    public function test_notifications_arrive_without_a_reload_and_alerts_open_on_any_page(): void
    {
        $js = $this->source('resources/js/notifications.js');
        $this->assertStringContainsString('poll(ms = 60000)', $js);
        $this->assertStringContainsString('get pendingAlert()', $js);
        $this->assertStringContainsString('$store.adminNotifications.poll()', $this->source('resources/views/components/admin/topbar.blade.php'));
        $this->assertStringContainsString('$store.citizenNotifications.poll()', $this->source('resources/views/components/citizen/topbar.blade.php'));
        $this->assertStringContainsString('<x-citizen.alert-popup />', $this->source('resources/views/components/layouts/citizen.blade.php'));

        $settings = $this->source('resources/views/admin/settings.blade.php');
        $this->assertStringNotContainsString('administrators are not sent these alerts today', $settings);
        $this->assertStringContainsString("key: 'new_incident_reports'", $settings);

        $this->assertStringContainsString("c.status !== 'Archived'", $this->source('resources/views/admin/partials/sakuna/tab-centers.blade.php'), 'an unopened centre can be closed');
        foreach (['document-requests', 'assistance-requests'] as $page) {
            $this->assertStringContainsString('if (/working/i.test(r.days))', $this->source("resources/views/citizen/{$page}.blade.php"), $page);
        }
    }

    /** Icons in rows drawn after an API load stayed blank: the personnel Edit and Archive buttons were invisible. */
    public function test_icons_drawn_after_the_first_render_are_rendered_too(): void
    {
        $js = $this->source('resources/js/app.js');
        $this->assertStringContainsString('new MutationObserver((mutations) => {', $js);
        $this->assertStringContainsString("n.querySelector('i[data-lucide]')", $js);
    }

    /** A published alert that was cancelled read "Not yet published". */
    public function test_a_cancelled_alert_says_when_it_was_cancelled(): void
    {
        $this->assertStringContainsString("a.status === 'Cancelled' && a.cancelled_at ? ('Cancelled '", $this->source('resources/views/admin/partials/sakuna/tab-alerts.blade.php'));
    }

    /** Branding was published as versions that neither portal read. */
    /** Owner ruling 2026-10-05: the Super Administrator sees every action, as the server allows it. */
    public function test_the_super_admin_sees_every_action(): void
    {
        // can, canSettings (round 2), canPerm, canStep (bug hunt 25 Nov),
        // canExportReports and inScope; inDeptScope already lets every role
        // but Department Head and Staff across offices.
        $this->assertSame(6, substr_count($this->source('resources/js/app.js'), "if (this.account.roleKey === 'super_admin') return true;"), 'every gate in the session store');
    }

    public function test_both_portals_apply_the_published_branding(): void
    {
        $js = $this->source('resources/js/app.js');
        $this->assertStringContainsString('function applyBranding(', $js);
        $this->assertStringContainsString("root.style.setProperty('--color-brand-' + shade", $js);
        foreach (['admin', 'citizen'] as $side) {
            $this->assertStringContainsString('data-brand-name', $this->source("resources/views/components/{$side}/sidebar.blade.php"), $side);
        }
        $this->assertStringNotContainsString('not applied yet', $this->source('resources/views/admin/settings.blade.php'));
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
        $this->assertStringContainsString("'Receipt ' + payments[selected].receipt_no + ' · paid on '", $payments, 'a paid payment reopened later still shows its receipt');
    }

    /**
     * 2026-10-28: on the Incidents tab, Assign opened an empty "Assign to" list:
     * the staff list was loaded only by the Command Center, and each tab is its
     * own page. Assign now loads it wherever it is opened.
     */
    public function test_assign_lists_staff_on_every_sakuna_tab(): void
    {
        $page = $this->source('resources/views/admin/sakuna.blade.php');
        $this->assertStringContainsString('async loadPersonnel() {', $page);
        $this->assertStringContainsString("const res = await (this.\$store.session.canPerm('sakuna_incidents', 'assign') ? \$api.get('/admin/sakuna/assignees') : this.nothing());", $page);
        $modal = $this->source('resources/views/admin/partials/sakuna/_incident-detail-modal.blade.php');
        $this->assertStringContainsString('@click="showAssignForm = !showAssignForm; showEscalateForm = false; loadPersonnel()"', $modal);
        $this->assertStringNotContainsString('ask an administrator to assign this incident', $modal);
    }

    /**
     * 2026-10-28 (owner): an MDRRMO Head holds Sakuna but not User Management,
     * and still picks a person. The picker reads Sakuna's own list and offers
     * only officers who cover the incident's barangay.
     */
    public function test_assign_picker_reads_the_sakuna_list(): void
    {
        $modal = $this->source('resources/views/admin/partials/sakuna/_incident-detail-modal.blade.php');
        $this->assertStringContainsString("personnel.filter(p => p.scope === 'Municipal' || p.scope === selectedIncident?.barangay)", $modal);
        $this->assertStringContainsString("') - ' + p.role", $modal);
        $this->assertStringNotContainsString("canPerm('um_personnel'", $modal);
    }

    /**
     * 2026-10-28/29 (owner): Constituents' Export CSV and PDF built the file in
     * the browser from what was on screen, for every role that could view the
     * registry. Now each export asks the server, which checks the dataset's
     * export permission, keeps to the account's scope and logs it; the
     * buttons show only with that permission. Print prints the page on
     * screen (one page of at most 50 rows), so it stays as it was.
     */
    /**
     * 2026-10-29 (owner): every occupation field is the two DRRM dropdowns from
     * one list (GET /livelihood-classes): the registry's add and edit, the
     * resident's My Profile and Resident Profile with family members, and
     * Sakuna check-in, where a registered resident's occupation comes from the
     * registry. The Occupations tab is the review list; imports take a DRRM
     * code column and say how the file's occupations were classified.
     */
    /**
     * 2026-10-30 (owner): Missing Persons in Sakuna. Its own permission
     * (sakuna_missing: the MDRRMO's roles and the Super Administrator write;
     * the Municipal Administrator and the Information Officer read only, owner
     * 2026-11-05), a tab with filters, the case
     * lifecycle and a record form, the Command Center widget, and the
     * incident's own list of the people recorded under it.
     */
    /**
     * 2026-10-30 (owner): Sakuna's Reports tab is one server calculation under
     * combined filters (barangay, dates, type, status, livelihood group and
     * item); XLSX and PDF are built from the export endpoint, which checks
     * sakuna_reports.export and logs it. Nothing is computed from lists in
     * the browser any more.
     */
    /**
     * 2026-10-30 (owner): livelihood in Sakuna. The Vulnerability tab shows
     * registered residents by DRRM livelihood beside the flood risk their
     * households record; the Agriculture, Fishing and Business forms pick
     * registered farmers, fisherfolk and business owners (owner_ref), and a
     * name typed over a picked one drops the link.
     */
    public function test_vulnerability_and_damage_forms_use_registered_livelihoods(): void
    {
        $vulnerability = $this->source('resources/views/admin/partials/sakuna/tab-vulnerability.blade.php');
        $this->assertStringContainsString("'/admin/sakuna/vulnerability/livelihood'", $vulnerability);
        $this->assertStringContainsString('x-model="lvF.flood_risk"', $vulnerability);

        $forms = $this->source('resources/views/admin/partials/sakuna/tab-bdrrmc-forms.blade.php');
        $this->assertStringContainsString("'/admin/sakuna/registered-people'", $forms);
        $this->assertStringContainsString('row.owner = p.name; row.owner_ref = p.ref;', $forms);
        $this->assertStringContainsString('Not registered? Write the owner in on the form.', $forms);
        $this->assertStringContainsString("if (c.key === 'owner' && slot.r.owner_ref) slot.r.owner_ref = ''", $this->source('resources/views/admin/partials/sakuna/bdrrmc-paper.blade.php'));
    }

    public function test_sakuna_reports_come_from_the_server_with_combined_filters(): void
    {
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-reports.blade.php');
        $this->assertStringContainsString('x-data="sakunaReport"', $tab);
        foreach (['f.barangay', 'f.from', 'f.to', 'f.type', 'f.status', 'f.livelihood_group', 'f.livelihood_code'] as $filter) {
            $this->assertStringContainsString('x-model="'.$filter.'"', $tab, $filter);
        }
        $this->assertStringContainsString("x-show=\"can('sakuna_reports','export')\"", $tab);
        $this->assertStringNotContainsString('reportScopedIncidents', $tab, 'no figures computed in the browser');

        $js = $this->source('resources/js/sakuna-report.js');
        foreach (["'/admin/sakuna/reports/summary'", "'/admin/sakuna/reports/incidents'", "'/admin/sakuna/reports/export'", 'writeXlsx', 'writePdf'] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
        $this->assertStringContainsString("Alpine.data('sakunaReport', sakunaReport)", $this->source('resources/js/app.js'));

        // 2026-10-30 (owner): a situation report, not incidents alone. The
        // screen, the XLSX and the PDF carry the same sections, as counts.
        $this->assertStringContainsString('aria-label="Report sections"', $tab);
        $this->assertStringContainsString('tablesFor(section)', $tab);
        foreach (["'evacuation'", "'relief'", "'resources'", "'damage'", "'missing'", "'alerts'", 'reportTables(d, true)', 'window.reportPdf(sections'] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
        $this->assertStringContainsString('export async function reportPdf(', $this->source('resources/js/table-pdf.js'));
        $this->assertStringContainsString('window.reportPdf = ', $this->source('resources/js/app.js'));
    }

    public function test_missing_persons_has_its_tab_widget_and_permission(): void
    {
        $roles = collect(config('esperanza_rbac.roles'))->keyBy('key');
        $this->assertSame(['view', 'create', 'edit', 'close'], $roles['super_admin']['permissions']['sakuna_missing']);
        $this->assertSame(['view'], $roles['municipal_admin']['permissions']['sakuna_missing']);
        $this->assertArrayNotHasKey('sakuna_missing', $roles['records_officer']['permissions']);

        $page = $this->source('resources/views/admin/sakuna.blade.php');
        $this->assertStringContainsString("'missing-persons': 'sakuna_missing'", $page);
        $this->assertStringContainsString("@include('admin.partials.sakuna.tab-missing'", $page);
        $this->get('/admin/sakuna/missing-persons')->assertOk();

        // The tab's logic lives in sakuna-casualties.js since casualty records (2026-11-14).
        $tab = $this->source('resources/views/admin/partials/sakuna/tab-missing.blade.php').$this->source('resources/js/sakuna-casualties.js');
        foreach (["'/admin/sakuna/missing'", "'/validation'", "'/status'", "can('sakuna_missing','create')", "can('sakuna_missing','close')", 'FEATURE_OFF'] as $needle) {
            $this->assertStringContainsString($needle, $tab);
        }
        $this->assertStringContainsString("'/admin/sakuna/missing/summary'", $this->source('resources/views/admin/partials/sakuna/tab-command.blade.php'));
        $this->assertStringContainsString('selectedIncident?.persons', $this->source('resources/views/admin/partials/sakuna/_incident-detail-modal.blade.php'));

        // A Balita post from a case is a draft for review, never posted by itself.
        $this->assertStringContainsString("'/announcement'", $tab);
        $this->assertStringContainsString("can('sakuna_missing','edit') && can('comm_news','create')", $tab);
        $this->assertStringContainsString('Nothing is posted by itself.', $tab);
    }

    public function test_occupations_use_the_drrm_dropdowns_everywhere(): void
    {
        $component = $this->source('resources/views/components/livelihood-select.blade.php');
        $this->assertStringContainsString('$store.livelihood.items(lvGroup)', $component);
        $this->assertStringContainsString('livelihood_other', $component);
        $this->assertStringContainsString("api.get('/livelihood-classes')", $this->source('resources/js/app.js'));

        foreach ([
            'resources/views/admin/constituents/modal-add-individual.blade.php' => 'model="form"',
            'resources/views/admin/constituents/modal-individual-detail.blade.php' => 'model="edit"',
            'resources/views/citizen/profile.blade.php' => 'model="editForm"',
            'resources/views/components/citizen/resident-profile-form.blade.php' => 'model="personal"',
            'resources/views/admin/partials/sakuna/tab-evacuees.blade.php' => 'model="m"',
        ] as $path => $model) {
            $view = $this->source($path);
            $this->assertStringContainsString('<x-livelihood-select '.$model, $view, $path);
            $this->assertDoesNotMatchRegularExpression('/<input[^>]*x-model="[^"]*occupation"/', $view, "{$path}: no free-text occupation left");
        }
        $this->assertStringContainsString('<x-livelihood-select model="m" dynamic-id', $this->source('resources/views/components/citizen/resident-profile-form.blade.php'), 'family members too');

        // An unchanged choice is not re-sent, so saving another field never makes the matcher's reading a staff decision.
        $this->assertStringContainsString('delete body.livelihood_code', $this->source('resources/views/admin/constituents/modal-individual-detail.blade.php'));
        $this->assertStringContainsString('x._lv.code', $this->source('resources/js/resident-profile-form.js'));
        // Check-in sends nothing for a registered resident's standing occupation.
        $this->assertStringContainsString('if (m.individual_id && m.registry_has_occupation && !m.update_registry) return {};', $this->source('resources/views/admin/sakuna.blade.php'));
        // The desk learns only that the registry has one, never the occupation itself.
        $this->assertStringContainsString('registry_has_occupation: !!m.occupation_on_record', $this->source('resources/js/sakuna-shelter.js'));

        $review = $this->source('resources/views/admin/constituents/tab-occupations.blade.php');
        foreach (["'/admin/livelihood/review'", "'/admin/livelihood/review/records'", "'/admin/livelihood/review/classify'"] as $endpoint) {
            $this->assertStringContainsString($endpoint, $review);
        }
        $this->assertStringContainsString("@include('admin.constituents.tab-occupations')", $this->source('resources/views/admin/constituents.blade.php'));
        $this->assertStringContainsString("key: 'livelihood_code'", $this->source('resources/js/resident-import.js'));
        $this->assertStringContainsString('livelihood-review-import', $this->source('resources/views/lazy/constituents-import.blade.php'));
    }

    public function test_constituents_export_goes_through_the_server(): void
    {
        $page = $this->source('resources/views/admin/constituents.blade.php');
        $quality = $this->source('resources/views/admin/constituents/tab-data-quality.blade.php');
        $profiling = $this->source('resources/views/admin/constituents/tab-profiling.blade.php');

        $this->assertSame(2, substr_count($page, "x-show=\"can(view==='individuals' ? 'const_resident' : 'const_household', 'export')\" @click=\"view==='individuals'?exportIndividuals("));
        $this->assertSame(2, substr_count($quality, "x-show=\"can('const_data_quality', 'export')\""));
        $this->assertSame(2, substr_count($profiling, "x-show=\"can('const_barangay', 'export')\""));

        // Every file is made from the rows the server returned.
        $this->assertStringContainsString("\$api.post('/admin/constituents/exports/' + dataset", $page);
        foreach (['residents', 'families', 'households'] as $dataset) {
            $this->assertStringContainsString("'{$dataset}'", $page);
        }
        $this->assertSame(2, substr_count($quality, "exportShown('data-quality', filteredQualityIssues, qualityIssues"));
        $this->assertSame(2, substr_count($profiling, "exportFrom('coverage', {}"));
        foreach ([$page, $quality, $profiling] as $source) {
            $this->assertDoesNotMatchRegularExpression('/@click="[^"]*export(CSV|PDF)\(/', $source, 'no button builds a file from browser data');
        }
        $this->assertStringNotContainsString("\$api.getAll('/admin/residents', { params: this.remote.params", $page);

        $this->assertStringContainsString('<button @click="window.print()"', $page, 'Print stays');
    }

    /**
     * 2026-10-28: the MDRRMO's roles (in the role contract since 2026-11-05)
     * read "MDRRMO Head" rather than "Mdrrmo Head", are office-restricted like a
     * department (backend Scope.php), and the Head is told what Balita is for.
     */
    public function test_mdrrmo_roles_are_named_and_kept_to_their_office(): void
    {
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString("mdrrmo_head: 'MDRRMO Head', mdrrmo_staff: 'MDRRMO Staff'", $app);
        $this->assertStringContainsString("['department_head', 'department_staff', 'mdrrmo_head', 'mdrrmo_staff', 'mio_staff'].includes(this.account.roleKey)", $app);
        $this->assertStringContainsString("mdrrmo_head: 'MDRRMO Head: you approve and publish the MDRRMO’s own Balita posts", $this->source('resources/views/admin/communications.blade.php'));

        // The Municipal Information Office as a department (2026-11-02): MIO
        // Staff write and send for review, held to their own office, with
        // their own note; the Municipal Information Officer is their head.
        $staff = collect(config('esperanza_rbac.roles'))->keyBy('key')['mio_staff'];
        $this->assertSame('MIO Staff', $staff['name']);
        $this->assertSame([
            'comm_news' => ['view', 'create', 'edit', 'review'],
            'comm_banners' => ['view', 'create', 'edit', 'review'],
            'comm_events' => ['view', 'create', 'edit', 'review'],
        ], $staff['permissions'], 'no approve, archive or delete: the head does those');
        $this->assertStringContainsString("mio_staff: 'MIO Staff'", $app);
        $page = $this->source('resources/views/admin/communications.blade.php');
        $this->assertStringContainsString("mio_staff: 'MIO Staff: you write Balita posts, banners, occasions and events and send them for review. Your MIO Head", $page);
        $this->assertStringContainsString('review what your MIO Staff and the offices send', $page);
        $this->assertStringContainsString("x-show=\"can('create') && !approver && \$store.session.account?.roleKey !== 'mio_staff'\"", $this->source('resources/views/admin/communications/balita.blade.php'));
        $this->assertStringContainsString("'Your MIO Head is notified to review it.'", $this->source('resources/js/scope-admin-records.js'));
    }
}
