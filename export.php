<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

/**
 * Esperanza Web Platform — Static HTML Exporter
 *
 * Boots Laravel internally, renders every known route to a static HTML file
 * under dist/, then copies compiled assets from public/build and public/images.
 * Run after `npm run build` so the Vite manifest is available.
 *
 * Usage: php export.php
 */
define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);

// ── Route map: DERIVED from the router, never hand-maintained ────────────
// F-001: a hand-written list silently drifts from routes/web.php, and every
// route missing from it 404s in production while still returning 200 locally.
// The derivation lives in build/export_manifest.php so the drift gate in
// tests/Feature/ExportManifestTest.php runs the exact same code as the deploy.
require_once __DIR__.'/build/export_manifest.php';

// Seed the RNG so the export is REPRODUCIBLE. Three views build decorative
// randomness at render time (confetti in the birthday and fiesta popups,
// sampling in admin/communications). Unseeded, every build produced different
// HTML for the same source -- so every deploy rewrote unchanged pages, and
// "did this change the output?" could not be answered at all.
// Decorative only: nothing a user reads depends on these numbers.
mt_srand(20260830);

$kernel->bootstrap();

$routes = esperanza_export_routes($app->make('router')->getRoutes());

// Fail loudly rather than publishing an empty site: an exporter that writes
// nothing still exits 0 and looks like a successful build.
if ($routes === []) {
    fwrite(STDERR, 'No routes derived from the router - refusing to publish an empty site.'.PHP_EOL);
    exit(1);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function copyDir(string $src, string $dst): int
{
    if (! is_dir($src)) {
        return 0;
    }
    @mkdir($dst, 0777, true);
    $count = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
        $dest = $dst.'/'.substr($item->getPathname(), strlen($src) + 1);
        if ($item->isDir()) {
            @mkdir($dest, 0777, true);
        } else {
            copy($item->getPathname(), $dest);
            $count++;
        }
    }

    return $count;
}

// ── Detect host from APP_URL ──────────────────────────────────────────────────
$appUrl = env('APP_URL', 'http://localhost');
$parsed = parse_url($appUrl);
$host = $parsed['host'] ?? 'localhost';
$isHttps = ($parsed['scheme'] ?? 'http') === 'https';
// Honour an explicit port when APP_URL carries one. Production sets no port, so
// this changes nothing there -- but forcing 80 meant dist/ could only ever be
// served from a privileged port, which is why scripts/audit.mjs needed root and
// therefore never ran in an ordinary gate.
$port = (string) ($parsed['port'] ?? ($isHttps ? 443 : 80));

$distDir = __DIR__.'/dist';
@mkdir($distDir, 0777, true);

echo "\n── Esperanza Static Exporter ──────────────────────────────\n";
echo "APP_URL : {$appUrl}\n";
echo "Dist    : {$distDir}\n\n";

// ── Render routes ─────────────────────────────────────────────────────────────
$ok = 0;
$fail = 0;

foreach ($routes as $uri => $outputPath) {
    try {
        // Laravel builds absolute URLs from HTTP_HOST, so a non-default port
        // has to be in the header itself -- SERVER_PORT alone is ignored and the
        // port silently vanishes from every asset URL.
        $hostHeader = $host.(in_array($port, ['80', '443'], true) ? '' : ':'.$port);

        $server = [
            'HTTP_HOST' => $hostHeader,
            'SERVER_NAME' => $host,
            'SERVER_PORT' => $port,
            'HTTPS' => $isHttps ? 'on' : 'off',
        ];

        $request = Request::create($uri, 'GET', [], [], [], $server);
        $response = $kernel->handle($request);
        $html = $response->getContent();

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException("HTTP {$response->getStatusCode()}");
        }

        $fullPath = $distDir.'/'.$outputPath;
        @mkdir(dirname($fullPath), 0777, true);
        file_put_contents($fullPath, $html);

        echo "  ✓  {$uri}\n";
        $ok++;

        $kernel->terminate($request, $response);
    } catch (Throwable $e) {
        echo "  ✗  {$uri}  ({$e->getMessage()})\n";
        $fail++;
    }
}

// ── Copy public assets ────────────────────────────────────────────────────────
echo "\n── Assets ─────────────────────────────────────────────────\n";

$assets = esperanza_export_assets();

foreach ($assets as $src => $dst) {
    $srcPath = __DIR__.'/'.$src;
    $dstPath = __DIR__.'/'.$dst;
    if (is_dir($srcPath)) {
        $n = copyDir($srcPath, $dstPath);
        echo "  ✓  {$src}/ → {$dst}/  ({$n} files)\n";
    }
}

foreach (['favicon.ico', 'robots.txt'] as $file) {
    $src = __DIR__.'/public/'.$file;
    if (file_exists($src)) {
        copy($src, $distDir.'/'.$file);
        echo "  ✓  public/{$file}\n";
    }
}

// ── Summary ───────────────────────────────────────────────────────────────────
echo "\n── Done ────────────────────────────────────────────────────\n";
echo "  Pages : {$ok} rendered, {$fail} failed\n\n";

if ($fail > 0) {
    exit(1);
}
