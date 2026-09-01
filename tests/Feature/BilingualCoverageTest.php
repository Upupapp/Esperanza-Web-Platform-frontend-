<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Gate 3 of TAB 02 — bilingual coverage.
 *
 * Ruled 2026-08-30 (see CLAUDE.md "Language"): the Citizen Portal is bilingual,
 * the Administrative Portal is English-only BY DECISION. So this gate is scoped
 * to citizen-facing views on purpose — running it over the admin portal would
 * report 32 "failures" that are the owner's settled choice.
 *
 * A file satisfies the gate by doing one of two things:
 *   - handling language (x-ui.t, or a $store.lang binding for attributes), or
 *   - carrying a {{-- LANGUAGE (TAB 09) --}} comment saying why it does not.
 *
 * The second option matters. Three citizen components deliberately stay English
 * (official document bodies and the Digital ID card face) and one has no
 * user-visible text at all. Without a way to record that, the only honest gate
 * would be one that is permanently red, and a permanently red gate gets ignored.
 */
class BilingualCoverageTest extends TestCase
{
    /** Citizen-facing view roots. The admin portal is excluded by ruling. */
    private const CITIZEN_ROOTS = [
        'resources/views/citizen',
        'resources/views/components/citizen',
    ];

    private const LANGUAGE_MARKERS = [
        'x-ui.t',                 // the bilingual component
        'store.lang',             // bound attributes: placeholder/title/aria-label
        'LANGUAGE (TAB 09)',      // a recorded, reasoned exemption
    ];

    /** @return string[] */
    private function citizenViews(): array
    {
        $files = [];

        foreach (self::CITIZEN_ROOTS as $root) {
            $path = base_path($root);

            if (! is_dir($path)) {
                continue;
            }

            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    public function test_the_gate_is_looking_at_something(): void
    {
        // Without this, deleting the citizen views would turn this file green.
        $this->assertGreaterThanOrEqual(
            20,
            count($this->citizenViews()),
            'Found suspiciously few citizen views — discovery has probably broken, and every '
            .'other assertion in this file would pass vacuously.'
        );
    }

    public function test_every_citizen_facing_view_handles_language(): void
    {
        $missing = [];

        foreach ($this->citizenViews() as $file) {
            $source = file_get_contents($file);

            $handled = false;
            foreach (self::LANGUAGE_MARKERS as $marker) {
                if (str_contains($source, $marker)) {
                    $handled = true;
                    break;
                }
            }

            if (! $handled) {
                $missing[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
            }
        }

        sort($missing);

        $this->assertSame([], $missing, sprintf(
            "%d citizen-facing view(s) ship user-visible text in one language only.\n"
            ."The Citizen Portal is bilingual by ruling (CLAUDE.md). Either wrap the strings in\n"
            ."<x-ui.t fil=\"...\" en=\"...\" />, bind attributes off \$store.lang.current, or record a\n"
            ."{{-- LANGUAGE (TAB 09) --}} comment explaining why this file is exempt:\n  %s",
            count($missing), implode("\n  ", $missing)
        ));
    }

    public function test_the_admin_portal_is_not_being_policed_by_this_gate(): void
    {
        // Guards the SCOPE, not the content. If someone widens CITIZEN_ROOTS to
        // the whole view tree, this fails and forces the owner ruling to be
        // revisited deliberately rather than by a silently broadened glob.
        foreach ($this->citizenViews() as $file) {
            $this->assertStringNotContainsString(
                DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR,
                $file,
                'This gate has been widened to the Administrative Portal, which is English-only '
                .'by owner decision (CLAUDE.md "Language"). Reverse the scope change, or get the '
                .'ruling changed first.'
            );
        }

        $this->assertTrue(true);
    }
}
