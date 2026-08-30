<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * CLAUDE.md's status list and x-ui.badge must agree.
 *
 * They did not. The badge styled 17 labels; the constitution's "reuse exactly
 * these names everywhere — never invent new ones" list had 15. The two missing
 * were `Verified` and `Unverified` — the pair that decides whether a citizen
 * gets Dokyu/Tulong access at all.
 *
 * That is not cosmetic, because the list is what every OTHER codebase builds
 * from, and a client cannot see this repo's Blade to notice the difference:
 *
 *   - The mobile app built an enum from the 15, had no `Verified`, silently
 *     degraded it to `Draft`, and locked verified citizens out of Dokyu.
 *   - The backend built `Status::ALL` from the same 15, ended up with no access
 *     tier at all, and accepted requests from citizens this UI refuses —
 *     client stricter than server, on who may request a government document.
 *
 * Ruled 2026-08-30: both belong, on a SEPARATE axis from the request lifecycle.
 * This test is what stops the two lists drifting apart a third time.
 */
class StatusVocabularyTest extends TestCase
{
    /** Axis 1 — the state of a Dokyu/Tulong request. */
    private const LIFECYCLE = [
        'Draft', 'Submitted', 'Pending Review', 'Under Verification', 'Assigned',
        'Processing', 'Under Review', 'Resubmitted', 'Approved', 'Rejected',
        'Mark to Release', 'Released', 'Completed', 'Cancelled', 'Archived',
    ];

    /** Axis 2 — the state of an account or a constituent record. Never a request. */
    private const VERIFICATION_TIER = ['Verified', 'Unverified'];

    /** @return string[] every label x-ui.badge knows how to render */
    private function badgeLabels(): array
    {
        $source = file_get_contents(base_path('resources/views/components/ui/badge.blade.php'));

        preg_match_all("/^\s+'([A-Za-z][A-Za-z ]*)'\s*=>\s*\[/m", $source, $m);

        $labels = array_values(array_unique($m[1]));
        sort($labels);

        return $labels;
    }

    public function test_the_badge_component_is_actually_being_read(): void
    {
        // Without this, a regex that stops matching turns every assertion below
        // green while comparing two empty sets.
        $this->assertGreaterThanOrEqual(
            15,
            count($this->badgeLabels()),
            'Parsed suspiciously few labels out of badge.blade.php — the parser has probably '
            .'broken, and this whole file would pass vacuously.'
        );
    }

    public function test_badge_and_the_constitution_cover_exactly_the_same_names(): void
    {
        $documented = array_merge(self::LIFECYCLE, self::VERIFICATION_TIER);
        sort($documented);

        $rendered = $this->badgeLabels();

        $undocumented = array_values(array_diff($rendered, $documented));
        $unrendered = array_values(array_diff($documented, $rendered));

        $this->assertSame([], $undocumented, sprintf(
            "x-ui.badge renders %d name(s) that CLAUDE.md does not list: %s\n"
            .'Every other codebase builds its enum from that list and cannot see this file. '
            .'Add them to CLAUDE.md (on the correct axis) or stop rendering them.',
            count($undocumented), implode(', ', $undocumented)
        ));

        $this->assertSame([], $unrendered, sprintf(
            "CLAUDE.md lists %d name(s) that x-ui.badge cannot render: %s\n"
            .'A status with no badge style renders unstyled wherever it appears.',
            count($unrendered), implode(', ', $unrendered)
        ));
    }

    public function test_the_two_axes_do_not_overlap(): void
    {
        $this->assertSame(
            [],
            array_values(array_intersect(self::LIFECYCLE, self::VERIFICATION_TIER)),
            'A name cannot be both a request lifecycle status and a verification tier. '
            .'A request that passed verification is Approved; Verified describes the person.'
        );
    }

    public function test_claude_md_documents_both_axes(): void
    {
        $constitution = file_get_contents(base_path('CLAUDE.md'));

        foreach (array_merge(self::LIFECYCLE, self::VERIFICATION_TIER) as $name) {
            $this->assertStringContainsString(
                $name,
                $constitution,
                "CLAUDE.md no longer documents the status name '{$name}'."
            );
        }

        $this->assertStringContainsString(
            'Verification tier',
            $constitution,
            'CLAUDE.md no longer separates the verification tier from the request lifecycle. '
            .'Collapsing them invites a request whose status is "Verified".'
        );
    }
}
