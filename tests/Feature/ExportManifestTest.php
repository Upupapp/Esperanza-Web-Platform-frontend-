<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Tests\TestCase;

/**
 * Gate 2 of TAB 02 — the export-map drift gate.
 *
 * F-001 and F-002 were both the same defect: a hand-maintained list in
 * export.php that drifted from reality, invisible locally because every route
 * returns 200 on `artisan serve`. These assertions make that drift fail the
 * build instead of the deploy.
 */
class ExportManifestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once base_path('build/export_manifest.php');
    }

    public function test_every_renderable_route_is_in_the_export_map(): void
    {
        // Pages, plus the lazy partials (windows kept out of their page's HTML,
        // 2026-11-25), which the export writes as files of their own.
        $exported = esperanza_export_routes(app('router')->getRoutes())
            + esperanza_export_partials(app('router')->getRoutes());
        $skip = esperanza_export_skip_uris();

        $missing = [];

        foreach (app('router')->getRoutes() as $route) {
            $uri = $route->uri();

            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            if (str_contains($uri, '{') || in_array($uri, $skip, true)) {
                continue;
            }
            if (str_starts_with($uri, '_') || str_starts_with($uri, 'storage/')) {
                continue;
            }

            if (! array_key_exists('/'.ltrim($uri, '/'), $exported)) {
                $missing[] = $uri;
            }
        }

        $this->assertSame([], $missing, sprintf(
            '%d route(s) exist in the router but would never be written to dist/, so they 404 in '
            ."production while returning 200 locally:\n  /%s",
            count($missing), implode("\n  /", $missing)
        ));
    }

    public function test_the_export_map_is_not_vacuous(): void
    {
        $exported = esperanza_export_routes(app('router')->getRoutes());

        $this->assertGreaterThanOrEqual(55, count($exported),
            'The export map collapsed. A gate that exports nothing passes every other assertion here.');
        $this->assertArrayHasKey('/', $exported, 'The portal chooser is not being exported.');
    }

    public function test_the_legal_pages_the_registration_form_requires_are_exported(): void
    {
        $exported = esperanza_export_routes(app('router')->getRoutes());

        // auth/citizen-register.blade.php makes the citizen accept these before
        // they can register. F-001 shipped with both of them 404ing.
        $this->assertArrayHasKey('/citizen/legal/terms', $exported);
        $this->assertArrayHasKey('/citizen/legal/privacy-policy', $exported);
    }

    public function test_every_public_directory_is_either_exported_or_deliberately_excluded(): void
    {
        $assets = esperanza_export_assets();

        // Directories that exist only to serve the local PHP runtime, which has
        // no counterpart in a static export.
        $notDeployed = ['hot', 'storage'];

        $unmapped = [];

        foreach (glob(public_path('*'), GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);

            if (in_array($name, $notDeployed, true)) {
                continue;
            }
            if (! array_key_exists('public/'.$name, $assets)) {
                $unmapped[] = $name;
            }
        }

        $this->assertSame([], $unmapped, sprintf(
            'public/%s is served locally but never copied into dist/, so every asset in it 404s in '
            .'production. Add it to esperanza_export_assets() or to the notDeployed list.',
            implode(', public/', $unmapped)
        ));
    }

    public function test_the_reference_form_pdfs_are_mapped(): void
    {
        // F-002: 16 reference-form PDFs are rendered in a live <iframe> on the
        // admin request screens and were never copied into dist/.
        $this->assertArrayHasKey('public/documents', esperanza_export_assets());
        // The PDFs sit under documents/reference-forms/, so this must recurse:
        // a flat glob reports zero and turns a working export into a red gate.
        $pdfs = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator(public_path('documents'))),
            '/[.]pdf$/i'
        );

        $this->assertNotEmpty(iterator_to_array($pdfs), 'No reference PDFs found to export.');
    }
}
