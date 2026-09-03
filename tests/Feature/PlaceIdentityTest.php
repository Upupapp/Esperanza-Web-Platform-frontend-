<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * This platform is the Municipality of Esperanza, MASBATE (Region V — Bicol).
 *
 * `rectangle_cityhall.jpg` photographs a municipal hall whose facade seal reads
 * "MUNICIPALITY OF ESPERANZA — Agusan del Sur" — a different Esperanza, in
 * Caraga. Flagged by the mobile lane on 2026-09-03, which blocked it from
 * onboarding; on this side it was worse, serving as the full-bleed hero of the
 * LGU Personnel sign-in page.
 *
 * It is NOT deleted: it stays usable as Balita media, where the wrong province
 * is a known and accepted property of a stock photo. What it may not do is
 * represent this municipality in a position of authority.
 */
class PlaceIdentityTest extends TestCase
{
    private const WRONG_PROVINCE_IMAGE = 'rectangle_cityhall.jpg';

    /**
     * Layout heroes are the "this is us" slot: full-bleed, unlabelled, and read
     * as a photograph of this municipality.
     */
    public function test_the_wrong_province_photo_is_not_a_layout_hero(): void
    {
        $offenders = [];

        foreach (glob(resource_path('views/**/*.blade.php')) as $file) {
            $source = file_get_contents($file);

            if (! preg_match_all('/<x-layouts\.[a-z-]+[^>]*>/s', $source, $tags)) {
                continue;
            }

            foreach ($tags[0] as $tag) {
                if (str_contains($tag, self::WRONG_PROVINCE_IMAGE)) {
                    $offenders[] = str_replace(resource_path('views').'/', '', $file);
                }
            }
        }

        $this->assertSame([], $offenders, sprintf(
            "%s shows a municipal hall sealed \"Esperanza, Agusan del Sur\" — the wrong\n"
            ."province — and must not be a layout hero. It is fine as Balita media.\nUsed as a hero in:\n  %s",
            self::WRONG_PROVINCE_IMAGE, implode("\n  ", $offenders)
        ));
    }

    public function test_the_image_is_still_present_for_balita_use(): void
    {
        // Guards against "fixing" this by deleting the asset: the banner and
        // media-library fixtures reference it by name and would break.
        $this->assertFileExists(
            public_path('images/esperanza/'.self::WRONG_PROVINCE_IMAGE),
            'The asset was removed; the Balita fixtures still name it.'
        );
    }
}
