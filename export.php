<?php

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

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

/** @var \Illuminate\Contracts\Http\Kernel $kernel */
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// ── Route map: URI → dist-relative output path ───────────────────────────
// Derived from the router, never hand-maintained. A hand-written list silently
// drifts from routes/web.php, and because Netlify serves dist/ as flat files a
// route missing here is a 404 in production while still returning 200 locally --
// which is exactly how /admin/papeles, /admin/internal-forms and the Terms and
// Privacy Policy pages shipped broken. Deriving it makes that drift impossible.
$kernel->bootstrap();

$routes = [];

foreach ($app->make('router')->getRoutes() as $route) {
    if (! in_array('GET', $route->methods(), true)) {
        continue;
    }

    $uri = '/' . ltrim($route->uri(), '/');

    // Parameterised routes have no single static representation, and /up is
    // Laravel's health endpoint -- neither belongs in a static export.
    if (str_contains($uri, '{') || $uri === '/up') {
        continue;
    }

    $routes[$uri] = $uri === '/' ? 'index.html' : trim($uri, '/') . '/index.html';
}

ksort($routes);

// ── Helpers ───────────────────────────────────────────────────────────────────
function copyDir(string $src, string $dst): int
{
    if (!is_dir($src)) {
        return 0;
    }
    @mkdir($dst, 0777, true);
    $count = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
        $dest = $dst . '/' . substr($item->getPathname(), strlen($src) + 1);
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
$appUrl  = env('APP_URL', 'http://localhost');
$parsed  = parse_url($appUrl);
$host    = $parsed['host'] ?? 'localhost';
$isHttps = ($parsed['scheme'] ?? 'http') === 'https';

$distDir = __DIR__ . '/dist';
@mkdir($distDir, 0777, true);

echo "\n── Esperanza Static Exporter ──────────────────────────────\n";
echo "APP_URL : {$appUrl}\n";
echo "Dist    : {$distDir}\n\n";

// ── Render routes ─────────────────────────────────────────────────────────────
$ok = 0;
$fail = 0;

foreach ($routes as $uri => $outputPath) {
    try {
        $server = [
            'HTTP_HOST'   => $host,
            'SERVER_NAME' => $host,
            'SERVER_PORT' => $isHttps ? '443' : '80',
            'HTTPS'       => $isHttps ? 'on' : 'off',
        ];

        $request  = Illuminate\Http\Request::create($uri, 'GET', [], [], [], $server);
        $response = $kernel->handle($request);
        $html     = $response->getContent();

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException("HTTP {$response->getStatusCode()}");
        }

        $fullPath = $distDir . '/' . $outputPath;
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

$assets = [
    'public/build'     => 'dist/build',
    'public/images'    => 'dist/images',
    'public/fonts'     => 'dist/fonts',
    // Reference forms surfaced as an <iframe> preview and a download link on the
    // admin Dokyu/Tulong pages (config reference_pdf keys). Absent from dist/,
    // every one of those previews is an empty frame in production.
    'public/documents' => 'dist/documents',
];

foreach ($assets as $src => $dst) {
    $srcPath = __DIR__ . '/' . $src;
    $dstPath = __DIR__ . '/' . $dst;
    if (is_dir($srcPath)) {
        $n = copyDir($srcPath, $dstPath);
        echo "  ✓  {$src}/ → {$dst}/  ({$n} files)\n";
    }
}

foreach (['favicon.ico', 'robots.txt'] as $file) {
    $src = __DIR__ . '/public/' . $file;
    if (file_exists($src)) {
        copy($src, $distDir . '/' . $file);
        echo "  ✓  public/{$file}\n";
    }
}

// ── Summary ───────────────────────────────────────────────────────────────────
echo "\n── Done ────────────────────────────────────────────────────\n";
echo "  Pages : {$ok} rendered, {$fail} failed\n\n";

if ($fail > 0) {
    exit(1);
}
