#!/usr/bin/env bash
#
# Esperanza Web Platform — the gate.
#
# There is no CI on this project by standing instruction, so this script IS the
# gate and a push to main IS the deploy (see DEPLOY_PROTOCOL.md). It is wired to
# .git hooks via scripts/hooks/pre-push.
#
# Prints an explicit PASS/FAIL per step and a final VERDICT. Never rely on the
# exit code alone to tell you a step ran: a skipped step also exits 0.
#
set -uo pipefail
cd "$(dirname "$0")/.." || exit 1

FAILED=0
STEPS_RUN=0

say()  { echo ""; echo "-- $*"; }
pass() { echo "   [PASS] $*"; STEPS_RUN=$((STEPS_RUN + 1)); }
fail() { echo "   [FAIL] $*"; STEPS_RUN=$((STEPS_RUN + 1)); FAILED=1; }

# -- Resolve PHP. Never hardcode one machine's path: this repo is worked on from
# -- more than one box, so try the env var, then PATH, then known install roots.
resolve_php() {
  if [ -n "${PHP:-}" ] && "$PHP" -v >/dev/null 2>&1; then echo "$PHP"; return; fi
  if command -v php >/dev/null 2>&1; then echo "php"; return; fi
  for c in /c/laragon/bin/php/php-*/php.exe /c/xampp/php/php.exe "/c/Program Files/php/php.exe"; do
    [ -x "$c" ] && { echo "$c"; return; }
  done
  echo ""
}

PHP_BIN="$(resolve_php)"
if [ -z "$PHP_BIN" ]; then
  echo "   [FAIL] no PHP interpreter found. Set PHP=/path/to/php or put php on PATH."
  echo ""
  echo "=== VERDICT: FAIL (cannot run the gate) ==="
  exit 1
fi

echo "=== Esperanza gate @ $(git rev-parse --short HEAD 2>/dev/null || echo unknown) ==="
echo "    php: $("$PHP_BIN" -r 'echo PHP_VERSION;' 2>/dev/null)"

# 1. Vite build. Must run BEFORE the tests: public/build is gitignored, so a
#    fresh checkout has no manifest and every route 500s with "Vite manifest
#    not found" -- which reads like a catastrophe that is not there.
say "vite build"
if npm run build </dev/null >/tmp/esperanza-gate-build.log 2>&1; then
  pass "vite build"
else
  fail "vite build"; tail -25 /tmp/esperanza-gate-build.log
fi

# 2. Formatting.
say "pint --test"
if "$PHP_BIN" vendor/bin/pint --test </dev/null >/tmp/esperanza-gate-pint.log 2>&1; then
  pass "pint --test"
else
  fail "pint --test"; tail -5 /tmp/esperanza-gate-pint.log
fi

# 3. The suite, which carries the route-render and export-drift gates.
say "phpunit (route render + export drift gates)"
if "$PHP_BIN" artisan test </dev/null >/tmp/esperanza-gate-test.log 2>&1; then
  pass "phpunit"
else
  fail "phpunit"; tail -40 /tmp/esperanza-gate-test.log
fi

# 4. The deploy artifact itself.
say "static export"
rm -rf dist
if "$PHP_BIN" export.php </dev/null >/tmp/esperanza-gate-export.log 2>&1; then
  pass "static export"
else
  fail "static export"; tail -25 /tmp/esperanza-gate-export.log
fi

# 5. Assertions ON dist/, not on the exporter's own summary.
say "dist/ assertions"
PAGES=$(find dist -name index.html 2>/dev/null | wc -l | tr -d ' ')
ROUTES=$("$PHP_BIN" -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); require "build/export_manifest.php"; echo count(esperanza_export_routes(app("router")->getRoutes()));' 2>/dev/null)
echo "   routes derived: ${ROUTES:-?}   pages in dist/: $PAGES"
if [ -n "$ROUTES" ] && [ "$PAGES" = "$ROUTES" ]; then
  pass "every derived route reached dist/ ($PAGES)"
else
  fail "dist/ has $PAGES pages for ${ROUTES:-?} derived routes"
fi

EMPTY=$(find dist -name '*.html' -size 0 2>/dev/null | wc -l | tr -d ' ')
[ "$EMPTY" = "0" ] && pass "no zero-byte HTML" || { fail "$EMPTY zero-byte HTML file(s)"; }

PDFS=$(find dist/documents -name '*.pdf' 2>/dev/null | wc -l | tr -d ' ')
[ "$PDFS" -ge 16 ] && pass "$PDFS reference PDFs in dist/documents" || fail "only $PDFS reference PDFs reached dist/"

echo ""
echo "    steps run: $STEPS_RUN"
if [ "$FAILED" -eq 0 ]; then
  echo "=== VERDICT: PASS ==="
else
  echo "=== VERDICT: FAIL ==="
fi
exit $FAILED
