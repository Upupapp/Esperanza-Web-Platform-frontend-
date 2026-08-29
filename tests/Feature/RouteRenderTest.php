<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Gate 1 of TAB 02 — the route render gate.
 *
 * Walks every statically exportable route, asserts 200, and asserts the body
 * carries none of the Blade failure markers that still return 200. This is the
 * class of defect a static export turns into a broken deploy: the page renders,
 * the exporter writes it, and the error ships as content.
 */
class RouteRenderTest extends TestCase
{
    /** Failures that can reach the browser without changing the status code. */
    private const ERROR_MARKERS = [
        'Undefined variable',
        'Undefined array key',
        'Undefined property',
        'ViewException',
        'is not defined',            // Route [...] not defined
        'htmlspecialchars(): Argument',
        'Attempt to read property',
    ];

    public function test_the_vite_manifest_is_present_before_any_route_is_judged(): void
    {
        $this->assertFileExists(
            public_path('build/manifest.json'),
            'public/build is gitignored, so a fresh checkout has no Vite manifest and EVERY route '
            .'500s with "Vite manifest not found". Run `npm run build` before this gate, or it '
            .'reports a catastrophe that is not there.'
        );
    }

    public function test_every_exportable_route_renders_cleanly(): void
    {
        require_once base_path('build/export_manifest.php');

        $routes = esperanza_export_routes(app('router')->getRoutes());

        $this->assertNotEmpty($routes, 'No exportable routes were derived — the gate would pass vacuously.');

        $failures = [];

        foreach (array_keys($routes) as $uri) {
            try {
                $response = $this->get($uri);
            } catch (\Throwable $e) {
                $failures[] = sprintf('%s — threw %s: %s', $uri, $e::class, $e->getMessage());

                continue;
            }

            if ($response->getStatusCode() !== 200) {
                $failures[] = sprintf('%s — HTTP %d', $uri, $response->getStatusCode());

                continue;
            }

            $body = $response->getContent();

            foreach (self::ERROR_MARKERS as $marker) {
                if (str_contains($body, $marker)) {
                    $failures[] = sprintf('%s — 200 but body contains "%s"', $uri, $marker);
                    break;
                }
            }
        }

        $this->assertSame(
            [],
            $failures,
            sprintf("%d of %d routes did not render cleanly:\n  %s",
                count($failures), count($routes), implode("\n  ", $failures))
        );
    }
}
