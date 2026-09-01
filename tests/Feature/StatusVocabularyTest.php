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

    /** Axis 2 — the state of an ACCOUNT. Binary: may transact, or may not. */
    private const ACCOUNT_TIER = ['Verified', 'Unverified'];

    /** Axis 3 — how well a CONSTITUENT RECORD is evidenced. Never an account. */
    private const RECORD_LADDER = ['Unverified', 'For Validation', 'Partially Verified', 'Verified'];

    /** Every name the constitution documents, across all three axes. */
    private function documented(): array
    {
        $all = array_unique(array_merge(self::LIFECYCLE, self::ACCOUNT_TIER, self::RECORD_LADDER));
        sort($all);

        return $all;
    }

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
        $documented = $this->documented();
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

    public function test_the_lifecycle_does_not_collide_with_either_verification_axis(): void
    {
        $this->assertSame(
            [],
            array_values(array_intersect(self::LIFECYCLE, self::ACCOUNT_TIER)),
            'A name cannot be both a request lifecycle status and an account tier. '
            .'A request that passed verification is Approved; Verified describes the person.'
        );

        $this->assertSame(
            [],
            array_values(array_intersect(self::LIFECYCLE, self::RECORD_LADDER)),
            'A name cannot be both a request lifecycle status and a record verification rung.'
        );
    }

    public function test_the_account_tier_and_record_ladder_share_words_on_purpose(): void
    {
        // These two DO overlap, and that is the point: an account is Verified
        // when it may transact; a record is Verified when its evidence is
        // complete. Asserting it keeps a future cleanup from "deduplicating"
        // them back into one axis.
        $this->assertSame(
            ['Unverified', 'Verified'],
            array_values(array_intersect(self::RECORD_LADDER, self::ACCOUNT_TIER)),
            'The account tier and the record ladder are expected to share exactly '
            .'Verified and Unverified. If that changed, one of the axes moved.'
        );

        $this->assertCount(4, self::RECORD_LADDER, 'The record ladder has four rungs.');
        $this->assertCount(2, self::ACCOUNT_TIER, 'The account tier is binary.');
    }

    public function test_every_verification_value_in_the_data_is_in_the_vocabulary(): void
    {
        // THE assertion this whole ruling exists for. The list is what every
        // other codebase builds its enum from, and none of them can see this
        // repo's data. A value that lives in the fixtures but not in the list
        // is a value a client will not have a case for.
        $found = [];

        foreach (['individuals', 'families', 'households'] as $collection) {
            foreach (config('esperanza_constituents.'.$collection, []) as $row) {
                if (isset($row['verification'])) {
                    $found[$row['verification']] = ($found[$row['verification']] ?? 0) + 1;
                }
            }
        }

        $this->assertNotEmpty($found, 'No verification values found — the reader has probably broken.');

        $undocumented = array_diff(array_keys($found), self::RECORD_LADDER);

        $this->assertSame([], array_values($undocumented), sprintf(
            "The constituent data carries %d verification value(s) that CLAUDE.md's record "
            ."ladder does not list: %s\nx-ui.badge has no style for them, and every downstream "
            .'client builds its enum from that list.',
            count($undocumented), implode(', ', $undocumented)
        ));
    }

    public function test_claude_md_documents_both_axes(): void
    {
        $constitution = file_get_contents(base_path('CLAUDE.md'));

        foreach ($this->documented() as $name) {
            $this->assertStringContainsString(
                $name,
                $constitution,
                "CLAUDE.md no longer documents the status name '{$name}'."
            );
        }

        foreach (['Request lifecycle', 'Account access tier', 'Record verification ladder'] as $heading) {
            $this->assertStringContainsString(
                $heading,
                $constitution,
                "CLAUDE.md no longer documents the \"{$heading}\" axis. Collapsing the axes is how "
                .'a request ends up with a status of "Verified", or an account with "Partially Verified".'
            );
        }
    }
}
