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

if (!function_exists('esperanza_response_headers')) {
    /**
     * Netlify `_headers` for the exported site (live test 2026-10-07):
     * PR-1, the site sent HSTS only (no CSP, no frame-ancestors, no nosniff,
     * no Referrer-Policy, no Permissions-Policy); PR-2, staff pages could be
     * indexed; PR-5, the content-hashed bundle was revalidated on every page.
     */
    function esperanza_response_headers(string $apiOrigin): string
    {
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: {$apiOrigin}",
            "connect-src 'self' {$apiOrigin}",
            "media-src 'self' blob: {$apiOrigin}",
            "worker-src 'self' blob:",
            "frame-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        return implode("\n", [
            '/*',
            '  Content-Security-Policy: '.$csp,
            '  X-Frame-Options: DENY',
            '  X-Content-Type-Options: nosniff',
            '  Referrer-Policy: strict-origin-when-cross-origin',
            '  Permissions-Policy: camera=(self), microphone=(), geolocation=(), payment=()',
            '',
            '/admin/*',
            '  X-Robots-Tag: noindex, nofollow',
            '',
            '/build/assets/*',
            '  Cache-Control: public, max-age=31536000, immutable',
            '',
        ]);
    }
}

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
