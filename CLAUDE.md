# Esperanza Web Platform — Project Constitution

Municipality of Esperanza, Masbate (Region V — Bicol Region), 20 barangays.
The canonical barangay list lives in `config/esperanza.php` — always pull
barangay names from there rather than hardcoding a list in a view. This file
is the permanent development constitution for this project. Every task in
this repo follows these rules unless the user explicitly overrides them in
the conversation.

## What this project is

A premium, enterprise-grade LGU digital platform with exactly two applications:

- **Citizen Portal**
- **Administrative Portal**

There is **no public marketing website** in this project.

Goal: a modern, production-ready frontend that can later be wired to real
backend APIs without rework.

## Hard rule: frontend only

Never generate: Controllers (beyond a bare `Route::view`/closure to return a
Blade view), Models, Migrations, Seeders, APIs, business logic, DB queries,
auth logic, authorization logic, or middleware.

Use realistic mock data and frontend-only interactions. Build everything so
it is *backend-ready* in shape (predictable prop/data structures, clear
seams) but contains no real backend code.

## Tech stack

- Laravel 12 (routes/views only, per the rule above)
- Blade templates + Blade components
- TailwindCSS
- Alpine.js
- Lucide icons
- Chart.js or ApexCharts for charts
- Vite

## Design philosophy — non-negotiable

Every screen must feel like a premium SaaS product adapted for government:
modern, minimal, elegant, professional, fast, citizen-centered,
enterprise-grade, accessible, clean, trustworthy. No static-looking pages,
no Bootstrap-like layouts, no clutter, no outdated gov-UI look.

Default to including (don't wait to be asked):
smooth page transitions, micro-interactions, hover/active states, skeleton
loaders, shimmer effects, animated charts, beautiful empty states, animated
notifications, smooth dropdowns/sidebars/modals, progress indicators,
floating action buttons where appropriate, soft shadows, generous/premium
spacing, sparing gradients, responsive transitions, smooth scrolling. Every
interaction gets immediate visual feedback.

## Brand reference

The Bacoor City LGUID screenshots shared earlier are a **pure style
reference only** — layout patterns, color language, typography, component
style. No Bacoor text, copy, or branding appears anywhere in this project.
All actual branding (seal, photography) comes from the assets folder:
`C:\Users\admin\Desktop\esperanza assets`.

## Application structure

```
Citizen Portal
├── Authentication
├── Dashboard
├── Profile
├── Document Requests
├── Assistance Requests
├── Notifications
├── Government Directory
├── Announcements
├── Events
├── FAQs
├── Tutorials
├── Support
└── Settings

Administrative Portal
├── Authentication
├── Dashboard
├── Constituents
├── Resident Profiling
├── Document Requests
├── Assistance Requests
├── Reports
├── Analytics
├── Announcements
├── Events
├── Directory
├── User Management
├── Roles
├── Audit Logs (UI)
├── Municipality Branding
└── Settings
```

## Authentication

One entry screen: user chooses **Citizen Login** or **LGU Personnel Login**.
Citizen registration exists. Admin registration does not — admin accounts
are provisioned internally (UI can imply this, no backend needed).

## Language — RULED 2026-08-30

**Citizen Portal: bilingual (Filipino + English). Administrative Portal:
English-only, by decision.**

The language store defaults to `fil`. Every citizen-facing user-visible string
must exist in both languages, written with the `x-ui.t` component
(`<x-ui.t fil="..." en="..." />`) so coverage stays greppable and testable.
`x-ui.t` renders a `<span>`, so attributes — `placeholder`, `title`,
`aria-label` — use a bound ternary on `$store.lang.current` instead.

The Administrative Portal is English-only **because the owner decided so**, not
by omission. Do not add a language toggle to the admin topbar, and do not treat
untranslated admin views as a defect. If that decision is ever revisited, it is
an owner decision, and this section is where it gets rewritten.

Three deliberate exceptions inside the citizen portal, each recorded in a
`{{-- LANGUAGE (TAB 09) --}}` comment in the file itself:

- **Official document bodies** (`barangay-certificate-preview`,
  `municipal-request-preview`, `doc-field`) — the wording is the artifact.
  Philippine LGU certificates are issued in English and their phrasing is fixed
  by the issuing office. A bilingual certificate would not match the paper it
  stands in for. The controls *around* the document are bilingual.
- **The Digital ID card face** (`digital-id-card`) — "Republic of the
  Philippines", "Civil Status", "Mother's Maiden Name" are the printed labels of
  a Barangay ID, part of the credential rather than UI chrome. Its chrome (flip
  hint, disclaimer, accessible names) IS bilingual.
- **Proper nouns** — module and event names (Dokyu, Tulong, Balita, Sakuna,
  Papeles, "Fiesta ng Esperanza 2026"), font names, and the 15 canonical status
  names, which are a fixed vocabulary and must never be renamed or translated.

`tests/Feature/BilingualCoverageTest.php` enforces this and is part of the gate.

## Standard workflows

- **Citizen Registration**: Registration → Verification → Resident Profile → Dashboard
- **Document Requests / Citizen Assistance (Dokyu & Tulong)**: Citizen (Select Service → Fill Form → Submit) → Admin (Submitted → Under Verification → Approved → Mark to Release → Released), with a correction branch (Under Verification → Under Review → Resubmitted → back into verification) and a terminal Rejected outcome. Both Dokyu and Tulong share this exact process-tab lifecycle — see the Process tab in each admin page for the full transition rules.
- **Announcements**: Draft → Review → Publish → Archive

## Universal status system

Reuse exactly these status names everywhere — never invent new ones:

Draft, Submitted, Pending Review, Under Verification, Assigned, Processing,
Under Review, Resubmitted, Approved, Rejected, Mark to Release, Released,
Completed, Cancelled, Archived.

## Component architecture

Always build reusable Blade components — never duplicate UI. Expected
component library: buttons, cards, tables, forms, inputs, status badges,
modals, drawers, sidebars, topbars, breadcrumbs, charts, statistic cards,
notifications, empty states, skeleton loaders, pagination, search.

Before writing new markup: check whether an existing component covers it,
extend existing layouts, follow established naming conventions, keep
folders organized, refactor rather than duplicate.

## Mock data

Always realistic Philippine LGU content: Filipino names, real Esperanza,
Masbate barangays (see `config/esperanza.php`), realistic announcements/
document requests/citizen data. No Lorem Ipsum unless explicitly requested.

## Development process for every task

1. Understand the feature.
2. Reuse existing layouts/components before creating new ones.
3. Build modular, scalable code.
4. Add modern interactions by default (see design philosophy above).
5. Make it responsive.
6. Use realistic mock data.
7. Keep visual consistency with what's already built.
8. Keep it structured so backend integration later is straightforward.

Never redesign existing patterns unless the user explicitly instructs it.

## Output bar

Every deliverable: production-ready, enterprise-grade, responsive,
accessible, beautiful, modern, premium, interactive, modular, scalable,
backend-ready, frontend-only.

## Deploying — HARD RULE

Every push to `main` follows `DEPLOY_PROTOCOL.md`: sweep remote main, enumerate
local-only work (including untracked files), test, merge preserving local-only
items, sweep and test again, then push. A push to `main` deploys this repo via
Netlify, so the post-merge gate is not a formality.

Open findings live in `FRONTEND_PENDING.md`. The backend work order derived from
this frontend is `ESPERANZA_BACKEND_MASTER_COMMAND.md`.
