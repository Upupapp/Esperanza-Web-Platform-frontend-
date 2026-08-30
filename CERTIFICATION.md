# Esperanza Web Platform — Front End Certification (TAB 17)

Assessed 2026-08-29 against `ESPERANZA_FRONTEND_MASTER_COMMAND.md`.
Every number below was measured on this tree, not recalled.

---

# VERDICT: **NOT CERTIFIED**

TAB 17 is explicit that a partial pass is NOT CERTIFIED, not a qualified yes.
**5 of the 10 gates pass. 5 fail.** The named list is below.

---

## The ten gates

| # | Gate | Result |
| --- | --- | --- |
| 1 | All 55 routes render 200 and appear in `dist/` — automated | **PASS** |
| 2 | Export-map drift gate green | **PASS** |
| 3 | Every data surface demonstrates loading, empty, error and loaded | **FAIL** |
| 4 | Every write path submits, shows in-flight state, renders server field errors, cannot double-submit | **FAIL** |
| 5 | Client permission gating fails closed; no session shows nothing | **PASS** |
| 6 | Bilingual gate green for citizen-facing strings; admin decision recorded | **FAIL** |
| 7 | Accessibility: automated audit clean, plus keyboard-only and screen-reader passes | **FAIL** |
| 8 | Page-weight budget met on every route, measured throttled | **FAIL** |
| 9 | `pint --test` green | **PASS** |
| 10 | A `git clone` to a running local site with no undocumented steps | **PASS** |

### Why each failing gate fails

**3 — four states.** `x-ui.error-state` now exists (there were zero error states
across 98 views before, because nothing could fail). It is not wired into any
data surface, and it cannot be meaningfully wired until the views stop declaring
their own fixtures (TAB 04) and start reading the API client (TAB 05). 22
skeleton usages and 6 empty states across 98 views is unchanged.

**4 — submission.** Unchanged and unstarted: **3 `<form>` elements against 297
`<input>` and 366 `x-model` bindings**, and 3 `@submit` handlers. The 25 form
specs in `config/esperanza_service_form_specs.php` that TAB 07 wants to render
from are present and unused. `x-ui.button` gained the `loading` prop this work
needs, so in-flight disabling is now a property of the button rather than
something each form must remember — but no write path exists to use it.

**6 — bilingual.** **38 of 99 views** contain any language handling. 16
citizen-facing views and components have none, and the Administrative Portal has
no language toggle at all. TAB 09's own first instruction is to get an owner
ruling on whether the admin portal is bilingual or English-only, because that
changes the size of the work by roughly 3x. **That ruling has not been given, so
the work was not guessed at.** The coverage gate (TAB 02 gate 3) is deliberately
not added yet: it would be red on arrival and tell nobody anything new.

**7 — accessibility.** Measured on the *rendered* DOM across all 55 routes
(`node scripts/audit.mjs`), which is a much larger denominator than a source
grep sees, because Alpine renders most of the app from data:

- **799 of 1729** form controls have no accessible name.
- **52 of 797** images have no `alt`.

Genuine progress this round: every modal (39 component instances *and* all 26
hand-rolled ones) now has a focus trap, focus restore, Escape and dialog
semantics; 39 non-interactive click targets became keyboard-operable. But no
keyboard-only or screen-reader pass has been performed, and there is no agreed
standard to certify against — WCAG 2.1 AA is the usual public-sector target and
is an open owner question.

One correction to the findings register while here: it is **not** that labels are
missing. There are **324 `<label>` elements** in the views; only 6 use `for=`.
The defect is *association*, not absence, and the remedy is different work.

### The two passing gates that are new, and how they were verified

**10 — clean clone.** `.env.example` shipped database session/cache/queue drivers
against a sqlite file that is gitignored, so a fresh clone returned HTTP 500 on
*every* page while the machine that wrote it worked fine (F-004). Now
`array`/`array`/`sync`, and `artisan migrate` is gone from the composer setup
script.

Verified end to end against the merged tree (`1e18079`), not assumed — clone,
`composer install`, `cp .env.example .env`, `key:generate`, `npm ci`,
`npm run build`, then the suite:

```
Tests:  11 passed (19 assertions)
VERDICT: PASS -- clean clone reaches a working site with only the documented steps
```

**8 — page weight.** Both measurements improved a lot, but the gate asks for a
*budget*, enforced, *measured throttled* — none of which exists yet:

| | Before | After |
| --- | --- | --- |
| Initial JS | 1,665 KB (475 KB gzip) | **506 KB (130 KB gzip)** |
| `/admin/sakuna` per tab URL | 1.59 MB × 11 | **~244 KB × 11** |
| Sakuna module total | 17.5 MB | **2.68 MB** |

No budget number has been agreed, and nothing measures on a throttled
connection. Setting the budget is an owner decision (it follows from gate 4 of
the open questions: what minimum device must be supported).

---

## What the gate enforces today

There is **no CI on this project** by standing instruction. `scripts/gate.sh` is
the gate and it runs on every push via `scripts/hooks/pre-push`:

```
vite build → pint --test → phpunit (11 tests) → static export → assertions ON dist/
```

The `dist/` assertions deliberately do not trust the exporter's own summary:
they compare the page count against the route count derived from the router,
fail on zero-byte HTML, require the 16 reference PDFs, and require that no page
still points at `http://localhost` (i.e. that `APP_URL` was honoured).

Both new gates were **proven to fail before being trusted**: an unmapped
`public/uploads` turned the drift gate red, and a deliberately broken view turned
the render gate red on both routes that share it.

---

## Still open, by owner decision

These are not oversights. The book says they need an answer, not a guess.

1. **Is the Administrative Portal bilingual, or English-only?** English-only by
   omission today, with no toggle. Changes TAB 09 by ~3x.
2. **Where does the API live, and what is the CORS posture?** The client assumes
   a bearer token in an `Authorization` header. A cookie would need
   `withCredentials` and a preflight budget. Settle before the contract closes.
3. **Do request drafts stay in `localStorage` or move to the server?** Two keys
   already persist them.
4. **What browsers and what minimum device must be supported?** This sets the
   page-weight budget in gate 8.
5. **Is there an accessibility standard to certify against?** WCAG 2.1 AA is the
   usual public-sector target.
6. **Citizen Satisfaction Survey** — the admin report exists with no collection
   UI anywhere. Build the survey, or drop the report.

## Still open, and NOT an owner decision — it is a security action

**The `APP_KEY` was published in a `netlify.toml` comment in a public repo
(F-003).** The literal value is redacted now, but redaction does not un-leak it:
**it remains in this repository's git history and must be rotated.**

---

## TAB-by-TAB

| TAB | State | Note |
| --- | --- | --- |
| 01 The two production defects | **DONE** | 46 → 55 routes exported; 16 PDFs reach `dist/` |
| 02 Test harness and gate | **DONE** (adapted) | 2 → 11 tests. Enforced by a local pre-push hook, not CI — see below |
| 03 Local setup and the `.env` trap | **DONE** | Clean clone verified end to end on the merged tree — 11 tests from a fresh checkout |
| 04 Lift 167 inline arrays | **NOT DONE** | 166 declarations across 43 views, unchanged |
| 05 The HTTP layer | **DONE** | Client + error envelope built; no screen consumes it yet |
| 06 Loading, empty, error states | **PARTIAL** | `x-ui.error-state` added; not wired (blocked behind TAB 04) |
| 07 Real forms and submission | **NOT DONE** | Button loading state added; no write path exists |
| 08 Session and permission gating | **DONE** | Fails closed; signed-out state added |
| 09 Bilingual completion | **NOT DONE** | Blocked on the owner ruling |
| 10 Accessibility | **PARTIAL** | Modals and keyboard reach done; naming and alt open |
| 11 Performance | **DONE** | Both measurements improved; no budget agreed (gate 8) |
| 12 Component discipline | **PARTIAL** | a11y fix reached all 26 hand-rolled modals; consolidation to the component not done |
| 13 Responsive and the `md:` tier | **DONE** | Hypothesis **disproven** by measurement — zero overflow at 375/768/1024/1440 px |
| 14 Print and document rendering | **BLOCKED** | Needs the backend's server-rendered authoritative document; paper size is an owner question |
| 15 The deploy pipeline | **DONE** | Route map derived; `APP_URL` now asserted |
| 16 Housekeeping | **PARTIAL** | pint, axios, README, images done; 23 MB of PDFs need tooling not installed |
| 17 Certification gate | **THIS DOCUMENT** | NOT CERTIFIED |

### One deliberate deviation from the book

TABs 02, 16 and 17 ask for these gates to run **in CI**. The project owner's
standing instruction is that there is **no CI on any project, ever, and no
workflow file is to be authored**. The gates were therefore built as
`scripts/gate.sh` and wired to `scripts/hooks/pre-push`, which has the same
blocking power on the machine that actually deploys — a push to `main` *is* the
deploy here. Nothing was dropped; only where it runs changed.
