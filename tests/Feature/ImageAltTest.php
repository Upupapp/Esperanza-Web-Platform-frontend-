<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every rendered <img> carries an alt attribute.
 *
 * This is a government service whose users include the people registering for
 * PWD and Senior Citizen assistance, so an unlabelled image is not a cosmetic
 * miss. `alt=""` is a valid and expected answer for decoration -- the point is
 * that the choice was made, not that every image gets prose.
 *
 * A source scan is used deliberately: the rendered-DOM audit
 * (scripts/audit.mjs) is the better instrument but needs port 80 and therefore
 * root, so it cannot run in an ordinary gate. This catches the same defect at
 * authoring time.
 */
class ImageAltTest extends TestCase
{
    /**
     * Comments must be stripped before scanning, or the gate reports its own
     * explanation as a defect -- admin/document-requests.blade.php alone
     * mentions `<img>` in seven JS comments describing the editor.
     */
    private function strip(string $s): string
    {
        $blank = fn ($m) => str_repeat("\n", substr_count($m[0], "\n"));

        $s = preg_replace_callback('/\{\{--.*?--\}\}/s', $blank, $s);
        $s = preg_replace_callback('/<!--.*?-->/s', $blank, $s);
        $s = preg_replace_callback('/<script\b.*?<\/script>/s', $blank, $s);

        // Line comments in Alpine/JS blocks. "://" is left alone so a URL is
        // never mistaken for a comment.
        return preg_replace('/(?<!:)\/\/[^\n]*/', '', $s);
    }

    public function test_every_image_has_an_alt_attribute(): void
    {
        $missing = [];
        $total = 0;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $source = $this->strip(file_get_contents($file->getPathname()));

            if (! preg_match_all('/<img\b(.*?)>/s', $source, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[1] as $i => $attrs) {
                $total++;

                if (preg_match('/\balt\s*=/', $attrs[0])) {
                    continue;
                }

                $line = substr_count(substr($source, 0, $matches[0][$i][1]), "\n") + 1;
                $missing[] = str_replace(resource_path('views').'/', '', $file->getPathname()).':'.$line;
            }
        }

        // Guards the scan itself: if the traversal broke, an empty result would
        // otherwise read as a clean bill of health.
        $this->assertGreaterThan(50, $total, 'Found suspiciously few <img> tags -- the scan has probably broken.');

        sort($missing);

        $this->assertSame([], $missing, sprintf(
            "%d of %d <img> tags have no alt attribute.\n"
            ."Give each one a description, or alt=\"\" if it is decorative -- an empty alt is a\n"
            ."decision and is correct for artwork; a missing one is an omission:\n  %s",
            count($missing), $total, implode("\n  ", $missing)
        ));
    }
}
