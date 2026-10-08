# Apply the slab KPI cards to every card in the repo

All 97 `<x-ui.stat-card …>` usages (admin + citizen) render through one component, so this is a one-component swap. No call site needs to change.

## Steps
1. **CSS** — paste the contents of `slab.css` inside `@layer components { … }` in `resources/css/app.css`.
2. **Component** — replace `resources/views/components/ui/stat-card.blade.php` with `stat-card.blade.php` from this folder.
3. **Objects** — create `resources/views/components/ui/kpi-object/` and copy the six partials (`people`, `document`, `tray`, `ring`, `stamp`, `calendar`).
4. **Build** — `npm run build` (or `npm run dev`), then open `/admin/dashboard`, `/admin/constituents`, `/admin/document-requests`, `/citizen/dashboard`.
5. **Grids** — slabs want ≥ 300px. Where a KPI row still uses a 4–6 column grid (e.g. `xl:grid-cols-6`), change the wrapper above the `x-ui.stat-card` group to  
   `grid gap-5 grid-cols-[repeat(auto-fill,minmax(300px,1fr))]`.
6. **Commit.**

## How existing props are mapped (automatic)
- `color` → tone: brand→brand · light→sky · deep→deep · slate→navy · purple→violet · green→teal · gold→gold · orange→amber · red→rose
- `icon` → object: users/house/building/smile → **people** · file-text/clipboard/pencil-line/flag/eye → **document** · inbox/clock/loader/timer/bell/search → **tray** · hand-heart/shield-*/badge-check/wallet/alert/siren/circle-x → **ring** · package-check/circle-check/megaphone/banknote/receipt → **stamp** · calendar-* → **calendar**

## Overriding per card (optional)
Add `tone="…"` and/or `object="…"` on a call site to pick explicitly, e.g.  
`<x-ui.stat-card label="Dokyu Open" tone="violet" object="document" … />`

## Prompt for Claude Code
> Follow `design_handoff_dashboard_clay/kpi/APPLY_KPI.md`: paste `slab.css` into `@layer components` in `resources/css/app.css`, replace `components/ui/stat-card.blade.php` with the provided file, add the six `components/ui/kpi-object/*.blade.php` partials, then make every KPI grid wrapper `grid gap-5 grid-cols-[repeat(auto-fill,minmax(300px,1fr))]`. Keep all Alpine bindings, bilingual `x-text`, keyboard handling and `php artisan test` green. Show me screenshots of /admin/dashboard and /citizen/dashboard.
