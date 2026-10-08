# Esperanza — system-wide style guide (soft clay · pressed · glass)

Companion to `README.md` (which specs the Dashboard screen in full). This file tells a developer / Claude Code how to carry the same visual language across **every** screen of `Upupapp/Esperanza-Web-Platform-frontend-` — staff portal, citizen portal, sign-in pages — by changing tokens, layouts and the shared Blade components first, then sweeping the inline Tailwind that pages repeat.

It is written against the repo as read on 2026-10-07 (`main`): Tailwind v4 `@theme` tokens in `resources/css/app.css`, Blade components in `resources/views/components/ui/*`, `components/admin/*`, `components/citizen/*`, `components/layouts/*`, Alpine for state, Lucide icons via `data-lucide`.

`Component Kit.dc.html` in this folder renders every component below in the new style — open it next to the code.

---

## 0. Principles (read first)

1. **Three textures, used consistently.** *Glass* = containers (cards, tables, modals, dropdowns, topbar chips). *Pressed* = anything you type into or that holds a value (inputs, search, selects, clock, tab tracks, progress tracks, segmented controls). *Clay* = anything you press or that signals (primary/gold/danger buttons, active nav, icon "coins", count badges, avatars). Flat elements are allowed only for text, links, dividers and ghost buttons.
2. **Blue is primary, seal yellow is secondary.** Yellow marks *attention/identity* (live dot, unread dot, count badges, the one "needs staff" KPI, the signed-in scope pill, hero avatar), never status success/error. The universal status colours (badge.blade.php) stay as they are.
3. **Radii go up one step everywhere:** `rounded-lg→rounded-xl`, `rounded-xl→rounded-2xl`, `rounded-2xl→rounded-[26px]` (cards) / `rounded-[28px]` (modals, chart cards), sidebar `rounded-[30px]`, hero `rounded-[32px]`. Pills stay `rounded-full`.
4. **Nothing printable gets the treatment.** `.doc-paper`, certificate/permit previews, payment receipts, the digital ID card, PDF/print output and the Dokyu document-editor chrome (`.doc-toolbar-*`, `.doc-floating-panel*`) keep their current neutral styling. Glass (`backdrop-filter`) also doesn't survive html2canvas.
5. **Shadows must not be clipped.** Any scroll container that holds glass cards needs ≥ 32px padding on the sides where shadows fall (the design's shadows reach ~30px right/bottom, ~16px left/top), or the cards look "cut". See §2.
6. **Accessibility floor stays.** 44px targets, global `:focus-visible` ring, reduced-motion switch, 4.5:1 text contrast: body `#3A4664`, muted `#5F6B8A`; `#7D89A8` only for ≥12px non-essential meta.

---

## 1. Tokens — `resources/css/app.css` (`@theme`)

### 1.1 Replace the gold scale with the seal yellow
Every existing `gold-*` usage (button `gold` variant, stat-card `gold`, page-intro `gold`, blobs) picks up the brighter seal colour automatically.

```css
--color-gold-50:  #FFFBE0;
--color-gold-100: #FFF4B8;   /* light tile bg */
--color-gold-200: #FFEE85;
--color-gold-300: #FFEB5C;   /* clay gradient start */
--color-gold-400: #FFE11A;   /* seal yellow — dots, text on navy */
--color-gold-500: #F5CF00;   /* clay gradient end */
--color-gold-600: #E8C200;   /* lines, strokes */
--color-gold-700: #7A5E00;   /* text/icon on gold-100 */
```

### 1.2 New surface + ink colours
```css
--color-canvas: #E6EDFB;        /* page background (replaces bg-slate-50 on layouts) */
--color-well:   #E1E9F9;        /* pressed surfaces */
--color-track:  #DCE4F5;        /* progress / meter tracks */
--color-ink:        #0B1730;    /* headings (= navy-900) */
--color-ink-body:   #3A4664;
--color-ink-muted:  #5F6B8A;
--color-ink-faint:  #7D89A8;
--color-sidebar-text: #C3CFEA;
--color-sidebar-muted: #A9B8DD;
--color-sidebar-label: #8E9DC4;
```

### 1.3 Shadows (Tailwind v4 exposes these as `shadow-*` utilities)
```css
/* glass containers */
--shadow-glass:       12px 14px 34px rgb(29 71 214 / .12), -8px -8px 22px rgb(255 255 255 / .75), inset 0 1px 0 rgb(255 255 255 / .95);
--shadow-glass-hover: 16px 22px 42px rgb(29 71 214 / .20), -8px -8px 22px rgb(255 255 255 / .85), inset 0 1px 0 #fff;
--shadow-glass-sm:     6px  8px 18px rgb(29 71 214 / .14), -5px -5px 14px rgb(255 255 255 / .80), inset 0 1px 0 rgb(255 255 255 / .95);
/* pressed */
--shadow-pressed:     inset 4px 4px 9px rgb(29 71 214 / .12), inset -4px -4px 9px rgb(255 255 255 / .90);
--shadow-pressed-sm:  inset 2px 2px 5px rgb(29 71 214 / .12), inset -2px -2px 5px rgb(255 255 255 / .90);
--shadow-pressed-dark:inset 3px 3px 8px rgb(11 23 48 / .45),  inset -2px -2px 6px rgb(255 255 255 / .15);
/* clay */
--shadow-clay-blue:  6px 8px 18px rgb(29 71 214 / .35), inset 2px 2px 4px rgb(255 255 255 / .35), inset -3px -4px 8px rgb(11 23 48 / .35);
--shadow-clay-gold:  5px 7px 14px rgb(227 188 0 / .30), inset 2px 2px 4px rgb(255 255 255 / .70), inset -3px -4px 7px rgb(140 110 0 / .28);
--shadow-clay-light: 5px 7px 14px rgb(29 71 214 / .14), inset 2px 2px 4px rgb(255 255 255 / .90), inset -3px -3px 6px rgb(29 71 214 / .14);
--shadow-clay-navy:  5px 7px 14px rgb(11 23 48 / .30),  inset 2px 2px 4px rgb(255 255 255 / .25), inset -3px -4px 7px rgb(0 0 0 / .40);
--shadow-clay-rose:  6px 8px 18px rgb(225 29 72 / .32), inset 2px 2px 4px rgb(255 255 255 / .35), inset -3px -4px 8px rgb(80 10 30 / .35);
/* floating (modals, dropdowns, toasts) */
--shadow-float: 16px 20px 48px rgb(11 23 48 / .28), inset 0 1px 0 rgb(255 255 255 / .90);
/* keep the old names alive so untouched pages don't break; point them at the new look */
--shadow-card:       var(--shadow-glass-sm);
--shadow-card-hover: var(--shadow-glass);
```

### 1.4 Surface classes — add to `@layer components`
These bundle background + blur + border + shadow; a utility soup can't express them and they repeat hundreds of times.

```css
.surface-glass    { background: rgb(255 255 255 / .58); backdrop-filter: blur(22px) saturate(140%); border: 1px solid rgb(255 255 255 / .85); box-shadow: var(--shadow-glass); }
.surface-glass-sm { background: rgb(255 255 255 / .60); backdrop-filter: blur(16px); border: 1px solid rgb(255 255 255 / .90); box-shadow: var(--shadow-glass-sm); }
.surface-pressed    { background: var(--color-well); box-shadow: var(--shadow-pressed); }
.surface-pressed-sm { background: var(--color-well); box-shadow: var(--shadow-pressed-sm); }
.clay-blue  { background: linear-gradient(160deg, var(--color-brand-500), var(--color-brand-600)); color: #fff; box-shadow: var(--shadow-clay-blue); }
.clay-gold  { background: linear-gradient(160deg, var(--color-gold-300), var(--color-gold-500)); color: var(--color-navy-900); box-shadow: var(--shadow-clay-gold); }
.clay-light { background: var(--color-brand-100); color: var(--color-brand-600); box-shadow: var(--shadow-clay-light); }
.clay-navy  { background: linear-gradient(160deg, #1F3B7A, var(--color-navy-900)); color: #fff; box-shadow: var(--shadow-clay-navy); }
.clay-rose  { background: linear-gradient(160deg, #F43F5E, #E11D48); color: #fff; box-shadow: var(--shadow-clay-rose); }
.clay-gold-light { background: var(--color-gold-100); color: var(--color-gold-700); box-shadow: 5px 7px 14px rgb(227 188 0 / .20), inset 2px 2px 4px rgb(255 255 255 / .90), inset -3px -3px 6px rgb(140 110 0 / .14); }
.sidebar-glass { background: linear-gradient(180deg, rgb(11 23 48 / .95), rgb(17 36 84 / .90)); backdrop-filter: blur(20px); border: 1px solid rgb(255 255 255 / .09); box-shadow: 16px 18px 44px rgb(11 23 48 / .30), inset 0 1px 0 rgb(255 255 255 / .14), inset -1px -2px 6px rgb(0 0 0 / .25); }
.sidebar-well  { background: rgb(255 255 255 / .05); border: 1px solid rgb(255 255 255 / .06); box-shadow: inset 4px 4px 10px rgb(0 0 0 / .35), inset -3px -3px 8px rgb(255 255 255 / .06); }
/* page canvas with the two colour washes */
.canvas-washed { background: radial-gradient(760px 440px at 90% -8%, rgb(86 135 255 / .42), transparent 70%), radial-gradient(600px 440px at 6% 106%, rgb(255 225 26 / .36), transparent 70%), radial-gradient(900px 640px at 58% 58%, rgb(47 98 245 / .12), transparent 70%), var(--color-canvas); }
/* fallbacks: no blur support or user prefers less transparency → solid */
@supports not (backdrop-filter: blur(1px)) { .surface-glass, .surface-glass-sm { background: #F3F6FC; } }
@media (prefers-reduced-transparency: reduce) { .surface-glass, .surface-glass-sm, .sidebar-glass { backdrop-filter: none; background: #F3F6FC; } .sidebar-glass { background: var(--color-navy-900); } }
```
Also update `.glass-panel` (exists) to alias `.surface-glass-sm`, and `body { @apply bg-canvas text-ink-body }`.

Performance note: `backdrop-filter` on dozens of elements is costly on older LGU desktops. Apply `surface-glass` to **cards/containers only**, never to table rows, list items or badges.

---

## 2. Layouts

### `components/layouts/admin.blade.php` and `citizen.blade.php`
Keep the `flex h-screen overflow-hidden` shell (modals and the Dokyu print path depend on it). Change:
- Shell: `bg-slate-50` → `canvas-washed`, add `gap-5 p-4 lg:p-5`.
- `<main>`: `p-4 lg:p-6` → `px-5 py-4 lg:px-9 lg:pb-10 -mx-4 lg:-mx-6` *(the extra horizontal padding is the shadow gutter — §0.5)*. Remove any `bg-*` on main.
- Content column gets `flex flex-col gap-5`.

### `components/admin/sidebar.blade.php`, `components/citizen/sidebar.blade.php`
- `<aside>`: replace `bg-navy-900` with `sidebar-glass lg:rounded-[30px] lg:my-0 lg:h-[calc(100vh-2.5rem)]` (mobile drawer stays full-height, square). Padding `px-3.5 py-4`.
- Brand row: seal `w-11 h-11 rounded-full object-cover` inside a `w-[46px] h-[46px] rounded-full bg-gold-400 shadow-clay-gold p-[2px]` disc. Name `text-[15px] font-extrabold text-white`, sub `text-xs text-sidebar-muted`. Drop the `border-b border-white/10`.
- Signed-in block: `sidebar-well rounded-[20px] px-3.5 py-3.5`. Label `text-[10.5px] font-bold tracking-[.08em] uppercase text-sidebar-label`; scope pill → `bg-gold-400/15 border border-gold-400/35 text-gold-400 text-[11.5px] font-bold rounded-full px-2.5 py-1 whitespace-nowrap` (Municipal) / keep purple tint for barangay scope but same shape.
- `<nav>`: `px-3 py-2.5 space-y-[3px] scrollbar-dark` → add `scrollbar-width:thin` (already via `.scrollbar-dark`); make sure items are 40px so all 11 fit without scrolling at 768px tall.
- Collapse button: `h-11 rounded-2xl bg-white/[.06] border border-white/[.08] text-sidebar-text shadow-[4px_5px_12px_rgb(0_0_0/.25),inset_0_1px_0_rgb(255_255_255/.12)] active:shadow-pressed-dark`. Remove `border-t`.

### `components/ui/nav-item.blade.php`
```
base:     group relative flex items-center gap-3 min-h-[40px] px-3.5 rounded-2xl font-semibold text-[14.5px] transition-all duration-200
inactive: text-sidebar-text hover:bg-white/[.07] hover:text-white
active:   clay-blue font-bold shadow-[0_6px_14px_-2px_rgb(47_98_245/.5),inset_2px_2px_4px_rgb(255_255_255/.35),inset_-3px_-4px_8px_rgb(11_23_48/.4)]
```
(Active shadow falls straight **down** — a sideways offset gets clipped by the scrolling `<nav>`.) Count badge (`badge`/`badgeBind`): `ml-auto min-w-[22px] h-[22px] px-1.5 rounded-full clay-gold text-[11.5px] font-extrabold grid place-items-center` on both active/inactive. Icon `w-[18px] h-[18px]`, inactive icon colour `text-sidebar-text` (not slate-500).

### `components/admin/topbar.blade.php`, `components/citizen/topbar.blade.php`
No more full-width white bar. `<header>`: `relative z-20 flex items-center gap-4 flex-wrap px-1 pt-1` — transparent, no border, no fixed height.
- Title `text-[26px] font-extrabold tracking-[-.02em] text-ink`; subtitle `text-[13.5px] text-ink-muted`.
- Clock: `surface-pressed rounded-2xl h-11 px-4 font-mono text-[13px] text-ink-body whitespace-nowrap` with a leading `w-2 h-2 rounded-full bg-gold-400 shadow-[0_0_0_3px_rgb(255_225_26/.3)]` dot (replaces the clock icon).
- Search: `surface-pressed rounded-2xl h-11 w-[min(360px,100%)] px-4` wrapper; input `bg-transparent border-0 focus:ring-0 text-sm text-ink placeholder:text-ink-faint`; focus state on the wrapper via `focus-within:bg-white/90 focus-within:shadow-[var(--shadow-pressed-sm),0_0_0_4px_var(--color-brand-100)]`.
- Bell: `surface-glass-sm rounded-2xl w-11 h-11 grid place-items-center text-ink-body`; unread dot `bg-gold-400 ring-2 ring-white` (drop the rose ping, keep `animate-ping` only if you keep it subtle).
- User chip: `surface-glass-sm rounded-[20px] h-[52px] pl-1.5 pr-3.5 flex items-center gap-2.5`; avatar `w-10 h-10 rounded-full clay-blue font-extrabold text-sm`.
- Dropdown panels (notifications, profile): `surface-glass rounded-[22px] shadow-float border-white/90 overflow-hidden`; row hover `hover:bg-white/60`; dividers `divide-brand-600/10`.
- Remove the `w-px h-8 bg-slate-200` separator.

### `components/layouts/guest.blade.php` (sign-in / register / select-portal)
- Left panel: keep photo + navy gradient + `bg-grid-dots`; make the gold blob `bg-gold-400/25`; the Lora tagline stays.
- Right side: `canvas-washed`; the `<main>` card → `surface-glass rounded-[32px] p-7 sm:p-9`.
- Language switch: segmented pressed control (§3.6).
- Sign-in submit (admin): `clay-navy btn-shine rounded-2xl min-h-[48px] font-bold`; citizen: `clay-blue`. Inputs per §3.2. Error box: `rounded-2xl bg-rose-50/80 border border-rose-100`; notice: `bg-emerald-50/80`.
- Admin scope chips (Municipal-wide / barangay buttons): `surface-glass-sm rounded-2xl hover:-translate-y-0.5 hover:shadow-glass`, icon tile `clay-light w-9 h-9 rounded-xl`.

---

## 3. Shared components — `resources/views/components/ui/*`

Change these once; they propagate to all 98 views. Keep every prop, slot, `aria-*`, Alpine binding and bilingual `x-text` exactly as is — only class strings change.

### 3.1 `button.blade.php`
```php
$base = 'inline-flex items-center justify-center gap-2 font-semibold rounded-2xl whitespace-nowrap transition-all duration-[160ms] ease-standard select-none focus:outline-none focus-visible:ring-4 active:duration-[80ms] disabled:opacity-50 disabled:pointer-events-none';
// sizes unchanged (36 / 44 / 48)
$variants = [
  'primary'   => 'clay-blue btn-shine hover:brightness-95 active:shadow-pressed-dark focus-visible:ring-brand-100',
  'secondary' => 'surface-glass-sm text-ink-body hover:bg-white/90 hover:shadow-glass active:shadow-pressed-sm active:bg-well focus-visible:ring-brand-100',
  'ghost'     => 'bg-transparent text-ink-muted hover:bg-white/60 hover:text-ink focus-visible:ring-brand-100',
  'danger'    => 'clay-rose btn-shine hover:brightness-95 active:shadow-pressed-dark focus-visible:ring-rose-100',
  'gold'      => 'clay-gold btn-shine hover:brightness-[.97] active:shadow-[inset_3px_3px_8px_rgb(140_110_0/.35)] focus-visible:ring-gold-100',
];
```
Drop `active:scale-[0.985]` — the pressed shadow *is* the press feedback. Icon-only buttons (bell, row actions, modal close): `w-11 h-11 rounded-2xl` (or `w-9 h-9 rounded-xl` in table rows) with the secondary/ghost recipe.

### 3.2 `input.blade.php`, `spec-field.blade.php` (`$control`), search boxes, selects, textareas
```
label:    block text-sm font-semibold text-ink-body mb-1.5
control:  w-full min-h-[44px] rounded-2xl border-0 surface-pressed px-4 py-2.5 text-sm text-ink placeholder:text-ink-faint transition-all duration-[160ms] ease-standard
          focus:bg-white/90 focus:outline-none focus:ring-4 focus:ring-brand-100
error:    ring-2 ring-inset ring-rose-300 focus:ring-rose-100   (+ existing rose error text, role=alert)
readonly: surface-pressed-sm text-ink-muted (derivedAge)
with icon: pl-10; icon text-ink-muted w-[18px]
textarea: same control, rows=3, resize-y
select:   same control + appearance-none pr-10 + chevron-down icon absolutely positioned right-3.5 text-ink-muted
checkbox/radio: w-5 h-5 rounded-md border-0 bg-well shadow-pressed-sm accent-brand-600 (or `appearance-none ... checked:clay-blue` with a check icon if you want the clay look)
toggle (Alpine switches in settings): track w-11 h-6 rounded-full surface-pressed-sm; checked track clay-blue; knob w-5 h-5 rounded-full bg-white shadow-[2px_3px_6px_rgb(11_23_48/.25)]
hint: text-xs text-ink-muted mt-1.5
```
`input-underline` (the one underline variant) stays as is.

### 3.3 `card.blade.php`, `table.blade.php`, `file-picker.blade.php`, `card-skeleton`
`bg-white rounded-2xl border border-slate-100 shadow-card` → `surface-glass rounded-[26px]`. Card padding `p-4` → `p-5`. Hover variant: `transition-[transform,box-shadow] duration-[220ms] hover:-translate-y-1 hover:shadow-glass-hover`. `overflow-hidden` stays on tables.

### 3.4 `stat-card.blade.php` (KPI) — colour slab + frosted object (colours and object recipes in README §KPI)
Card: `relative overflow-hidden flex flex-col justify-center gap-[5px] min-h-[118px] rounded-3xl pl-[22px] pr-[130px] py-[18px] border border-white/30 cursor-pointer transition-[transform,box-shadow] duration-[220ms] hover:-translate-y-[3px] slab slab-{tone}`. Add to `@layer components`:
```css
.slab { color: var(--slab-text); background: linear-gradient(100deg, var(--slab-1) 0%, var(--slab-1) 45%, var(--slab-2) 100%); box-shadow: 12px 14px 32px var(--slab-shadow), -6px -6px 16px rgb(255 255 255 / .55), inset 0 1px 0 rgb(255 255 255 / .4), inset -4px -6px 14px rgb(0 0 0 / .12); }
.slab:hover { box-shadow: 16px 20px 40px var(--slab-shadow), -6px -6px 16px rgb(255 255 255 / .6), inset 0 1px 0 rgb(255 255 255 / .45), inset -4px -6px 14px rgb(0 0 0 / .12); }
.slab::before { content: ''; position: absolute; inset: 0; background: radial-gradient(180px 100px at 10% 0%, rgb(255 255 255 / .28), transparent 70%); pointer-events: none; }
.slab-brand  { --slab-1:#2F62F5; --slab-2:#7C9FFF; --slab-text:#fff;    --slab-sub:#DCE5FF; --slab-shadow: rgb(29 71 214 / .30); }
.slab-violet { --slab-1:#5B4FE0; --slab-2:#A39CF4; --slab-text:#fff;    --slab-sub:#E6E3FF; --slab-shadow: rgb(91 79 224 / .30); }
.slab-gold   { --slab-1:#7F5E00; --slab-2:#E0B400; --slab-text:#fff;    --slab-sub:#FFF0BF; --slab-shadow: rgb(127 94 0 / .35); }
.slab-teal   { --slab-1:#0A6F8F; --slab-2:#4FBAD6; --slab-text:#fff;    --slab-sub:#DDF4FA; --slab-shadow: rgb(10 111 143 / .30); }
.slab-navy   { --slab-1:#142B66; --slab-2:#3F63C0; --slab-text:#fff;    --slab-sub:#BFCBEB; --slab-shadow: rgb(20 43 102 / .35); }
.slab-amber  { --slab-1:#8C4500; --slab-2:#F08A1D; --slab-text:#fff;    --slab-sub:#FFE3C2; --slab-shadow: rgb(140 69 0 / .35); }
.frost-0 { background: rgb(255 255 255 / .18); box-shadow: inset 1px 1px 0 rgb(255 255 255 / .35); }
.frost-1 { background: rgb(255 255 255 / .30); box-shadow: inset 1px 1px 0 rgb(255 255 255 / .55), 0 8px 16px rgb(0 0 0 / .10); }
.frost-2 { background: rgb(255 255 255 / .42); box-shadow: inset 1px 1px 0 rgb(255 255 255 / .70), 0 8px 16px rgb(0 0 0 / .10); }
.frost-line { background: rgb(255 255 255 / .55); }
```
- Text: label `relative text-[12.5px] font-semibold text-[var(--slab-sub)]`; value row `relative flex items-baseline gap-1.5 whitespace-nowrap` → `text-[32px] leading-none font-extrabold tracking-[-.03em] tabular-nums` + context `text-[13px] font-semibold text-[var(--slab-sub)]`; footnote `relative text-xs text-[var(--slab-sub)]`.
- Object: `<div aria-hidden="true" class="absolute right-2.5 top-1/2 w-[104px] h-[104px] -translate-y-1/2 -rotate-6 pointer-events-none">` containing `frost-*` divs. Make one Blade partial per object — `components/ui/kpi-object/{people,document,tray,ring,stamp,calendar}.blade.php` — copying the div coordinates from `Dashboard v3.dc.html`; `calendar` prints `now()->format('j')` and `now()->format('M')`.
- Props: replace `icon`/`color` with `tone` (brand|violet|gold|teal|navy|amber) and `object` (people|document|tray|ring|stamp|calendar); keep old prop names accepted and map them (brand→brand/people, green→teal/ring, purple→violet/document, orange→gold/tray, red→amber/calendar, slate→navy/stamp) so existing callers render.
- Row: `grid gap-5 grid-cols-[repeat(auto-fill,minmax(300px,1fr))]`. Reuse on `citizen/dashboard` and `admin/reports`.

### 3.5 `badge.blade.php`, `pill.blade.php`
**Keep `$styles` keys and colour classes untouched** (`tests/Feature/StatusVocabularyTest.php` and the JS mirror depend on them). Change only the shared base string:
`inline-flex items-center gap-1.5 min-h-[26px] px-2.5 py-1.5 rounded-full text-xs font-semibold tracking-[0.02em] ring-1 ring-inset whitespace-nowrap shadow-[inset_0_1px_0_rgb(255_255_255/.7)]`. Count badges (queue counts, "1" on Sakuna alerts): `clay-gold rounded-full min-w-[26px] h-[26px] px-2 text-[12.5px] font-extrabold`.

### 3.6 Tabs / segmented controls (inline pattern — e.g. `resident-profile-form.blade.php` `role="tablist"`, Sakuna's 11 tabs, Constituents views)
```
track:    inline-flex p-1 rounded-2xl surface-pressed-sm w-fit max-w-full overflow-x-auto scrollbar-thin
tab:      px-3.5 py-2 rounded-xl text-[13px] font-semibold text-ink-muted whitespace-nowrap transition-all duration-200 hover:text-ink
active:   bg-white/85 text-brand-600 font-bold shadow-[3px_4px_8px_rgb(29_71_214/.16),-2px_-2px_5px_rgb(255_255_255/.9),inset_0_1px_0_#fff]
```
For >6 tabs keep the same track and let it scroll; no underline tabs anywhere.

### 3.7 Tables (inline in `view-*.blade.php`, Dokyu/Tulong/Payments/Users lists)
- Wrapper: `x-ui.table` (→ glass, §3.3).
- `thead tr`: `bg-well/70` (no `border-b`); `th`: `px-5 py-3 text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted whitespace-nowrap`.
- `tbody`: `divide-y divide-brand-600/[.08]` (replace `divide-slate-50`), no `bg-white`.
- Row: `hover:bg-white/60 transition-colors`; clickable rows keep `cursor-pointer group tabindex=0`.
- Cells `px-5 py-3.5`; primary text `text-sm font-semibold text-ink`; secondary `text-xs text-ink-muted`; IDs/refs `font-mono text-xs text-brand-600`.
- Avatars in rows: `clay-blue` instead of the brand→navy gradient + `ring-2 ring-white`.
- Row actions: `w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:surface-glass-sm hover:text-brand-600`; keep `opacity-0 group-hover:opacity-100 focus-within:opacity-100`.
- Footer / pagination: `flex items-center justify-between px-5 py-3.5 border-t border-brand-600/[.08] text-[13px] text-ink-muted`; page buttons `w-9 h-9 rounded-xl text-sm font-semibold text-ink-body hover:bg-white/70`, current page `clay-blue font-bold`, prev/next `surface-glass-sm`.
- Tag chips inside cells (senior/pwd/4ps…): keep colours; `rounded-md px-1.5 py-0.5 text-[10px] font-bold`.

### 3.8 `modal.blade.php`
- Backdrop: `bg-navy-950/50 backdrop-blur-md`.
- Dialog: `bg-white rounded-2xl shadow-float` → `bg-white/85 backdrop-blur-2xl border border-white/90 rounded-[28px] shadow-float` (both compact and fullscreen variants).
- Header: `px-6 py-4 border-b border-brand-600/10`; title `text-base font-extrabold text-ink`. Close/expand buttons: `w-9 h-9 rounded-xl text-ink-muted hover:surface-glass-sm hover:text-ink`.
- Footer: `px-6 py-4 border-t border-brand-600/10 bg-well/50` (replaces `bg-slate-50/60`).
- Body unchanged (`px-6 py-6 sm:px-10 sm:py-8`, scrollbar-thin).

### 3.9 `toast-container.blade.php`
Item: `surface-glass-sm rounded-2xl px-4 py-3.5 shadow-float border-white/90` (drop the per-variant border colours). Icon tile `w-8 h-8 rounded-xl`: success `bg-emerald-100 text-emerald-700 shadow-clay-light`, info `clay-light`, error `bg-rose-100 text-rose-700 shadow-clay-light`, warning `clay-gold-light`. Text `text-sm text-ink-body`.

### 3.10 `empty-state.blade.php`, `error-state.blade.php`, `skeleton`
- Empty icon tile: `w-16 h-16 rounded-[22px] surface-pressed text-ink-muted` (error: `bg-rose-50 text-rose-500 shadow-pressed-sm`). Title `text-sm font-bold text-ink`, description `text-sm text-ink-muted`.
- `.skeleton`: `bg-well` with the existing shimmer; `.card-skeleton` → `surface-glass rounded-[26px] p-5`.

### 3.11 `page-intro.blade.php`, `tooltip.blade.php`, `avatar.blade.php`, `data-panel.blade.php`
- Page intro: `surface-glass-sm rounded-[22px] px-4 py-3.5`; icon tile `clay-light w-9 h-9 rounded-xl` (gold variant → `clay-gold-light`).
- Tooltip: unchanged shape, `bg-navy-900 rounded-xl shadow-float`.
- Avatar fallback: `clay-blue font-extrabold` (no ring); image avatars `ring-2 ring-white/90 shadow-glass-sm`.
- Data panel: no visual change (it only switches states).

### 3.12 Progress / meters (`.meter-fill` users, "Open requests by barangay", Sakuna capacity bars)
Track `h-3.5 rounded-full bg-track shadow-pressed-sm p-[2px]`; fill `h-full rounded-full bg-gradient-to-r from-brand-400 to-brand-600 shadow-[inset_0_2px_2px_rgb(255_255_255/.45),inset_0_-2px_3px_rgb(11_23_48/.3)]`. Danger fills use the rose gradient, capacity-full uses gold.

### 3.13 Charts (admin/citizen dashboards, reports)
Follow README §Donut and §Requests trend. Palette order: `brand-600, brand-400, gold-400, navy-900, #B9C7E8`. Donut: 29–31px ring, rounded segment ends, ~10px gaps, 16° minimum slice, hover pops 7px and dims the rest, centre shows the hovered status. Lines: 3.5px, smooth, area fade; tooltip is a `surface-glass-sm` chip. If a charting library is used, set `borderRadius`, `spacing`, `hoverOffset` and these colours; disable the library's own legend and render the pressed legend rows from README.

---

## 4. Sweep of repeated inline patterns (regex across `resources/views/**`)

Run after §3, excluding `components/citizen/*preview*`, `*receipt*`, `digital-id-card`, `document-editor-*`, `document-watermark`, `document-template-artwork`, `barangay-certificate-preview`, and anything inside `.doc-paper`.

| find (literal class run) | replace |
|---|---|
| `bg-white rounded-2xl border border-slate-100 shadow-card` | `surface-glass rounded-[26px]` |
| `bg-white rounded-xl border border-slate-100 shadow-card` / `shadow-sm` | `surface-glass-sm rounded-2xl` |
| `bg-slate-50/60 border border-slate-200` (inputs) | `surface-pressed border-0` |
| `bg-slate-100/80 border border-transparent` / `bg-slate-100/70` (search, clock) | `surface-pressed` |
| `focus:border-brand-300 focus:ring-4 focus:ring-brand-100` | `focus:bg-white/90 focus:ring-4 focus:ring-brand-100` |
| `hover:bg-slate-50` (rows, menu items) | `hover:bg-white/60` |
| `divide-slate-50` / `divide-slate-100` | `divide-brand-600/[.08]` |
| `border-slate-100` / `border-slate-200/70` (dividers) | `border-brand-600/10` |
| `bg-brand-600 text-white` on buttons | `clay-blue` |
| `bg-gradient-to-br from-brand-500 to-navy-800 text-white … ring-2 ring-white shadow-sm` (avatars) | `clay-blue font-extrabold` |
| `text-navy-900` (headings) | `text-ink` · `text-slate-800`→`text-ink-body` · `text-slate-500`→`text-ink-muted` · `text-slate-300/400` (meta)→`text-ink-faint` |
| `rounded-lg` → `rounded-xl`, `rounded-xl` → `rounded-2xl` on interactive elements (not on tag chips) | |
| `shadow-float` on dropdowns | `shadow-float surface-glass border-white/90` |

Do it with a script that reports counts per file, then eyeball each page once — the big pages (`document-requests`, `assistance-requests`, `communications`, `settings`, Sakuna tabs) have bespoke panels that need a manual pass.

---

## 5. Rollout plan for Claude Code (suggested prompt per phase)

1. **Tokens** — "Apply §1 of `STYLE_GUIDE.md` to `resources/css/app.css`. Build with Vite and confirm `shadow-glass`, `bg-well`, `text-ink` utilities exist." (Whole site immediately gets the new gold, canvas and softer shadows.)
2. **Shell** — "Apply §2: both layouts, both sidebars, both topbars, nav-item, guest layout. Keep all Alpine/ARIA. Check 1280×720 and 1920×1080, sidebar collapsed/expanded, mobile drawer."
3. **Components** — "Apply §3.1–3.11 to `components/ui/*`. Run `php artisan test` — `StatusVocabularyTest` and the rest must stay green."
4. **Sweep** — "Apply §4 with a dry-run report, then commit per directory (admin/, citizen/, auth/, components/citizen/ minus the excluded files)."
5. **Dashboards + charts** — "Rebuild `admin/dashboard.blade.php` to `README.md`; apply the same KPI/chart recipes to `citizen/dashboard.blade.php` and `admin/reports.blade.php`."
6. **QA** — contrast spot-check (body/muted text on glass), focus rings visible on clay buttons, no clipped shadows in any scroll container, print/PDF of a Dokyu document unchanged, reduced-motion and `prefers-reduced-transparency` fallbacks.

## 6. Files in this package
- `README.md` — Dashboard screen spec (hi-fi).
- `STYLE_GUIDE.md` — this file (system-wide).
- `Dashboard v3.dc.html` — the Dashboard design (open in a browser; donut/trend math in its logic block is reusable JS).
- `Component Kit.dc.html` — every component in §3 rendered in the new style.
- `assets/esperanza-seal.png`, `support.js` (runtime for the HTML files only).
