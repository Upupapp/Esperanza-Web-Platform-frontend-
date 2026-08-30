<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The Constituents module's own vocabularies, pinned to the data. B-018.
 *
 * Ruled 2026-08-30: "reuse exactly these names, never invent new ones" governs
 * requests, accounts and records — it was never meant to forbid a module from
 * having a vocabulary for its own domain, which read literally would make a
 * data-quality queue illegal. So these sets are legal, but they are CLOSED, and
 * this is what closes them.
 *
 * The point is the same as B-020 and B-022: every other codebase builds its
 * enums from CLAUDE.md and none of them can see these fixtures. A value that
 * exists in the data but not in the document is a case a client will not have.
 */
class ModuleVocabularyTest extends TestCase
{
    /** households[].status, families[].status, individuals[].status */
    private const RECORD_ACTIVITY = ['Active', 'Under Review'];

    /** barangayCoverage[].status */
    private const PROFILING_COVERAGE = [
        'Not Started (Re-visit Required)', 'In Progress', 'Needs Follow-up', 'Complete',
    ];

    /** dataQualityIssues[].status and individuals[].incident_reports[].status */
    private const ISSUE_TRIAGE = ['Open', 'In Review', 'Resolved'];

    /** The 15 request-lifecycle statuses; nested request rows must use these. */
    private const LIFECYCLE = [
        'Draft', 'Submitted', 'Pending Review', 'Under Verification', 'Assigned',
        'Processing', 'Under Review', 'Resubmitted', 'Approved', 'Rejected',
        'Mark to Release', 'Released', 'Completed', 'Cancelled', 'Archived',
    ];

    /** @return string[] distinct values of $field across $rows */
    private function distinct(array $rows, string $field): array
    {
        $seen = [];

        foreach ($rows as $row) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $seen[$row[$field]] = true;
            }
        }

        return array_keys($seen);
    }

    private function assertVocabulary(array $found, array $allowed, string $where): void
    {
        $this->assertNotEmpty($found, "No values read from {$where} — the reader has broken and this would pass vacuously.");

        $undocumented = array_values(array_diff($found, $allowed));

        $this->assertSame([], $undocumented, sprintf(
            "%s carries %d value(s) CLAUDE.md does not document: %s\n"
            .'Add it to the module-local vocabulary table, or stop using it.',
            $where, count($undocumented), implode(', ', $undocumented)
        ));
    }

    public function test_record_activity_vocabulary(): void
    {
        foreach (['households', 'families', 'individuals'] as $collection) {
            $this->assertVocabulary(
                $this->distinct(config('esperanza_constituents.'.$collection, []), 'status'),
                self::RECORD_ACTIVITY,
                $collection.'[].status'
            );
        }
    }

    public function test_profiling_coverage_vocabulary(): void
    {
        $this->assertVocabulary(
            $this->distinct(config('esperanza_constituents.barangayCoverage', []), 'status'),
            self::PROFILING_COVERAGE,
            'barangayCoverage[].status'
        );
    }

    public function test_issue_triage_vocabulary(): void
    {
        $this->assertVocabulary(
            $this->distinct(config('esperanza_constituents.dataQualityIssues', []), 'status'),
            self::ISSUE_TRIAGE,
            'dataQualityIssues[].status'
        );

        $incidents = [];
        foreach (config('esperanza_constituents.individuals', []) as $individual) {
            foreach ($individual['incident_reports'] ?? [] as $report) {
                $incidents[] = $report;
            }
        }

        $this->assertVocabulary(
            $this->distinct($incidents, 'status'),
            self::ISSUE_TRIAGE,
            'individuals[].incident_reports[].status'
        );
    }

    public function test_nested_request_rows_use_the_lifecycle_not_a_local_vocabulary(): void
    {
        // These are requests, not records, so they are NOT module-local. If one
        // ever drifts to "Active" or "Open" the two vocabularies have started
        // leaking into each other.
        foreach (['document_requests', 'assistance_requests', 'services'] as $collection) {
            $rows = [];
            foreach (config('esperanza_constituents.individuals', []) as $individual) {
                foreach ($individual[$collection] ?? [] as $row) {
                    $rows[] = $row;
                }
            }

            $this->assertVocabulary(
                $this->distinct($rows, 'status'),
                self::LIFECYCLE,
                'individuals[].'.$collection.'[].status'
            );
        }
    }

    public function test_the_documented_collisions_still_hold(): void
    {
        // Two names look like typos and are not. If either stops being true the
        // CLAUDE.md note explaining them is now misleading.
        $this->assertContains('Under Review', self::LIFECYCLE);
        $this->assertContains('Under Review', self::RECORD_ACTIVITY);
        $this->assertContains('In Review', self::ISSUE_TRIAGE);
        $this->assertNotContains('In Review', self::LIFECYCLE, 'In Review is issue triage, not a request status.');
    }
}
