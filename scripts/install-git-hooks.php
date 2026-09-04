<?php

/**
 * Point this clone's git at scripts/hooks, so the pre-push gate actually runs.
 *
 * Runs from composer's post-autoload-dump, which means plain `composer install`
 * installs it — not only `composer setup`, which nobody types after day one.
 * Raised by the backend lane as B-039 and measured here: the line lived in the
 * `setup` block alone, so every clone that ran `composer install` got no gate,
 * and a missing hook is silent by construction — `git push` succeeds and prints
 * nothing, so an absent gate is indistinguishable from a gate that passed.
 *
 * PHP rather than shell so the Windows lane runs it too.
 *
 * It never exits non-zero: failing `composer install` over a git hook is worse
 * than the missing hook. And it refuses to overwrite a core.hooksPath someone
 * else set — that is their decision, and it gets reported rather than clobbered.
 */
$root = dirname(__DIR__);
$desired = 'scripts/hooks';

$say = static fn (string $m) => fwrite(STDOUT, "  hooks: {$m}\n");

// A tarball or a vendored deployment has no .git. Nothing to do, and not a fault.
if (! is_dir($root.'/.git') && ! is_file($root.'/.git')) {
    exit(0);
}

$run = static function (array $args) use ($root): array {
    $cmd = 'git -C '.escapeshellarg($root).' '.implode(' ', array_map('escapeshellarg', $args)).' 2>&1';
    exec($cmd, $out, $code);

    return [trim(implode("\n", $out)), $code];
};

[$current, $code] = $run(['config', '--local', 'core.hooksPath']);

if ($code === 0 && $current !== '' && $current !== $desired) {
    $say("core.hooksPath is already set to \"{$current}\" — left alone.");
    $say("This repo's gate lives in {$desired}; if that is not deliberate, run:");
    $say("  git config core.hooksPath {$desired}");
    exit(0);
}

if ($current === $desired) {
    exit(0);   // already correct; say nothing on every install
}

[$out, $code] = $run(['config', 'core.hooksPath', $desired]);

if ($code !== 0) {
    $say("could not set core.hooksPath ({$out}). The pre-push gate will NOT run.");
    $say("Set it by hand:  git config core.hooksPath {$desired}");
    exit(0);   // deliberately not a failure
}

$say("pre-push gate installed (core.hooksPath = {$desired}).");
exit(0);
