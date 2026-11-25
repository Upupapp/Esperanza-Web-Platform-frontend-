<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * A page as the browser ends up with it: each lazy partial (a window kept
     * out of the page's HTML, <x-lazy-partial>, 2026-11-25) is fetched from its
     * own address and put inside its placeholder, as resources/js/lazy-partial.js
     * does. So a test that reads a page also reads its lazy windows, and a
     * partial that fails to render fails the page's tests. rawGet() reads the
     * HTML the server sends, placeholders empty.
     */
    public function get($uri, array $headers = []): TestResponse
    {
        $response = parent::get($uri, $headers);
        $html = (string) $response->baseResponse->getContent();

        if (! str_contains($html, 'data-lazy-partial="')) {
            return $response;
        }

        $expanded = preg_replace_callback(
            '#(<div class="contents" data-lazy-partial="(/partials/[a-z0-9-]+\.html)\?v=[0-9a-f]+" x-lazy-partial="[^"]*">)(</div>)#',
            function (array $m) {
                $partial = parent::get($m[2]);
                $this->assertSame(200, $partial->getStatusCode(), "lazy partial {$m[2]} did not render");

                return $m[1].$partial->baseResponse->getContent().$m[3];
            },
            $html
        );
        $response->baseResponse->setContent($expanded);

        return $response;
    }

    /** The page's HTML exactly as served: lazy partials left as empty placeholders. */
    public function rawGet(string $uri, array $headers = []): TestResponse
    {
        return parent::get($uri, $headers);
    }
}
