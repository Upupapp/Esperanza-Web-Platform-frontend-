# Esperanza Web Platform

The LGU digital platform for the **Municipality of Esperanza, Masbate** (Region V
— Bicol), 20 barangays. Two applications, one codebase:

- **Citizen Portal** — residents request documents (Dokyu), apply for assistance
  (Tulong), read announcements and events, and manage their profile.
- **Administrative Portal** — LGU personnel process those requests, manage
  constituents and resident profiling, publish communications, and run the
  Sakuna disaster-management module.

There is no public marketing website in this project, and no mobile app.

## The one thing to know before you change anything

**In production there is no PHP.** `netlify.toml` runs `export.php` at build time
to render every route to static HTML and publishes `dist/`. The deployed site is
flat files on a CDN — Laravel never runs for a visitor.

Three consequences:

1. Every API call is a browser call. Blade cannot render real data; `@php` arrays
   are build-time constants baked into the HTML.
2. CORS and token storage are frontend problems — the static origin and the API
   origin differ.
3. A route that works on `artisan serve` still 404s in production if it never
   reaches `dist/`. The gate in `tests/Feature/ExportManifestTest.php` exists
   because that already happened once to nine routes.

## Getting started

```bash
git clone <this repo>
cd esperanza_web_platform
composer install
cp .env.example .env
php artisan key:generate
npm ci
npm run build          # required: public/build is gitignored, and without the
                       # Vite manifest every route 500s
php artisan serve
```

That is the whole list. `.env.example` ships in-memory drivers
(`SESSION_DRIVER=array`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`) because
this project has no database — do not point them at a sqlite file.

## The gate

There is **no CI on this project**. `scripts/gate.sh` is the gate, and it runs
automatically before every push:

`composer install` installs it — the hook is wired from `post-autoload-dump`,
so a fresh clone gets the gate without anyone reading this section. To run it by
hand, or to check the wiring:

```bash
bash scripts/gate.sh                       # run the gate directly
git config core.hooksPath                  # should print: scripts/hooks
php scripts/install-git-hooks.php          # re-install; idempotent, never fails a build
```

It builds the assets, checks formatting, runs the suite (including the
route-render and export-drift gates), produces `dist/`, and then asserts against
`dist/` itself rather than trusting the exporter's own summary. Never bypass it
with `--no-verify`.

## Building the deployable site

```bash
npm run build && php export.php    # writes dist/
```

## Documents that govern this repo

| File | What it is |
| --- | --- |
| `CLAUDE.md` | The project constitution. Frontend only — no controllers beyond `Route::view`, no models, no migrations, no business logic, no auth logic, no middleware. |
| `DEPLOY_PROTOCOL.md` | The required procedure for every push to `main`. A push deploys. |
| `ESPERANZA_FRONTEND_MASTER_COMMAND.md` | The frontend work programme, TABs 00–17. |
| `ESPERANZA_BACKEND_MASTER_COMMAND.md` | The backend work order (a separate repo's job). |
| `FRONTEND_PENDING.md` | The findings register, F-001..F-009. |
| `PROCESS_TAB_FLOW.txt` | The Dokyu/Tulong request lifecycle. |

## Stack

Laravel 12 (routes and views only) · Blade · TailwindCSS 4 · Alpine.js ·
Chart.js · Lucide · Vite.
