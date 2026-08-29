# Esperanza Web Platform — Frontend Findings Register

Sweep + test run: 2026-08-29, macOS, clone of `Upupapp/Esperanza-Web-Platform-frontend-`
HEAD verified: `dd7e0d400a7cd17402ac269a09c6c708f912ff9f` ("Enhance Esperanza Web Admin", 2026-08-28)
Toolchain: PHP 8.4.23 (Herd), Composer 2.10.1, Node 24.19.0, npm 11.17.0

State legend: **BLOCKED** / **OWNER DECISION** / **NOT REACHED** / **OPEN**

---

## Part 1 — Front end (F-###)

| ID | Sev | State | Finding |
| --- | --- | --- | --- |
| F-001 | P0 | **CLOSED** | ~~`export.php` route map omits 9 live routes — they 404 on the deployed Netlify site~~ — map now derived from `Route::getRoutes()`; 55/55 exported |
| F-002 | P0 | **CLOSED** | ~~`public/documents/` is not copied into `dist/`~~ — added to the asset map; 16 PDFs (22 MB) now exported |
| F-003 | P1 | OWNER DECISION | `netlify.toml` publishes a real `APP_KEY` value in a comment, in a public repo |
| F-004 | P1 | OPEN | `.env.example` ships database session/cache/queue drivers with no sqlite file — a fresh clone 500s on every page |
| F-005 | P2 | OPEN | Single 1.66 MB JS chunk; `/admin/sakuna` renders 1.59 MB of HTML in one response |
| F-006 | P2 | OPEN | No real test coverage — 2 untouched Laravel example tests against 57 routes / 98 views |
| F-007 | P3 | OPEN | `pint --test` fails on 9 files |
| F-008 | P3 | OPEN | `dist/` is not gitignored — a 53 MB build output can be committed by accident |
| F-009 | P3 | OPEN | 8 images over 1 MB (largest 2.2 MB) unoptimised; working tree is 130 MB |

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
