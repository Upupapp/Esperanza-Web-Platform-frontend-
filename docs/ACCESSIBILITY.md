# Accessibility

**Standard.** WCAG 2.2 level AA. The Philippine standard for government websites,
LGUs included, is WCAG 2.0 (ISO/IEC 40500:2012), adopted by the DICT circular of
12 May 2017. WCAG 2.2 AA contains every 2.0 AA criterion, so meeting 2.2 meets it.

## What the gate checks on every push (since 2026-10-17)

`scripts/gate.sh` runs `node scripts/a11y.mjs` (also `npm run a11y`) on every page
of the built site, in headless Chrome. A failure blocks the push.

- **axe-core 4.10** with the WCAG 2.0, 2.1 and 2.2 A/AA rules. Any *serious* or
  *critical* violation fails; `A11Y_REPORT=1` lists every violation of any impact.
- **Keyboard, for real** (key presses, not markup reading), up to 30 Tab stops a page:
  - the first Tab reaches a "skip to content" link (WCAG 2.4.1);
  - every stop is on screen and visibly focused (2.4.7, 2.4.11);
  - focus is never trapped, and Shift+Tab goes back (2.1.1, 2.1.2);
  - an open dialog closes with Escape.

The run is offline and repeatable: the pages come from `dist/` and the API is answered
with small sample data (an approved resident, a Super Admin, empty lists). It judges
each page's own markup and behaviour.

`scripts/audit.mjs` (unchanged) still measures page overflow at a given width and the
older structural counts.

## What changed to pass (2026-10-17)

| Finding (first run, 68 pages) | Fix |
|---|---|
| Muted grey text at 4.35:1 on the page surfaces (261 elements, 54 pages) | `--color-ink-muted` #5f6b8a → #545f7d (5.2:1) |
| No skip link on any page | "Lumaktaw sa nilalaman / Skip to content" first in all three layouts; `<main id="main-content" tabindex="-1">` |
| Password show/hide buttons with no name, 18px target | Named ("Show password" / "Ipakita ang password"), `aria-pressed`, 32px target |
| Empty day cells of the Events calendar announced as unnamed buttons | Hidden from assistive technology; each day cell names its date and its events |
| Priority incident table scrolled sideways without keyboard access | The scroll region is focusable and named |
| Pale text on the red Emergency call banner (3.48:1) | Full white |

## Keyboard-only and screen-reader passes

The gate covers each page as it first appears. The flows people use were also walked
with the keyboard alone, on the live site against staging, on 2026-10-17. See the
release notes of that date for the flows and the results.

A pass with a real screen reader is still recommended before go-live: NVDA on Windows
with Chrome, and TalkBack on an Android phone. Use this checklist:

1. Sign in and out (resident and staff).
2. File a Dokyu request, attach a document, then track it.
3. File a Tulong application.
4. Report an incident from Emergency.
5. Open and close a staff request, and mark a workflow step done.
6. Publish a Service Catalog version.

At each step, check that:

- headings and landmarks are announced;
- every control says what it is and what it does;
- toasts are read out;
- dialogs announce their title, and focus returns where it was;
- a status change is announced.
