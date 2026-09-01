<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every page route must render.
 *
 * This project deploys as a static export (export.php renders each route to
 * dist/ and Netlify serves flat files), so a view that throws is not a runtime
 * error a user might never hit -- it is a page missing from the build. Two P0
 * defects reached production before this gate existed, and both would have been
 * caught here in about a second.
 */
class RouteRenderTest extends TestCase
{
    /**
     * Markers that mean Blade rendered but produced a broken page. A 200 alone
     * is not proof of health -- Laravel returns 200 with an error partial in
     * several of these cases.
     */
    private const FAILURE_MARKERS = [
        'Undefined variable',
        'Undefined array key',
        'Undefined property',
        'ErrorException',
        'ViewException',
        'BadMethodCallException',
        'Attempt to read property',
        'htmlspecialchars(): Argument',
        'is not defined',            // Route [...] not defined
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // The layouts call @vite, which throws without a build. Fail with the
        // fix rather than 55 confusing stack traces -- a fresh checkout has no
        // public/build (it is gitignored), which reads as a catastrophe.
        if (! file_exists(public_path('build/manifest.json'))) {
            $this->fail(
                'Vite manifest missing. Run "npm ci && npm run build" before the test suite; '.
                'public/build is gitignored, so a fresh checkout has none.'
            );
        }
    }

    /**
     * The routes the exporter will actually write.
     *
     * This deliberately calls the SAME function export.php calls rather than
     * re-implementing the filter here. A test that reassembles the production
     * predicate can drift wider or narrower than it, and then it is guarding
     * something the deploy does not do.
     */
    private function pageRoutes(): array
    {
        require_once base_path('build/export_manifest.php');

        return array_keys(esperanza_export_routes($this->app->make('router')->getRoutes()));
    }

    public function test_the_vite_manifest_is_present_before_any_route_is_judged(): void
    {
        $this->assertFileExists(
            public_path('build/manifest.json'),
            'public/build is gitignored, so a fresh checkout has no Vite manifest and EVERY route '
            .'500s with "Vite manifest not found". Run `npm run build` before this gate, or it '
            .'reports a catastrophe that is not there.'
        );
    }

    public function test_every_page_route_renders_without_error(): void
    {
        $routes = $this->pageRoutes();

        // Guards the discovery itself: if it silently stopped finding routes,
        // this test would otherwise pass vacuously.
        $this->assertGreaterThanOrEqual(
            50,
            count($routes),
            'Found suspiciously few page routes -- route discovery has probably broken.'
        );

        $failures = [];

        foreach ($routes as $uri) {
            try {
                $response = $this->get($uri);
            } catch (\Throwable $e) {
                $failures[] = sprintf('%s threw %s: %s', $uri, $e::class, $e->getMessage());

                continue;
            }

            $status = $response->getStatusCode();

            if ($status !== 200) {
                $failures[] = "{$uri} returned HTTP {$status}";

                continue;
            }

            $body = (string) $response->getContent();

            if (trim($body) === '') {
                $failures[] = "{$uri} rendered an empty page";

                continue;
            }

            foreach (self::FAILURE_MARKERS as $marker) {
                if (str_contains($body, $marker)) {
                    $failures[] = "{$uri} returned 200 but its HTML contains \"{$marker}\"";

                    break;
                }
            }
        }

        // Report every broken route at once; fixing them one failed run at a
        // time is the slow way to find out the layout is at fault.
        $this->assertSame(
            [],
            $failures,
            count($failures).' of '.count($routes)." route(s) failed to render:\n  ".implode("\n  ", $failures)
        );
    }
}
