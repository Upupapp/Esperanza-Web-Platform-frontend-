<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * CERTIFICATION.md must not be able to lie about itself.
 *
 * The document states a count ("6 of the 10 gates pass. 4 fail.") above a table
 * that states each gate's result. Nothing connected the two, so flipping a row
 * without editing the sentence — or the reverse — left a summary that read as
 * authoritative and was wrong. That is the same shape as B-023, where a partial
 * edit left a superseded paragraph standing, and as B-020, where a list and the
 * component it described drifted apart.
 *
 * Suggested by the esperanza_backend session after the identical problem bit
 * their STATUS.md: a summary line that counts open items should have something
 * that fails when the count moves.
 *
 * TAB 17 is explicit that a partial pass is NOT CERTIFIED rather than a
 * qualified yes, so that is asserted here too — the verdict cannot quietly
 * soften while gates are still red.
 */
class CertificationConsistencyTest extends TestCase
{
    private function document(): string
    {
        $path = base_path('CERTIFICATION.md');

        $this->assertFileExists($path, 'CERTIFICATION.md is the TAB 17 deliverable and must exist.');

        return file_get_contents($path);
    }

    /** @return array{pass:int, fail:int, other:int, total:int, ids:list<int>} */
    private function tally(string $doc): array
    {
        // Rows of the ten-gate table: "| 3 | ... | **FAIL** |"
        preg_match_all('/^\|\s*(\d+)\s*\|.*?\|\s*\*\*([A-Z ]+)\*\*\s*\|/m', $doc, $rows, PREG_SET_ORDER);

        $tally = ['pass' => 0, 'fail' => 0, 'other' => 0, 'total' => 0, 'ids' => []];

        foreach ($rows as $row) {
            $tally['total']++;
            $tally['ids'][] = (int) $row[1];
            $result = trim($row[2]);

            if ($result === 'PASS') {
                $tally['pass']++;
            } elseif ($result === 'FAIL') {
                $tally['fail']++;
            } else {
                $tally['other']++;
            }
        }

        return $tally;
    }

    public function test_the_gate_table_is_readable_and_complete(): void
    {
        $tally = $this->tally($this->document());

        // Without this the count assertions below could pass by comparing
        // nothing against nothing.
        $this->assertSame(
            10,
            $tally['total'],
            'TAB 17 defines exactly ten certification gates; the table in CERTIFICATION.md '
            ."parsed as {$tally['total']} row(s). Either a gate was added without updating "
            .'this test, or the table format changed and the parser no longer reads it.'
        );

        $this->assertSame(
            0,
            $tally['other'],
            'A gate row is neither **PASS** nor **FAIL**. A gate with a third state is a gate '
            .'that has stopped being a gate — TAB 17 allows no "partially" column.'
        );
    }

    /**
     * The gate NUMBERS are load-bearing, not decoration.
     *
     * "Gate 7" is cited from FRONTEND_PENDING.md in nine places, from
     * scripts/gate.sh (the page-weight block names "certification gate 8"), from
     * ESPERANZA_FRONTEND_MASTER_COMMAND.md and from this document's own prose.
     * Those references resolve by number and nothing follows them, so a
     * duplicated or skipped row silently repoints every one of them.
     *
     * The three assertions above could not see that: they count rows and read
     * results, and a table numbered 1..9 with 7 listed twice has ten rows and a
     * correct tally. Raised by the esperanza-backend lane, whose own register
     * carried two findings numbered B-040 with the whole gate green — their
     * parser built a map keyed by id, so the duplicate overwrote rather than
     * collided. Ours counts instead, so the totals stay right and the identity
     * problem is what goes unseen. Different failure, same blind spot: an
     * assertion that reads a projection of the data cannot see what the
     * projection drops.
     */
    public function test_the_gates_are_numbered_one_to_ten_with_no_repeats(): void
    {
        $ids = $this->tally($this->document())['ids'];

        $duplicates = array_keys(array_filter(array_count_values($ids), fn ($n) => $n > 1));
        sort($duplicates);

        $this->assertSame([], $duplicates, sprintf(
            'Gate number(s) %s appear on more than one row of the table in CERTIFICATION.md. '
            .'The row count and the tally stay correct when this happens, so nothing else here '
            .'fails — but every "gate N" reference elsewhere in the repo now points at two rows.',
            implode(', ', $duplicates)
        ));

        $this->assertSame(range(1, 10), $ids, sprintf(
            'The gate table must be numbered 1..10 in order; it reads %s. A skipped or reordered '
            .'number silently repoints the "gate N" citations in FRONTEND_PENDING.md, '
            .'scripts/gate.sh and this document.',
            implode(', ', $ids)
        ));
    }

    public function test_the_stated_count_matches_the_table(): void
    {
        $doc = $this->document();
        $tally = $this->tally($doc);

        $this->assertMatchesRegularExpression(
            '/\*\*(\d+) of the (\d+) gates pass\.\s*(\d+) fails?\.\*\*/',
            $doc,
            'CERTIFICATION.md no longer states its own tally in the expected form, so nothing '
            .'can check it against the table.'
        );

        preg_match('/\*\*(\d+) of the (\d+) gates pass\.\s*(\d+) fails?\.\*\*/', $doc, $m);

        [$statedPass, $statedTotal, $statedFail] = [(int) $m[1], (int) $m[2], (int) $m[3]];

        $this->assertSame($tally['pass'], $statedPass, sprintf(
            'CERTIFICATION.md says %d gate(s) pass; the table shows %d. A summary that '
            .'disagrees with the thing it summarises is worse than no summary.',
            $statedPass, $tally['pass']
        ));

        $this->assertSame($tally['fail'], $statedFail, sprintf(
            'CERTIFICATION.md says %d gate(s) fail; the table shows %d.',
            $statedFail, $tally['fail']
        ));

        $this->assertSame($tally['total'], $statedTotal, sprintf(
            'CERTIFICATION.md says there are %d gates; the table has %d rows.',
            $statedTotal, $tally['total']
        ));
    }

    public function test_the_verdict_follows_from_the_gates(): void
    {
        $doc = $this->document();
        $tally = $this->tally($doc);

        if ($tally['fail'] > 0) {
            $this->assertStringContainsString(
                'NOT CERTIFIED',
                $doc,
                sprintf(
                    '%d gate(s) fail, so the verdict must be NOT CERTIFIED. TAB 17 is explicit '
                    .'that a partial pass is NOT CERTIFIED, not a qualified yes.',
                    $tally['fail']
                )
            );

            return;
        }

        $this->assertStringNotContainsString(
            'NOT CERTIFIED',
            $doc,
            'Every gate passes, but the document still reads NOT CERTIFIED.'
        );
    }
}
