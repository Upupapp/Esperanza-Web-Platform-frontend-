# Esperanza Web Platform — Front-End Master Command

**Web portal front end only · Citizen Portal + Administrative Portal**

Issued 2026-08-29. Measured against `605de1d` on `main`
(`Upupapp/Esperanza-Web-Platform-frontend-`).

---

## Scope, stated once

This book covers **only the web portal front end** — the Laravel 12 + Blade +
Tailwind + Alpine application in this repository. It does not cover backend
work (that is `ESPERANZA_BACKEND_MASTER_COMMAND.md`), and there is no mobile
app in this project.

The repo's own constitution (`CLAUDE.md`) applies to every TAB below and is not
overridden by anything here: **no controllers beyond `Route::view`, no models,
no migrations, no APIs, no business logic, no auth logic, no middleware.**
Everything in this book is view, component, asset, state, or build work.

## How to read this book

**TAB 00 is the comprehensive command** — the single brief to hand a front-end
developer that covers the whole programme. TABs 01–17 are the same programme
broken into finishable units, ordered so that the two production defects and
the test harness come first and the polish comes last.

Every number below was counted from the repo on 2026-08-29. Where something is
a judgement call rather than a measurement, it says so.

---

## The one architectural fact that shapes everything else

**In production there is no PHP.** `netlify.toml` runs `export.php` at build
time to render every route to static HTML and publishes the `dist/` directory.
The deployed site is flat files on a CDN — Laravel never runs for a visitor.

Three consequences the front-end developer must design around from day one:

1. **Every API call is a browser call.** There is no server-side data fetching
   available, so Blade cannot render real data. `@php`-computed arrays are
   build-time constants baked into the HTML. Real data arrives only via
   `fetch`/`axios` from the client, after paint.
2. **CORS and token storage are front-end problems**, not backend
   afterthoughts. The static origin and the API origin will differ.
3. **Every page currently renders its data at build time.** Swapping to live
   data is not "replace `config()` with an endpoint" — it is a change in *when*
   the data exists, which is why TABs 04–07 are the largest block of work in
   this book.

This is not a defect. It is the deployment the project chose. It just needs to
be the first thing the developer knows.

---

## The measured state of the front end

| Dimension | Measured | Note |
| --- | --- | --- |
| Blade views | 98 (36,338 lines) | |
| Page routes | 55 | all 55 render 200 |
| Routes missing from `export.php` | **9** | 404 in production — TAB 01 |
| Blade components | 45 | `ui` 17, `citizen` 18, `admin` 7, `layouts` 3 |
| `x-ui.*` component uses | 800 | healthy reuse |
| Raw `<button>` elements | **695** | vs the button component — TAB 12 |
| Hand-rolled modals (`fixed inset-0 z-50`) | **26** | vs 39 `x-ui.modal` uses — TAB 12 |
| Raw `<table>` elements | **36** | vs 19 `x-ui.table` uses — TAB 12 |
| Inline PHP mock arrays inside views | **167 across 43 views** | the API migration burden — TAB 04 |
| Views reading `config()` | 29 | the easy half of the migration |
| `x-data` blocks | 134 | |
| `x-show` / `x-if` | 1,247 | |
| `fetch(` / `axios.` calls | **1** | and it is the `window.axios` assignment — TAB 05 |
| `<form>` elements | **3** | against 297 `<input>` and 366 `x-model` — TAB 07 |
| Views with bilingual handling | **38 of 98** | TAB 09 |
| `<img>` with `alt` | **25 of 69** | TAB 10 |
| `<label for=>` | **6** | against 297 `<input>` — TAB 10 |
| `@click` on non-interactive elements | **56** | keyboard-inaccessible — TAB 10 |
| `focus:` / `focus-visible:` utilities | 1,429 | this part is already good |
| JS bundle | **1.66 MB** (473 KB gzip), one chunk | TAB 11 |
| `/admin/sakuna` HTML per page | **1.59 MB × 11 tab URLs** | all 11 tabs `@include`d unconditionally — TAB 11 |
| `md:` breakpoint uses | **31** | against `sm:` 329, `lg:` 153 — TAB 13 |
| Skeleton / `animate-pulse` uses | 22 | TAB 06 |
| `x-ui.empty-state` uses | **6** | for 98 views — TAB 06 |
| `localStorage` keys in use | 7 | two of them simulate sessions — TAB 08 |
| Automated tests | **2** (Laravel skeleton examples) | TAB 02 |

---

## TAB 00 — THE COMPREHENSIVE FRONT-END COMMAND

> Hand this section, whole, to the front-end developer. TABs 01–17 expand it;
> they do not contradict it.

### The command

You own the **web portal front end** of the Esperanza Web Platform — the
Citizen Portal and the Administrative Portal in
`Upupapp/Esperanza-Web-Platform-frontend-` (Laravel 12, Blade, Tailwind 4,
Alpine, Chart.js, Lucide, Vite). The UI is built and client-approved: 98 views,
55 routes, 44 components, running entirely on mock data in
`config/esperanza_*.php` and in `@php` arrays inside the views themselves.

Your job, in order of priority:

**First, stop the bleeding.** Nine live routes are missing from `export.php`'s
hand-maintained route map, so they 404 on the deployed site — including the
Terms and Data Privacy pages that the citizen registration form requires users
to accept before they can register. `public/documents/` is never copied into
`dist/`, so all 16 reference-form PDF previews are broken in production. Fix
both by **deriving the export map from `Route::getRoutes()`** rather than
patching the list, then add a CI gate that fails when the router and the export
map disagree. Both defects are invisible locally — every route returns 200 on
`artisan serve`. That is exactly why they survived.

**Second, build the harness that would have caught them.** The test suite is
the untouched Laravel skeleton: two example tests against 55 routes and 98
views. Add a feature test that walks the router and asserts 200 on every route,
plus the export-map drift assertion, and run both in CI on every push.

**Third, make the UI ready for real data.** This is the largest block of work
and it has four parts, and it must be done knowing that **in production there
is no PHP** — the site is a static export, so all real data arrives via
browser-side calls after paint:

- **Lift the mock data out of the views.** 167 inline PHP arrays live in 43
  Blade files; `admin/document-requests.blade.php` alone holds 18 across 3,313
  lines. Move each to a named data source with the same shape, so a view reads
  from one seam instead of declaring its own fixtures. Keep the field names
  exactly as they are — the backend contract is being written against them.
- **Build the HTTP layer that does not exist.** There is one `axios` reference
  in the entire codebase and it is the `window.axios` assignment in
  `bootstrap.js`. You need a single client with base URL, auth header, a
  standard error envelope, and one place that handles 401/403/422/500 — not
  per-page `fetch` calls.
- **Give every list, detail and action a loading, empty and error state.**
  There are 22 skeleton usages and 6 empty states across 98 views, and no error
  states at all, because nothing can currently fail. Everything can fail once
  the API is real.
- **Build real submission.** There are 3 `<form>` elements against 297 inputs
  and 366 `x-model` bindings — the whole app is Alpine state with no submit
  path. Every citizen request, every admin decision, every settings change
  needs submission, validation display, in-flight disabling, success feedback
  and failure recovery.

**Fourth, close the gaps the constitution already asks for.** Bilingual is a
stated requirement and the app defaults to Filipino, yet only 38 of 98 views
handle language at all — 16 citizen views and components have none, and the
entire Administrative Portal has no language toggle. Accessibility is not
optional for a government service: 44 of 69 images have no `alt`, there are 6
`<label for>` against 297 inputs, and 56 click handlers sit on non-interactive
elements that a keyboard cannot reach. Component discipline has drifted from
what `CLAUDE.md` requires — 695 raw `<button>` elements, 26 hand-rolled modals
and 36 raw tables alongside the components built to replace them.

**Fifth, performance.** One 1.66 MB JS chunk, and `/admin/sakuna` ships 1.59 MB
of HTML on each of its 11 tab URLs because all 11 tab partials are `@include`d
unconditionally. The audience is an LGU on mobile data.

**Constraints, non-negotiable:**

- `CLAUDE.md` still governs: no controllers beyond `Route::view`, no models, no
  migrations, no business logic, no auth logic, no middleware in this repo.
- **Do not rename fields, enums or statuses.** The backend contract is being
  written from these exact names. A rename here is a change request there.
- **The 15 statuses in `CLAUDE.md` are the whole vocabulary.** Do not add a
  sixteenth, and leave `Processing`, `Assigned` and `Completed` unused in the
  Dokyu/Tulong flows, as `PROCESS_TAB_FLOW.txt` specifies.
- **Never redesign an existing pattern** unless explicitly instructed. This
  work is plumbing and hardening, not a visual refresh.
- Every push to `main` follows `DEPLOY_PROTOCOL.md`. A push deploys.

**Definition of done:** no route 404s in production, a CI gate proves it,
every screen renders real API data with loading/empty/error states, every
citizen-facing string exists in Filipino and English, the accessibility gate
passes, and the bundle is code-split.

---

## The TAB index

| TAB | Title | Priority | Size |
| --- | --- | --- | --- |
| 00 | The comprehensive front-end command | — | whole programme |
| 01 | The two production defects | **P0** | S |
| 02 | The test harness and CI gate | **P0** | S |
| 03 | Local setup and the `.env` trap | P1 | XS |
| 04 | Lift 167 inline arrays out of 43 views | P1 | XL |
| 05 | The HTTP layer that does not exist | P1 | L |
| 06 | Loading, empty, and error states | P1 | L |
| 07 | Real forms and submission | P1 | XL |
| 08 | Session and permission gating on the client | P1 | M |
| 09 | Bilingual completion — 60 views have none | P2 | L |
| 10 | Accessibility | P2 | L |
| 11 | Performance — bundle and page weight | P2 | M |
| 12 | Component discipline | P2 | M |
| 13 | Responsive and the missing `md:` tier | P3 | M |
| 14 | Print and document rendering | P3 | M |
| 15 | The deploy pipeline | P2 | S |
| 16 | Housekeeping — lint, images, weight | P3 | S |
| 17 | Certification gate | — | M |

---

## TAB 01 — The two production defects · P0

**CLOSED 2026-08-29.** Both fixed by deriving the export map from
`Route::getRoutes()` and adding `public/documents` to the asset map: 46 pages
became 55, router-vs-export drift went from 9 to 0, and 16 reference PDFs now
reach `dist/`. The record below is kept for the reasoning.

**These were live. Everything else in this book can wait; these could not.**

### F-001 — nine routes 404 in production

`routes/web.php` defines 55 page routes. `export.php`'s `$routes` map covers
46. `netlify.toml`'s catch-all is `from "/*" → "/index.html" status = 404`, so a
visitor to any of the nine lands on the portal chooser under a 404.

```
/admin/communications/banners     /admin/sakuna/bdrrmc-forms
/admin/communications/blotter     /admin/settings/site-content
/admin/internal-forms             /admin/users/permissions
/admin/papeles                    /citizen/legal/privacy-policy
                                  /citizen/legal/terms
```

Four are linked from the shipped UI:

- `admin.papeles` — `components/admin/sidebar.blade.php:48`, and
  `admin/document-requests.blade.php:2521`
- `admin.internal-forms` — `components/admin/sidebar.blade.php:56`
- `citizen.terms` and `citizen.privacy-policy` —
  `auth/citizen-register.blade.php:120` (the consent checkbox) and
  `citizen/settings.blade.php:141-144`

**A citizen cannot read the Terms or Data Privacy policy they are being asked
to accept.** That is the one to fix first.

### F-002 — every reference-form PDF is broken in production

`export.php` copies `public/build`, `public/images` and `public/fonts`. It does
not copy `public/documents/`, which holds 16 reference-form PDFs (22 MB)
referenced by `reference_pdf` keys in three config files and rendered as a live
`<iframe :src>` plus a download link at
`admin/document-requests.blade.php:3050-3052` and the same pattern in
`admin/assistance-requests.blade.php`.

### The fix — do it once, not nine times

Both defects are the same defect: a hand-maintained list that drifts. Replace
the literal `$routes` map with an enumeration of `Route::getRoutes()`, skipping
parameterised routes and `/up`, and add `public/documents` to the `$assets` map.
Then TAB 02 makes the drift impossible to reintroduce.

**Acceptance.** `php export.php` renders 55 pages, `dist/documents/` exists with
16 PDFs, and the drift gate from TAB 02 passes.

---

## TAB 02 — The test harness and CI gate · P0

**CLOSED 2026-08-29.** `RouteRenderTest`, `StaticExportTest` and
`ExportManifestTest` added, and each was proven to FAIL before being trusted.
They run from `scripts/gate.sh` via `scripts/hooks/pre-push` -- **not** from a
workflow file: there is no CI on this project by standing instruction, so the
gate runs on the machine that deploys. Gate 3, the bilingual coverage assertion,
is still open and belongs with TAB 09. `pint --test` is now green and blocking.

**Scope.** The suite is `tests/Unit/ExampleTest` asserting `true === true` and
`tests/Feature/ExampleTest` asserting `/` returns 200. Two tests, 55 routes, 98
views. TAB 01's defects survived to production because nothing looked.

**Build three gates, in this order of value:**

1. **Route render gate.** Walk `Route::getRoutes()`, request every GET route
   that has no parameters, assert 200, and assert the body contains no
   `Undefined variable`, `Undefined array key`, `ViewException`, or
   `Route [...] not defined`. This catches the entire class of Blade defect
   that a static export turns into a broken deploy.
2. **Export-map drift gate.** Assert every renderable route appears in
   `export.php`'s map, and that every asset directory the views reference is in
   the `$assets` map. This is the gate that makes TAB 01 permanent.
3. **Bilingual coverage gate** (after TAB 09). Assert no citizen-facing view
   ships a user-visible string without both `fil` and `en`.

**Run them in CI on every push**, alongside `npm run build` and
`vendor/bin/pint --test`. There is no CI configuration in this repo today —
create it.

**Note the trap** for whoever writes the CI job: `/public/build` is gitignored,
so a fresh checkout has no Vite manifest and **every route 500s with "Vite
manifest not found"** until `npm run build` runs. Build before you test, or the
gate reports a catastrophe that isn't there.

**Acceptance.** All three gates green in CI; deliberately removing a route from
`export.php` turns the build red.

---

## TAB 03 — Local setup and the `.env` trap · P1

**Scope.** Ten minutes of work that saves every future developer an hour.

`.env.example` ships `SESSION_DRIVER=database`, `CACHE_STORE=database` and
`QUEUE_CONNECTION=database` against `DB_CONNECTION=sqlite`, and
`database/database.sqlite` is not in the repo. Following the documented setup
produces **HTTP 500 on every page**:

```
Database file at path [.../database/database.sqlite] does not exist.
(SQL: select * from "sessions" where "id" = ... limit 1)
```

It reads like the application is broken. It is one line of configuration.

**Do.** Ship `SESSION_DRIVER=array`, `CACHE_STORE=array`,
`QUEUE_CONNECTION=sync` in `.env.example` — exactly what `netlify.toml` already
forces in production, and correct for a project with no database. Remove
`@php artisan migrate --force` from the `composer setup` script. Write a short
"getting started" section in the README that replaces the stock Laravel
boilerplate.

**Acceptance.** `git clone && composer install && cp .env.example .env &&
php artisan key:generate && npm ci && npm run build && php artisan serve`
serves every page with no further steps.

---

## TAB 04 — Lift 167 inline arrays out of 43 views · P1

**Scope.** The largest single piece of work in this book, and the one that
makes TABs 05–07 possible.

**The measurement.** 167 `@php`-declared arrays live inside 43 Blade files.
The worst offenders:

| View | Inline arrays | Lines |
| --- | --- | --- |
| `admin/document-requests.blade.php` | 18 | 3,313 |
| `admin/assistance-requests.blade.php` | 15 | 1,457 |
| `admin/sakuna.blade.php` | 10 | 572 |
| `admin/dashboard.blade.php` | 10 | 359 |
| `admin/communications.blade.php` | 9 | 1,231 |
| `admin/reports.blade.php` | 9 | 266 |
| `citizen/document-requests.blade.php` | 8 | 1,464 |
| `admin/papeles.blade.php` | 7 | 458 |

29 views already read from `config/esperanza_*.php`, which is the pattern to
follow — those are the easy half.

**Do.**

- Move every inline array into a named source with the **same keys and the same
  values**. Field names are the backend contract; a rename here is a change
  request there.
- One seam per entity, not one per view. `admin/document-requests` and
  `citizen/document-requests` render the same requests from two angles — they
  should read the same source.
- The view's job afterwards is presentation only: no data declaration, no
  fixture construction.
- Do this **before** TAB 05. Wiring an HTTP client into 43 files that each
  declare their own fixtures means doing the work twice.

**Do not.** Do not "improve" the shapes while moving them. Same keys, same
enums, same status strings. The improvement conversation happens against the
API contract, with the backend in the room.

**Acceptance.** No `@php $x = [...]` fixture declarations remain in
`resources/views`; every view's data arrives from one named source; all 55
routes still render identically (compare rendered HTML before and after).

---

## TAB 05 — The HTTP layer that does not exist · P1

**Scope.** There is exactly **one** `axios` reference in the codebase, and it is
the `window.axios` assignment in `resources/js/bootstrap.js`. No page makes a
network call. The entire data layer has to be built.

**Remember TAB 00's architectural fact:** production is static HTML on a CDN
with no PHP runtime, so this client runs **in the browser**, against a
different origin.

**Do.**

- **One client module.** Base URL from build-time config, `Accept`/
  `Content-Type`, auth header, request id, and a timeout. Not per-page `fetch`.
- **One error envelope handler.** The backend contract defines a single error
  body with a machine code and bilingual messages (see the backend book, TAB
  01). Parse it in one place. Map 401 → re-auth, 403 → the existing
  `x-admin.access-restricted` component, 422 → field-level form errors (TAB
  07), 5xx → a retryable error state (TAB 06).
- **Never swallow an error.** A caught exception that renders an empty list is
  the worst outcome available: the officer believes there is no data.
- **CORS and credentials** are yours to specify to the backend — origin
  allowlist, whether the token rides in a header or a cookie, and preflight
  cost. Settle it while the contract is still open, not after.
- **Cancel in-flight requests** on navigation and on rapid filter changes; the
  admin lists are all filtered by barangay, status, date and service type.
- Move `axios` from `devDependencies` to `dependencies` — it is runtime code.

**Acceptance.** One client, one error handler, no direct `fetch` in any Blade
file, and a forced 500 from the API produces a visible, recoverable state on
every screen that reads data.

---

## TAB 06 — Loading, empty, and error states · P1

**Scope.** Today nothing can fail, so nothing handles failure. There are 22
skeleton/`animate-pulse` usages and **6** `x-ui.empty-state` usages across 98
views, and no error states at all.

**Do.** Every list, table, chart, detail panel and stat card needs four states,
not one:

| State | Requirement |
| --- | --- |
| **Loading** | A skeleton matching the real layout's shape — `x-ui.skeleton` exists; use it everywhere, not in 22 places. Never a spinner where a skeleton fits. |
| **Empty** | `x-ui.empty-state` with copy specific to the thing that is empty and, where it applies, the action that fills it. "No results" on a filtered table is a different message from "no requests yet". |
| **Error** | What failed, and a retry. Never a blank panel, never a silent zero. |
| **Loaded** | What exists today. |

**Also.**

- **Distinguish "zero" from "not loaded".** A stat card showing `0` while the
  request is still in flight is a lie an officer will act on.
- **Partial failure is normal.** The admin dashboard renders many independent
  panels; one failing endpoint must not blank the page.
- The toast/`$dispatch` infrastructure already exists (447 references) — reuse
  it for transient failures rather than inventing a second notification path.
- Respect `prefers-reduced-motion`; `app.css` already honours it, so keep new
  shimmer and transition work inside that guard.

**Acceptance.** Every data-bearing surface demonstrates all four states on
demand; no surface renders `0` or an empty table before its request resolves.

---

## TAB 07 — Real forms and submission · P1

**Scope.** The app has **3 `<form>` elements** against **297 `<input>`** and
**366 `x-model`** bindings. It is Alpine state with no submit path. Every
write in the platform has to be built.

**What needs a submission path.** Citizen: registration, profile edit, all 28
Dokyu request forms, all 13 Tulong applications, document replacement on a
flagged requirement, request resubmission, support contact, settings.
Admin: every per-requirement decision (approve / flag / reject / mark
resubmitted), every whole-request transition, release with proof-of-release
evidence, payments, announcements through Draft → Review → Publish → Archive,
events, directory, banners, personnel and role edits, all 24 internal forms,
and the full Sakuna verb set.

**Do.**

- **Use the 25 form specs in `config/esperanza_service_form_specs.php`** —
  steps, and per-field `key`, `label`, `type`, `required` — as the definition.
  Render forms from the spec rather than hand-writing each one; that is what
  makes 28 services maintainable.
- **Client validation mirrors the spec, and never replaces server validation.**
  Display 422 field errors from the API against the same field keys.
- **Disable during flight and guard against double submission.** Every write in
  this platform is consequential — a duplicated Tulong application is a
  duplicated claim.
- **Preserve user input on failure.** A citizen who filled a 19-field PWD
  registration must not lose it to a 500.
- **File inputs**: `x-ui.file-picker` exists — validate type and size client-side
  for feedback, show upload progress, and never treat client validation as the
  gate.
- **Unsaved-changes warning** on navigation away from a dirty form.
- Two `localStorage` keys already persist draft progress
  (`esperanza_dokyu_progress`, `esperanza_tulong_progress`). Decide
  deliberately whether drafts stay client-side or move to the server once the
  API exists, and make the behaviour consistent between Dokyu and Tulong.

**Acceptance.** Every write path submits, shows in-flight state, renders
server-side field errors, survives a failure without data loss, and cannot be
double-submitted.

---

## TAB 08 — Session and permission gating on the client · P1

**Scope.** `resources/js/app.js` implements a session and permission simulation
in `Alpine.store('session')` and `Alpine.store('citizenSession')`, persisted to
`localStorage` under `esperanza_session` and `esperanza_citizen_session`.

**CLOSED 2026-08-30 (TAB 08).** The file used to carry this comment:

> No session -> fail-open (unrestricted preview), since this is a design demo,
> not a real security boundary.

`can()`, `canPerm()`, `inScope()`, `inDeptScope()` and `canExportReports()`
returned `true` with no account. That was correct for a design preview and
wrong the moment a real API exists. **All five now return `false`**, and the
admin layout shows `x-admin.sign-in-required` rather than a blank shell. The
quoted comment no longer exists in `app.js` — it is reproduced here only so the
change is legible to anyone holding an older copy of this book.

**Do.**

- **Invert the default to deny.** No session means no module, no permission, no
  scope. The UI then shows the existing `x-admin.access-restricted` component
  rather than a full preview.
- **Understand what client gating is for.** It hides what the user cannot use;
  it is *not* the security boundary. The server enforces all 490 permission
  triples (backend book, TAB 03). Never present a client check as protection.
- **Keep the existing re-derivation behaviour.** The store deliberately
  re-reads `moduleKeys`/`permissions` from current config on every load rather
  than trusting what was frozen into `localStorage` at login — that solved a
  real bug where a newly added module stayed invisible to logged-in sessions.
  Preserve that property against the API: refresh permissions on load, do not
  trust stale local copies.
- **Handle token expiry** as a first-class state — re-authenticate without
  losing the page the user was on, and without dumping them into a full
  preview.
- **Preserve the three scopes** the UI already models: module access,
  geographic (`Municipal` sees all, otherwise own barangay only), and
  department (only `department_head` and `department_staff` are office-limited).
  These are real business rules, documented in the backend book.

**Acceptance.** With no session, the admin portal shows nothing; with a session,
the visible surface matches the account's triples exactly; an expired token
recovers without data loss.

---

## TAB 09 — Bilingual completion · P2

**Scope.** `CLAUDE.md` and the shipped UI both treat bilingual as a
requirement — the language store defaults to **`fil`** — but coverage is
partial.

**The measurement:** **38 of 98 views** contain any language handling
(`$store.lang` or the `x-ui.t` component). 342 `x-ui.t` uses and 117 direct
`$store.lang` references, unevenly distributed.

- **16 citizen views and components have none**, including
  `digital-id-card`, `banner-carousel`, `status-guide`,
  `municipal-request-preview`, `barangay-certificate-preview`, the birthday and
  fiesta popups, and all seven document-editor panels.
- **32 admin views and components have none**, and the admin topbar has **no language toggle
  at all** — the toggle exists only in the citizen topbar and citizen settings.

**Do.**

1. **Get a ruling first: is the Administrative Portal bilingual or
   English-only?** Right now it is English-only *by omission*, not by decision.
   Both answers are defensible for LGU staff; guessing is not. This is an owner
   question and it changes the size of this TAB by a factor of three.
2. **Complete the citizen portal regardless of that answer.** Residents are the
   population the Filipino default exists for, and 16 citizen files
   currently ignore it.
3. **Use `x-ui.t` consistently** — it is a two-line component
   (`@props(['fil','en'])`) and it already works. Prefer it over ad-hoc
   `$store.lang.current === 'en' ? ... : ...` ternaries so coverage is
   greppable and testable.
4. **Include the things people forget**: `alt` text, `aria-label`, placeholders,
   validation messages, empty-state copy, error copy, toast text, and date and
   number formatting.
5. **Add the coverage gate** from TAB 02 so this cannot regress.

**Acceptance.** Every citizen-facing user-visible string has `fil` and `en`; the
gate proves it; the admin decision is recorded in `CLAUDE.md` either way.

---

## TAB 10 — Accessibility · P2

**Scope.** This is a government service. Accessibility is a duty of the
platform, and the PWD and Senior Citizen registration flows mean some of the
people filling these forms are precisely the people a keyboard-inaccessible
control excludes.

**The measurement:**

| Check | Measured |
| --- | --- |
| `<img>` with `alt` | **25 of 69** — 44 images unlabelled |
| `<label for=>` | **6** against 297 `<input>` |
| `aria-*` attributes | 23 |
| `role=` attributes | 7 |
| `@click` on `div`/`span`/`li`/`td`/`tr` | **56** — not keyboard reachable |
| `@click` on `button`/`a` | 386 — the correct pattern, already dominant |
| `focus:` / `focus-visible:` utilities | 1,429 — **this is already good** |

**Do.**

- **Label every input.** 297 inputs, 6 labels. A placeholder is not a label —
  it disappears on focus and screen readers treat it differently.
- **Alt text on all 69 images**, and empty `alt=""` where an image is genuinely
  decorative — that is a decision, not an omission.
- **Move the 56 non-interactive click handlers onto `<button>`**, or give them
  `role`, `tabindex` and keyboard handlers. A table row that opens a request
  detail must be reachable by keyboard.
- **Modals**: focus trap, `Escape` to close, focus restored to the trigger,
  `aria-modal`, and a labelled title. There are 39 `x-ui.modal` uses and 26
  hand-rolled ones — fix the component once, then TAB 12 converts the rest.
- **Charts** (12 `<canvas>`) are invisible to assistive technology. Provide a
  table or text summary alongside each.
- **Colour contrast** against the Tailwind palette in use, in both the default
  and any branding theme (Brand Blue, Navy, Gold, Emerald).
- **Live regions** for toasts and for status changes, so a decision is
  announced and not merely shown.
- Keep the existing focus-visible discipline; it is the part that is already
  right.

**Acceptance.** Automated audit clean on every route; a keyboard-only pass
completes a full Dokyu request as a citizen and a full approval as an admin;
screen-reader pass on registration, request submission and the admin decision
panel.

---

## TAB 11 — Performance · P2

**Scope.** The audience is an LGU and its residents on mobile data.

**The two measurements that matter:**

1. **One 1.66 MB JS chunk** (473 KB gzipped), which Vite itself warns about.
   Alpine, Chart.js, Lucide, `html2pdf.js` and the 730-line Dokyu editor all
   ship to every page, including the login screen.
2. **`/admin/sakuna` renders 1.59 MB of HTML — on each of its 11 tab URLs.**
   `admin/sakuna.blade.php` `@include`s all 11 tab partials unconditionally and
   hides ten of them with Alpine, so every tab URL ships every tab. For
   comparison: `/citizen/dashboard` is 94 KB and `/` is 10 KB.

**Do.**

- **Code-split.** `html2pdf.js` and the Dokyu editor are needed on a handful of
  admin screens; Chart.js on the dashboards and reports. Dynamic-import them.
  Lucide should ship only the icons actually used.
- **Split the Sakuna tabs.** Because deployment is a static export, each tab URL
  is already its own HTML file — render only the requested tab's partial per
  route and the page drops by roughly an order of magnitude, with no change to
  behaviour.
- **Apply the same test to the other heavy pages** — `admin/papeles` at 224 KB
  and the two request pages at 3,313 and 1,464 lines are the next candidates.
- **Images** (TAB 16): eight files over 1 MB, the largest 2.2 MB, served
  unresized.
- Set a **page-weight budget** and add it to the CI gate so this cannot creep
  back.

**Acceptance.** No route ships more than a stated HTML budget; the initial JS
payload excludes the PDF and editor libraries; measured on a throttled
connection, not on a laptop.

---

## TAB 12 — Component discipline · P2

**Scope.** `CLAUDE.md` is explicit: *"Always build reusable Blade components —
never duplicate UI"* and *"Before writing new markup: check whether an existing
component covers it… refactor rather than duplicate."* Practice has drifted.

**The measurement:**

| Component | Uses | Raw equivalent still in the codebase |
| --- | --- | --- |
| `x-ui.button` etc. (`x-ui.*` total) | 800 | **695 raw `<button>`** |
| `x-ui.modal` | 39 | **26 hand-rolled** (`fixed inset-0 z-50`) |
| `x-ui.table` | 19 | **36 raw `<table>`** |
| `x-ui.empty-state` | 6 | — (mostly just absent) |

800 `x-ui.*` uses is genuine, healthy reuse — this TAB is not a rewrite. It is
closing the gap on three specific components where the raw form still
outnumbers or rivals the component.

**Do.**

- **Modals first**, because they carry the accessibility fix from TAB 10. Fix
  focus trap, Escape, focus restore and ARIA in `x-ui.modal` once, then convert
  the 26 hand-rolled instances and get the fix everywhere for free.
- **Buttons next** — the loading/disabled state from TAB 07 belongs in the
  component, so converting raw buttons is what makes in-flight state universal.
- **Tables** — sorting, pagination and the loading/empty/error states from TAB
  06 belong in `x-ui.table`, not repeated 36 times.
- **Do not redesign anything** while converting. Same visual result, one
  implementation.

**Acceptance.** Hand-rolled modals: zero. Raw `<button>` and `<table>` reduced
to cases with a written reason. Rendered output unchanged — diff the HTML.

---

## TAB 13 — Responsive and the missing `md:` tier · P3

**Scope.** Breakpoint usage is lopsided: `sm:` 329, `lg:` 153, `xl:` 131,
`2xl:` 69 — and **`md:` just 31**.

That gap is a hypothesis, not a proven defect: it suggests layouts were designed
for phone and desktop and the tablet range between them was never exercised.
**Verify before rebuilding.** The admin portal's dense tables and the Sakuna
command centre are where it will show first.

**Do.**

- Walk all 55 routes at 768–1024 px and record what actually breaks. Fix what
  breaks; do not add `md:` variants for their own sake.
- Check the admin sidebar and topbar behaviour in that range, the wide tables,
  the charts, and the modals.
- Confirm horizontal overflow is contained — wide tables should scroll inside
  their own container, never scroll the page body.
- Check touch targets on tablet: the 56 non-interactive click handlers from TAB
  10 are often small.

**Acceptance.** All 55 routes verified at phone, tablet and desktop widths, with
findings recorded even where nothing needed changing.

---

## TAB 14 — Print and document rendering · P3

**Scope.** The frontend renders and prints official documents today:
`resources/js/dokyu-editor.js` (730 lines of `contenteditable` rich-text
editing), `html2pdf.js` for citizen-side download, per-barangay seals and
officials, and 25 document→template mappings. `app.css` carries `dp-printing`
print-isolation rules and `app.js` clears the class on `afterprint`.

**The handover to know about.** The backend book (TAB 10) specifies that the
**authoritative** document is rendered server-side, stored, hashed and given a
verifiable serial or QR. Client-side generation stays — as a *preview* and as a
convenience download — but it stops being the source of the official artefact.

**Do.**

- Keep the editor and the preview; retarget "Download" and "Print" at the
  server-rendered document once TAB 10 of the backend book lands.
- Make the distinction visible in the UI: a preview is labelled a preview; the
  official copy carries its control number and verification mark.
- Test print output on the paper size the municipal hall actually uses.
- `dokyu-editor.js` leans on `execCommand`, deprecated but universally
  supported; the file documents that choice with its reasoning. **Do not
  rewrite it on deprecation grounds alone** — revisit only if a browser
  actually breaks it.

**Acceptance.** Preview and official copy are visually distinguishable and
functionally separate; printing produces a correctly laid-out document.

---

## TAB 15 — The deploy pipeline · P2

**Scope.** `export.php` is the deploy artifact. It deserves the care given to
application code, and currently gets less.

**Do.**

- **Derive the route map from the router** (TAB 01) so drift is structurally
  impossible.
- **Derive the asset map** from what the views reference, or at minimum assert
  it in CI (TAB 02).
- **Fail the build loudly.** `export.php` already exits non-zero on a render
  failure — keep that, and extend it to fail on drift.
- **Verify `dist/` after export**, not just the exporter's own summary: page
  count, asset directories present, no zero-byte HTML.
- `/dist` is now gitignored (closed 2026-08-29, `605de1d`).
- Follow `DEPLOY_PROTOCOL.md` for every push. **A push to `main` deploys.**

**Acceptance.** A route added to `routes/web.php` with no other change appears
in `dist/` automatically; a deliberately broken view fails the build.

---

## TAB 16 — Housekeeping · P3

- **`vendor/bin/pint --test` fails on 9 files** — 8 `config/esperanza_*.php`
  plus `export.php`. `vendor/bin/pint` fixes all of them. Add it to CI so it
  stays fixed.
- **Eight images exceed 1 MB**, the largest 2.2 MB
  (`cristy-profile.png`, `theodoro-profile.png`, `rectangle_lgu.png`), served
  unresized and unoptimised. Compress, resize to display dimensions, and serve
  modern formats with fallbacks.
- **`public/documents/` is 22 MB** of reference PDFs shipped to every visitor's
  CDN. They are needed (TAB 01) but they do not need to be this large.
- **Move `axios` to `dependencies`** (TAB 05).
- **Replace the stock Laravel README** with a real one: what this project is,
  the two portals, setup, the constitution, and pointers to
  `PROCESS_TAB_FLOW.txt`, `FRONTEND_PENDING.md` and `DEPLOY_PROTOCOL.md`.

---

## TAB 17 — Certification gate

Ship nothing as "done" without these, measured and recorded:

1. **All 55 routes render 200** and appear in `dist/` — automated, in CI.
2. **Export-map drift gate green.**
3. **Every data surface** demonstrates loading, empty, error and loaded states.
4. **Every write path** submits, shows in-flight state, renders server field
   errors, and cannot be double-submitted.
5. **Client permission gating fails closed**; no session shows nothing.
6. **Bilingual gate green** for citizen-facing strings; the admin-portal
   decision recorded in `CLAUDE.md`.
7. **Accessibility**: automated audit clean, plus a keyboard-only and a
   screen-reader pass on registration, request submission, and admin decision.
8. **Page-weight budget** met on every route, measured throttled.
9. **`pint --test` green.**
10. **A `git clone` to a running local site** with no undocumented steps.

Publish the verdict as **CERTIFIED** or **NOT CERTIFIED with the named list**. A
partial pass is NOT CERTIFIED, not a qualified yes.

---

## Open questions — these need an answer, not a guess

1. **Is the Administrative Portal bilingual, or English-only?** It is currently
   English-only by omission and has no language toggle. This changes TAB 09
   substantially. **Owner decision.**
2. **Where does the API live, and what is the CORS posture?** Static origin,
   separate API origin. Token in a header or a cookie? **Backend + owner.**
3. **Do request drafts stay in `localStorage` or move to the server** once the
   API exists? Two keys already persist them. **Owner.**
4. **What browsers and what minimum device** must be supported? This decides how
   far the performance work in TAB 11 has to go. **Owner.**
5. **Is there an accessibility standard to certify against** (WCAG 2.1 AA is the
   usual public-sector target)? **Owner.**
6. **Citizen Satisfaction Survey** — the admin report exists with no collection
   UI anywhere in the frontend. Build the survey, or drop the report. **Owner.**

---

## Provenance

Every figure in this book was counted from the repository at `605de1d` on
2026-08-29 — not recalled, not estimated. The application was cloned, built
(`composer install`, `npm ci`, `npm run build`), and all 55 routes were rendered
and verified to return 200. Where a number suggests a problem rather than
proving one — the `md:` breakpoint gap in TAB 13 is the clearest case — the book
says so and asks for verification before work.

Companion documents: `FRONTEND_PENDING.md` (findings register F-001..F-009),
`ESPERANZA_BACKEND_MASTER_COMMAND.md` (the backend programme),
`PROCESS_TAB_FLOW.txt` (request lifecycle), `DEPLOY_PROTOCOL.md` (push
procedure), `CLAUDE.md` (project constitution).
