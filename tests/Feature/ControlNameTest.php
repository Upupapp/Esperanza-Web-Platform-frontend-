<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Form controls carry an accessible name, and the count only ever falls.
 *
 * A ratchet rather than a clean assertion, because 161 controls are still
 * unnamed and a gate that fails on the day it lands is a gate people disable.
 * The number below may only be lowered. Lower it whenever you fix some.
 *
 * A name comes from aria-label, aria-labelledby, a wrapping <label>, or a
 * <label for> pointing at the control's id. A placeholder is NOT a name: it
 * disappears on focus and assistive technology treats it differently.
 */
class ControlNameTest extends TestCase
{
    /**
     * Lower this as controls are named. Never raise it.
     *
     * 2026-09-03: 413 -> 161 -> 95 -> 46. Each pass required two independent
     * sources to agree before a name was written: column header vs x-model
     * binding for the BDRRMC grids, and first-<option> prompt vs x-model for
     * filter selects ("All Statuses" + statusFilter -> "Status filter").
     */
    private const CEILING = 46;

    private function strip(string $s): string
    {
        $blank = fn ($m) => str_repeat("\n", substr_count($m[0], "\n"));
        $s = preg_replace_callback('/\{\{--.*?--\}\}/s', $blank, $s);
        $s = preg_replace_callback('/<!--.*?-->/s', $blank, $s);
        $s = preg_replace_callback('/<script\b.*?<\/script>/s', $blank, $s);

        return preg_replace('/(?<!:)\/\/[^\n]*/', '', $s);
    }

    /** @return array{0:int,1:array<int,string>} */
    private function survey(): array
    {
        $unnamed = [];
        $total = 0;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $source = $this->strip(file_get_contents($file->getPathname()));

            preg_match_all('/<label[^>]*\bfor="([^"]+)"/', $source, $fors);
            $labelled = array_flip($fors[1]);

            if (! preg_match_all('/<(input|select|textarea)\b(.*?)>/s', $source, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($m[2] as $i => $attrs) {
                $a = $attrs[0];

                if (preg_match('/type\s*=\s*["\']?(hidden|submit|button|image|reset)/', $a)) {
                    continue;
                }

                $total++;

                if (preg_match('/\baria-label\b|\baria-labelledby\b/', $a)) {
                    continue;
                }

                if (preg_match('/\bid\s*=\s*"([^"]+)"/', $a, $id) && isset($labelled[$id[1]])) {
                    continue;
                }

                // Wrapped directly inside a <label> element.
                $before = substr($source, 0, $m[0][$i][1]);
                if (strrpos($before, '<label') > strrpos($before, '</label>')) {
                    continue;
                }

                $rel = str_replace(resource_path('views').'/', '', $file->getPathname());
                $unnamed[] = $rel.':'.(substr_count($before, "\n") + 1);
            }
        }

        return [$total, $unnamed];
    }

    public function test_unnamed_form_controls_never_increase(): void
    {
        [$total, $unnamed] = $this->survey();

        $this->assertGreaterThan(400, $total, 'Found suspiciously few form controls -- the scan has probably broken.');

        $this->assertLessThanOrEqual(self::CEILING, count($unnamed), sprintf(
            "Controls with no accessible name rose to %d (ceiling %d) of %d.\n"
            ."Give the new one a <label for>/id pair, or aria-label inside an Alpine loop\n"
            ."where a static id would collide. A placeholder is not a name.\nFirst few:\n  %s",
            count($unnamed), self::CEILING, $total,
            implode("\n  ", array_slice($unnamed, 0, 10))
        ));
    }

    public function test_the_ceiling_is_not_left_slack(): void
    {
        // A ratchet that is never tightened stops being a ratchet: if the real
        // count has fallen well below the ceiling, lower the constant.
        [, $unnamed] = $this->survey();

        $this->assertGreaterThan(
            self::CEILING - 25,
            count($unnamed),
            sprintf('Unnamed controls are down to %d; lower ControlNameTest::CEILING to match.', count($unnamed))
        );
    }
}
