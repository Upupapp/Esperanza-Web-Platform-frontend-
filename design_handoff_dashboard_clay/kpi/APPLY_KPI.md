# Apply the slab KPI cards to every card in the repo

All 97 `<x-ui.stat-card …>` usages (admin + citizen) render through one component, so this is a one-component swap. No call site needs to change.

## Steps
1. **CSS** — paste the contents of `slab.css` inside `@layer components { … }` in `resources/css/app.css`.
2. **Component** — replace `resources/views/components/ui/stat-card.blade.php` with `stat-card.blade.php` from this folder.
3. **Objects** — create `resources/views/components/ui/kpi-object/` and copy the six partials (`people`, `document`, `tray`, `ring`, `stamp`, `calendar`).
4. **Build** — `npm run build` (or `npm run dev`), then open `/admin/dashboard`, `/admin/constituents`, `/admin/document-requests`, `/citizen/dashboard`.
5. **Grids — leave them as they are.** Every page keeps its current KPI grid and card size. Only the admin Dashboard (`admin/dashboard.blade.php`) uses the wide layout; everywhere else pass `layout="compact"` (see step 7) so the cards stay the size they are today.
6. **Commit.**
7. **Compact everywhere except the Dashboard** — add `layout="compact"` to every `<x-ui.stat-card` outside `admin/dashboard.blade.php`. One command from the repo root (dry-run first by removing `-i`):

   ```bash
   grep -rl "<x-ui.stat-card" resources/views --include=*.blade.php | grep -v "admin/dashboard.blade.php" | xargs sed -i 's/<x-ui\.stat-card /<x-ui.stat-card layout="compact" /g'
   ```
   (macOS: `sed -i ''`.) Multi-line tags (`<x-ui.stat-card` followed by a newline, e.g. in `citizen/dashboard.blade.php` and `constituents/tab-*.blade.php`) need `layout="compact"` added by hand on the next line.

## How existing props are mapped (automatic)
- `color` → tone: brand→brand · light→sky · deep→deep · slate→navy · purple→violet · green→teal · gold→gold · orange→amber · red→rose
- `icon` → object: users/house/building/smile → **people** · file-text/clipboard/pencil-line/flag/eye → **document** · inbox/clock/loader/timer/bell/search → **tray** · hand-heart/shield-*/badge-check/wallet/alert/siren/circle-x → **ring** · package-check/circle-check/megaphone/banknote/receipt → **stamp** · calendar-* → **calendar**

## Overriding per card (optional)
Add `tone="…"` and/or `object="…"` on a call site to pick explicitly, e.g.  
`<x-ui.stat-card label="Dokyu Open" tone="violet" object="document" … />`

## Sakuna (and any other hand-written KPI tiles)
Sakuna's cards are plain `<div class="surface-glass rounded-[26px] p-4">` blocks, not `x-ui.stat-card`, so the component swap doesn't reach them. Use the **compact** layout — same 6-up grid and card size, the object faded into the corner as a background:

1. Copy the four extra objects into `components/ui/kpi-object/`: `flame`, `building`, `box`, `bell` (the `stat-card.blade.php` in this folder already knows them and has the `layout="compact"` prop; `slab.css` has `.slab-diag`).
2. In `resources/views/admin/partials/sakuna/tab-command.blade.php`, replace the whole "B1. KPI Cards" grid with `sakuna-command-kpi.blade.php` from this folder (and optionally the skeleton in section A with the commented block at its bottom).
3. The other Sakuna tabs (`tab-alerts`, `tab-damage`, `tab-evacuees`, `tab-relief`, `tab-reports`) each open with a 4-up row of `surface-glass rounded-[26px] p-4` stat tiles. Convert each tile to
   `<x-ui.stat-card layout="compact" tone="…" object="…" label="…" valueBind="…" />`
   keeping its existing `x-text` expression as `valueBind`. Tones: rose = incidents/danger, teal = centers, brand = people, gold = relief/supplies, violet = reports, amber = alerts, navy = totals.

Compact spec: `min-h-[136px] rounded-[22px] p-4`; label 11.5px (`pr-6`), value 30px pushed down with `mt-auto`, sub 11.5px in a fixed two-line box `min-h-[30px] max-w-[96px] text-balance` so it wraps instead of running under the object and the numbers share one baseline whether the sub is 0, 1 or 2 lines; object `104px` box scaled 1.25× (`-right-[30px] -bottom-[34px] -rotate-6 scale-[1.25] origin-[70%_70%] opacity-45`) so it fills the right half of the card as a big cropped watermark while the text column on the left stays clear; diagonal gradient (`slab-diag`). Reference render: `Sakuna KPI.dc.html`.

## Prompt for Claude Code
> Follow `design_handoff_dashboard_clay/kpi/APPLY_KPI.md`: paste `slab.css` into `@layer components` in `resources/css/app.css`, replace `components/ui/stat-card.blade.php` with the provided file, add all ten `components/ui/kpi-object/*.blade.php` partials. Do NOT change any grid wrapper or card size: only `admin/dashboard.blade.php` keeps the wide layout; add `layout="compact"` to every other `<x-ui.stat-card` in the repo (including multi-line tags). Keep all Alpine bindings, bilingual `x-text`, keyboard handling and `php artisan test` green. Then do the Sakuna section: add the four extra kpi-object partials, replace the B1 KPI grid in `admin/partials/sakuna/tab-command.blade.php` with `sakuna-command-kpi.blade.php`, and convert the 4-up stat tiles on the other Sakuna tabs to `layout="compact"`. Show me screenshots of /admin/dashboard, /citizen/dashboard and /admin/sakuna.
