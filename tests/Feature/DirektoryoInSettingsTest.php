<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The directory moved from Balita to Settings (owner, 2026-10-21: "move it to
 * the settings screen ... name the offices + barangays as direktoryo, as same
 * as how it flows in user side"). Everyone who kept it before keeps it: the
 * administrators, the Municipal Information Officer and each Barangay
 * Secretary (their own barangay), who reach it from the profile menu.
 */
class DirektoryoInSettingsTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_settings_has_direktoryo_with_offices_and_barangays_as_residents_see_them(): void
    {
        $settings = $this->source('resources/views/admin/settings.blade.php');
        $tab = $this->source('resources/views/admin/settings/directory.blade.php');
        $js = $this->source('resources/js/scope-admin-settings.js');

        $this->assertStringContainsString("'directory' => ['book-user', 'Direktoryo', '/admin/settings/directory']", $settings);
        $this->assertStringContainsString("@include('admin.settings.directory')", $settings);
        $this->assertStringContainsString('x-data="directoryTab()"', $tab);
        $this->assertStringContainsString("Alpine.data('directoryTab', directoryTab)", $js);
        // The residents' flow: a search, Municipal Offices | Barangays, a count, the cards.
        $this->assertStringContainsString('aria-label="Search the directory"', $tab);
        $this->assertStringContainsString('Municipal Offices', $tab);
        $this->assertStringContainsString('x-text="countLine"', $tab);
        $this->assertStringContainsString("api.get('/admin/directory', { params: { scope: 'government' } })", $js);
        $this->assertStringContainsString("api.put('/admin/directory/barangay/' + encodeURIComponent(b.name)", $js);
        // Each part for whoever holds it; a secretary sees their own barangay.
        $this->assertStringContainsString("get canOffices() { return may('comm_directory', 'view'); }", $js);
        $this->assertStringContainsString("get canBarangays() { return may('comm_barangay_directory', 'view'); }", $js);
        $this->assertStringContainsString('session()?.inScope(b.name)', $js);
        $this->assertStringNotContainsString('confirm(', $tab, 'removal is asked in the page');
    }

    public function test_officers_without_settings_still_reach_it_and_nothing_else_there(): void
    {
        $settings = $this->source('resources/views/admin/settings.blade.php');
        $topbar = $this->source('resources/views/components/admin/topbar.blade.php');

        // Since 2026-10-29 anyone who can post in Balita also reaches the
        // Facebook Page card on Integrations, and nothing more. Settings is
        // the module without the Service Catalog since round 2 (canSettings):
        // a Department Head holds that one for their own office's types.
        // Feedback joined them on 2026-11-18 (FeedbackInSettingsTest).
        $this->assertStringContainsString('x-show="$store.session.canSettings() || directoryAllowed || facebookAllowed || feedbackAllowed"', $settings);
        $this->assertStringContainsString('get facebookAllowed() { return this.$store.session.canPerm(\'comm_news\', \'create\') || this.$store.session.canPerm(\'set_system\', \'view\'); },', $settings);
        $this->assertStringContainsString('@elseif($tabKey === \'integrations\') x-show="$store.session.canSettings() || facebookAllowed"', $settings);
        $this->assertStringContainsString('@elseif($tabKey === \'directory\') x-show="directoryAllowed"', $settings);
        $this->assertStringContainsString('<template x-if="$store.session.canSettings()">', $settings, 'the other tabs load only for Settings holders');
        $this->assertStringContainsString("if (this.\$store.session.canSettings()) {\n                        if (this.tab === 'feedback' && !this.feedbackAllowed) this.go('general');\n                        return;\n                    }", $settings);
        $this->assertStringContainsString("if (this.tab === 'directory' && this.directoryAllowed) return;", $settings);
        $this->assertStringContainsString("if (this.directoryAllowed) this.go('directory');", $settings);
        $this->assertStringContainsString("else if (this.facebookAllowed) this.go('integrations');", $settings);
        $this->assertStringContainsString("\$store.session.canPerm('comm_barangay_directory', 'view') || \$store.session.canPerm('comm_news', 'create') || \$store.session.canPerm('set_system', 'view')\" x-cloak", $topbar);
        $this->assertStringContainsString("route('admin.settings.directory')", $topbar);
    }

    public function test_balita_no_longer_has_the_directory_and_old_links_open_direktoryo(): void
    {
        $balita = $this->source('resources/views/admin/communications.blade.php');
        $routes = $this->source('routes/web.php');

        foreach (["tabAllowed('offices')", "tabAllowed('barangays')", 'OFFICES (government directory)', '============================= BARANGAYS', 'comm_barangay_directory'] as $gone) {
            $this->assertStringNotContainsString($gone, $balita, $gone);
        }
        $this->assertStringContainsString("window.location.replace('/admin/settings/directory' + (old === 'barangays' ? '?view=barangays' : ''));", $balita);
        // The old address is Balita's page, which sends it on: Settings' scripts load only under /admin/settings.
        $this->assertStringContainsString("Route::view('/admin/communications/offices', 'admin.communications', ['tab' => 'offices'])->name('admin.directory');", $routes);
        $this->assertStringContainsString("Route::view('/admin/settings/directory', 'admin.settings', ['tab' => 'directory'])->name('admin.settings.directory');", $routes);
        $this->assertStringNotContainsString('comm_barangay_directory', $this->source('resources/views/components/admin/sidebar.blade.php'));
    }
}
