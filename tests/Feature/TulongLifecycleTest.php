<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The Tulong vocabulary in config/ must match what the app actually renders.
 *
 * The vocabulary lived only in PROCESS_TAB_FLOW.txt and in view fixtures until
 * 2026-09-04. The backend lane vendors config/, so a status declared only in
 * prose is one they cannot consume — and hand-typing it on their side is
 * forbidden by their own rules. Declaring it is only half the job: a config
 * that drifts from the fixtures is worse than no config, because it is
 * consumed as truth.
 */
class TulongLifecycleTest extends TestCase
{
    private function vocabulary(): array
    {
        $c = config('esperanza_tulong_lifecycle');

        return array_values(array_unique(array_merge(
            $c['stages'],
            array_keys($c['side_states']),
            $c['terminal'],
        )));
    }

    public function test_every_status_in_the_tulong_fixtures_is_declared(): void
    {
        $declared = $this->vocabulary();
        $unknown = [];

        foreach (['admin', 'citizen'] as $side) {
            $path = base_path("resources/data/{$side}/assistance-requests.php");

            if (! file_exists($path)) {
                continue;
            }

            foreach ((require $path)['requests'] ?? [] as $request) {
                $status = $request['status'] ?? null;

                if ($status !== null && ! in_array($status, $declared, true)) {
                    $unknown[] = "{$side}: {$status}";
                }
            }
        }

        $this->assertSame([], array_values(array_unique($unknown)), sprintf(
            "A Tulong fixture uses a status the config does not declare:\n  %s\n"
            .'Add it to config/esperanza_tulong_lifecycle.php, or correct the fixture. '
            .'The config is what the backend lane vendors.',
            implode("\n  ", array_unique($unknown))
        ));
    }

    public function test_every_declared_status_is_reachable_by_a_transition(): void
    {
        $c = config('esperanza_tulong_lifecycle');
        $reachable = ['Submitted'];   // the entry state

        foreach ($c['transitions'] as $t) {
            $reachable[] = $t['to'];
        }

        $orphans = array_values(array_diff($this->vocabulary(), array_unique($reachable)));

        $this->assertSame([], $orphans, sprintf(
            'Declared but unreachable: %s. A status no transition leads to is either dead '
            .'or a transition is missing — both are defects in a state machine the backend '
            .'will build from.',
            implode(', ', $orphans)
        ));
    }

    public function test_the_tulong_vocabulary_does_not_silently_reuse_dokyu_stages(): void
    {
        // PROCESS_TAB_FLOW.txt PART 2 is explicit that these are NOT Tulong
        // states. If one appears, someone has merged the two vocabularies.
        foreach (config('esperanza_tulong_lifecycle.not_used') as $forbidden) {
            $this->assertNotContains($forbidden, $this->vocabulary(), sprintf(
                '"%s" is a Dokyu status and must not appear in the Tulong vocabulary. '
                .'Tulong has no Resubmitted, and its terminal release state is Completed, '
                .'never Released.',
                $forbidden
            ));
        }
    }
}
