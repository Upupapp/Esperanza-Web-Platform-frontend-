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

if (!function_exists('esperanza_map_origins')) {
    /**
     * The hosts Sakuna's maps reach (2026-10-26): the map tiles and the staff
     * address search, read from the same VITE_ settings the bundle is built
     * with (resources/js/sakuna-map.js), so the CSP allows exactly those.
     * `{s}` subdomains become a wildcard; an empty VITE_GEOCODER_URL turns
     * the search off and adds no host.
     *
     * @return array{tiles: ?string, geocoder: ?string}
     */
    function esperanza_map_origins(): array
    {
        $setting = function (string $key, string $default): string {
            $value = getenv($key);
            if ($value === false) {
                $value = $_ENV[$key] ?? $default;
            }

            return (string) $value;
        };
        $origin = fn (string $url): ?string => preg_match('#^(https://[^/{}]+)#', str_replace('{s}', '*', $url), $m) ? $m[1] : null;

        return [
            'tiles' => $origin($setting('VITE_MAP_TILE_URL', '') ?: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
            'geocoder' => $origin($setting('VITE_GEOCODER_URL', 'https://nominatim.openstreetmap.org')),
        ];
    }
}

if (!function_exists('esperanza_response_headers')) {
    /**
     * Netlify `_headers` for the exported site (live test 2026-10-07):
     * PR-1, the site sent HSTS only (no CSP, no frame-ancestors, no nosniff,
     * no Referrer-Policy, no Permissions-Policy); PR-2, staff pages could be
     * indexed; PR-5, the content-hashed bundle was revalidated on every page.
     *
     * Sakuna's maps (2026-10-26): the tile host in img-src, the address
     * search in connect-src, and the page's own location (geolocation=self)
     * so a resident can pin an emergency from their phone.
     */
    function esperanza_response_headers(string $apiOrigin, ?array $map = null): string
    {
        $map ??= esperanza_map_origins();
        $tiles = $map['tiles'] ? ' '.$map['tiles'] : '';
        $geocoder = $map['geocoder'] ? ' '.$map['geocoder'] : '';
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: {$apiOrigin}{$tiles}",
            "connect-src 'self' {$apiOrigin}{$geocoder}",
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
            '  Permissions-Policy: camera=(self), microphone=(), geolocation=(self), payment=()',
            '',
            '/admin/*',
            '  X-Robots-Tag: noindex, nofollow',
            '',
            '/partials/*',
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
            if (str_starts_with($uri, 'partials/')) {  // not a page: esperanza_export_partials()
                continue;
            }

            $routes['/' . ltrim($uri, '/')] = $uri === '/'
                ? 'index.html'
                : trim($uri, '/') . '/index.html';
        }

        ksort($routes);

        return $routes;
    }

    /**
     * The lazy partials (page budget, 2026-11-25): windows kept out of their
     * page's HTML (resources/views/lazy/, <x-lazy-partial>). URI => the
     * dist-relative file, written as is (partials/<name>.html) and never as a
     * page: no index.html, so the gate's page count and the accessibility run
     * see pages only.
     */
    function esperanza_export_partials(iterable $routeCollection): array
    {
        $partials = [];

        foreach ($routeCollection as $route) {
            $uri = $route->uri();
            if (in_array('GET', $route->methods(), true) && preg_match('#^partials/[a-z0-9-]+\.html$#', $uri)) {
                $partials['/'.$uri] = $uri;
            }
        }

        ksort($partials);

        return $partials;
    }
}
