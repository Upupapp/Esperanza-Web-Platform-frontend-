# Deploy Protocol — HARD RULE

**This is the procedure for every push to `main` in this repository. It is not
optional and it is not shortened because "nothing changed upstream."**

Stated by the project owner on 2026-08-29:

> Sweep main git, find items not in local, test, merge with local, preserving
> items only in local, sweep + test and then push back to main the most updated
> merged repo.

## The seven steps, in order

| # | Step | The command that proves it |
| --- | --- | --- |
| 1 | **Sweep remote `main`.** Fetch first — never reason about the remote from a stale ref. Read the incoming commits; do not summarise them as "some changes". | `git fetch origin` then `git log --oneline main..origin/main` and `git diff --stat main...origin/main` |
| 2 | **Enumerate local-only work** *before* merging — tracked commits **and** untracked/uncommitted files. This list is what step 4 must preserve. | `git log --oneline origin/main..main` **and** `git status --porcelain` |
| 3 | **Test local HEAD** to get a baseline, so a later failure can be blamed on the merge rather than on something already broken. | `composer install && npm ci && npm run build && php artisan test` |
| 4 | **Merge remote into local, preserving local-only items.** Merge — never `reset --hard`, never force-push. | `git merge origin/main` |
| 5 | **Sweep + test again after the merge.** This is the gate that matters; step 3 was only the baseline. | full build + test + a render of every route |
| 6 | **Push** — only once step 5 is green. | `git push origin main` |
| 7 | **Report what moved**: the merge base, what arrived from the remote, what was local-only and survived, and the pushed SHA. | — |

## Why each trap is in here

- **Untracked files are invisible to `git log` and to a merge.** A deliverable
  written this session and never `git add`-ed appears in no commit comparison,
  and a careless `git checkout` or `git clean` destroys it. Step 2 exists for
  exactly that. *(This has already happened once in this repo: a generated PDF
  was lost between sessions and had to be rebuilt from its markdown source.)*
- **A fast-forward that succeeds silently is still a failed sweep** if nobody
  read what arrived.
- **Local green plus remote green does not imply merged green.** Never push
  before re-testing the merged tree.
- **A push to `main` here is a deploy.** `netlify.toml` builds and publishes
  this repo on push. Step 5 is not a formality.
- **Never commit a file that reproduces a secret**, even one already leaked
  elsewhere in the repo. Redact it and point at the source location.

## Repository-specific notes

- **Testing needs array drivers.** `.env.example` ships database session/cache/
  queue drivers against a sqlite file that does not exist, so a fresh clone
  returns HTTP 500 on every page. Set `SESSION_DRIVER=array`,
  `CACHE_STORE=array`, `QUEUE_CONNECTION=sync` in your local `.env` before
  testing. See `FRONTEND_PENDING.md` F-004.
- **`php artisan test` proves almost nothing** — the suite is the untouched
  Laravel skeleton (2 example tests) against 57 routes and 98 views. The real
  step-3/step-5 gate is rendering every route and asserting 200. See F-006.
- **`export.php` is the deploy artifact**, not `routes/web.php`. A route that
  works locally can still 404 in production if it is missing from the export
  map. Diff them as part of the sweep. See F-001 and F-002.

Companion documents: `FRONTEND_PENDING.md` (findings register),
`PROCESS_TAB_FLOW.txt` (request lifecycle), `CLAUDE.md` (project constitution),
`ESPERANZA_BACKEND_MASTER_COMMAND.md` (the backend work order).
