# Esperanza Web Platform — Frontend Findings Register

Sweep + test run: 2026-08-29, macOS, clone of `Upupapp/Esperanza-Web-Platform-frontend-`
HEAD verified: `dd7e0d400a7cd17402ac269a09c6c708f912ff9f` ("Enhance Esperanza Web Admin", 2026-08-28)
Toolchain: PHP 8.4.23 (Herd), Composer 2.10.1, Node 24.19.0, npm 11.17.0

> **Update 2026-08-29 (this session).** Worked against the Front-End Master
> Command. Every FIXED row below is covered by an assertion in `scripts/gate.sh`,
> which runs on every push via `scripts/hooks/pre-push`. See `CERTIFICATION.md`
> for the TAB 17 verdict and the list of what is still open.

State legend: **BLOCKED** / **OWNER DECISION** / **NOT REACHED** / **OPEN**

---

## Part 1 — Front end (F-###)

| ID | Sev | State | Finding |
| --- | --- | --- | --- |
| F-001 | P0 | **FIXED** | `export.php` route map is now DERIVED from `Route::getRoutes()` -- 55/55 routes export; drift gate added |
| F-002 | P0 | **FIXED** | `public/documents` added to the asset map -- all 16 reference PDFs reach `dist/`; asserted by the drift gate |
| F-003 | P1 | **PARTIAL / OWNER** | Redacted; local key ROTATED 2026-08-30; not reused elsewhere. Remaining: set the new APP_KEY in the Netlify UI (runbook in `netlify.toml`) |
| F-004 | P1 | **FIXED** | `.env.example` ships array/array/sync drivers; `artisan migrate` removed from composer setup |
| F-005 | P2 | **FIXED** | Initial JS 1,665 KB -> 506 KB (chart.js + html2pdf lazy); /admin/sakuna 1.59 MB -> ~244 KB per tab URL |
| F-006 | P2 | **FIXED** | Route-render gate + export-drift gate; 2 tests -> 120, enforced by `scripts/hooks/pre-push` |
| F-007 | P3 | **FIXED** | `pint --test` green and part of the gate |
| F-008 | P3 | **FIXED** | `/dist` is gitignored |
| F-009 | P3 | **PARTIAL** | 7 oversized images 13.6 MB -> 5.8 MB. `public/documents` is still 23 MB -- needs ghostscript/qpdf, not installed |

### F-001 — 9 routes are unreachable in the production build (P0)

`routes/web.php` defines 55 page routes. `export.php`'s `$routes` map covers 46.
The 9 with no static export:

```
/admin/communications/banners     /admin/sakuna/bdrrmc-forms
/admin/communications/blotter     /admin/settings/site-content
/admin/internal-forms             /admin/users/permissions
/admin/papeles                    /citizen/legal/privacy-policy
                                  /citizen/legal/terms
```

`netlify.toml`'s catch-all is `from "/*" → "/index.html" status = 404`, so a
visitor on any of these lands on the portal-chooser page under a 404 status.

Four of them are **linked from the shipped UI**, so this is user-visible, not
theoretical:

- `admin.papeles` — [components/admin/sidebar.blade.php:48](resources/views/components/admin/sidebar.blade.php#L48) and [admin/document-requests.blade.php:2521](resources/views/admin/document-requests.blade.php#L2521)
- `admin.internal-forms` — [components/admin/sidebar.blade.php:56](resources/views/components/admin/sidebar.blade.php#L56)
- `citizen.terms` and `citizen.privacy-policy` — [auth/citizen-register.blade.php:120](resources/views/auth/citizen-register.blade.php#L120) (the consent checkbox a citizen must agree to) and [citizen/settings.blade.php:141-144](resources/views/citizen/settings.blade.php#L141-L144)

Worst case: a citizen cannot read the Terms or Data Privacy policy they are
being asked to accept at registration.

**Fix:** add the 9 URIs to the map in [export.php:23-71](export.php#L23-L71). Better, derive the
map from `Route::getRoutes()` so it cannot drift again.

**Evidence:** `php export.php` → `Pages : 46 rendered, 0 failed`, against 56
routes that render 200 locally.

### F-002 — reference-form PDFs are never exported (P0)

[export.php:145-149](export.php#L145-L149) copies `public/build`, `public/images` and `public/fonts`.
It does not copy `public/documents/`, which holds 16 reference-form PDFs (22 MB)
referenced by `reference_pdf` keys in `config/esperanza_municipal_documents.php`,
`config/esperanza_barangay_documents.php` and `config/esperanza_tulong_types.php`.

Those values are rendered as a live `<iframe :src="selected.reference_pdf">` plus
a download link at [admin/document-requests.blade.php:3050-3052](resources/views/admin/document-requests.blade.php#L3050-L3052), and the same
pattern at [admin/assistance-requests.blade.php:403-408](resources/views/admin/assistance-requests.blade.php#L403-L408). Locally they serve 200;
in `dist/` the directory does not exist, so every preview is an empty frame.

**Fix:** add `'public/documents' => 'dist/documents'` to the `$assets` map.

### F-003 — a real APP_KEY is committed in netlify.toml (P1)

[netlify.toml:12-15](netlify.toml#L12-L15) tells the reader not to commit `APP_KEY`, then prints a
working key in the comment body:

```
#   Value: base64:<a real 32-byte key, printed in full at netlify.toml:14>
```

The value is deliberately not repeated here — read it at its source. The repo is public, and the key signs sessions and cookies and drives
`encrypt()`/`decrypt()`. Nothing sensitive is encrypted with it *today* — the
site is static and Netlify forces `SESSION_DRIVER=array` — but the key is burnt
and must not be reused once a backend exists.

**Fix:** delete the value from the comment, rotate the key in the Netlify UI.
**Owner decision:** whether to rewrite git history or accept the leak as
retired.

### F-004 — a fresh clone 500s on every page (P1)

`.env.example` sets `SESSION_DRIVER=database`, `CACHE_STORE=database`,
`QUEUE_CONNECTION=database` on `DB_CONNECTION=sqlite`, and no
`database/database.sqlite` exists in the repo. Following the documented setup
(`cp .env.example .env` → `artisan key:generate` → `artisan serve`) yields on
**every route**:

```
Database file at path [.../database/database.sqlite] does not exist.
(SQL: select * from "sessions" where "id" = ... limit 1)
```

`composer setup` papers over it with `artisan migrate --force`, but this is a
frontend-only project by its own constitution — it should not need a database at
all. Netlify already overrides to `array`/`sync`, so production is unaffected;
only local onboarding is broken.

**Fix:** ship `SESSION_DRIVER=array`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`
in `.env.example`, matching `netlify.toml`. Drop `artisan migrate` from
`composer setup`.

**Repro'd and worked around locally** by editing `.env` (gitignored) to the
array drivers — after which all 56 routes returned 200.

### F-005 — bundle and page weight (P2)

`npm run build` emits one chunk:

```
public/build/assets/app-BsuQeDcU.js   1,660.89 kB │ gzip: 473.07 kB
public/build/assets/app-8RGiGznm.css    159.22 kB │ gzip:  24.03 kB
```

Vite warns about the 500 kB threshold. Separately, `/admin/sakuna` and its 10 tab
routes each render **1.59 MB of HTML** — every tab's markup is emitted inline and
hidden with Alpine, so all 11 tabs ship on every one of the 11 URLs. Served page
weight vs the rest of the app:

```
   1,593,257 b  /admin/sakuna/evacuation-centers
     224,250 b  /admin/papeles
      94,238 b  /citizen/dashboard
      10,341 b  /
```

Not a defect — it renders correctly — but it is the single largest performance
item, and an LGU audience on mobile data is the stated user.

### F-006 — the test suite tests nothing (P2)

`tests/` is the untouched Laravel skeleton: `Unit/ExampleTest` asserts
`true === true`, `Feature/ExampleTest` asserts `/` returns 200.

```
Tests:    2 passed (2 assertions)   Duration: 1.02s
```

98 Blade views and 57 routes have no coverage. F-001 and F-004 are both exactly
the class of defect a route-render smoke test catches in one second. A test that
loops `Route::getRoutes()` and asserts 200 on each would have caught both.

### F-007 — style gate fails (P3)

`vendor/bin/pint --test` fails on 9 files: 8 `config/esperanza_*.php` plus
`export.php` (`single_quote`, `array_indentation`, `concat_space`,
`binary_operator_spaces`, `blank_line_before_statement`, others).
`vendor/bin/pint` fixes all of them; no gate currently runs it.

### F-008 / F-009 — repo hygiene (P3)

`dist/` (53 MB) is not in `.gitignore` even though `netlify.toml` publishes it.
`public/images` holds 8 files over 1 MB — `cristy-profile.png` 2.2 MB,
`theodoro-profile.png` 2.2 MB, `rectangle_lgu.png` 1.9 MB — served unoptimised
and unresized.

---

## Part 2 — Backend (B-#)

None. The project is frontend-only by constitution (`CLAUDE.md`), `app/` holds
three skeleton files, and `routes/web.php` is `Route::view` only. Nothing in this
sweep needs a backend change.

---

## What PASSED — measured, not assumed

| Check | Result |
| --- | --- |
| Route render, all 56 GET routes (kernel) | **56 pass, 0 fail** — no `Undefined variable`, no `ViewException` |
| Route render over HTTP (`artisan serve`) | 200 on every page probed; compiled CSS + JS both 200 |
| `route()` name integrity across 98 views | 32 distinct names used, **0 undefined** |
| `asset()` target existence | 41 refs, **0 missing files** |
| Status vocabulary vs `CLAUDE.md` | All 15 canonical statuses in use, **0 invented** |
| `npm ci` + `npm run build` | clean, 1821 modules, 28.7 s |
| `composer install` | clean, 81 packages |
| `php artisan test` | 2/2 pass (but see F-006) |
| Remote branch drift | `Jave-progress`, `Jave-revision`, `Jave-web-admin-enhancement` all **0 ahead** of `main` — fully merged |

---

## Deliberately NOT done

- **No code was changed.** The task was sweep + test + clone; every finding above
  is reported, not fixed. Ordering is the owner's call.
- **`.env` was edited locally** (gitignored) to `array`/`array`/`sync` drivers, to
  get past F-004 and run the sweep. That file is not tracked; the fix to
  `.env.example` is still OPEN.
- **`dist/` was generated** by running `export.php` to measure F-001/F-002. It is
  untracked and should not be committed (F-008).
- **No browser/JS-runtime testing.** Alpine interactions, Chart.js rendering and
  `html2pdf.js` export were not exercised — server-render only. Anything that
  fails only after hydration is out of this sweep's reach.


---

## Closed 2026-08-29 — F-001 and F-002

Both were the same defect: `export.php` kept a hand-maintained `$routes` list
that had drifted from `routes/web.php`, and a hand-maintained `$assets` list
that never included `public/documents`.

**Fixed at the class, not the instances.** The route map is now derived from
`Route::getRoutes()` — parameterised routes and `/up` skipped — so drift is
structurally impossible rather than merely corrected. `public/documents` was
added to the asset map.

**Measured after the fix**, on a clean `dist/`:

| Check | Before | After |
| --- | --- | --- |
| Pages rendered by `export.php` | 46 | **55** |
| Routes in the router but not in `dist/` | 9 | **0** |
| Reference-form PDFs in `dist/documents/` | 0 | **16** (22 MB) |
| Zero-byte pages | — | 0 |

The nine previously-unreachable routes now render, including
`/citizen/legal/terms` and `/citizen/legal/privacy-policy` — the pages the
registration consent checkbox links to — plus `/admin/papeles` and
`/admin/internal-forms`, both linked from the admin sidebar.

**Still open from this register:** F-003 (APP_KEY rotation, owner decision),
F-004, F-005, F-006, F-007, F-009. F-006 is the one that matters most next —
a route-render and export-drift test in CI is what stops this class of defect
returning, and neither exists yet.


---

## Closed 2026-08-29 — F-006

The suite was the untouched Laravel skeleton: two example tests against 55
routes and 98 views. It is now a real gate.

**`tests/Feature/RouteRenderTest.php`** walks the router and asserts every page
route returns 200, is non-empty, and contains none of `Undefined variable`,
`Undefined array key`, `ErrorException`, `ViewException`,
`BadMethodCallException` — because Laravel returns 200 with an error partial in
several of those cases, so status alone is not proof. It reports every broken
route in one run rather than one per failed build, and it asserts it found at
least 50 routes so it cannot pass vacuously.

**`tests/Feature/StaticExportTest.php`** guards the deploy artifact: the route
map must still be derived from `Route::getRoutes()` and contain no hardcoded
`URI => index.html` pairs (F-001), and every directory under `public/` must
appear in the exporter's `$assets` map, with an explicit check for
`public/documents` (F-002).

**`scripts/gate.sh`** — the repo had no automated verification at all. It runs
the same sequence Netlify does (npm, **build**, pint, test, `php export.php`)
and then verifies the artifact: exported page count must equal the router's
route count, no zero-byte pages, reference PDFs present, and no page still
pointing at `http://localhost` (i.e. `APP_URL` was honoured).

> **There is no CI on this project, by standing instruction, and no workflow
> file is to be authored.** The gate therefore runs where the deploy happens:
> `scripts/hooks/pre-push` execs `scripts/gate.sh` before every push, and a
> push to `main` *is* the deploy. Enable it once per clone with
> `git config core.hooksPath scripts/hooks`. Never bypass it with
> `--no-verify`; if the gate cannot finish, fix the gate.
>
> The earlier plan to land `.github/workflows/ci.yml` (blocked on a PAT lacking
> `workflow` scope) is **withdrawn**, not pending.

### The gates were mutation-tested, not assumed

| Mutation | Result |
| --- | --- |
| Reintroduce a hardcoded `'/citizen/login' => ...` pair | **caught** — `route map is derived` fails |
| Remove `public/documents` from the `$assets` map | **caught** — two tests fail |
| Add `{{ $undefined }}` to `citizen/events.blade.php` | **caught** — `1 of 55 route(s) failed to render: /citizen/events returned HTTP 500` |

Suite after restoring the tree: **6 passed, 13 assertions, 1.7s.**

### Known limitation

Code style is in CI as `continue-on-error` because `pint --test` fails on 9
pre-existing files (**F-007**, still open). Run `vendor/bin/pint`, then delete
that line and style becomes a real gate. It is marked non-blocking deliberately
rather than reformatting 9 client-owned config files as a side effect of adding
CI.

**The build step must run before the tests.** `public/build` is gitignored, so a
fresh checkout has no Vite manifest and every route 500s with "Vite manifest not
found" — a failure that reads as catastrophic and is not. `RouteRenderTest`
fails early with that instruction rather than 55 stack traces.


---

## 2026-09-03 — the gate is now enabled by setup, not by reading

The pre-push hook was documented in **four** places (README, `CLAUDE.md`,
`DEPLOY_PROTOCOL.md`, this register) and a clone with it unset still pushed a
tree carrying unresolved merge-conflict markers to `main` on 2026-09-01,
breaking three pages. **Documentation was not the missing piece**, so two
mechanisms replace it:

- **`composer setup` now runs `git config core.hooksPath scripts/hooks`.** A
  developer following the documented setup gets a live gate without reading
  anything.
- **`GateIsRunnableTest` now asserts the hook is *enabled in this clone*,** not
  merely present and executable. An unconfigured clone turns the suite red with
  the exact command to fix it.

Mutation-tested: unsetting `core.hooksPath` fails the assertion with
*"This clone will push without running the gate (core.hooksPath is unset)"*;
restoring it returns 3 passed / 7 assertions.

**For an existing clone** — including anyone who cloned before today — the one
line is still needed by hand:

```sh
git config core.hooksPath scripts/hooks
```


---

## 2026-09-03 — TAB 10: images labelled, and a gate to keep them so

Every `<img>` in `resources/views` now carries an `alt`, and
`tests/Feature/ImageAltTest.php` holds that.

**The measurement had to be fixed before the code could be.** A naive
`grep '<img[^>]*alt='` reported **49** unlabelled images. The real number was
**9**. The other 40 were artefacts:

| Cause | Count |
| --- | --- |
| Multi-line `<img>` tags the single-line grep could not see | 33 |
| `<img>` mentioned inside Blade or JS comments — including a comment of mine explaining a decorative image | 7 |

`admin/document-requests.blade.php` alone mentions `<img>` in seven JS comments
describing the editor. A gate that reported those would have been ignored within
a week, so `ImageAltTest` strips Blade comments, HTML comments, `<script>`
blocks and `//` line comments (leaving `://` alone so a URL is never mistaken
for one) before it scans.

**The nine, and what each was judged to be:**

| File | Decision |
| --- | --- |
| `admin/communications` — review-modal image | described; it is the thing being reviewed |
| `admin/communications` — banner card backdrop | `alt=""`; blended behind the title, which is already text |
| `admin/communications` — banner form preview | `alt=""`; same |
| `admin/communications` — moderation-modal image | described by author |
| `citizen/announcements` ×2, `citizen/dashboard` — Esperanza seal | described; **the seal is the only marker that a post is official** |
| `citizen/directory` — official's photograph | named from the roster |
| `citizen/document-requests` — barangay seal | names which barangay's certificate this is |

Mutation-tested: removing one added `alt` fails the gate naming the file and
line; an image whose only `alt` sits inside a comment still fails.

**Still open on TAB 10** — this closes the `alt` half only. Form-control naming
is untouched: **496 controls (317 `input`, 136 `select`, 43 `textarea`) against
9 `<label for>`**. Certification gate 7 stays **FAIL** until that and the
keyboard-only and screen-reader passes are done.

**`scripts/audit.mjs` could not be run here.** It measures accessible names on
the rendered DOM, which is the better instrument, but it binds port 80 and so
needs root: `sudo node scripts/audit.mjs`. The source scan above is a weaker
substitute chosen knowingly, not a claim of equivalence.


---

## 2026-09-03 — TAB 10: form controls named, 413 → 161

**494 controls needed an accessible name; 413 had none.** 252 now do.

The fix was applied by markup shape, not uniformly, because the right mechanism
differs:

| Shape | Count | Fix | Why this one |
| --- | --- | --- | --- |
| `<label>Text</label>` then a control, **outside** an Alpine loop | 151 | `for`/`id` pair | proper association, and the visible label becomes clickable |
| Same shape **inside** `<template x-for>` | 55 | `aria-label` | a static `id` inside a loop renders many times — duplicate ids are a worse defect than the one being fixed |
| Placeholder only, no adjacent label | 46 | `aria-label` from the placeholder | **a placeholder is not a name**: it vanishes on focus and assistive tech treats it differently |

Verified: every changed view parses, all 55 routes still render, and the three
ids that appear twice repo-wide (`password`, `barangay`, `dp-print-area`) sit on
**different pages**, so no page has a collision.

### Held by a ratchet, not a clean assertion

`tests/Feature/ControlNameTest.php` asserts the unnamed count never exceeds
**161**. A gate that fails on the day it lands is a gate someone disables, so
this one starts where reality is and may only be lowered. A second test asserts
the ceiling is not left slack — if the real count falls 25 below it, the suite
tells you to tighten the constant, so the ratchet cannot quietly stop ratcheting.

Mutation-tested: adding one unlabelled input fails with
*"Controls with no accessible name rose to 162 (ceiling 161) of 495"*.

### What remains, and why it is not mechanical

**66 of the 161 are in `admin/partials/sakuna/tab-bdrrmc-forms.blade.php`** —
controls laid out as printed-form grids with no adjacent label text to derive a
name from. Naming them means reading the BDRRMC forms and deciding what each
cell is; a script would invent names, which is worse than none. Certification
gate 7 stays **FAIL** until these, the keyboard-only pass and the screen-reader
pass are done.


### Same day, later: 161 → 95, and the BDRRMC grids were derivable after all

I had written that the 66 BDRRMC controls "need a human to say what each field
is" because a script would invent names. That was half right: **inventing** them
would have been wrong, but they did not need inventing. They sit in `<td>` cells
of two-row `<thead>` tables, so each cell's name is simply **its column header**
— and the `x-model` binding independently agrees with it (`r.cas_m` ↔ "Casualty
M", `r.pre03m` ↔ "Pre-Schooler 0-3 M").

The mapping was **printed and inspected before anything was written**, and a name
was only applied where the column header and the binding name concurred. Headers
were expanded honouring `colspan`/`rowspan`, so "Roof Damage (Nipa / GI Sheets)"
+ "Slight-GI" compose into one name rather than either half alone. All 66 are
inside `<template x-for>`, so `aria-label` was correct — a static `id` there
renders once per row and collides.

**The ratchet caught its own slack**, which is the point of the second test:
after the fix it failed with *"Unnamed controls are down to 95; lower
ControlNameTest::CEILING to match"*. Ceiling tightened 161 → 95.

**95 remain**, now genuinely miscellaneous rather than concentrated. Certification
gate 7 still **FAIL**: the keyboard-only and screen-reader passes are untouched,
and `scripts/audit.mjs` still needs root to run.


---

## 2026-09-03 — the rendered-DOM audit now runs without root

`scripts/audit.mjs` is the better a11y instrument — it measures the DOM after
Alpine renders — but it could not run in an ordinary gate because `dist/`
hard-coded port 80 and serving there needs elevation. Two small changes removed
that, and neither affects production:

- **`export.php` honours an explicit port in `APP_URL`.** It previously forced
  `SERVER_PORT` to 80/443 regardless. Netlify sets no port, so output there is
  byte-identical — verified by re-exporting with a portless `APP_URL` and
  diffing the emitted asset URLs.
- **The port has to be in `HTTP_HOST`, not just `SERVER_PORT`.** Laravel builds
  absolute URLs from the host header, so setting `SERVER_PORT` alone made the
  port vanish silently from every asset URL and the audit's own style guard
  correctly refused to measure. That guard earned its place.
- **`scripts/audit.mjs` takes `AUDIT_PORT`** to match.

```sh
APP_URL=http://127.0.0.1:8080 php export.php && AUDIT_PORT=8080 node scripts/audit.mjs
```

### First run, measured rather than inferred

```
viewport 768px -- routes audited: 55 (errors: 0)
routes with horizontal PAGE overflow: 0
images without alt: 0 of 797
form controls with no accessible name: 190 of 1730
```

**Images: 0 of 797 — independently confirms the `alt` work from the rendered
side.** The source scan sees 70 `<img>` tags; the browser sees 797 because
Alpine loops multiply them, so this is a genuinely different measurement
agreeing with the first.

**Controls: 190 of 1730 rendered instances still unnamed**, against 95 source
sites — consistent, since one unnamed control inside a loop renders many times.
The source ratchet (`ControlNameTest`, ceiling 95) remains the fast gate; this is
the truthful total.

**Horizontal overflow: 0 across all 55 routes** — re-confirms TAB 13.

Not wired into `scripts/gate.sh`: it drives 55 routes through Chrome and would
add minutes to every push. It is now runnable by anyone, on demand, which was
the blocker.


### Filter selects named from their prompt option, 95 → 46

The remaining controls were dominated by 70 `<select>` elements with no adjacent
label. Their name was already on screen: the **first `<option>`** is a prompt
("All Statuses", "Select a reason…"), and the `x-model` binding agrees with it
(`statusFilter`). 49 had both sources concurring and were named
"Status filter", "Citizen verification barangay filter", and so on — the section
prefix comes from the file (`tab-citizen-verification`) or the binding's leading
token, so two "Category" selects on one page do not read identically.

**21 selects were left alone** because the two sources did not corroborate —
`formData[f.key]` with an option of "—" says nothing about what the field is.
Those need a human, and inventing a name for them would be worse than none.

### A mistake worth recording

The first attempt **corrupted the markup**: offsets were computed on a
comment-stripped copy and applied to the original, so every insertion landed
progressively further from its target — `placeholder="Searc aria-label="Category
filter"h ref`. Caught by inspecting the diff rather than by the suite, which
would have gone green because the ratchet only counts. Reverted with
`git checkout` and redone with **length-preserving masking**, where comments
become spaces rather than being deleted, so offsets stay aligned.

### Both instruments agree

| | before | after |
| --- | --- | --- |
| Source sites (`ControlNameTest`) | 95 | **46** |
| Rendered instances (`scripts/audit.mjs`) | 190 | **99** |
| Images without alt (rendered) | 0 of 797 | 0 of 797 |
| Routes with horizontal overflow | 0 | 0 |

Ratchet tightened 95 → 46. Certification gate 7 remains **FAIL**: the
keyboard-only and screen-reader passes are still untouched.


---

## 2026-09-03 — a page-weight budget, proposed from measurement

Certification gate 8 was failing only because **no budget had ever been agreed**
— not because any page was too heavy. A budget nobody sets is a gate that can
never pass, so here is one derived from what the site actually weighs, now
enforced in `scripts/gate.sh`.

**Measured first** (55 routes, `APP_URL=https://esperanza.example`):

| | value |
| --- | --- |
| Mean HTML per route | 281 KB raw |
| Heaviest route | 916 KB raw — the `admin/constituents` family |
| Heaviest route, gzipped | **101 KB** |
| App JS | 524 KB raw / **137 KB gz** |
| CSS | 160 KB raw / **24 KB gz** |
| Chart chunk | 204 KB / 69 KB gz — lazy |
| html2pdf chunk | 956 KB / 272 KB gz — lazy |
| Worst-route initial transfer | **262 KB gz** |

**The budget is expressed gzipped**, because that is what crosses the wire:
Netlify serves `dist/` compressed, and the audience is an LGU and its residents
on mobile data in Masbate. Raw byte counts would flatter or frighten for no
reason — 916 KB of HTML is 101 KB on the wire.

| Ceiling | Value | Current worst |
| --- | --- | --- |
| HTML per route | 120 KB gz | 101 KB |
| Initial JS | 150 KB gz | 137 KB |
| Initial transfer (HTML + CSS + JS) | 300 KB gz | 262 KB |

Set just above today's worst, so a regression bites while the current tree
passes. **`chart` and `html2pdf` are deliberately excluded** — they are separate
chunks and must stay lazily loaded; if either is ever folded back into the
initial bundle, the JS ceiling is what catches it.

Mutation-tested: lowering the HTML ceiling to 50 KB fails with
*"admin/constituents/index.html is 101KB gz, over the 50KB budget"*.

> **These three numbers are a proposal, not a ruling.** They come from
> measurement and from who the users are, but the LGU may want a stricter target
> (a 2G-era budget would be nearer 150 KB total) or a looser one. Change the
> three constants at the top of the budget block in `scripts/gate.sh`.


### Spec-driven fields named from their own spec, 46 → 9

The last large group were fields rendered from a form spec — `formData[f.key]`
inside `x-for="f in fields"`. **Their name was already in scope**: every field in
`esperanza_service_form_specs.php` and `esperanza_internal_forms.php` carries a
`label` alongside its `key`, and table columns carry one too. So the fix is a
binding, not a string:

```blade
:aria-label="f.label"        {{-- and :aria-label="col.label" for table cells --}}
```

which stays correct when the spec changes. The rest were named from their
`x-model` (`assignedFilter` → "Assigned filter", `cvSearch` → "Citizen
verification search"), and a bracketed model names itself by its collection —
`commentDraft[post.id]` → "Comment draft", never "Id".

**Names that would say nothing were rejected rather than written.** `{{ $amt }}`
→ "Amt", `selected.requirements[i]` → "Requirements i", `commentDraft[post.id]`
→ "Id" were all refused by a rule that drops any candidate under four characters
or ending in a one- or two-letter word. A wrong name is worse than none: it
sounds authoritative and misleads.

### Where the two instruments stand now

| | start of day | now |
| --- | --- | --- |
| Source sites (`ControlNameTest`) | 413 | **9** |
| Rendered instances (`audit.mjs`) | — | **19 of 1730** |
| Images without alt (rendered) | 49 claimed / 9 real | **0 of 797** |

**The 9 that remain** are genuinely undecidable from the markup: 6 selects whose
options are data ("Insert a standard reason…", a timezone list), a damage-total
input bound to a Blade variable, and two fields inside
`components/citizen/doc-field.blade.php` whose caller supplies the label. Naming
them needs a decision, not a script.

Certification gate 7 stays **FAIL** — but now only on the keyboard-only and
screen-reader passes. The automated half is clean.


---

## 2026-09-03 — keyboard reachability, measured in the browser

`scripts/audit.mjs` now measures three keyboard properties on the rendered DOM,
which no source scan can see. First run against 55 routes found real defects:

| | first run | after |
| --- | --- | --- |
| Visible click targets not in the tab order | **40** | **0** |
| Focusable but invisible even when focused | **11 real** (33 reported) | **0** |
| Positive `tabindex` (breaks natural order) | 0 | 0 |

### The 40 came from two components, not 40 places

`x-ui.stat-card` and `x-ui.card` both render a plain `<div>` and merge
`$attributes`, so a caller passing `@click` produced a card a mouse can activate
and a keyboard cannot — 28 and 8 call sites respectively. **Both were fixed in
the component**: when it receives a click handler and has no `href`, it takes
`tabindex="0"`, `role="button"`, and mirrors the handler onto Enter and Space.
One edit each, every caller covered.

### What was deliberately NOT made focusable

The first pass would have put `role="button"` on **modal backdrops** — elements
with `@click.self` that dismiss an overlay, which already carry
`@keydown.escape.window`. Announcing a full-screen overlay as a button is a
defect, not a fix. The rule now skips `.self`, `.outside`, `.away`, inline
`position: fixed; inset: 0` (which a Tailwind-class check misses), anything
already handling Escape, and `contenteditable` regions — the document editor is
a text surface, not a button. Twenty elements were skipped on purpose; one span
("Select all / Clear all" in the permission tree) was a genuine control and was
fixed.

### Three measurement artefacts, each fixed in the instrument

The reported "focusable but invisible" count was **33**; only **11** were real.

1. **Read at rest.** A control revealed by `focus:opacity-100` is hidden until
   focused. The probe now **focuses each candidate** before judging it.
2. **Read mid-transition.** These controls use `transition-opacity`, so
   `getComputedStyle` immediately after `focus()` still returns ~0. The probe
   now sets `transition: none` before measuring.
3. **Read mid-animation.** `animate-fade-up` starts at opacity 0, so cards
   caught during their entrance looked permanently hidden. Elements with a
   running `animationName` are now skipped.

The 11 real ones were hover-reveal controls with `group-hover:opacity-100` and
no focus equivalent; they now carry `focus:opacity-100 focus-within:opacity-100`.

> **A Tailwind class added to a Blade file does not exist until `npm run build`
> runs.** The fix appeared to do nothing for a full measurement cycle because
> the utility had not been generated yet.

Certification gate 7 still **FAIL** — the screen-reader pass is untouched, and
19 rendered controls remain unnamed — but the keyboard half is now measured
rather than assumed, and it is clean.


### Screen-reader structure, measured (2026-09-03)

A human with VoiceOver is still required for gate 7's listening pass — but the
*inputs* a screen reader builds its picture from are measurable, and
`scripts/audit.mjs` now checks them across all 55 routes.

| | before | after |
| --- | --- | --- |
| No `lang` on `<html>` | 0 | 0 |
| No `<title>` | 0 | 0 |
| **No `main` landmark** | **5** | **0** |
| **Not exactly one `<h1>`** | **7** | **0** |
| No live region for announcements | 0 | 0 |
| No `nav` landmark | 5 | 5 — **by design** |
| Skipped heading levels | 14 | 11 — **not yet resolved** |

**The 5 missing `main` landmarks were all the auth pages**, which share
`components/layouts/guest.blade.php`. Without one, a screen-reader user has no
"skip to content" target. One change to the layout fixed all five.

**The 7 double-`<h1>` pages** were `admin/constituents` (×5 routes, one file) and
`citizen/legal` (×2). Every other page has exactly one `<h1>` — the topbar page
title — so the body headings were the anomaly and were demoted to `<h2>`.

**The 5 remaining "no nav landmark" are the auth pages and are correct.** A
sign-in screen has no navigation; fabricating an empty `<nav>` to satisfy a
counter would be worse than the finding.

**The 11 skipped heading levels are unresolved and possibly an artefact.** The
pattern is `h1` (topbar) followed by `h3` with no `h2`, and the `h3`s come from
`x-ui.modal`, `x-ui.empty-state`, `x-ui.error-state` and `admin.access-
restricted`. **A dialog's title legitimately restarts the outline** — WCAG's
heading-order expectation applies to the document flow, not to a dialog's
contents — so the honest next step is to teach the probe to exclude headings
inside `[role=dialog]`, not to renumber the markup on a hypothesis. Recorded
rather than guessed at.

Gate 7 remains **FAIL**: 19 rendered controls unnamed, the heading-order question
open, and the listening pass not done.


---

## 2026-09-03 — F-022: the sign-in hero showed the wrong province

**Raised by the mobile lane**, which found `rectangle_cityhall.jpg` shows a
municipal hall whose facade seal reads **"MUNICIPALITY OF ESPERANZA — Agusan del
Sur"**. This platform is Esperanza, **Masbate** (Region V — Bicol). A different
municipality, in Caraga, ~700 km away.

They asked whether it was used anywhere prominent on the web side. **It was
worse here than there.** On mobile it was Balita media and a test now blocks it
from onboarding. Here it was:

| Where | Verdict |
| --- | --- |
| `auth/admin-login.blade.php` — **full-bleed hero of the LGU Personnel sign-in page** | **the wrong province was the first thing municipal staff saw** |
| `config/esperanza_site_content.php` banner | acceptable — Balita media |
| `admin/communications.blade.php` banner picker | acceptable — Balita media |
| `resources/data/admin/communications.php` media library | acceptable — Balita media |

Verified by opening the file, not by taking the report on trust: the seal is
legible and reads Agusan del Sur. The web and mobile copies are byte-identical.

**Fixed:** the hero is now `rectangle_sunset.jpg` — coastal scenery with no
seal, signage or building, so it makes **no place claim at all**. That matters:
swapping one unverified photograph for another risks repeating the defect, and
none of the remaining photos' provenance has been confirmed.

**Not deleted.** `tests/Feature/PlaceIdentityTest.php` asserts two things: the
image is never a layout hero, and it is **still present** — the Balita fixtures
name it, and "fixing" this by deleting the asset would break them. Mutation-
tested: restoring it as the hero fails the gate with the province named.

**Still unverified:** the provenance of every other photograph in
`public/images/esperanza/`. `rectangle_masbate.jpg` is the Province of Masbate
seal (correct), `rectangle_lgu.png` is the vendor's own logo, and the landscape
photos carry no identifying marks either way. If the client can confirm which
were taken in Esperanza, Masbate, a hero with a genuine sense of place would be
better than neutral scenery.


---

## 2026-09-03 — F-023: a missing fee would have rendered as "Free"

**Raised by the mobile lane**, who reported that their `ReceiptType` and
`ServiceCategory` decoders no longer fall back to `onsite`/`dokyu` — both now
decode to an explicit unknown rendered as `—`, "because the old defaults stated
something false about money, and about which service a citizen had filed."

Swept this side for the same class. One match, in the Service Catalog screen:

```php
'fee'  => $d['fee']  ?? 'Free',   // <- asserts a price
'days' => $d['days'] ?? '—',      // <- two characters away, correct
```

An absent amount would have stated, **on a government fee schedule**, that a
chargeable service costs nothing.

**Latent, not live.** All 40 catalogue entries — 15 municipal, 13 Tulong, 12
barangay — carry a fee today, so nothing has ever displayed a false price. But
this screen is meant to become administrable (backend TAB 04), and the first
service added without a fee would have said it silently. Fixed to `—`.

Two tests, both mutation-tested:

- `a missing fee never defaults to a price` — fails if any `'fee' => … ?? '…'`
  falls back to anything but `—`. Comments are stripped first, or the gate reads
  its own explanation as the defect.
- `every catalogue entry actually carries a fee` — catches the absence at the
  data level too, so the `—` is a safety net rather than the plan. Removing one
  fee fails with `tulong: Medical Assistance (AICS)`.

### Related defaults, recorded not fixed

The same sweep found defaults that assert **authorship** rather than money:
`?? 'Admin'` (`admin/communications`, `admin/settings` ×2), `|| 'Barangay
Secretary'` (`admin/communications`), `|| 'Staff Member'` and `|| 'an
administrator'` (`admin/internal-forms`), `?? 'Barangay DRRM Officer'`
(`sakuna/tab-damage`). Each names a specific actor when the real one is unknown.
That matters for anything that becomes an audit trail (backend TAB 17), but
unlike the fee they are display-only today and none is wired to a record of who
did what. **Not fixed, deliberately — the right fix is knowing the actor, which
needs the API, not a better default.**


---

## 2026-09-04 — F-024: the pre-push gate was installed by a command nobody types twice

**Raised by the backend lane over the bus** (#0003, their B-039), and **verified
here before acting** — the claim was exact.

`git config core.hooksPath scripts/hooks` sat in composer's **`setup`** block
only. `composer install` — what every clone and every CI-less checkout actually
runs — executes `post-autoload-dump`, not `setup`, so **an ordinary clone got no
gate at all**.

**Why it was invisible:** `core.hooksPath` lives in `.git/config`, which is not
cloned. A missing hook is silent by construction — `git push` succeeds, prints
nothing unusual, and an absent gate is indistinguishable from a gate that
passed. This machine's checkout had it set, so the exposure was never here; it
was the second developer machine, the Windows lane, and every future clone.

**Fixed** by porting the backend lane's design:

- `scripts/install-git-hooks.php`, run from `post-autoload-dump`, so the hook
  installs on the way to a working checkout.
- **PHP, not shell**, so the Windows lane runs it.
- **Never exits non-zero** — failing `composer install` over a git hook is worse
  than the missing hook.
- **Refuses to clobber** an existing `core.hooksPath`, reporting it instead:
  that is someone's decision, not ours to overwrite.
- Removed from `setup`, which calls `composer install` anyway.

**Two tests, three mutations, each watched failing:**

| Mutation | Caught by |
| --- | --- |
| `core.hooksPath` unset (a fresh clone) | *"core.hooksPath is unset, so no hook runs at all"* |
| set to a directory with no `pre-push` in it | *"points at 'scripts', which contains no pre-push. git will run nothing and say nothing"* |
| installer removed from `post-autoload-dump` | *"…so `composer install` — what every clone actually runs — leaves the pre-push gate uninstalled"* |

The second matters most: equality with `scripts/hooks` cannot see a right path
with a missing file. **An installer nobody checks is the same category of hope as
an instruction nobody follows** — their phrasing, and it is the reason for the
test rather than just the installer.

`README.md` and `CLAUDE.md` both described it as "one time, per clone"; both now
say `composer install` does it.

### Their two cleared classes, and their inference

The backend lane also measured two related classes in this repo and cleared
both — recorded so nobody re-checks: `tests/Unit` has a tracked file (their
B-038, a declared-but-uncommitted testsuite directory that made PHPUnit exit
with zero tests, does not apply), and `scripts/hooks/pre-push` is mode `100755`
in the index (their B-030, git silently skipping a non-executable hook, is
clear).

**Their inference — which other `setup` lines every clone needs — was checked
and the answer is none.** What remains is `.env` creation, `key:generate`,
`npm install` and `npm run build`. `key:generate` writes a secret and must not
run unattended on every install; the npm steps are not composer's business. The
`.env` copy alone is idempotent but useless without the key. So `setup` keeps
them, and the gate is the only thing that moved.

---

## 2026-09-04 — F-025: CERTIFICATION.md argued that a passing gate failed

**Found by applying the backend lane's question** (#0018, their B-042) to this
repo: not *"is this asserted?"* but **"could this assertion see the failure if it
happened?"** Their register had carried two findings numbered B-040 with the
whole gate green, because the parser built a map keyed by id and the duplicate
overwrote rather than collided. They then checked this repo and cleared it —
correctly, our `tally()` counts rather than keys, so their exact defect cannot
occur here. Applying the *question* rather than the instance found two live
defects the three existing tests could not see.

**1. The gate 8 paragraph was stale prose from when gate 8 failed.**
`CERTIFICATION.md` line 116 read *"the gate asks for a budget, enforced,
measured throttled — none of which exists yet"* and *"No budget number has been
agreed"*. All of that is false and was contradicted three ways in the same
document: the table row says **PASS**, the block sits under the heading *"The two
passing gates that are new, and how they were verified"*, and open question 5
says the budget is enforced *"so gate 8 passes"*. MEASURED at
`scripts/gate.sh:213-215` — `MAX_HTML_GZ_KB=120`, `MAX_JS_GZ_KB=150`,
`MAX_INITIAL_GZ_KB=300` are set and enforced.

This is B-023's shape exactly, which this test's own docblock already cites: a
partial edit flipped the row, the tally and the open question, and left the
paragraph standing. Rewritten to state what is enforced, and — per the mobile
lane's caveat in #0007, that one overstated claim must not be swapped for
another — to keep naming the two things genuinely outstanding: **owner
ratification of the three numbers**, and the fact that **nothing measures on a
throttled connection**. Neither is a gate failure.

**2. A cross-reference pointed at the wrong item.** The same paragraph sent the
reader to *"gate 4 of the open questions"*. Open question **4** is about
`localStorage` request drafts; the minimum-device question it meant is open
question **5**. It also called an open question a "gate", the one word in this
document that already means something else.

**Why nothing caught either:** all three existing tests read the same
projection — the table's row results and the tally sentence. Prose that
contradicts the row it explains is invisible to every one of them, and a
cross-reference is invisible to all software here.

**The guard that was addable.** Prose consistency is not mechanically checkable;
**row identity is**, and it turned out to matter more than the backend lane
assumed. Their note said it was *"only worth a line if your table numbers are
cited from elsewhere"* — MEASURED, they are, from at least twelve places:
`FRONTEND_PENDING.md` cites "gate 7" nine times, `scripts/gate.sh:197` names
"certification gate 8", plus `CERTIFICATION.md` and
`ESPERANZA_FRONTEND_MASTER_COMMAND.md:274`. Every one resolves by number, and
nothing follows them. So a duplicated or skipped row silently repoints all of
them while the row count and the tally stay correct.

`test_the_gates_are_numbered_one_to_ten_with_no_repeats` asserts the ids are
exactly `1..10`, in order, no repeats. **Mutation-tested before being believed:**
renumbering the gate 8 row to `7` made the new test fail and **left the other
three passing** — which is the finding restated as evidence, not a claim about
it. Reverted; full suite 46 passed.

**Not done, and why:** `./vendor/bin/pint --test` could not run in this
non-interactive session (approval required). The change follows the file's
existing style but the formatter has not confirmed it. It runs in
`scripts/gate.sh:63` before any push, so it cannot reach `main` unformatted.
