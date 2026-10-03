<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * An inline Alpine component is a double-quoted HTML attribute, x-data="{ ... }",
 * so the first double quote inside it ends the attribute. Everything after that
 * is then printed onto the page as text, and the component never starts: no
 * data loads, no button works, and the page still renders with a 200, so the
 * route-render gate passes.
 *
 * It happened to Constituents (73e6128), and on 2026-10-04 to six more pages
 * at once, Sakuna and Payments among them, from a quoted word in a comment
 * ("progress", "View in Payments") or a string ("'s assessment approved.").
 * This reads every view and checks that each such attribute reaches its
 * closing brace before its first double quote.
 */
class AlpineQuotingTest extends TestCase
{
    public function test_no_inline_component_is_cut_short_by_a_double_quote(): void
    {
        $broken = [];
        $checked = 0;

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $source = $file->getContents();
            preg_match_all('/x-data="\s*\{/', $source, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$match, $offset]) {
                $checked++;
                $start = $offset + strlen($match);
                $end = strpos($source, '"', $start);
                $body = $end === false ? substr($source, $start) : substr($source, $start, $end - $start);

                if (! str_ends_with(rtrim($body), '}')) {
                    $line = substr_count(substr($source, 0, $end === false ? $start : $end), "\n") + 1;
                    $near = trim(preg_replace('/\s+/', ' ', substr($source, max($start, ($end ?: $start) - 50), 70)));
                    $broken[] = sprintf('%s:%d near "...%s..."', $file->getRelativePathname(), $line, $near);
                }
            }
        }

        // Without this the test passes by finding nothing to check.
        $this->assertGreaterThan(50, $checked, 'No inline x-data components were found; has the pattern changed?');

        $this->assertSame([], $broken, sprintf(
            "%d inline component(s) end at a stray double quote (use single quotes inside x-data):\n  %s",
            count($broken), implode("\n  ", $broken)
        ));
    }
}
