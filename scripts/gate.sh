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

# The fingerprint of the code this run checks: a git tree hash of every
# tracked and untracked (non-ignored) file as it is on disk, built in a scratch
# index so the real index is untouched. It is recorded when the gate passes and
# nothing changed during the run; the pre-push hook then skips the gate for a
# push whose commits hold exactly this code. The record lives in .git and is
# never committed.
worktree_tree() {
  local idx
  idx="$(mktemp)" || return 1
  cp "$(git rev-parse --git-path index)" "$idx" 2>/dev/null || :
  if GIT_INDEX_FILE="$idx" git add -A . >/dev/null 2>&1; then
    GIT_INDEX_FILE="$idx" git write-tree 2>/dev/null
  fi
  rm -f "$idx"
}
record_verified_tree() {
  local now
  now="$(worktree_tree)"
  if [ -n "$TREE_AT_START" ] && [ "$now" = "$TREE_AT_START" ]; then
    echo "$now $(date -u +%Y-%m-%dT%H:%M:%SZ)" >> "$(git rev-parse --git-path esperanza-verified-trees)"
    echo "Recorded: this exact code passed; pushing it will not run the gate again."
  else
    echo "Not recorded: files changed while the gate ran, so the next push runs it again."
  fi
}
TREE_AT_START="$(worktree_tree)"

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
# export.php bakes APP_URL into ABSOLUTE asset URLs. Export with an explicit one
# so the assertion below can prove it is honoured: a dist/ pinned to the wrong
# origin serves pages whose every stylesheet and script 404s, and the site looks
# like unstyled HTML rather than like a broken build.
GATE_APP_URL="${APP_URL:-http://gate.invalid}"
if APP_URL="$GATE_APP_URL" "$PHP_BIN" export.php </dev/null >/tmp/esperanza-gate-export.log 2>&1; then
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

# A Blade component that fails to compile reaches the page as a literal
# <x-...> tag and renders nothing: a broken attribute quote did exactly that to
# nine Sakuna error states (2026-11-03), and every check above still passed.
RAWX=$(grep -rlE '<x-[a-z]' dist --include='*.html' 2>/dev/null | head -5)
[ -z "$RAWX" ] && pass "no uncompiled <x-...> component reached dist/" || { fail "uncompiled <x-...> component in: $(echo $RAWX | tr '\n' ' ')"; }

# Every Alpine expression in the rendered pages must parse. A stray double quote
# inside x-data="..." ends the attribute and prints the component as page text,
# and nothing above notices: the page still renders and exports.
if node scripts/check-alpine.mjs dist </dev/null >/tmp/esperanza-gate-alpine.log 2>&1; then
  pass "every Alpine expression parses ($(grep -oE '[0-9]+ Alpine expressions' /tmp/esperanza-gate-alpine.log | grep -oE '[0-9]+'))"
else
  fail "Alpine expressions that do not parse"; tail -20 /tmp/esperanza-gate-alpine.log
fi

# The Master Commands state RBAC COUNTS by hand -- the triple count alone
# appears ten times across the two documents, and it went stale within a day
# of the contract moving with nothing disagreeing with it.
#
# Checked by SET MEMBERSHIP, not against the global total. The documents
# legitimately quote per-module subtotals as well ("Ten submodules, 67
# permission triples" is Sakuna; "28 submodules" is Dokyu), so a check that
# compared every number to the grand total produced false failures on correct
# prose -- which is how a gate earns a reputation for crying wolf and stops
# being read. A documented number is accepted if it equals ANY real count of
# that kind; 485 is caught because it is no longer any of them.
RBAC_SETS=$("$PHP_BIN" -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $m=config("esperanza_rbac.permission_modules"); $tr=[]; $su=[]; $gt=0; $gs=0; foreach($m as $x){ $t=0; foreach($x["submodules"] as $y){ $t+=count($y["perms"]); } $tr[]=$t; $su[]=count($x["submodules"]); $gt+=$t; $gs+=count($x["submodules"]); } $tr[]=$gt; $su[]=$gs; echo implode(",",array_unique($tr))."|".implode(",",array_unique($su))."|".count(config("esperanza_rbac.permission_types"))."|".count($m);' 2>/dev/null)

if [ -z "$RBAC_SETS" ]; then
  fail "could not read the RBAC counts out of config/esperanza_rbac.php"
else
  VALID_TRIPLES=",$(echo "$RBAC_SETS" | cut -d"|" -f1),"
  VALID_SUBS=",$(echo "$RBAC_SETS" | cut -d"|" -f2),"
  ACT_TYPES=$(echo "$RBAC_SETS" | cut -d"|" -f3)
  ACT_MODULES=$(echo "$RBAC_SETS" | cut -d"|" -f4)
  RBAC_MISMATCH=0

  check_in_set() {
    # $1 = file, $2 = pattern, $3 = ",a,b,c," valid set, $4 = label
    for n in $(grep -oE "$2" "$1" 2>/dev/null | grep -oE "^[0-9]+"); do
      case "$3" in
        *",$n,"*) ;;
        *) echo "   $1 says $n $4, which matches no module or total in the config"
           RBAC_MISMATCH=1 ;;
      esac
    done
  }

  check_exact() {
    for n in $(grep -oE "$2" "$1" 2>/dev/null | grep -oE "^[0-9]+"); do
      if [ "$n" != "$3" ]; then
        echo "   $1 says $n $4; config has $3"
        RBAC_MISMATCH=1
      fi
    done
  }

  for DOC in ESPERANZA_BACKEND_MASTER_COMMAND.md ESPERANZA_FRONTEND_MASTER_COMMAND.md; do
    [ -f "$DOC" ] || continue
    check_in_set "$DOC" "[0-9]+ permission triples" "$VALID_TRIPLES" "permission triples"
    check_in_set "$DOC" "[0-9]+ triples"            "$VALID_TRIPLES" "triples"
    check_in_set "$DOC" "[0-9]+ submodules"         "$VALID_SUBS"    "submodules"
    check_exact  "$DOC" "[0-9]+ permission types"   "$ACT_TYPES"     "permission types"
    check_exact  "$DOC" "[0-9]+ distinct types"     "$ACT_TYPES"     "distinct types"
  done

  if [ "$RBAC_MISMATCH" -eq 0 ]; then
    pass "Master Commands match the RBAC config ($ACT_MODULES modules, $ACT_TYPES types)"
  else
    fail "a Master Command states an RBAC count the config disagrees with"
  fi
fi

# The docs state a TEST COUNT. A PHPUnit test cannot check that without
# re-running itself, so the check belongs here -- the gate is what produced the
# number. Three such claims sat stale by 10 before this existed: they were
# hand-edited each time the suite grew, and nothing ever disagreed with them.
# The phpunit summary is ANSI-coloured, so escape codes sit between 'Tests:'
# and the number. Match the contiguous '<n> passed' instead of anchoring on the
# label.
ACTUAL_TESTS=$(grep -aoE '[0-9]+ passed' /tmp/esperanza-gate-test.log 2>/dev/null | grep -oE '[0-9]+' | tail -1)

if [ -z "$ACTUAL_TESTS" ]; then
  fail "could not read the test count out of the phpunit run"
else
  COUNT_MISMATCH=0

  check_claim() {
    # $1 = file, $2 = grep -oE pattern whose match ends in the number
    stated=$(grep -oE "$2" "$1" 2>/dev/null | grep -oE "[0-9]+" | tail -1)
    if [ -n "$stated" ] && [ "$stated" != "$ACTUAL_TESTS" ]; then
      echo "   $1 claims $stated tests; the suite ran $ACTUAL_TESTS"
      COUNT_MISMATCH=1
    fi
  }

  check_claim CERTIFICATION.md "phpunit \(([0-9]+) tests\)"
  check_claim CERTIFICATION.md "2 . ([0-9]+) tests"
  check_claim FRONTEND_PENDING.md "2 tests -> ([0-9]+)"

  if [ "$COUNT_MISMATCH" -eq 0 ]; then
    pass "documented test count matches the run ($ACTUAL_TESTS)"
  else
    fail "a document states a test count the suite disagrees with"
  fi
fi

STRAY=$(grep -rl "http://localhost/" dist --include='*.html' 2>/dev/null | wc -l | tr -d ' ')
if [ "$STRAY" = "0" ]; then
  pass "asset URLs honour APP_URL (no http://localhost left in dist/)"
else
  fail "$STRAY page(s) still point at http://localhost - APP_URL was ignored, so every asset 404s"
fi

# -- Page-weight budget (certification gate 8) -------------------------------
# These are gzipped, because that is what crosses the wire: Netlify serves
# dist/ compressed, and the audience is an LGU and its residents on mobile data
# in Masbate. Measured 2026-09-03, then set just above the worst route so a
# regression bites while today's tree passes:
#
#   worst HTML gz          100 KB   (admin/constituents family)
#   app JS gz              136 KB
#   CSS gz                  23 KB
#   worst initial transfer 259 KB   (HTML + CSS + app JS)
#
# chart and html2pdf are deliberately NOT counted: they are separate chunks and
# must stay lazily loaded. If either ever lands in the initial bundle, the JS
# ceiling below is what catches it.
#
# PROPOSED by measurement, not yet ratified by the owner -- change the three
# numbers here if the LGU wants a different target.
MAX_HTML_GZ_KB=120
MAX_JS_GZ_KB=150
MAX_INITIAL_GZ_KB=300

gz_kb() { gzip -c "$1" | wc -c | awk '{printf "%d", ($1+1023)/1024}'; }

WORST_HTML=0; WORST_PAGE=""
while IFS= read -r page; do
  KB=$(gz_kb "$page")
  if [ "$KB" -gt "$WORST_HTML" ]; then WORST_HTML=$KB; WORST_PAGE=$page; fi
done < <(find dist -name index.html)

APP_JS=$(find dist/build/assets -name 'app-*.js' | head -1)
CSS=$(find dist/build/assets -name 'app-*.css' | head -1)
JS_KB=0;  [ -n "$APP_JS" ] && JS_KB=$(gz_kb "$APP_JS")
CSS_KB=0; [ -n "$CSS" ]    && CSS_KB=$(gz_kb "$CSS")
INITIAL=$((WORST_HTML + JS_KB + CSS_KB))

echo "   worst page ${WORST_HTML}KB gz + js ${JS_KB}KB + css ${CSS_KB}KB = ${INITIAL}KB initial transfer"

if [ "$WORST_HTML" -le "$MAX_HTML_GZ_KB" ]; then
  pass "heaviest page is ${WORST_HTML}KB gz (budget ${MAX_HTML_GZ_KB}KB)"
else
  fail "${WORST_PAGE#dist/} is ${WORST_HTML}KB gz, over the ${MAX_HTML_GZ_KB}KB budget"
fi

if [ "$JS_KB" -le "$MAX_JS_GZ_KB" ]; then
  pass "initial JS is ${JS_KB}KB gz (budget ${MAX_JS_GZ_KB}KB)"
else
  fail "initial JS is ${JS_KB}KB gz, over the ${MAX_JS_GZ_KB}KB budget -- has a lazy chunk been folded back in?"
fi

if [ "$INITIAL" -le "$MAX_INITIAL_GZ_KB" ]; then
  pass "worst-route initial transfer ${INITIAL}KB gz (budget ${MAX_INITIAL_GZ_KB}KB)"
else
  fail "worst-route initial transfer ${INITIAL}KB gz, over the ${MAX_INITIAL_GZ_KB}KB budget"
fi

PDFS=$(find dist/documents -name '*.pdf' 2>/dev/null | wc -l | tr -d ' ')
[ "$PDFS" -ge 16 ] && pass "$PDFS reference PDFs in dist/documents" || fail "only $PDFS reference PDFs reached dist/"

# -- Accessibility (2026-10-17) ---------------------------------------------------
# Every page of dist/ in headless Chrome: axe-core's WCAG 2.2 A/AA rules (a
# serious or critical violation fails) and the keyboard (skip link first,
# focus always visible and on screen, never trapped, Shift+Tab back, Escape
# closes a dialog). Offline: the site from dist/, the API from sample data.
# Needs Chrome or Edge (CHROME=/path to point at one) and `npm install`.
say "accessibility: axe WCAG 2.2 AA + keyboard"
if MSYS_NO_PATHCONV=1 PHP="$PHP_BIN" node scripts/a11y.mjs </dev/null >/tmp/esperanza-gate-a11y.log 2>&1; then
  pass "$(grep -o 'pages checked: [0-9]*' /tmp/esperanza-gate-a11y.log), none failing"
else
  fail "accessibility: $(grep -o 'failing: [0-9]*' /tmp/esperanza-gate-a11y.log || echo 'did not run') page(s) -- see /tmp/esperanza-gate-a11y.log"
  grep -E '^FAIL|axe (serious|critical)|keyboard:|FAILED TO RUN' /tmp/esperanza-gate-a11y.log | head -25
fi

echo ""
echo "    steps run: $STEPS_RUN"
if [ "$FAILED" -eq 0 ]; then
  echo "=== VERDICT: PASS ==="
  record_verified_tree
else
  echo "=== VERDICT: FAIL ==="
fi
exit $FAILED
