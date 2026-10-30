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
        $this->assertStringContainsString("|| \$store.session.canPerm('set_service_catalog', 'view')\" x-cloak>", $tulong, 'the switch shows for whoever may see the types');
    }

    public function test_settings_lost_the_catalog_and_its_old_address_forwards(): void
    {
        $settings = $this->source('resources/views/admin/settings.blade.php');
        $this->assertStringNotContainsString('service-catalog', $settings);
        $this->assertStringNotContainsString('Service Catalog\',', $settings);
        $this->assertFileDoesNotExist(base_path('resources/views/admin/settings/service-catalog.blade.php'));

        $page = $this->get('/admin/settings/service-catalog')->assertOk()
            ->assertSee("x-init=\"window.location.replace('/admin/document-requests/types')\"", false)
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
}
