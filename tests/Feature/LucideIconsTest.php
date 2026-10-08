<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Only the icons the site names are in the bundle (resources/js/lucide-icons.js,
 * from scripts/lucide-icons.mjs): all ~1,700 were most of the initial JavaScript
 * (2026-10-08). A view naming an icon that list lacks would make every visit
 * fetch the full set, so a new icon means running the script again.
 */
class LucideIconsTest extends TestCase
{
    public function test_every_icon_a_view_names_is_in_the_bundled_set(): void
    {
        $bundled = file_get_contents(base_path('resources/js/lucide-icons.js'));
        preg_match_all('/^\s{4}([A-Z][A-Za-z0-9]*),$/m', $bundled, $m);
        $set = array_flip($m[1]);
        $this->assertGreaterThan(100, count($set), 'The bundled icon list is empty or unreadable.');

        $missing = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('resources/views')));
        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            preg_match_all('/(?<![:\w-])(?:data-lucide|icon)="([a-z][a-z0-9-]*)"/', file_get_contents($file->getPathname()), $hits);
            foreach ($hits[1] as $name) {
                $pascal = str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));
                if (! isset($set[$pascal])) {
                    $missing[$name] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $missing, 'Icons named in views but not bundled (run `node scripts/lucide-icons.mjs`, or the name is not a Lucide icon): '.json_encode($missing));
        $this->assertStringContainsString("import('./lucide-all')", file_get_contents(base_path('resources/js/app.js')), 'An icon outside the set still draws.');
    }
}
