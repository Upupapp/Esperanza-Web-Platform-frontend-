<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * export.php is the deploy artifact, not routes/web.php.
 *
 * Netlify runs it at build time and publishes dist/. Anything it fails to write
 * is a 404 on the live site while still returning 200 locally -- which is how
 * nine routes and sixteen reference PDFs shipped broken. These tests guard the
 * two ways that file can silently drift from the application.
 */
class StaticExportTest extends TestCase
{
    private function exporter(): string
    {
        return file_get_contents(base_path('export.php'));
    }

    public function test_route_map_is_derived_from_the_router_not_hardcoded(): void
    {
        $exporter = $this->exporter();

        $this->assertStringContainsString(
            'getRoutes()',
            $exporter,
            'export.php must derive its route map from the router. A hand-maintained '.
            'list drifts from routes/web.php and the drift is invisible locally.'
        );

        // A reverted map would look like "'/citizen/login' => 'citizen/login/index.html'".
        $this->assertSame(
            0,
            preg_match_all('#[\'"]/[a-z0-9/-]+[\'"]\s*=>\s*[\'"][a-z0-9/-]+/index\.html[\'"]#i', $exporter),
            'export.php contains hardcoded URI => index.html pairs. The route map must be '.
            'derived from Route::getRoutes(); see FRONTEND_PENDING.md F-001.'
        );
    }

    public function test_asset_map_covers_every_public_directory(): void
    {
        preg_match('/\$assets\s*=\s*\[(.*?)\];/s', $this->exporter(), $m);

        $this->assertNotEmpty($m, 'Could not find the $assets map in export.php.');

        preg_match_all('#[\'"](public/[a-z0-9_-]+)[\'"]#i', $m[1], $found);
        $mapped = $found[1];

        foreach (glob(public_path('*'), GLOB_ONLYDIR) as $dir) {
            $relative = 'public/'.basename($dir);

            $this->assertContains(
                $relative,
                $mapped,
                "{$relative}/ exists but export.php never copies it into dist/, so every file "
                .'in it is a 404 in production. Add it to the $assets map.'
            );
        }
    }

    public function test_reference_form_pdfs_are_exported(): void
    {
        // Explicit regression guard for F-002: these are rendered as a live
        // <iframe> and a download link on the admin Dokyu and Tulong pages.
        $this->assertStringContainsString(
            "'public/documents'",
            $this->exporter(),
            'public/documents is missing from the $assets map; every reference-form '.
            'preview would be an empty frame in production (FRONTEND_PENDING.md F-002).'
        );

        $this->assertNotEmpty(
            glob(public_path('documents/reference-forms/*.pdf')),
            'No reference-form PDFs found under public/documents/reference-forms/.'
        );
    }
}
