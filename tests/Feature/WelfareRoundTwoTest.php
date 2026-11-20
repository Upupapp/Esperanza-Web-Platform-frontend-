<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Round 2 of the welfare work (owner, 2026-10-30), the web's part of items 1-5:
 * Dokyu's and Tulong's types edited on those tabs' own pages instead of
 * Settings > Service Catalog, a Department Head included for their office.
 */
class WelfareRoundTwoTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /** The source with its Blade comments taken out: what can reach the page. */
    private function shown(string $path): string
    {
        return preg_replace('/\{\{--.*?--\}\}/s', '', $this->source($path));
    }

    // ------------------------------------------------- 1. types in their tabs

    public function test_each_tab_has_a_types_page_that_is_exported(): void
    {
        require_once base_path('build/export_manifest.php');
        $exported = esperanza_export_routes(app('router')->getRoutes());

        foreach (['/admin/document-requests/types', '/admin/assistance-requests/types', '/admin/settings/service-catalog'] as $uri) {
            $this->assertArrayHasKey($uri, $exported, $uri.' is written to dist/');
        }

        $this->get('/admin/document-requests/types')->assertOk()
            ->assertSee("catalogEditor('dokyu')", false)
            ->assertSee('Add a document type')
            ->assertSee('Search document types', false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('Add a service')
            ->assertDontSee('Kind of service');

        $this->get('/admin/assistance-requests/types')->assertOk()
            ->assertSee("catalogEditor('tulong')", false)
            ->assertSee('Add a Tulong type')
            ->assertSee('Tulong types')
            ->assertSee("canPerm('tulong_sessions', 'view')", false)
            ->assertDontSee('Add a service');
    }

    public function test_the_editor_has_a_fixed_kind_and_never_says_service(): void
    {
        $partial = $this->shown('resources/views/admin/partials/type-catalog.blade.php');
        $js = $this->source('resources/js/catalog-editor.js');

        $this->assertStringNotContainsString('x-model="kind"', $partial, 'no kind to choose');
        $this->assertStringNotContainsString('new-svc-type', $partial);
        foreach (['Service name', 'Add a service', 'Service Details', 'No services match', 'Search services', 'Add service', 'service off', 'Choose a service'] as $gone) {
            $this->assertStringNotContainsString($gone, $partial, $gone);
        }
        $this->assertStringContainsString("'one' => 'document type'", $partial);
        $this->assertStringContainsString("'one' => 'Tulong type'", $partial);
        $this->assertStringContainsString('type: this.fixedType, name: d.name.trim()', $js, 'a new type is of the page\'s kind');
        $this->assertStringContainsString('this.rows = (res.data || []).filter((r) => r.type === this.fixedType);', $js);

        // The editor's own words: only API addresses, the permission and the audit action mention a service.
        $code = preg_replace(['#/\*.*?\*/#s', '#^\s*//.*$#m'], '', $js);
        preg_match_all("/'((?:[^'\\\\\\n]|\\\\.)*)'/", $code, $m);
        $wording = array_filter($m[1], fn ($s) => stripos($s, 'service') !== false && ! str_starts_with($s, '/') && ! in_array($s, ['catalog.service_updated', 'set_service_catalog'], true));
        $this->assertSame([], array_values($wording), 'no "service" in what the editor says');
    }

    public function test_the_editor_loads_only_on_the_types_pages(): void
    {
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString("area(/^\\/admin\\/(document-requests|assistance-requests)\\/types(\\/|$)/.test(areaPath), () => import('./catalog-editor'));", $app);
        $this->assertStringContainsString("Alpine.data('catalogEditor', catalogEditor);", $this->source('resources/js/catalog-editor.js'));
        $this->assertStringNotContainsString('catalogEditor', $this->source('resources/js/scope-admin-settings.js'));
        // Not on the Dokyu and Tulong pages themselves (the Dokyu page is near its budget).
        foreach (['resources/views/admin/document-requests.blade.php', 'resources/views/admin/assistance-requests.blade.php'] as $page) {
            $this->assertStringNotContainsString('catalogEditor', $this->source($page), $page);
            $this->assertStringNotContainsString('type-catalog', $this->source($page), $page);
        }
    }

    public function test_the_tab_pages_switch_to_their_types(): void
    {
        $dokyu = $this->source('resources/views/admin/document-requests.blade.php');
        $tulong = $this->source('resources/views/admin/assistance-requests.blade.php');

        $this->assertStringContainsString('<a href="{{ route(\'admin.document-requests.types\') }}" x-show="$store.session.canPerm(\'set_service_catalog\', \'view\')"', $dokyu);
        $this->assertStringContainsString('<i data-lucide="file-text" class="w-3.5 h-3.5"></i> Requests</button>', $dokyu);
        $this->assertStringContainsString('<a href="{{ route(\'admin.assistance-requests.types\') }}" x-show="$store.session.canPerm(\'set_service_catalog\', \'view\')"', $tulong);
        $this->assertStringContainsString("|| \$store.session.canPerm('set_service_catalog', 'view') || \$store.session.canStaffAccess()\" x-cloak>", $tulong, 'the switch shows for whoever may see the types or set Staff access');
    }

    public function test_settings_lost_the_catalog_and_its_old_address_forwards(): void
    {
        $settings = $this->source('resources/views/admin/settings.blade.php');
        $this->assertStringNotContainsString('service-catalog', $settings);
        $this->assertStringNotContainsString('Service Catalog\',', $settings);
        $this->assertFileDoesNotExist(base_path('resources/views/admin/settings/service-catalog.blade.php'));

        $page = $this->get('/admin/settings/service-catalog')->assertOk()
            // The query and fragment go along since 2026-11-18 (FeedbackInSettingsTest).
            ->assertSee("x-init=\"window.location.replace('/admin/document-requests/types' + window.location.search + window.location.hash)\"", false)
            ->assertSee('href="/admin/document-requests/types"', false)
            ->getContent();
        // The CSP allows no inline script: every <script> on the page has a src.
        preg_match_all('/<script\b([^>]*)>/i', $page, $scripts);
        foreach ($scripts[1] as $attributes) {
            $this->assertStringContainsString('src=', $attributes, 'no inline script on the forwarding page');
        }
        $this->assertStringNotContainsString('onclick=', $page);
    }

    /**
     * A Department Head holds set_service_catalog for their office's types.
     * It is a settings submodule, so the module alone would have opened
     * Settings for every head: Settings is the module without it.
     */
    public function test_a_department_head_gets_the_catalog_but_not_settings(): void
    {
        $app = $this->source('resources/js/app.js');
        $this->assertStringContainsString("canSettings() {\n            if (!this.can('settings')) return false;", $app);
        $this->assertStringContainsString("sub.startsWith('set_') && sub !== 'set_service_catalog'", $app);

        // Every Settings check in the views asks canSettings(), never the bare module.
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($views as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $this->assertStringNotContainsString("can('settings')", file_get_contents($file->getPathname()), $file->getFilename());
            }
        }
        $this->assertStringContainsString('<a :href="$store.session.canSettings() ?', $this->source('resources/views/components/admin/topbar.blade.php'));

        // The rule over the contract's roles: who would get Settings.
        $settingsPage = function (array $permissions): bool {
            foreach ($permissions as $sub => $actions) {
                if (str_starts_with($sub, 'set_') && $sub !== 'set_service_catalog' && ! empty($actions)) {
                    return true;
                }
            }

            return false;
        };
        $roles = collect(config('esperanza_rbac.roles'))->keyBy('key');
        $head = $roles['department_head']['permissions'];
        $this->assertSame(['view', 'edit'], $head['set_service_catalog'], 'a head edits their office\'s types');
        $this->assertFalse($settingsPage($head), 'a head has no Settings page');
        $this->assertTrue($settingsPage($roles['municipal_admin']['permissions']), 'the Municipal Administrator keeps Settings');
    }

    public function test_a_department_heads_office_is_fixed(): void
    {
        $js = $this->source('resources/js/catalog-editor.js');
        $partial = $this->source('resources/views/admin/partials/type-catalog.blade.php');

        $this->assertStringContainsString('if (!s?.deptRestricted?.()) return \'\';', $js);
        $this->assertStringContainsString('if (this.ownOffice) return [this.ownOffice];', $js);
        $this->assertStringContainsString("office: this.ownOffice || ''", $js);
        $this->assertStringContainsString(':disabled="officeLocked"', $partial);
        $this->assertStringContainsString(':disabled="!canEdit || officeLocked"', $partial);
        $this->assertStringContainsString('deptRestricted() {', $this->source('resources/js/app.js'));
    }

    // ------------------------------------- 2. requirements uploaded, not seen

    public function test_apply_uploads_each_requirement_instead_of_ticking_it(): void
    {
        $apply = $this->shown('resources/views/admin/tulong/apply.blade.php');
        $js = $this->source('resources/js/welfare-admin.js');

        $this->assertStringContainsString('Upload a photo or scan of each document. Documents not uploaded now can be attached to the case later.', $apply);
        $this->assertStringNotContainsString('Presented at the office', $apply);
        $this->assertStringNotContainsString('r.presented', $apply);
        $this->assertStringContainsString('<input type="file" accept="image/*,application/pdf" class="sr-only" @change="apPickFile(r, $event)">', $apply);
        $this->assertStringContainsString('<input type="file" accept="image/*" capture="environment" class="sr-only" @change="apPickFile(r, $event)">', $apply);
        // The chosen file: its name, its size, and Remove.
        $this->assertStringContainsString('x-text="r.file?.name"', $apply);
        $this->assertStringContainsString('x-text="fileSize(r.file?.size)"', $apply);
        $this->assertStringContainsString('@click="apDropFile(r)" class="font-medium text-rose-600 hover:underline">Remove</button>', $apply);
        // Each upload's progress and outcome, kept on its row.
        $this->assertStringContainsString("x-text=\"'Uploading… ' + r.progress + '%'\"", $apply);
        $this->assertStringContainsString('role="progressbar"', $apply);
        $this->assertStringContainsString('x-text="r.error"', $apply);
        $this->assertStringContainsString("file: null, state: '', progress: 0, error: ''", $js);

        // Filed first, without requirements_presented; then each file to the new case.
        $this->assertStringNotContainsString('requirements_presented:', $js);
        $this->assertStringContainsString("const back = await attachAtOffice('tulong', caseRef, target.key, r.file, (p) => { r.progress = p; });", $js);
        $this->assertStringContainsString("r.error = 'Not uploaded: ' + says(err, 'the upload did not go through') + ' Attach it from the case.';", $js);
        $this->assertStringContainsString("this.apError = 'Not uploaded: ' + failed.join(', ')", $js);
        $this->assertStringContainsString('if (this.apBusy || this.apFiled) return;', $js, 'a filed case is never filed again');
        // A walk-in's case is the one new in the session.
        $this->assertStringContainsString('knownRefs } }));', $js);
        $this->assertStringContainsString('!this.apKnownRefs.includes(ref)', $js);
    }

    public function test_one_upload_helper_checks_and_sends_the_file(): void
    {
        $up = $this->source('resources/js/requirement-upload.js');

        $this->assertStringContainsString("'/admin/' + type + '/requests/' + encodeURIComponent(ref) + '/requirements/' + encodeURIComponent(key) + '/attach'", $up);
        $this->assertStringContainsString("body.append('file', file);", $up);
        $this->assertStringContainsString("const UPLOAD_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];", $up);
        $this->assertStringContainsString('const UPLOAD_MAX_BYTES = 10 * 1024 * 1024;', $up);
        $this->assertStringContainsString('onUploadProgress', $up);
        // Imported by the two chunks that upload, never by app.js.
        $this->assertStringContainsString("from './requirement-upload';", $this->source('resources/js/welfare-admin.js'));
        $this->assertStringContainsString("from './requirement-upload';", $this->source('resources/js/scope-admin-requests.js'));
        $this->assertStringNotContainsString('requirement-upload', $this->source('resources/js/app.js'));
    }

    public function test_a_requirement_with_no_file_can_be_uploaded_from_the_case(): void
    {
        $component = $this->source('resources/views/components/admin/requirement-upload.blade.php');
        $js = $this->source('resources/js/scope-admin-requests.js');

        foreach (['resources/views/admin/assistance-requests.blade.php', 'resources/views/admin/document-requests.blade.php'] as $page) {
            $this->assertStringContainsString('<x-admin.requirement-upload />', $this->source($page), $page);
        }
        $this->assertStringContainsString('x-data="requirementUpload()" x-show="upShown(req)"', $component);
        $this->assertStringContainsString('<input type="file" accept="image/*,application/pdf" class="sr-only" :disabled="req._upBusy" @change="upAttach(req, $event)">', $component);
        $this->assertStringContainsString("'Upload file'", $component);
        $this->assertStringContainsString("Alpine.data('requirementUpload', requirementUpload);", $js);
        // Undecided, or accepted as presented with nothing on file; an open
        // request, or a case recorded as help already given.
        $this->assertStringContainsString("if (!r || !req || req.file || !['Submitted', 'Approved'].includes(req.status)) return false;", $js);
        $this->assertStringContainsString("(r.status === 'Completed' && r.filed?.channel === 'record')", $js);
        $this->assertStringContainsString('r.filed = d.filed || null;', $this->source('resources/views/admin/assistance-requests.blade.php'));
        // The answer is the request's detail, which the page takes as its state.
        $this->assertStringContainsString('if (detail && typeof this.applyDetail === \'function\') this.applyDetail(r, detail);', $js);
    }

    // ------------------------------- 3 + 4. services rendered folds into Tulong

    public function test_the_tulong_page_has_no_services_rendered_view_or_window(): void
    {
        foreach (['services-rendered', 'record-service', 'case-service-records'] as $gone) {
            $this->assertFileDoesNotExist(base_path('resources/views/admin/tulong/'.$gone.'.blade.php'));
        }
        $page = $this->shown('resources/views/admin/assistance-requests.blade.php');
        $js = $this->source('resources/js/welfare-admin.js');

        $this->assertStringNotContainsString("'services' => ['Services rendered'", $page);
        $this->assertStringNotContainsString("@include('admin.tulong.services-rendered')", $page);
        $this->assertStringNotContainsString("@include('admin.tulong.record-service')", $page);
        $this->assertStringContainsString("if (params.get('view') === 'sessions') this.view = 'sessions';", $page);
        $this->assertStringContainsString("@include('admin.tulong.case-notes')", $page);
        foreach (['servicesRendered', 'serviceRecordForm', 'residentServices', 'caseServiceRecords', 'welfare-record-service'] as $gone) {
            $this->assertStringNotContainsString($gone, $js, $gone);
        }
        // Record assistance already given stays in Apply.
        $this->assertStringContainsString('Record assistance already given', $this->source('resources/views/admin/tulong/apply.blade.php'));
    }

    public function test_every_tulong_case_has_its_case_notes(): void
    {
        $notes = $this->source('resources/views/admin/tulong/case-notes.blade.php');
        $js = $this->source('resources/js/welfare-admin.js');

        $this->assertStringContainsString('x-data="caseNotes()" x-effect="cnSync()" x-show="cnShown()"', $notes);
        $this->assertStringContainsString('</i> Case notes</p>', $notes);
        $this->assertStringContainsString('@click="cnStartAdding()">Add a note</x-ui.button>', $notes);
        foreach (['>Date *</label>', '>How</label>', '>What was done *</label>'] as $field) {
            $this->assertStringContainsString($field, $notes, $field);
        }
        $this->assertStringNotContainsString('Service records', $this->shown('resources/views/admin/tulong/case-notes.blade.php'));
        $this->assertStringContainsString("Alpine.data('caseNotes', caseNotes);", $js);
        // On every Tulong type (owner, 2026-11-06), not only counseling,
        // referral and session cases; staff only.
        $this->assertStringContainsString("cnShown() {\n            return !!this.selected;\n        },", $js);
        $this->assertStringNotContainsString("['counseling', 'referral', 'session'].includes(this.processingKind)", $js);
        $this->assertStringContainsString('x-text="cnHint()"', $notes);
        $this->assertStringContainsString('Staff only: the resident does not see these notes.', $notes);
        // Still a services-rendered row on the case: the person and type are the case's.
        $this->assertStringContainsString("await api.post('/admin/services-rendered', {\n                    request_ref: ref,", $js);
        $this->assertStringContainsString("const NOTE_MODES = [['office', 'At the office'], ['home_visit', 'Home visit'], ['field', 'In the field'], ['online', 'Online or by phone']];", $js);
        $this->assertStringContainsString('/complete-service', $js);
    }

    public function test_the_resident_profile_and_the_citizen_page_drop_their_services_lists(): void
    {
        $detail = $this->source('resources/views/admin/constituents/modal-individual-detail.blade.php');
        $this->assertStringNotContainsString('residentServices', $detail);
        $this->assertStringNotContainsString('Services Rendered', $detail);
        $this->assertStringNotContainsString('services-rendered', $detail);
        // So Constituents no longer loads the welfare chunk.
        $this->assertStringContainsString("area(/^\\/admin\\/assistance-requests(\\/|$)/.test(areaPath), () => import('./welfare-admin'));", $this->source('resources/js/app.js'));

        $card = $this->source('resources/views/components/citizen/welfare-received.blade.php');
        $citizen = $this->source('resources/js/welfare-citizen.js');
        $this->assertStringNotContainsString('<x-ui.t fil="Mga natanggap na serbisyo"', $card);
        $this->assertStringNotContainsString("api.get('/citizen/services-rendered')", $citizen);
        // The certificates stay.
        $this->assertStringContainsString('<x-ui.t fil="Aking mga sertipiko" en="My certificates" />', $card);
        $this->assertStringContainsString("api.get('/citizen/certificates')", $citizen);
    }

    // ------------------------------------------------ 5. "Tulong Type" wording

    public function test_the_tulong_screens_say_tulong_type_not_service(): void
    {
        $apply = $this->shown('resources/views/admin/tulong/apply.blade.php');
        $js = $this->source('resources/js/welfare-admin.js');

        $this->assertStringContainsString("apSteps: ['Person', 'Tulong Type', 'Form', 'Requirements & consent'],", $js);
        $this->assertStringContainsString("'Which Tulong type?'", $apply);
        $this->assertStringContainsString('aria-label="Search Tulong types" placeholder="Search Tulong types"', $apply);
        $this->assertStringContainsString("if (step === 2) return this.apKey ? '' : 'Choose the Tulong type.';", $js);
        $this->assertStringContainsString('<label for="sf-service" class="{{ $sdLabel }}">Tulong Type *</label>', $this->source('resources/views/admin/tulong/session-desk.blade.php'));
        $this->assertStringContainsString('<option value="">All Tulong types</option>', $this->source('resources/views/admin/tulong/sessions.blade.php'));
        // The Tulong list and the case window too (live check, 2026-11-02).
        $list = $this->source('resources/views/admin/assistance-requests.blade.php');
        $this->assertStringContainsString('text-ink-muted">Tulong Type</th>', $list);
        $this->assertStringContainsString('uppercase tracking-wide">Tulong Type</p>', $list);
        $this->assertStringNotContainsString('>Assistance Type<', $list);

        // No "service" left in what these screens show: text between tags,
        // and the quoted strings their bindings print.
        $left = [];
        foreach (['apply', 'sessions', 'session-desk', 'case-session', 'case-notes'] as $partial) {
            $view = $this->shown('resources/views/admin/tulong/'.$partial.'.blade.php');
            preg_match_all('/>([^<>]*)</', $view, $text);
            preg_match_all("/'([^'\\n]*)'/", $view, $strings);
            foreach (array_merge($text[1], $strings[1]) as $words) {
                if (preg_match('/\bservices?\b/i', $words)) {
                    $left[] = $partial.': '.trim($words);
                }
            }
        }
        $this->assertSame([], $left);
        foreach (["'Choose the service.'", "'Service'", 'All session services', 'Which service?', 'Search the services'] as $gone) {
            $this->assertStringNotContainsString($gone, $js.$apply, $gone);
        }
    }
}
