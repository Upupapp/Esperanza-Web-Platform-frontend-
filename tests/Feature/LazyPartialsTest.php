<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Lazy partials (page budget, 2026-11-25). Dokyu sat exactly at the 120 KB gz
 * page budget, Tulong and the Constituents pages a KB or two under it. The
 * windows most visits never open (the import windows, the Prepared Document
 * editor, Apply for New Tulong, the session windows, Constituents' add,
 * life-event and merge windows) left those pages' HTML: each is a view in
 * resources/views/lazy/, served at /partials/<name>.html, exported beside the
 * pages, and put back in its place by x-lazy-partial (resources/js/lazy-partial.js)
 * once the page is idle, or at once when the window is asked for.
 */
class LazyPartialsTest extends TestCase
{
    /** Page => [partial => a string only that window has]. */
    private const MOVED = [
        '/admin/document-requests' => [
            'dokyu-prepared-document' => 'id="dp-print-area"',
            'request-import-dokyu' => 'Import Dokyu records from a file',
        ],
        '/admin/assistance-requests' => [
            'request-import-tulong' => 'Import Tulong records from a file',
            'tulong-apply' => 'id="ap-step-title"',
            'tulong-session-desk' => '>Group session</h3>',
        ],
        '/admin/constituents' => [
            'constituents-import' => 'Import residents from a file',
            'constituents-add-individual' => 'showAddIndividual = v',
            'constituents-add-family' => 'showAddFamily = v',
            'constituents-add-household' => 'showAddHousehold = v',
            'constituents-lifecycle' => 'showLifecycle = v',
            'constituents-merge' => 'showMerge = v',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        require_once base_path('build/export_manifest.php');
    }

    private function lazyViews(): array
    {
        return array_map(fn ($f) => basename($f, '.blade.php'), glob(resource_path('views/lazy/*.blade.php')));
    }

    public function test_every_lazy_view_has_its_address_and_reaches_dist(): void
    {
        $views = $this->lazyViews();
        sort($views);
        $moved = array_merge(...array_map('array_keys', array_values(self::MOVED)));
        sort($moved);
        $this->assertSame($moved, $views, 'every lazy view is listed in MOVED, with a string only it has');

        $partials = esperanza_export_partials(app('router')->getRoutes());
        $this->assertSame(array_map(fn ($v) => '/partials/'.$v.'.html', $views), array_keys($partials));
        foreach ($partials as $uri => $file) {
            $this->assertSame(ltrim($uri, '/'), $file, 'written as the file it is fetched as, never as a page');
        }

        // Not pages: the gate counts index.html files against these, and the
        // accessibility run opens every page.
        $pages = esperanza_export_routes(app('router')->getRoutes());
        foreach (array_keys($partials) as $uri) {
            $this->assertArrayNotHasKey($uri, $pages);
        }
        $this->assertStringContainsString('$routes + $partials', file_get_contents(base_path('export.php')));
    }

    public function test_each_window_left_its_page_and_comes_from_its_partial(): void
    {
        foreach (self::MOVED as $page => $windows) {
            $raw = $this->rawGet($page)->assertOk()->getContent();

            foreach ($windows as $name => $marker) {
                $this->assertStringNotContainsString($marker, $raw, "$name is still in $page's own HTML");
                $this->assertMatchesRegularExpression(
                    '#<div class="contents" data-lazy-partial="/partials/'.$name.'\.html\?v=[0-9a-f]{10}" x-lazy-partial="[^"]+"></div>#',
                    $raw,
                    "$page has no placeholder for $name"
                );

                $partial = $this->rawGet('/partials/'.$name.'.html')->assertOk()->getContent();
                $this->assertStringContainsString($marker, $partial);
                $this->assertStringNotContainsString('<x-', $partial, 'a component reached the partial uncompiled');
                $this->assertStringNotContainsString('<script', $partial, 'no script in a partial (CSP: no inline scripts)');
                $this->assertStringNotContainsString('<html', $partial, 'a partial is a fragment, not a page');

                // The address carries the content's hash: a page never takes
                // another build's copy of its window.
                preg_match('#/partials/'.$name.'\.html\?v=([0-9a-f]{10})#', $raw, $m);
                $this->assertSame(substr(md5($partial), 0, 10), $m[1], "$name's ?v= is not its content's hash");
            }

            // The page as the browser ends up with it (Tests\TestCase::get):
            // each window back inside its own placeholder.
            $full = $this->get($page)->getContent();
            foreach (array_keys($windows) as $name) {
                preg_match('#<div class="contents" data-lazy-partial="/partials/'.$name.'\.html\?v=[0-9a-f]+" x-lazy-partial="[^"]*">#', $raw, $open);
                $this->assertStringContainsString($open[0].$this->rawGet('/partials/'.$name.'.html')->getContent().'</div>', $full);
            }
        }
    }

    public function test_the_windows_open_on_the_same_flags_as_before(): void
    {
        $raw = $this->rawGet('/admin/document-requests')->getContent();
        $this->assertStringContainsString('x-lazy-partial="docPreviewOpen"', $raw);
        $this->assertStringContainsString('x-lazy-partial="open"', $raw);

        $raw = $this->rawGet('/admin/assistance-requests')->getContent();
        $this->assertStringContainsString('data-lazy-partial="/partials/tulong-apply.html', $raw);
        $this->assertStringContainsString('x-lazy-partial="apOpen"', $raw);
        $this->assertStringContainsString('x-lazy-partial="sdOpen || sfOpen"', $raw);
        // The listeners that open them stay on the page, so a window asked
        // for before its markup arrives still opens.
        $this->assertStringContainsString('x-on:tulong-apply.window="openApply(', $raw);
        $this->assertStringContainsString('x-on:tulong-open-session.window="sdShow(', $raw);
        $this->assertStringContainsString('x-on:open-request-import.window=', $raw);

        $raw = $this->rawGet('/admin/constituents')->getContent();
        foreach (['showAddIndividual', 'showAddFamily', 'showAddHousehold', 'showLifecycle', 'showMerge'] as $flag) {
            $this->assertStringContainsString('<div x-show="'.$flag.'"', $raw);
            $this->assertStringContainsString('x-lazy-partial="'.$flag.'"', $raw);
        }
        $this->assertStringContainsString('x-on:open-resident-import.window="openImport(', $raw);
    }

    public function test_a_partial_numbers_its_window_headings_apart_from_the_page(): void
    {
        $page = $this->rawGet('/admin/assistance-requests')->getContent();
        $partial = $this->rawGet('/partials/tulong-session-desk.html')->getContent();

        $this->assertStringContainsString('id="lp-tulong-session-desk-title-1"', $partial);
        $this->assertStringContainsString('id="lp-tulong-session-desk-title-2"', $partial);
        $this->assertStringNotContainsString('modal-title-', $partial);

        // Every heading id on the page with its windows back is unique, and
        // rendering a partial for its hash does not shift the page's own count.
        preg_match_all('#id="((?:modal|lp-[a-z-]+)-title-\d+)"#', $this->get('/admin/assistance-requests')->getContent(), $m);
        $this->assertSame(array_unique($m[1]), $m[1]);
        $this->assertStringContainsString('id="modal-title-1"', $page);
    }

    public function test_the_directive_is_registered_and_the_partials_are_not_indexed(): void
    {
        $app = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString("import { registerLazyPartials } from './lazy-partial';", $app);
        $this->assertStringContainsString('registerLazyPartials(Alpine);', $app);

        $js = file_get_contents(resource_path('js/lazy-partial.js'));
        $this->assertStringContainsString("Alpine.directive('lazy-partial'", $js);
        $this->assertStringContainsString('Alpine.initTree(child)', $js);
        $this->assertStringContainsString("credentials: 'same-origin'", $js);
        $this->assertStringContainsString('requestIdleCallback', $js, 'the markup comes back on idle, not only on first open');

        $headers = esperanza_response_headers('https://api.example.test');
        $this->assertStringContainsString("/partials/*\n  X-Robots-Tag: noindex, nofollow", $headers);
        $this->assertStringContainsString("connect-src 'self'", $headers);
    }
}
