<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Dokyu and Tulong queues (UI sweep, 2026-11-18, at 1440 px for every staff
 * role): the reference broke at its hyphen ("DOK-2026-" / "00047") and the
 * Submitted date wrapped ("Nov 13," / "2026") while one long name kept the
 * Citizen column wide for every row; and Department Staff saw a Papeles tab
 * that opened Access Restricted.
 */
class RequestQueueTableTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_reference_and_date_stay_on_one_line(): void
    {
        foreach (['resources/views/admin/document-requests.blade.php', 'resources/views/admin/assistance-requests.blade.php'] as $path) {
            $view = $this->source($path);
            $this->assertStringContainsString('<td class="px-4 py-3 font-mono text-xs text-ink-muted whitespace-nowrap hidden xl:table-cell" x-text="r.ref"', $view, $path);
            $this->assertStringContainsString('<td class="px-4 py-3 text-ink-muted whitespace-nowrap hidden xl:table-cell" x-text="r.submitted"></td>', $view, $path);
            $this->assertStringContainsString('<p class="max-w-[12rem] text-slate-700 font-medium whitespace-nowrap truncate group-hover:text-brand-600 transition-colors" x-text="r.citizen" :title="r.citizen"></p>', $view, $path);
        }
    }

    public function test_the_papeles_tab_shows_only_to_a_role_that_may_open_it(): void
    {
        $view = $this->source('resources/views/admin/document-requests.blade.php');

        $this->assertStringContainsString('href="{{ route(\'admin.papeles\') }}" x-show="$store.session.can(\'papeles\')" x-cloak', $view);
        // The page itself gates on the same key.
        $this->assertStringContainsString('x-show="!$store.session.can(\'papeles\')"', $this->source('resources/views/admin/papeles.blade.php'));
        // With neither link, the strip would hold Requests alone: it hides, as on Tulong.
        $this->assertStringContainsString('mb-4" x-show="$store.session.canPerm(\'set_service_catalog\', \'view\') || $store.session.can(\'papeles\')" x-cloak>', $view);
    }

    /** The same strip on the Document types page carries the same check. */
    public function test_the_document_types_page_shows_the_papeles_tab_on_the_same_check(): void
    {
        $types = $this->source('resources/views/admin/request-types.blade.php');
        $this->assertStringContainsString("['Citizen Document Storage', 'folder-open', route('admin.papeles'), null, 'papeles'],", $types);
        $this->assertStringContainsString('@elseif($viewModule) x-show="$store.session.can(\'{{ $viewModule }}\')" x-cloak @endif', $types);

        // Rendered (the strip only: the sidebar links to Papeles too, on its own check).
        $dokyu = $this->viewStrip('/admin/document-requests/types', 'Dokyu');
        $this->assertMatchesRegularExpression('#<a href="[^"]*/admin/papeles"\s+x-show="\$store\.session\.can\(\'papeles\'\)" x-cloak\s#', $dokyu);
        $this->assertSame(1, substr_count($dokyu, 'Citizen Document Storage'), 'one link, the gated one');
        // Requests shows to all who reach the page, as before.
        $this->assertMatchesRegularExpression('#<a href="[^"]*/admin/document-requests"\s+class=#', $dokyu);

        // Tulong's types page keeps Sessions on its permission and has no Papeles link.
        $tulong = $this->viewStrip('/admin/assistance-requests/types', 'Tulong');
        $this->assertStringContainsString("x-show=\"\$store.session.canPerm('tulong_sessions', 'view')\" x-cloak", $tulong);
        $this->assertStringNotContainsString('/admin/papeles"', $tulong);
    }

    /** The rendered view switch above a types page ("Dokyu views" / "Tulong views"). */
    private function viewStrip(string $uri, string $tab): string
    {
        $html = $this->get($uri)->assertOk()->getContent();
        $this->assertSame(1, preg_match('#<nav [^>]*aria-label="'.$tab.' views">(.*?)</nav>#s', $html, $m), "{$uri}: the {$tab} views strip");

        return $m[1];
    }
}
