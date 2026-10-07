# Handoff: Esperanza LGU — Staff Dashboard (soft clay / glass restyle)

## Overview
A visual restyle of the staff **Dashboard** page of the Esperanza LGU administrative portal. Same information architecture and data as today (sidebar, header, greeting banner, 6 KPI cards, Dokyu/Tulong donuts, open-requests-by-barangay, 6-month requests trend, Sakuna alerts, recent activity) — with a new surface language: light claymorphism (puffy, inner-shaded coloured elements), a hint of neumorphism (pressed-in inputs and tracks) and glassmorphism (frosted translucent cards over soft colour washes). Brand blue remains primary; the seal's yellow is the secondary accent.

## About the design files
`Dashboard v3.dc.html` (+ `support.js`, `assets/esperanza-seal.png`) is a **design reference built in HTML** — a prototype of intended look and behaviour, not production code to paste in. The task is to **recreate it inside the existing Esperanza codebase** (Blade/Laravel views + `resources/css/app.css` with Tailwind utilities, as currently used), reusing the existing data bindings. Everything below is specified so it can be implemented from this README alone; open the HTML file in a browser to compare.

## Fidelity
**High-fidelity.** Colours, type, spacing, radii and shadows are final. Match them closely; data/copy should come from the real backend.

## Design tokens

### Colours (existing brand scale, unchanged)
- `brand-50 #EEF4FF`, `brand-100 #DBE7FF`, `brand-400 #5687FF`, `brand-500 #2F62F5`, `brand-600 #1D47D6`, `brand-700 #1836A8`, `navy-900 #0B1730`
- **New accent (seal yellow):** `yellow-400 #FFE11A` (base), `yellow-300 #FFEB5C` (gradient start), `yellow-500 #F5CF00` (gradient end), `yellow-600 #E8C200` (lines/strokes), `yellow-100 #FFF4B8` (light tile), `yellow-text #7A5E00` (text/icon on light yellow)
- **Neutrals:** page `#E6EDFB`; pressed/inset surface `#E1E9F9`; track `#DCE4F5`; heading `#0B1730`; body `#3A4664`; muted `#5F6B8A`; faint `#7D89A8`; sidebar text `#C3CFEA`; sidebar muted `#A9B8DD`; sidebar label `#8E9DC4`

### Typography
- **Inter** (already the site font) for everything. Weights used: 500, 600, 700, 800.
- **System monospace** (`ui-monospace, 'SF Mono', Menlo, Consolas, monospace`) for the clock, `SA-901`, reference numbers (`ALT-2026-0009`), activity meta, and chart month labels — same as the site's current practice.
- Scale: page title 26/800 (-0.02em); banner greeting 28/800; card titles 18/800; KPI value 36/800 (-0.03em); KPI label 11.5/700 uppercase (+0.07em); body 14–14.5; meta 12–12.5; micro 11–11.5.

### Radii
Sidebar 30px · banner 32px · cards 26–28px · inputs/buttons 16px · pills 999px · icon tiles 14–16px (or circle).

### Surfaces (the three textures)
1. **Glass card** — `background: rgba(255,255,255,.58); backdrop-filter: blur(22px) saturate(140%); border: 1px solid rgba(255,255,255,.85); box-shadow: 12px 14px 34px rgba(29,71,214,.12), -8px -8px 22px rgba(255,255,255,.75), inset 0 1px 0 rgba(255,255,255,.95);`
2. **Pressed / inset (neumorphic)** — `background: #E1E9F9; box-shadow: inset 4px 4px 9px rgba(29,71,214,.12), inset -4px -4px 9px rgba(255,255,255,.9);` (smaller elements use 2–3px offsets).
3. **Clay (raised, inner-shaded)** —
   - Blue: `background: linear-gradient(160deg,#2F62F5,#1D47D6); box-shadow: 6px 8px 18px rgba(29,71,214,.35), inset 2px 2px 4px rgba(255,255,255,.35), inset -3px -4px 8px rgba(11,23,48,.35);`
   - Yellow: `background: linear-gradient(160deg,#FFEB5C,#F5CF00); color:#0B1730; box-shadow: 5px 7px 14px rgba(227,188,0,.3), inset 2px 2px 4px rgba(255,255,255,.7), inset -3px -4px 7px rgba(140,110,0,.28);`
   - Light tile: `background:#DBE7FF; color:#1D47D6; box-shadow: 5px 7px 14px rgba(29,71,214,.14), inset 2px 2px 4px rgba(255,255,255,.9), inset -3px -3px 6px rgba(29,71,214,.14);`

### Page background
`#E6EDFB` with three soft washes: `radial-gradient(760px 440px at 90% -8%, rgba(86,135,255,.42), transparent 70%)`, `radial-gradient(600px 440px at 6% 106%, rgba(255,225,26,.36), transparent 70%)`, `radial-gradient(900px 640px at 58% 58%, rgba(47,98,245,.12), transparent 70%)`. Two decorative clay orbs sit behind the content (yellow 140px near the banner's bottom-right; blue 190px lower-left) so the glass cards have something to blur. Optional — drop them if they fight with real content.

## Layout
- Page: `display:flex; gap:20px; padding:16px; min-height:100vh;` **the page scrolls, not an inner box** (an inner scroll container clips the shadows). Sidebar is `position:sticky; top:16px; height:calc(100vh - 32px)`.
- Main column: flex column, `gap:22px`.
- KPI grid: `grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap:20px`.
- Chart rows (×2): `grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px; align-items:start`.

## Screens / components

### Sidebar (272px expanded, 84px collapsed; `transition: width .25s`)
- Panel: `linear-gradient(180deg, rgba(11,23,48,.95), rgba(17,36,84,.90))`, `backdrop-filter: blur(20px)`, `border:1px solid rgba(255,255,255,.09)`, `box-shadow: 16px 18px 44px rgba(11,23,48,.30), inset 0 1px 0 rgba(255,255,255,.14), inset -1px -2px 6px rgba(0,0,0,.25)`; padding 18px 14px; radius 30px.
- Logo: 46px yellow disc (`#FFE11A`, clay shadow) with the municipal seal image inset 2px, round-cropped. "Esperanza LGU" 16/800, "Administrative Portal" 12 `#A9B8DD`.
- Signed-in panel: pressed dark well (`rgba(255,255,255,.05)`, `inset 4px 4px 10px rgba(0,0,0,.35), inset -3px -3px 8px rgba(255,255,255,.06)`, radius 20). Label "SIGNED IN AS" 10.5/700 +0.08em `#8E9DC4`; name 14.5/700 white; "SA-901 · Super Admin" 12 `#A9B8DD`; pill "Municipality-wide" — `rgba(255,225,26,.14)` bg, `1px solid rgba(255,225,26,.35)`, text `#FFE11A` 11.5/700, landmark icon.
- Nav items: 40px tall, radius 16, 14.5/600, colour `#C3CFEA`; hover `rgba(255,255,255,.07)` + white text. **Active:** blue clay gradient, white 700, `box-shadow: 0 6px 14px -2px rgba(47,98,245,.5), inset 2px 2px 4px rgba(255,255,255,.35), inset -3px -4px 8px rgba(11,23,48,.4)` (shadow straight down so it isn't clipped by the scrolling list). The list has 12px side padding and `scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.18) transparent`.
- Count badge (Dokyu): yellow clay pill 22px, navy 11.5/800.
- Collapse button: 44px, `rgba(255,255,255,.06)` bg, `#C3CFEA`, pressed look on `:active`.

### Header
- Title "Dashboard" 26/800; subtitle 13.5 `#5F6B8A`.
- Clock: pressed pill 44px, mono 13/500 `#3A4664`, leading 8px yellow dot with `0 0 0 3px rgba(255,225,26,.3)` ring; `white-space:nowrap`.
- Search: pressed pill 44px, up to 360px wide, search icon `#5F6B8A`, placeholder `#7D89A8`.
- Bell: 44px glass square (radius 16) with yellow 8px dot top-right (`0 0 0 2px #fff`).
- User chip: glass, 52px tall, radius 20; 40px blue clay avatar circle ("TS" 14/800 white); name 14/700, role 12 `#5F6B8A`; chevron-down.

### Greeting banner
- `linear-gradient(118deg, #0B1730 0%, #1836A8 52%, #2F62F5 100%)`, radius 32, padding 30px 34px, `border:1px solid rgba(255,255,255,.18)`, `box-shadow: 16px 20px 44px rgba(24,54,168,.35), -8px -8px 20px rgba(255,255,255,.7), inset 0 1px 0 rgba(255,255,255,.35), inset -6px -8px 18px rgba(11,23,48,.45)`.
- Overlays: top sheen `linear-gradient(180deg, rgba(255,255,255,.14), transparent 48%)`; yellow glow 360px circle at bottom-right (`radial-gradient(circle, rgba(255,225,26,.55), transparent 68%)`); blue glow 260px circle top-centre.
- Avatar 72px yellow clay circle, navy "TS" 24/800.
- Date 12/700 +0.1em `#FFE11A` (uppercase, live); greeting 28/800 white; sub 15 `#DCE5FF` with `SA-901` in mono 13.5.
- Scope pills (right side, `flex: 0 1 460px; justify-content:flex-end; flex-wrap:wrap; gap:8px`, so they stack two per row with "+7 more" below): glass pills `rgba(255,255,255,.14)`, `blur(10px)`, `1px solid rgba(255,255,255,.28)`, 12.5/600 white, icon 13px, `white-space:nowrap`.

### "Live counts…" row
Text 14.5 `#3A4664`; **Export Report** = blue clay button 44px, radius 16, 13.5/700, download icon; hover darkens gradient to `#1D47D6→#1836A8`; `:active` becomes inset.

### KPI cards (×6)
Glass card, padding 18px 18px 16px, radius 26, flex column gap 14. Hover: `translateY(-4px)` + deeper shadow `16px 22px 42px rgba(29,71,214,.20)` (`transition .25s`).
- Row 1: label (11.5/700 uppercase, `#5F6B8A`, max-width 112px, `text-wrap:balance`) left; 44px **clay coin** (circle) right with 19px icon.
- Value 36/800.
- Sub-line in a **pressed pill** (`#E1E9F9`, 12/600 `#3A4664`, padding 5px 11px).
- Coins: Registered residents = blue gradient `#5687FF→#2F62F5` (users icon) · Dokyu open = light tile `#DBE7FF/#1D47D6` (file-text) · Waiting on staff = yellow clay (inbox) · Tulong open = `#1D47D6→#1836A8` (hand-heart) · Released this month = navy `#1F3B7A→#0B1730` (package-check) · Submitted today = `#FFF4B8/#7A5E00` (calendar-clock). Icons are Lucide.

### Donut cards (Dokyu, Tulong)
- Header: title 18/800 + "View details" link 13.5/700 `#1D47D6`.
- Chart 200×200 SVG (viewBox 0 0 200 200): faint track circle r=81, stroke-width 31, `rgba(29,71,214,.08)`. Segments are **arc paths** (outer R 94, inner r 68) with `stroke: same colour; stroke-width 5; stroke-linejoin: round` for rounded corners, a constant ~10px gap between pieces, and a **16° minimum angle** for tiny slices (legend percentages stay exact). Resting `filter: drop-shadow(0 2px 3px rgba(11,23,48,.16))`.
- Centre: pressed disc (inset 42px) showing total 30/800 and "Total requests"/"Total cases" 11/600 `#5F6B8A`.
- **Hover (segment or its legend row):** segment translates 7px outward along its mid-angle (`transition: transform .3s cubic-bezier(.34,1.56,.64,1)`), gets `drop-shadow(0 8px 12px rgba(11,23,48,.32))`; other segments fade to `opacity .35`; the centre swaps to that status's count and `Label · NN%`; the legend row lifts (`rgba(255,255,255,.8)` bg + soft shadow) while other rows go `opacity .5`. Clear on mouse-leave of the chart+legend block.
- Legend rows: 12px rounded-square swatch, label 13/600 `#3A4664`, count 13.5/800, share 11.5/600 `#7D89A8` right-aligned.
- Colours — Dokyu: Released `#1D47D6`, Processing `#5687FF`, Pending `#FFE11A`, Ready `#0B1730`. Tulong: Resolved `#2F62F5`, Closed `#B9C7E8`, Declined `#0B1730`. *(Status names/colour mapping should follow the real status enums.)*

### Open requests by barangay
Header + pressed segmented control (Dokyu | Tulong): track `#E1E9F9` inset; active segment `rgba(255,255,255,.85)` bg, `#1D47D6` 12.5/700, raised shadow. Rows: name 14.5/600 + count `#5F6B8A`; 14px pressed track (`#DCE4F5`) with blue gradient fill `#5687FF→#1D47D6` and a glossy inset highlight. Empty state (Tulong = 0): pressed well with "No open Tulong requests" 14.5/700 + explanation 12.5.

### Requests trend (last 6 months)
Title 18/800; legend "Last 6 months · ● Dokyu ● Tulong" 13/600. SVG viewBox 600×250, `width:100%`. Grid: dashed `rgba(29,71,214,.12)` lines at 0/10/20/30/40 with 11px labels `#7D89A8` (static `<text>`, not templated); months along the bottom in mono. Series: smooth cubic curves with horizontal tangents (control points at mid-x), 3.5px stroke, round caps — Dokyu `#2F62F5` with area fade `rgba(47,98,245,.38→0)`, Tulong `#E8C200` with area fade `rgba(245,207,0,.45→0)`. **Hover:** per-month hit columns; show dashed vertical guide `rgba(11,23,48,.28)`, 6.5px dots (white 3px stroke) on both lines, and a glass tooltip (month in mono, "Dokyu n · Tulong n") clamped to 16–84% of width.

### Sakuna alerts
Title + yellow clay count badge (26px). Alert row: glass sub-card (radius 18, `rgba(255,255,255,.55)`), 42px light-yellow tile with `triangle-alert`, title 15/700, "test · Advisory" 12.5 `#5F6B8A`, date 11.5 `#7D89A8`, right-side pressed pill "ADVISORY" 11/700. Hover lifts 2px. "View all alerts" link.

### Recent activity
Title + "View all". Rows separated by `1px solid rgba(29,71,214,.09)` top borders, padding 14px 4px. 38px pressed circle with a Lucide icon per action type (`bell-ring` published, `pencil-line` drafted, `log-in` sign-in, `eye` viewed record), colour `#1D47D6`. Text: "Actor — Action" 14 (actor 700, action 600 `#5F6B8A`), meta in mono 12 `#1D47D6`, time 11.5 `#7D89A8`.

## Interactions & state
- Sidebar `collapsed` (bool) · `activeNav` (string) · barangay `tab` ('Dokyu' | 'Tulong') · donut `hover` ({chart, index} | null) · trend `hoverIndex` (0–5 | null) · live clock (1s tick).
- All transitions 200–300ms; the only spring is the donut pop-out.
- Links: `#1D47D6`, hover `#1836A8`, no underline.

## Assets
- `assets/esperanza-seal.png` — the official municipal seal (from the user), round-cropped in the sidebar logo.
- Icons: Lucide (`lucide-static` 0.460) — use the project's existing icon approach.
- Font: Inter from Google Fonts (already loaded by `resources/css/app.css`).

## Files
- `Dashboard v3.dc.html` — the full design (open in a browser). Markup is inline-styled; the logic block at the bottom holds the donut-arc and trend-curve math (`donut()`, `trend()`), reusable as-is in JS.
- `support.js` — runtime needed only to open the HTML file; not for the codebase.
- `assets/esperanza-seal.png`
