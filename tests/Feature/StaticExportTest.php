<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * export.php is the deploy artifact, not routes/web.php.
 *
 * Netlify runs it at build time and publishes dist/. Anything it fails to write
 * is a 404 on the live site while still returning 200 locally -- which is how
 * nine routes and sixteen reference PDFs shipped broken.
 *
 * This file guards the SHAPE of the exporter: that nobody reintroduces a
 * hand-maintained route map. The behavioural guarantees -- that every route is
 * exported, that every public/ directory is copied, that the reference PDFs are
 * mapped -- are asserted against the real functions in ExportManifestTest,
 * because a regex over source text cannot tell you what the exporter will do.
 */
class StaticExportTest extends TestCase
{
    /**
     * Both halves of the exporter: the script and the manifest it delegates to.
     */
    private function exporterSources(): array
    {
        return [
            'export.php' => file_get_contents(base_path('export.php')),
            'build/export_manifest.php' => file_get_contents(base_path('build/export_manifest.php')),
        ];
    }

    public function test_route_map_is_derived_from_the_router_not_hardcoded(): void
    {
        $sources = $this->exporterSources();
        $combined = implode("\n", $sources);

        $this->assertStringContainsString(
            'getRoutes()',
            $combined,
            'The export route map must be derived from the router. A hand-maintained list '.
            'drifts from routes/web.php and the drift is invisible locally.'
        );

        // A reverted map would look like "'/citizen/login' => 'citizen/login/index.html'".
        foreach ($sources as $path => $source) {
            $this->assertSame(
                0,
                preg_match_all('#[\'"]/[a-z0-9/-]+[\'"]\s*=>\s*[\'"][a-z0-9/-]+/index\.html[\'"]#i', $source),
                "{$path} contains hardcoded URI => index.html pairs. The route map must be ".
                'derived from Route::getRoutes(); see FRONTEND_PENDING.md F-001.'
            );
        }
    }

    public function test_the_exporter_refuses_to_publish_an_empty_site(): void
    {
        // An exporter that derives nothing still exits 0 having written nothing,
        // which looks exactly like a successful build.
        $this->assertStringContainsString(
            'refusing to publish an empty site',
            file_get_contents(base_path('export.php')),
            'export.php must fail loudly when the derived route map is empty.'
        );
    }
}
