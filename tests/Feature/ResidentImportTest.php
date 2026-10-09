<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Constituents -> Import from a file (owner, 2026-10-09). The LGU's resident
 * lists are read in the browser by SheetJS, which must stay the patched build
 * and stay out of the first page load, and only mapped rows go to the API.
 */
class ResidentImportTest extends TestCase
{
    public function test_sheetjs_is_the_patched_build_from_its_own_cdn(): void
    {
        $package = json_decode(file_get_contents(base_path('package.json')), true);

        // npm's xlsx stops at 0.18.5, which has CVE-2023-30533 (prototype
        // pollution) and CVE-2024-22363 (ReDoS). SheetJS publishes the fixed
        // versions on its own CDN only.
        $this->assertSame('https://cdn.sheetjs.com/xlsx-0.20.3/xlsx-0.20.3.tgz', $package['dependencies']['xlsx'] ?? null);
    }

    public function test_reading_files_is_loaded_only_when_the_import_opens(): void
    {
        $app = file_get_contents(base_path('resources/js/app.js'));
        $ui = file_get_contents(base_path('resources/js/resident-import-ui.js'));
        $reader = file_get_contents(base_path('resources/js/resident-import.js'));

        $this->assertDoesNotMatchRegularExpression('/^import .*[\'"](xlsx|\.\/resident-import\.js)[\'"]/m', $app.$ui, 'a static import would put SheetJS in the initial bundle');
        $this->assertStringContainsString("import('./resident-import.js')", $ui);
        $this->assertStringContainsString("import('xlsx')", $reader);
        $this->assertStringNotContainsString('FormData', $ui, 'the file itself is never uploaded; only mapped rows are sent');
    }

    public function test_the_registry_offers_the_import_to_who_may_add_residents(): void
    {
        $page = file_get_contents(resource_path('views/admin/constituents.blade.php'));

        $this->assertStringContainsString("@include('admin.constituents.modal-import')", $page);
        $this->assertMatchesRegularExpression('/can\(\'const_resident\',\'create\'\)" class="border-t[^>]*>\s*<button @click="showAddMenu=false;\$dispatch\(\'open-resident-import\'\)"/', $page);
        $this->assertStringContainsString('x-on:residents-imported.window="loadRegistry()"', $page);
    }
}
