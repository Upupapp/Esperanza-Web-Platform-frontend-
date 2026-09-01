<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The gate has to be runnable on the machines that are not this one.
 *
 * There is no CI on this project, so scripts/gate.sh IS the gate and
 * scripts/hooks/pre-push is the only thing that runs it. Git will silently skip
 * a pre-push hook that is not executable -- no warning, no output, the push just
 * succeeds. On Windows this is invisible, because Git for Windows ignores the
 * executable bit and runs the hook anyway; on Linux and macOS the same commit
 * means the gate never runs at all.
 *
 * That is the worst shape a gate defect can take: green on the machine that
 * wrote it, absent everywhere else, and silent in both cases. Both files were
 * committed 100644 once already.
 */
class GateIsRunnableTest extends TestCase
{
    private const MUST_BE_EXECUTABLE = [
        'scripts/gate.sh',
        'scripts/hooks/pre-push',
    ];

    public function test_the_gate_and_its_hook_are_executable_in_the_index(): void
    {
        $output = [];
        $status = 0;
        exec('git -C '.escapeshellarg(base_path()).' ls-files -s scripts 2>&1', $output, $status);

        if ($status !== 0) {
            $this->markTestSkipped('git is not available to read the index.');
        }

        $modes = [];
        foreach ($output as $line) {
            // "100755 <sha> 0\tscripts/gate.sh"
            if (preg_match('/^(\d{6})\s+\S+\s+\d+\s+(.+)$/', $line, $m)) {
                $modes[trim($m[2])] = $m[1];
            }
        }

        $this->assertNotEmpty($modes, 'Could not read any file modes from the git index.');

        foreach (self::MUST_BE_EXECUTABLE as $path) {
            $this->assertArrayHasKey($path, $modes, "{$path} is not tracked by git.");

            $this->assertSame(
                '100755',
                $modes[$path],
                "{$path} is committed as {$modes[$path]}, not 100755. Git SILENTLY SKIPS a "
                .'non-executable pre-push hook on Linux and macOS, so the gate would not run '
                .'there at all -- and Git for Windows would hide that by running it anyway. '
                .'Fix with: git update-index --chmod=+x '.$path
            );
        }
    }

    public function test_the_hook_actually_invokes_the_gate(): void
    {
        // A hook that exists, is executable, and runs nothing is still no gate.
        $hook = file_get_contents(base_path('scripts/hooks/pre-push'));

        $this->assertStringContainsString(
            'gate.sh',
            $hook,
            'scripts/hooks/pre-push no longer runs scripts/gate.sh.'
        );
    }
}
