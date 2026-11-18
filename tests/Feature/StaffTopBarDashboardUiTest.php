<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The staff screen sweep of 2026-11-18 (1440px and 390px): the top bar's
 * search and the Dashboard, each finding pinned so it cannot come back.
 */
class StaffTopBarDashboardUiTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    /**
     * #9, #31: the search box leaves about 165px for its placeholder (w-64,
     * beside the icon and the "/" chip), and "Search residents, requests,
     * barangays..." read "Search residents, reques" on every staff page.
     */
    public function test_the_staff_search_placeholder_fits_its_box(): void
    {
        $bar = $this->source('resources/views/components/admin/topbar.blade.php');
        $this->assertSame(1, preg_match('/<x-ui\.global-search :config="\$searchConfig" placeholder-en="([^"]+)"/', $bar, $m));
        $this->assertStringNotContainsString('barangays...', $m[1]);
        $this->assertLessThanOrEqual(20, mb_strlen($m[1]), 'about 22 characters fill the box at 14px: "'.$m[1].'"');
        $this->assertStringNotContainsString('width=', substr($bar, strpos($bar, '<x-ui.global-search'), 160), 'a wider box takes the subtitle\'s room');
    }

    /** #17, #23, #26: Recent activity named the module by its raw audit key, "user_management · IO-901". */
    public function test_recent_activity_names_the_module_in_words(): void
    {
        $view = $this->source('resources/views/admin/dashboard.blade.php');
        $this->assertStringNotContainsString("(activity.module || '')", $view);
        $this->assertStringContainsString("x-text=\"[moduleLabel(activity.module), activity.target].filter(Boolean).join(' · ')\"", $view);
        foreach (["user_management: 'User Management'", "communications: 'Balita'", "constituents: 'Constituents'", "dokyu: 'Dokyu'"] as $name) {
            $this->assertStringContainsString($name, $view);
        }
        $this->assertStringNotContainsString('font-mono text-xs text-brand-600 mt-0.5 break-all', $view, 'words, not a code');
    }

    /** #36: at 390px the office picker, as wide as its longest name, ran off the card and the screen. */
    public function test_the_office_picker_stays_inside_the_card_on_a_phone(): void
    {
        $view = $this->source('resources/views/components/admin/dashboards/department.blade.php');
        $this->assertSame(1, preg_match('/<select id="dept-office"[^>]*class="([^"]+)"/', $view, $m));
        $classes = explode(' ', $m[1]);
        foreach (['max-w-full', 'sm:max-w-[380px]', 'text-ellipsis'] as $class) {
            $this->assertContains($class, $classes, $class);
        }
        $this->assertNotContains('max-w-[380px]', $classes, 'a fixed 380px cap is wider than a phone card');
        $this->assertStringContainsString('<div class="flex flex-wrap items-center gap-2 min-w-0 max-w-full">', $view);
    }
}
