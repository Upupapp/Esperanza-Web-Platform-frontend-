<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tulong's case-status vocabulary, pinned to the spec and to the views.
 *
 * Tulong is CLAUDE.md's documented exception to the fifteen-value request
 * lifecycle: it runs a separate, case-management-specific vocabulary, specified
 * in PROCESS_TAB_FLOW.txt PART 2. Until now that vocabulary existed in exactly
 * two places a consuming codebase cannot read — a .txt file and a hand-typed
 * array inside a Blade template — plus view fixtures. The backend lane measured
 * the consequence and reported it: its vendoring script reads config/, found
 * none of these names there, and was blocked on modelling thirteen assistance
 * programs.
 *
 * config/esperanza_tulong_lifecycle.php now declares the set. This test is what
 * stops the declaration, the spec and the two steppers drifting apart — a
 * config file nothing checks is a second source of truth, which is worse than
 * the single hand-typed one it replaced.
 */
class TulongLifecycleTest extends TestCase
{
    /** The fifteen request-lifecycle statuses. Tulong deliberately is not these. */
    private const LIFECYCLE = [
        'Draft', 'Submitted', 'Pending Review', 'Under Verification', 'Assigned',
        'Processing', 'Under Review', 'Resubmitted', 'Approved', 'Rejected',
        'Mark to Release', 'Released', 'Completed', 'Cancelled', 'Archived',
    ];

    private function all(): array
    {
        return config('esperanza_tulong_lifecycle.all', []);
    }

    /** @return string[] the quoted entries of a `name: [ ... ]` JS array in a Blade file */
    private function jsArray(string $bladePath, string $name): array
    {
        $source = file_get_contents(base_path($bladePath));

        $this->assertMatchesRegularExpression(
            '/'.preg_quote($name, '/').':\s*\[/',
            $source,
            "No `{$name}: [` found in {$bladePath} — the parser has broken, or the array was renamed."
        );

        preg_match('/'.preg_quote($name, '/').':\s*\[(.*?)\]/s', $source, $m);
        preg_match_all("/'([^']+)'/", $m[1], $entries);

        return $entries[1];
    }

    /** @return string[] the keys of a `statusStyles: { ... }` map in a Blade file */
    private function styleKeys(string $bladePath): array
    {
        $source = file_get_contents(base_path($bladePath));

        preg_match('/statusStyles:\s*\{(.*?)\n\s*\},/s', $source, $m);
        preg_match_all("/'([^']+)':\s*\[/", $m[1] ?? '', $keys);

        return $keys[1];
    }

    public function test_the_config_is_actually_being_read(): void
    {
        // Without this, every assertion below compares two empty sets and the
        // whole file passes vacuously the moment the config file is renamed.
        $this->assertCount(
            11,
            $this->all(),
            'esperanza_tulong_lifecycle.all should hold the eleven case statuses of '
            .'PROCESS_TAB_FLOW.txt PART 2.'
        );
    }

    public function test_all_is_exactly_the_union_of_its_parts(): void
    {
        $union = array_values(array_unique(array_merge(
            config('esperanza_tulong_lifecycle.main_sequence', []),
            config('esperanza_tulong_lifecycle.side_states', []),
            config('esperanza_tulong_lifecycle.terminal', []),
        )));

        sort($union);
        $all = $this->all();
        sort($all);

        $this->assertSame($union, $all,
            'The flat `all` set has drifted from main_sequence + side_states + terminal. '
            .'A consumer reading `all` and a consumer reading the parts would disagree.'
        );
    }

    public function test_tulong_only_is_the_seven_names_the_lifecycle_does_not_have(): void
    {
        // The count matters as much as the members. The backend lane enumerated
        // SIX of these by grepping config/ for absent names, and missed
        // `Assessment` — which is absent as a status but present as display copy
        // inside `process` arrays ("MSWDO Assessment"), so a substring search
        // finds it. Building an enum from that six is a status short.
        $expected = array_values(array_diff($this->all(), self::LIFECYCLE));
        sort($expected);

        $declared = config('esperanza_tulong_lifecycle.tulong_only', []);
        sort($declared);

        $this->assertSame($expected, $declared,
            '`tulong_only` must be exactly the Tulong statuses that are NOT among the fifteen.'
        );
        $this->assertCount(7, $declared, 'There are seven Tulong-only statuses, not six.');
    }

    public function test_the_four_shared_names_are_shared_by_coincidence_not_by_merger(): void
    {
        // Submitted, Rejected, Cancelled and Completed appear in both sets. That
        // is not evidence the two machines are one: asserting it keeps a future
        // cleanup from "deduplicating" the vocabularies into a single enum of
        // twenty-two, which is the specific misread the backend lane asked about.
        $shared = array_values(array_intersect($this->all(), self::LIFECYCLE));
        sort($shared);

        $this->assertSame(['Cancelled', 'Completed', 'Rejected', 'Submitted'], $shared);
        $this->assertCount(15, self::LIFECYCLE, 'The request lifecycle stays at fifteen.');
    }

    public function test_the_spec_documents_every_declared_status(): void
    {
        $spec = file_get_contents(base_path('PROCESS_TAB_FLOW.txt'));

        foreach ($this->all() as $status) {
            $this->assertStringContainsString($status, $spec,
                "PROCESS_TAB_FLOW.txt no longer documents the Tulong status '{$status}'."
            );
        }
    }

    /**
     * Both steppers are the declared main sequence, and so is the server.
     *
     * The backend runs Tulong's own case lifecycle since its finding B-044 closed
     * (2026-10-04), vendored from this config, so the declared sequence is the
     * one a real case moves through. (For the weeks between the pages going live
     * and that change, both steppers followed the shared request engine instead,
     * because a stepper drawn from statuses no request could reach sat on step
     * one forever.)
     */
    public function test_both_steppers_follow_the_declared_main_sequence(): void
    {
        $main = config('esperanza_tulong_lifecycle.main_sequence');

        $this->assertSame($main, $this->jsArray('resources/views/admin/assistance-requests.blade.php', 'statusOrder'),
            "The admin Process tab's statusOrder is not the declared main sequence."
        );

        $this->assertSame($main, $this->jsArray('resources/views/citizen/assistance-requests.blade.php', 'stepOrder'),
            "The citizen stepper's stepOrder is not the declared main sequence. "
            .'The two sides would then show a case at different stages.'
        );
    }

    /** The resident's status guide on the Tulong page explains every case status, and only those. */
    public function test_the_tulong_status_guide_explains_the_declared_vocabulary(): void
    {
        $html = (string) $this->blade('<x-citizen.status-guide type="tulong" />');

        foreach ($this->all() as $status) {
            $this->assertStringContainsString('>'.$status.'</span>', preg_replace('/\s+</', '<', $html),
                "The Tulong status guide does not explain '{$status}'."
            );
        }

        foreach (['Under Verification', 'Mark to Release', 'Released<'] as $engineOnly) {
            $this->assertStringNotContainsString($engineOnly, $html,
                "The Tulong status guide still explains the request engine's '{$engineOnly}'."
            );
        }
    }

    /** Neither page may still carry the shared engine's statuses. */
    public function test_neither_page_speaks_the_request_engine_vocabulary(): void
    {
        foreach ([
            'resources/views/admin/assistance-requests.blade.php',
            'resources/views/citizen/assistance-requests.blade.php',
        ] as $blade) {
            $source = file_get_contents(base_path($blade));

            // Case-level names only: Resubmitted and Approved are also
            // requirement statuses (shared with Dokyu), so they stay.
            foreach (['Under Verification', 'Under Review', 'Mark to Release', 'Released'] as $engineOnly) {
                $this->assertStringNotContainsString("'{$engineOnly}'", $source,
                    "{$blade} still names the request engine's '{$engineOnly}', which a Tulong case never holds."
                );
            }
        }
    }

    public function test_every_declared_status_has_a_style_on_both_sides(): void
    {
        // A status with no style renders unstyled wherever it appears; the
        // reverse (a styled name no status uses) is inert, so this asserts
        // coverage rather than equality.
        foreach ([
            'resources/views/admin/assistance-requests.blade.php',
            'resources/views/citizen/assistance-requests.blade.php',
        ] as $blade) {
            $styled = $this->styleKeys($blade);

            $this->assertNotEmpty($styled, "No statusStyles parsed from {$blade} — the parser has broken.");

            $unstyled = array_values(array_diff($this->all(), $styled));

            $this->assertSame([], $unstyled, sprintf(
                '%s has no statusStyles entry for: %s', $blade, implode(', ', $unstyled)
            ));
        }
    }

    public function test_the_transition_table_only_names_declared_statuses(): void
    {
        $all = $this->all();

        foreach (config('esperanza_tulong_lifecycle.transitions', []) as $from => $targets) {
            $this->assertContains($from, $all, "Transition source '{$from}' is not a declared Tulong status.");

            foreach ($targets as $to) {
                $this->assertContains($to, $all, "Transition target '{$to}' is not a declared Tulong status.");
            }
        }

        // Terminal states are terminal: none of them may have outbound moves.
        foreach (config('esperanza_tulong_lifecycle.terminal', []) as $terminal) {
            $this->assertArrayNotHasKey($terminal, config('esperanza_tulong_lifecycle.transitions', []),
                "'{$terminal}' is declared terminal but has outbound transitions."
            );
        }
    }

    public function test_every_reachable_status_is_reachable_from_submitted(): void
    {
        // A declared status that no transition can reach is either a dead value
        // or a missing edge; both are things a backend modelling this machine
        // would implement wrongly and never see fail.
        $transitions = config('esperanza_tulong_lifecycle.transitions', []);
        $seen = ['Submitted' => true];
        $queue = ['Submitted'];

        while ($queue) {
            foreach ($transitions[array_shift($queue)] ?? [] as $next) {
                if (! isset($seen[$next])) {
                    $seen[$next] = true;
                    $queue[] = $next;
                }
            }
        }

        $unreachable = array_values(array_diff($this->all(), array_keys($seen)));

        $this->assertSame([], $unreachable, sprintf(
            'Declared Tulong status(es) unreachable from Submitted: %s', implode(', ', $unreachable)
        ));
    }

    public function test_the_case_fixtures_only_use_declared_statuses(): void
    {
        // The fixtures exercise a SUBSET of the vocabulary — six of eleven at the
        // time of writing. That is fine for fixtures and fatal for anyone who
        // builds an enum by scanning them, which is why the declaration above
        // exists and this test asserts containment, not equality.
        foreach (['admin', 'citizen'] as $side) {
            $rows = require base_path("resources/data/{$side}/assistance-requests.php");

            $found = [];

            foreach ($this->caseRows($rows) as $row) {
                $found[$row['status']] = true;
            }

            $this->assertNotEmpty($found, "No case rows read from {$side} — the reader has broken.");

            $undeclared = array_values(array_diff(array_keys($found), $this->all()));

            $this->assertSame([], $undeclared, sprintf(
                '%s Tulong fixtures carry case status(es) not in the declared vocabulary: %s',
                $side, implode(', ', $undeclared)
            ));
        }
    }

    /**
     * Case rows are the ones keyed by an `AR-` reference. Requirement rows also
     * carry a `status`, on a DIFFERENT vocabulary (Submitted / Approved /
     * Rejected / Flagged for Replacement, shared with Dokyu) — reading both
     * levels as one set is how a consumer ends up with a merged enum that is
     * wrong at both levels.
     *
     * @return iterable<array{status: string}>
     */
    private function caseRows(array $node): iterable
    {
        foreach ($node as $value) {
            if (! is_array($value)) {
                continue;
            }

            if (isset($value['ref'], $value['status']) && is_string($value['ref'])
                && str_starts_with($value['ref'], 'AR-')) {
                yield $value;
            }

            yield from $this->caseRows($value);
        }
    }

    /**
     * The rules that protect a citizen, not just the labels.
     */
    public function test_the_reason_and_evidence_rules_are_declared(): void
    {
        $reasons = config('esperanza_tulong_lifecycle.requires_reason');

        foreach (['Rejected', 'Cancelled', 'Returned for Reassessment'] as $state) {
            $this->assertContains($state, $reasons, sprintf(
                '"%s" must require a typed reason. PROCESS_TAB_FLOW.txt PART 2 makes each of '
                .'these unavailable until text is entered, and that text is what the citizen reads.',
                $state
            ));
        }

        $this->assertSame(
            ['Ready for Release' => 'Completed'],
            config('esperanza_tulong_lifecycle.blocked_until_release_evidence'),
            'Confirm Release must stay blocked until proof of release is captured.'
        );
    }
}
