<?php

/**
 * Esperanza Web Platform — shared static-export manifest.
 *
 * Required by BOTH export.php (the deploy artifact) and the drift gate in
 * tests/Feature/ExportManifestTest.php, so the exporter and the test that
 * guards it can never disagree. A hand-maintained list that only export.php
 * knew about is exactly what let F-001 (9 routes 404) and F-002 (16 broken
 * PDFs) reach production.
 */

if (!function_exists('esperanza_export_skip_uris')) {
    /**
     * URIs the router exposes that are not pages and must not be exported.
     */
    function esperanza_export_skip_uris(): array
    {
        return [
            'up',
            '_ignition/health-check',
            '_ignition/execute-solution',
            '_ignition/update-config',
        ];
    }

    /**
     * Directories copied verbatim into dist/. Repo-relative source => target.
     *
     * Every directory the views reference under public/ must appear here or the
     * asset 404s in production while working perfectly on `artisan serve`.
     */
    function esperanza_export_assets(): array
    {
        return [
            'public/build'     => 'dist/build',
            'public/images'    => 'dist/images',
            'public/fonts'     => 'dist/fonts',
            'public/documents' => 'dist/documents',
        ];
    }

    /**
     * Derive URI => dist-relative output path for every statically exportable
     * route. Never hand-maintain this list.
     */
    function esperanza_export_routes(iterable $routeCollection): array
    {
        $skip   = esperanza_export_skip_uris();
        $routes = [];

        foreach ($routeCollection as $route) {
            $uri = $route->uri();

            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            if (str_contains($uri, '{')) {          // parameterised — no static form
                continue;
            }
            if (in_array($uri, $skip, true)) {
                continue;
            }
            if (str_starts_with($uri, '_') || str_starts_with($uri, 'storage/')) {
                continue;
            }

            $routes['/' . ltrim($uri, '/')] = $uri === '/'
                ? 'index.html'
                : trim($uri, '/') . '/index.html';
        }

        ksort($routes);

        return $routes;
    }
}
